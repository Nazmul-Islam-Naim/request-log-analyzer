<?php

namespace NIN\RequestLogAnalyzer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NIN\RequestLogAnalyzer\Facades\RequestLogAnalyzer;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldIgnore($request)) {
            $responseTimeMs = (int) round((hrtime(true) - $start) / 1_000_000);
            $memoryUsageBytes = memory_get_peak_usage(true);

            RequestLogAnalyzer::log(
                $request,
                $response->getStatusCode(),
                $responseTimeMs,
                $memoryUsageBytes
            );
        }

        return $response;
    }

    private function shouldIgnore(Request $request): bool
    {
        $ignoredPaths = config('request-log-analyzer.ignored_paths', []);

        foreach ($ignoredPaths as $pattern) {
            if (fnmatch($pattern, ltrim($request->path(), '/'))) {
                return true;
            }
        }

        return false;
    }
}
