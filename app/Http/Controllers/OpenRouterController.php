<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Log;
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

    public function generateStrctureOutputWithPrompt(Request $request): JsonResponse
    {
        $prompt = <<<TEXT
        Expand the niche "Beginner-friendly personal finance" into a single useful content idea suitable for building a structured resource like a course or membership.

        1. Do not describe the niche itself — focus directly on the content idea.
        2. Provide just one practical content idea that a creator could develop and offer to others.
        3. Include:
           - A clear, concise title (avoid using buzzwords or promotional terms)
           - A short description explaining the focus of the course or membership
           - A specific example of what the content would include
           - A brief explanation of how it helps the learner or user

        Before listing the idea, include a short and friendly summary (response_message) introducing the content idea in natural language, without referring to technical schema fields like "title" or "description".

        Additionally, include a follow-up question to gather any final requirements or changes needed for the resource structure, such as:
        - Any adjustments needed to the difficulty level or target audience?
        - Should we modify the module structure or lesson focus?
        - Any specific topics that should be emphasized or de-emphasized?
        - Changes to the overall learning path or progression?
        - Any other refinements to better serve your audience's needs?

        Note: Text will be appended after your response asking if the user wants to proceed with creating the resource.

        If the user has already provided comprehensive resource requirements and no adjustments are needed, set follow_up_question to null or empty string.

        Avoid using words like "monetization", "innovation", "transformative", or similar jargon. Use simple, helpful language focused on clarity and usefulness.

        Return the response as structured JSON.
        TEXT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'response_message' => [
                    'type' => 'string',
                    'description' => 'Response message for the user'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Title of the content'
                ],
                'ideas' => [
                    'type' => 'array',
                    'description' => 'List of content ideas',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Title of the idea'
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Description of the idea'
                            ],
                            'example' => [
                                'type' => 'string',
                                'description' => 'Example demonstrating the idea'
                            ],
                            'benefit' => [
                                'type' => 'string',
                                'description' => 'Benefit of implementing this idea'
                            ],
                        ],
                        'required' => ['title', 'description', 'example', 'benefit']
                    ]
                ],
                'follow_up_question' => [
                    'type' => 'string',
                    'description' => 'Follow-up question to gather additional requirements or set to empty if none needed'
                ],
            ],
            'required' => ['response_message', 'title', 'ideas', 'follow_up_question']
        ];

        $prompt .= "\n\nYou must respond with JSON that strictly follows this exact schema:\n";
        $prompt .= json_encode($schema, JSON_UNESCAPED_SLASHES);
        $prompt .= "\n\nImportant: Only return the raw JSON with no additional text, commentary, or markdown formatting. The response must be valid JSON that can be parsed directly.";

        $rawResponse = $this->openRouterService->structureOutputWithPrompt($prompt);
        Log::info($rawResponse);

        if (!$rawResponse) {
            return response()->json(['error' => 'Failed to generate response'], 500);
        }

        $output = ResponseHelper::cleanJsonResponse($rawResponse);
        Log::info($output);

        if (!$output) {
            return response()->json(['error' => 'Failed to parse structured JSON'], 500);
        }

        return response()->json(['data' => $output]);
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
