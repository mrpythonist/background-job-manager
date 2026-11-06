<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('app.api_key', env('JOB_MANAGER_API_KEY', ''));

        // No key configured: local demo mode, no auth required.
        if ($configuredKey === '') {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            $provided = (string) $request->header('X-API-Key', '');
            if (! hash_equals($configuredKey, $provided)) {
                return response()->json(['message' => 'Unauthorized. Provide a valid X-API-Key header.'], 401);
            }
        }

        return $next($request);
    }
}
