<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class FileController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json(['message' => 'Arquivo não enviado.'], 400);
            }

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            $validator = Validator::make(
                [
                    'file' => $file,
                    'extension' => $extension,
                ],
                [
                    'file' => 'required|file',
                    'extension' => 'required|in:csv,xlsx,xls',
                ]
            );

            if ($validator->fails() || !$file->isValid()) {
                return response()->json(['message' => 'Arquivo inválido.'], 400);
            }

            $fileHash = hash_file('sha256', $file->getRealPath());
            if (FileUpload::where('file_hash', $fileHash)->exists()) {
                return response()->json(['message' => 'Arquivo já carregado'], 400);
            }

            $originalFileName = $file->getClientOriginalName();

            $newFileName = $fileHash . '.' . $extension;

            $filePath = Storage::disk('public')->putFileAs('/uploads', $file, $newFileName);

            FileUpload::create([
                'file_name' => $originalFileName,
                'file_hash' => $fileHash,
                'path' => $filePath,
            ]);

            return response()->json(['message' => 'Upload realizado com sucesso'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao realizar o upload'], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Histórico de arquivos recuperado com sucesso.',
            'data' => FileUpload::filter($request)->get(),
        ], 200);
    }

    public function content($fileHash, Request $request): JsonResponse
    {
        try {
            $file = FileUpload::where('file_hash', $fileHash)->first();

            if (!$file) {
                return response()->json([
                    'message' => 'Arquivo não encontrado.',
                    'data' => []
                ], 404);
            }

            $filePath = 'uploads/' . $file->file_hash . '.' . pathinfo($file->file_name, PATHINFO_EXTENSION);

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'message' => 'Arquivo não encontrado.',
                    'data' => []
                ], 404);
            }

            $fileContents = Storage::disk('public')->get($filePath);

            $desiredColumns = [
                'RptDt',
                'TckrSymb',
                'MktNm',
                'SctyCtgyNm',
                'ISIN',
                'CrpnNm'
            ];

            $params = [
                'page' => $request->input('page', 1),
                'per_page' => 20,
                'rpt_dt' => $request->input('rpt_dt') ?? null,
                'tckr_symb' => $request->input('tckr_symb') ?? null,
                'desired_columns' => json_encode($desiredColumns)
            ];

            $fileExtension = pathinfo($file->file_name, PATHINFO_EXTENSION);
            if (strtolower($fileExtension) === 'xlsx' || strtolower($fileExtension) === 'xlxlssx') {
                $response = Http::attach('file', $fileContents, $file->file_name)
                    ->post(env('PYTHON_API_URL') . '/process_excel', $params);

            } else if (strtolower($fileExtension) === 'csv'){
                $response = Http::attach('file', $fileContents, $file->file_name)
                    ->post(env('PYTHON_API_URL') . '/process_csv', $params);
                //$responseData = $this->processCsvContent($fileContents, $desiredColumns, $page, $perPage); //leitor versão laravel -> utilizar apenas o python (laravel está incompleto)
            } else {
                return response()->json(['message' => 'Arquivo inválido.'], 400);
            }
            $responseData = $response->json();

            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar o conteúdo do arquivo.',
                'data' => []
            ], 500);
        }
    }

    // Comentado pois não será utilizado -> leitor será no python
    // private function processCsvContent($fileContents, $desiredColumns, $page, $perPage): array
    // {
    //     $lines = explode("\n", $fileContents);

    //     //checa linha inválida
    //     $firstLine = trim($lines[0]);
    //     if (str_contains($firstLine, 'Status do Arquivo: Final')) {
    //         array_shift($lines);
    //     }
    //     $header = str_getcsv(array_shift($lines), ';');

    //     $offset = ($page - 1) * $perPage;
    //     $paginatedLines = array_slice($lines, $offset, $perPage);

    //     $responseData = [];
    //     foreach ($paginatedLines as $line) {
    //         $row = str_getcsv($line, ';');
    //         if (count($row) > 0) {
    //             $rowAssoc = [];
    //             foreach ($row as $key => $value) {
    //                 $columnName = $header[$key];
    //                 // se a coluna estiver no array das desejadas a adiciona ao array de resposta
    //                 if (in_array($columnName, $desiredColumns)) {
    //                     $rowAssoc[$columnName] = $value;
    //                 }
    //             }
    //             $responseData[] = $rowAssoc;
    //         }
    //     }

    //     return $responseData;
    // }
}
