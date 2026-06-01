<?php

use Illuminate\Support\Facades\Route;
use NIN\RequestLogAnalyzer\Http\Controllers\Api\AnalyzerApiController;
use NIN\RequestLogAnalyzer\Http\Middleware\AnalyzerApiAuth;

Route::prefix(config('request-log-analyzer.api.prefix', 'api/analyzer'))
    ->middleware(['api', AnalyzerApiAuth::class])
    ->name('request-log-analyzer.api.')
    ->group(function () {

        Route::get('/requests', [AnalyzerApiController::class, 'requests'])
            ->name('requests');

        Route::get('/errors', [AnalyzerApiController::class, 'errors'])
            ->name('errors');

        Route::get('/stats', [AnalyzerApiController::class, 'stats'])
            ->name('stats');

    });
