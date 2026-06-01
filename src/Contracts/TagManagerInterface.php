<?php

namespace NIN\RequestLogAnalyzer\Contracts;

/**
 * TagManagerInterface
 *
 * Manages a per-request list of string tags that are persisted on the
 * `requests` row.  Implementations buffer tags in memory and expose them
 * to the middleware through flushTags() so that the DB write can be
 * deferred to terminate() time.
 *
 * Public API surface is intentionally minimal (ISP) and stateful within
 * a single request lifecycle (one Laravel process = one request = one flush).
 */
interface TagManagerInterface
{
    /**
     * Attach one or more tags to the current request.
     * Duplicate tags are silently deduplicated.
     * Returns static for fluent chaining.
     *
     * @param  string|string[]  $tags
     */
    public function tag(string|array $tags): static;

    /**
     * Return all buffered tags and clear the internal buffer.
     * Called by TrackRequest::terminate() just before the INSERT.
     *
     * @return string[]
     */
    public function flushTags(): array;

    /**
     * Peek at the current pending tags without clearing the buffer.
     * Useful in tests and for debugging.
     *
     * @return string[]
     */
    public function getTags(): array;
}
