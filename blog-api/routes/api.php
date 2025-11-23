<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;

// Public 
Route::middleware(['api'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,60');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/posts', [PostController::class, 'index'])->middleware('throttle:120,1');
    Route::get('/posts/{id}', [PostController::class, 'show'])->middleware('throttle:300,1');
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/posts/{id}', [PostController::class, 'update'])->middleware('throttle:30,1');
    Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:20,1');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->middleware('throttle:10,1');     
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('throttle:60,1');
});