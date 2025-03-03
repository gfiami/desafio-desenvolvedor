<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;

Route::post('/upload', [FileController::class, 'upload'])->middleware('auth:sanctum');
Route::get('/history', [FileController::class, 'history'])->middleware('auth:sanctum');
Route::get('/content/{filehash}', [FileController::class, 'content'])->name('content')->middleware('auth:sanctum');

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);



