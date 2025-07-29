<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OpenRouterController;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');
Route::post('/openrouter/generate', [OpenRouterController::class, 'generateTextResponse']);
Route::post('/openrouter/endpoint', [OpenRouterController::class, 'generateWithEndpoint']);
Route::get('/openrouter/credits', [OpenRouterController::class, 'getCredits']);
Route::get('/openrouter/providers', [OpenRouterController::class, 'getAvailableProviders']);
Route::get('/openrouter/structured', [OpenRouterController::class, 'getStructuredOutput']);
