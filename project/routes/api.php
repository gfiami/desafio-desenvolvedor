<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/upload', [FileController::class, 'upload']);
Route::get('/history', [FileController::class, 'history']);
Route::get('/content/{filehash}', [FileController::class, 'content'])->name('content');



