<?php

use App\Http\Controllers\FilesController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () {
    Route::post('/upload', [UploadController::class, 'store']);
    Route::post('/download/{slug}', [UploadController::class, 'download']);
    Route::get('/view/{slug}', [FilesController::class, 'show']);

    Route::get('/files', [FilesController::class, 'index']);
});
