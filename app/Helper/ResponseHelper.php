<?php

namespace App\Helper;

use Illuminate\Support\Facades\Log;
use Throwable;

class ResponseHelper
{
    public static function cleanJsonResponse(string $raw): ?array
    {
        $cleaned = preg_replace('/^```(?:json|JSON)?\s*/m', '', trim($raw));
        $cleaned = preg_replace('/```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        if (!str_starts_with($cleaned, '{') && !str_starts_with($cleaned, '[')) {
            preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches);
            $cleaned = $matches[1] ?? $cleaned;
        }

        try {
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        } catch (Throwable $e) {
            Log::warning('JSON parsing failed', [
                'raw' => $raw,
                'cleaned' => $cleaned,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}
