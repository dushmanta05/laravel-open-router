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
    protected string $providersUrl;

    public function __construct()
    {
        $config = config('services.openrouter');
        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->creditsUrl = $config['credits_url'];
        $this->providersUrl = $config['providers_url'];
    }

    public function generateResponse(string $message): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $message,
                            ]
                        ]
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info($data);
                return $data['choices'][0]['message']['content'] ?? null;
            }
            Log::info($response);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::generateResponse exception: ' . $e->getMessage());
            return null;
        }
    }

    public function generateStructuredResponse(string $message, array $schema): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $message,
                            ]
                        ],
                        'response_format' => [
                            'type' => 'json_schema',
                            'json_schema' => $schema,
                        ],
                    ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? null;
                return $content ? json_decode($content, true) : null;
            }

            Log::error('Structured message failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::generateStructuredResponse exception: ' . $e->getMessage());
            return null;
        }
    }

    public function generateWithChatHistory(array $messages): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                        'model' => $this->model,
                        'messages' => $messages,
                    ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenRouter multi-turn chat failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::generateWithChatHistory exception: ' . $e->getMessage());
            return null;
        }
    }

    public function generateStructuredChatHistory(array $messages, array $schema): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                        'model' => $this->model,
                        'messages' => $messages,
                        'response_format' => [
                            'type' => 'json_schema',
                            'json_schema' => $schema,
                        ],
                    ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? null;
                return $content ? json_decode($content, true) : null;
            }

            Log::error('Structured chat failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('OpenRouterService::generateStructuredChatHistory exception: ' . $e->getMessage());
            return null;
        }
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
