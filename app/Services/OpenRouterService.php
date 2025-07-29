<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenRouterService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;
    protected string $creditsUrl;

    public function __construct()
    {
        $config = config('services.openrouter');
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->creditsUrl = $config['credits_url'];
        $this->providersUrl = $config['providers_url'];
    }

    public function getCredits(): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->creditsUrl);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch OpenRouter credits', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::getCredits exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getProviders(): ?array
    {
        try {
            $response = Http::get($this->providersUrl);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch OpenRouter providers', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::getProviders exception: ' . $e->getMessage());
            return null;
        }
    }
}
