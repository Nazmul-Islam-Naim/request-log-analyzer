<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * AlertRepositoryInterface — tracks alert state to prevent alert spam.
 *
 * Stores the last time each alert type was sent, allowing the AlertChecker
 * to respect cooldown periods before sending another alert.
 */
interface AlertRepositoryInterface
{
    /**
     * Get the timestamp (Unix) when the last error alert was sent, or null.
     */
    public function getLastErrorAlertTime(): ?int;

    /**
     * Get the timestamp (Unix) when the last slow request alert was sent, or null.
     */
    public function getLastSlowAlertTime(): ?int;

    /**
     * Record that an error alert was just sent (now).
     */
    public function recordErrorAlert(): void;

    /**
     * Record that a slow request alert was just sent (now).
     */
    public function recordSlowAlert(): void;
}
