<?php

namespace App\Http\Controllers;

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

    public function generateResponse(Request $request): JsonResponse
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
}
