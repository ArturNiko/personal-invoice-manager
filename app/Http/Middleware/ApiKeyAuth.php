<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('services.api_key');

        if (! $expectedKey) {
            return response()->json(['message' => 'API key not configured'], 500);
        }

        $providedKey = $request->header('X-API-Key')
            ?: $request->bearerToken();

        if (! hash_equals($expectedKey, (string) $providedKey)) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        return $next($request);
    }
}