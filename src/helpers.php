<?php

use NIN\RequestLogAnalyzer\RequestLogAnalyzer;

if (! function_exists('logAnalyzer')) {
    /**
     * Return the Request Log Analyzer singleton.
     *
     * Usage:
     *   logAnalyzer()->tag('payment');
     *   logAnalyzer()->tag(['payment', 'checkout']);
     */
    function logAnalyzer(): RequestLogAnalyzer
    {
        return app('request-log-analyzer');
    }
}
