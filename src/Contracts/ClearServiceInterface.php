<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * ClearServiceInterface
 *
 * Encapsulates the data-deletion logic for the CLI clear command.
 * Extracted so ClearCommand focuses purely on user interaction (prompts,
 * output formatting) while this service owns the deletion strategy.
 *
 * A test fake can implement this to verify the command behaviour without
 * touching a real database.
 */
interface ClearServiceInterface
{
    /**
     * Delete analyzer records from all package tables in FK-safe order:
     *   request_steps → queries → errors → requests
     *
     * @param  int|null  $olderThanDays  When not null, only rows whose
     *                                   created_at is older than this many
     *                                   days are deleted.
     * @return array<string, int> Table name → count of rows deleted.
     */
    public function clear(?int $olderThanDays = null): array;

    /**
     * Count analyzer records that would be removed for the given retention
     * window without performing any deletions (dry-run support).
     *
     * @param  int|null  $olderThanDays  When not null, only rows whose
     *                                   created_at is older than this many
     *                                   days are counted.
     * @return array<string, int> Table name → count of matching rows.
     */
    public function count(?int $olderThanDays = null): array;
}
