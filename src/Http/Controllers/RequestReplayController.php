<?php

namespace NIN\RequestLogAnalyzer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NIN\RequestLogAnalyzer\Models\RequestReplay;
use NIN\RequestLogAnalyzer\Services\RequestReplayService;

class RequestReplayController extends Controller
{
    public function __construct(
        private readonly RequestReplayService $replayService,
    ) {}

    /**
     * Display list of stored requests available for replay
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $method = $request->input('method');

        $query = RequestReplay::recent();

        if ($status && in_array($status, ['pending', 'replayed', 'failed', 'archived'])) {
            $query->where('status', $status);
        }

        if ($method) {
            $query->byMethod($method);
        }

        $replays = $query->paginate(15);
        $stats = [
            'total' => RequestReplay::count(),
            'pending' => RequestReplay::pending()->count(),
            'replayed' => RequestReplay::replayed()->count(),
            'failed' => RequestReplay::failed()->count(),
        ];

        return view('request-log-analyzer::replay.index', compact('replays', 'stats', 'status', 'method'));
    }

    /**
     * Show detail of a single replay with execution history
     */
    public function show(RequestReplay $replay)
    {
        $executions = $replay->getExecutionHistory();

        return view('request-log-analyzer::replay.show', compact('replay', 'executions'));
    }

    /**
     * Execute a replay request
     */
    public function execute(RequestReplay $replay)
    {
        // Validate method
        if (!$this->replayService->isSafeMethod($replay->method)) {
            return back()->with('error', "Method {$replay->method} is not allowed. Only GET and POST are supported.");
        }

        try {
            $execution = $this->replayService->executeReplay($replay);

            $message = $execution->isSuccessful()
                ? "Replay executed successfully (Status: {$execution->status_code})"
                : "Replay executed but returned status {$execution->status_code}";

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', "Replay failed: {$e->getMessage()}");
        }
    }

    /**
     * Delete a replay record
     */
    public function destroy(RequestReplay $replay)
    {
        $replay->delete();

        return redirect()->route('request-log-analyzer.replay.index')
            ->with('success', 'Replay record deleted.');
    }

    /**
     * API endpoint: Store a new replay
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'method' => 'required|in:GET,POST',
            'payload' => 'nullable|array',
            'headers' => 'nullable|array',
            'uri' => 'required|string',
            'query_string' => 'nullable|string',
        ]);

        $replay = $this->replayService->createReplayFromRequest([
            'url' => $validated['url'],
            'method' => $validated['method'],
            'body' => $validated['payload'] ?? null,
            'headers' => $validated['headers'] ?? [],
            'uri' => $validated['uri'],
            'query_string' => $validated['query_string'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'replay_id' => $replay->id,
            'message' => 'Replay stored successfully',
        ], 201);
    }

    /**
     * API endpoint: Get replay details with masked data
     */
    public function getReplay(RequestReplay $replay)
    {
        return response()->json([
            'id' => $replay->id,
            'method' => $replay->method,
            'url' => $replay->url,
            'uri' => $replay->uri,
            'status' => $replay->status,
            'payload' => $replay->getSafePayload(),
            'headers' => $replay->getSafeHeaders(),
            'created_at' => $replay->created_at,
            'replayed_at' => $replay->replayed_at,
            'last_error' => $replay->last_error,
            'is_executable' => $replay->isExecutable(),
        ]);
    }

    /**
     * API endpoint: Execute replay and return result
     */
    public function executeReplayApi(RequestReplay $replay)
    {
        if (!$this->replayService->isSafeMethod($replay->method)) {
            return response()->json([
                'success' => false,
                'error' => "Method {$replay->method} is not allowed for replay",
            ], 403);
        }

        try {
            $execution = $this->replayService->executeReplay($replay);

            return response()->json([
                'success' => true,
                'status_code' => $execution->status_code,
                'response_time_ms' => $execution->response_time_ms,
                'error_message' => $execution->error_message,
                'executed_at' => $execution->executed_at,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint: Get execution history for a replay
     */
    public function getExecutions(RequestReplay $replay)
    {
        $executions = $replay->executions()
            ->orderByDesc('executed_at')
            ->limit(20)
            ->get()
            ->map(fn ($ex) => [
                'id' => $ex->id,
                'status_code' => $ex->status_code,
                'response_time_ms' => $ex->response_time_ms,
                'error_message' => $ex->error_message,
                'is_successful' => $ex->isSuccessful(),
                'executed_at' => $ex->executed_at,
            ]);

        return response()->json(['executions' => $executions]);
    }
}
