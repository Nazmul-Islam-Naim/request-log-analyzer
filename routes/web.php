<?php

use Illuminate\Support\Facades\Route;
use NIN\RequestLogAnalyzer\Http\Controllers\RequestLogController;
use NIN\RequestLogAnalyzer\Http\Controllers\RequestReplayController;

Route::prefix(config('request-log-analyzer.route_prefix', 'request-log-analyzer'))
    ->middleware(config('request-log-analyzer.middleware', ['web']))
    ->name('request-log-analyzer.')
    ->group(function () {

        Route::get('/', [RequestLogController::class, 'index'])
            ->name('dashboard');

        Route::get('/slow-requests', [RequestLogController::class, 'slowRequests'])
            ->name('slow-requests');

        Route::get('/requests', [RequestLogController::class, 'requests'])
            ->name('requests');

        Route::get('/requests/{id}', [RequestLogController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');

        Route::get('/requests/{id}/timeline', [RequestLogController::class, 'timeline'])
            ->name('timeline')
            ->where('id', '[0-9]+');

        Route::get('/analytics', [RequestLogController::class, 'analytics'])
            ->name('analytics');

        Route::get('/api-insights', [RequestLogController::class, 'apiInsights'])
            ->name('api-insights');

        Route::get('/geo', [RequestLogController::class, 'geo'])
            ->name('geo');

        Route::get('/active-users', [RequestLogController::class, 'activeUsers'])
            ->name('active-users');

        Route::get('/user-route-hits', [RequestLogController::class, 'userRouteHits'])
            ->name('user-route-hits');

        Route::get('/login-history', [RequestLogController::class, 'loginHistory'])
            ->name('login-history');

        Route::post('/cleanup', [RequestLogController::class, 'cleanup'])
            ->name('cleanup');

        Route::get('/export/{type}', [RequestLogController::class, 'export'])
            ->name('export')
            ->where('type', 'requests|errors|queries');

        Route::get('/tools', [RequestLogController::class, 'tools'])
            ->name('tools');

        // ── Request Replay Routes ─────────────────────────────────────────
        Route::prefix('replay')->name('replay.')->group(function () {
            Route::get('/', [RequestReplayController::class, 'index'])
                ->name('index');
            Route::get('/{replay}', [RequestReplayController::class, 'show'])
                ->name('show');
            Route::post('/{replay}/execute', [RequestReplayController::class, 'execute'])
                ->name('execute');
            Route::delete('/{replay}', [RequestReplayController::class, 'destroy'])
                ->name('destroy');
        });

    });
