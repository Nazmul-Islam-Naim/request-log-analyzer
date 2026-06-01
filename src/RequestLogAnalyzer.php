<?php

namespace NIN\RequestLogAnalyzer;

use NIN\RequestLogAnalyzer\Contracts\TagManagerInterface;

/**
 * RequestLogAnalyzer — the package's public entry point and container binding.
 *
 * Bound as the `'request-log-analyzer'` singleton and also aliased to this
 * FQCN so that type-hinted injection works anywhere in the application.
 *
 * Responsibilities (SRP — narrow):
 *  - Provide a fluent tagging API used by application code at call-time
 *    (e.g. inside controllers or service layers) to annotate the current
 *    request before the middleware persists it.
 *
 * What this class deliberately does NOT do (each concern moved elsewhere):
 *  - DB persistence  → RequestRepository / ErrorRepository / etc.
 *  - Stats queries   → RequestRepository::stats()
 *  - Recent requests → RequestRepository::recent()
 *
 * DIP: delegates all state management to TagManagerInterface so that
 * tests can swap in a fake implementation without touching the container.
 */
class RequestLogAnalyzer
{
    public function __construct(
        private readonly TagManagerInterface $tagManager,
    ) {}

    /**
     * Attach one or more tags to the current request.
     * Tags are deduplicated and stored as a JSON array on the requests row.
     *
     * Usage:
     *   logAnalyzer()->tag('payment');
     *   logAnalyzer()->tag(['payment', 'checkout']);
     *   \RequestLogAnalyzer::tag('admin');
     *
     * @param  string|string[]  $tags
     */
    public function tag(string|array $tags): static
    {
        $this->tagManager->tag($tags);

        return $this;
    }

    /**
     * Return all buffered tags and clear the internal buffer.
     * Called once by TrackRequest::terminate() just before the INSERT.
     *
     * @return string[]
     */
    public function flushTags(): array
    {
        return $this->tagManager->flushTags();
    }

    /**
     * Peek at the current pending tags without clearing the buffer.
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tagManager->getTags();
    }
}
