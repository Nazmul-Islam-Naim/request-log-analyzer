<?php

namespace NIN\RequestLogAnalyzer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyzerApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('request-log-analyzer.api.token');

        // If no token is configured, the API is disabled.
        if (empty($configuredToken)) {
            return response()->json([
                'message' => 'API access is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $provided = $this->extractToken($request);

        if (empty($provided) || ! hash_equals($configuredToken, $provided)) {
            return response()->json([
                'message' => 'Unauthorized. Provide a valid Bearer token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    private function extractToken(Request $request): string
    {
        // Accept: Authorization: Bearer <token>  OR  ?api_token=<token>
        $bearer = $request->bearerToken();

        if (! empty($bearer)) {
            return $bearer;
        }

        return (string) $request->query('api_token', '');
    }
}
