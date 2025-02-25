<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function upload(Request $request)
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

    public function download($fileHash)
    {
        try {
            $file = FileUpload::where('file_hash', $fileHash)->first();

            if (!$file) {
                return response()->json(['message' => 'Arquivo não encontrado.'], 404);
            }

            $filePath = 'uploads/' . $file->file_hash . '.' . pathinfo($file->file_name, PATHINFO_EXTENSION);

            if (Storage::disk('public')->exists($filePath)) {
                $downloadName = $file->file_name;

                return response()->download(storage_path('app/public/' . $filePath), $downloadName);
            }

            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao processar o download.'], 500);
        }
    }
}
