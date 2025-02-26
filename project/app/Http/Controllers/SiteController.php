<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function history(Request $request): View
    {
        $files = FileUpload::filter($request)->get();

        return view('historico', [
            'files' => $files,
        ]);
    }
}
