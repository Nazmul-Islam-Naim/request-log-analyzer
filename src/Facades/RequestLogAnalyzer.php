<?php

namespace NIN\RequestLogAnalyzer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \NIN\RequestLogAnalyzer\RequestLogAnalyzer tag(string|array $tags)
 * @method static string[] flushTags()
 * @method static string[] getTags()
 *
 * @see \NIN\RequestLogAnalyzer\RequestLogAnalyzer
 */
class RequestLogAnalyzer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'request-log-analyzer';
    }
}
