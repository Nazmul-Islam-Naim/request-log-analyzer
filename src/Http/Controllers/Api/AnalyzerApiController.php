<?php

namespace NIN\RequestLogAnalyzer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use NIN\RequestLogAnalyzer\Contracts\ErrorRepositoryInterface;
use NIN\RequestLogAnalyzer\Contracts\RequestRepositoryInterface;
use NIN\RequestLogAnalyzer\Http\Resources\ErrorResource;
use NIN\RequestLogAnalyzer\Http\Resources\RequestResource;
use NIN\RequestLogAnalyzer\Models\Error;
use NIN\RequestLogAnalyzer\Models\Request as RlaRequest;

class AnalyzerApiController extends Controller
{
    public function __construct(
        private readonly RequestRepositoryInterface $requests,
        private readonly ErrorRepositoryInterface $errors,
    ) {}

    // ── GET /api/analyzer/requests ─────────────────────────────────────────

    public function requests(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = RlaRequest::withCount(['errors', 'queries'])
            ->latest();

        if ($method = $request->query('method')) {
            $query->where('method', strtoupper($method));
        }

        if ($status = $request->query('status_code')) {
            $query->where('status_code', (int) $status);
        }

        if ($uri = $request->query('uri')) {
            $query->where('uri', 'like', '%'.$uri.'%');
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return RequestResource::collection($query->paginate($perPage));
    }

    // ── GET /api/analyzer/errors ───────────────────────────────────────────

    public function errors(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = Error::with('request')
            ->latest();

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return ErrorResource::collection($query->paginate($perPage));
    }

    // ── GET /api/analyzer/stats ────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $stats = $this->requests->stats();

        return response()->json([
            'data' => $stats,
            'meta' => [
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
