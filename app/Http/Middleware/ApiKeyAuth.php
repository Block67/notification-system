<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'success' => false,
                'message' => 'API credentials missing. Provide X-API-Key and X-API-Secret headers.'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.'
            ], 401);
        }

        if (!$key->verifySecret($apiSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API secret.'
            ], 401);
        }

        if (!$key->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'API key is inactive.'
            ], 403);
        }

        // Check permission
        if ($permission && !$key->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Permission denied. This API key doesn't have '{$permission}' permission."
            ], 403);
        }

        // Rate limiting
        $rateLimitKey = 'api_key:' . $key->id;
        $maxAttempts = $key->rate_limit;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            return response()->json([
                'success' => false,
                'message' => "Rate limit exceeded. Try again in {$seconds} seconds.",
                'retry_after' => $seconds
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600); // 1 hour window

        // Attach API key to request
        $request->merge(['api_key' => $key]);
        $key->updateLastUsed();

        return $next($request);
    }
}