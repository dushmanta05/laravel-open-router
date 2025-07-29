<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OpenRouterController;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');
Route::post('/generate', [OpenRouterController::class, 'generateResponse']);
