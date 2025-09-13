<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('test', function () {
    return 'Route fonctionne !';
});
Route::get('files/uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/uploads/' . $filename);
    
    if (!file_exists($path)) {
        return response()->json(['error' => 'File not found', 'path' => $path]);
    }
    
    return response()->file($path);
});
