<?php

namespace App\Http\Controllers;

use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use Throwable;

use App\Enums\RoleType;

class OpenRouterController extends Controller
{
    protected OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    public function generateTextResponse(Request $request): JsonResponse
    {
        try {
            $message = $request->input('message');

            if (!$message) {
                return response()->json(['error' => 'Message is required'], 400);
            }

            $openRouterConfig = config('services.openrouter');
            $model = $openRouterConfig['model'];
            $max_tokens = (int) ($openRouterConfig['max_tokens'] ?? 100);

            $messageData = new MessageData(
                content: $message,
                role: RoleType::USER->value,
            );

            $chatData = new ChatData(
                messages: [
                    $messageData,
                ],
                model: $model,
                max_tokens: 2000,
            );

            $chatResponse = LaravelOpenRouter::chatRequest($chatData);
            $responseArray = $chatResponse->toArray();

            return response()->json(['response' => $responseArray]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred while processing the request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getStructuredOutput(Request $request): JsonResponse
    {
        $prompt = "What's the weather like in London? Give a dummy example if you don't know.";

        $schema = [
            'name' => 'weather',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => 'City or location name',
                    ],
                    'temperature' => [
                        'type' => 'number',
                        'description' => 'Temperature in Celsius',
                    ],
                    'conditions' => [
                        'type' => 'string',
                        'description' => 'Weather conditions description',
                    ],
                ],
                'required' => ['location', 'temperature', 'conditions'],
                'additionalProperties' => false,
            ]
        ];

        $response = $this->openRouterService->generateStructuredResponse($prompt, $schema);

        if (!$response) {
            return response()->json(['error' => 'Failed to fetch structured data'], 500);
        }

        return response()->json(['data' => $response]);
    }

    public function multiTurnChat(): JsonResponse
    {
        $chatHistory = [
            [
                'role' => 'user',
                'content' => 'What is JavaScript?'
            ],
            [
                'role' => 'assistant',
                'content' => 'JavaScript is a programming language used to build interactive websites and applications.'
            ],
            [
                'role' => 'user',
                'content' => 'Can you explain what variables are in JavaScript?'
            ]
        ];

        try {
            $response = $this->openRouterService->generateWithChatHistory($chatHistory);

            if (!$response) {
                return response()->json(['error' => 'Failed to get model response'], 500);
            }

            return response()->json([
                'messages' => array_merge($chatHistory, [
                    [
                        'role' => 'assistant',
                        'content' => $response
                    ]
                ])
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function structuredMultiTurnChat(): JsonResponse
    {
        $chatHistory = [
            [
                'role' => 'user',
                'content' => 'Hey, I’m planning a trip to London.'
            ],
            [
                'role' => 'assistant',
                'content' => 'Sounds exciting! What info you need for the trip?'
            ],
            [
                'role' => 'user',
                'content' => 'Can you tell me the current weather there?'
            ]
        ];

        $schema = [
            'name' => 'weather',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => 'City or location name'
                    ],
                    'temperature' => [
                        'type' => 'number',
                        'description' => 'Temperature in Celsius'
                    ],
                    'conditions' => [
                        'type' => 'string',
                        'description' => 'Weather conditions description'
                    ]
                ],
                'required' => ['location', 'temperature', 'conditions'],
                'additionalProperties' => false
            ]
        ];

        try {
            $response = $this->openRouterService->generateStructuredChatHistory($chatHistory, $schema);

            if (!$response) {
                return response()->json(['error' => 'Failed to fetch structured weather data'], 500);
            }

            return response()->json([
                'messages' => $chatHistory,
                'structured_response' => $response
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function generateWithEndpoint(Request $request): JsonResponse
    {
        try {
            $message = $request->input('message');

            if (!$message) {
                return response()->json(['error' => 'Message is required'], 400);
            }

            $response = $this->openRouterService->generateResponse($message);

            if (!$response) {
                return response()->json(['error' => 'Failed to get a valid response from OpenRouter'], 500);
            }

            return response()->json(['response' => $response, 'success' => true]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred while processing the request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getCredits(): JsonResponse
    {
        try {
            $credits = $this->openRouterService->getCredits();

            if (!$credits) {
                return response()->json(['error' => 'Failed to fetch credits'], 500);
            }

            return response()->json(['credits' => $credits]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableProviders(): JsonResponse
    {
        try {
            $providers = $this->openRouterService->getProviders();

            if (!$providers) {
                return response()->json(['error' => 'Failed to fetch providers'], 500);
            }

            return response()->json(['providers' => $providers]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
