<?php

namespace NIN\RequestLogAnalyzer\Support;

use NIN\RequestLogAnalyzer\Contracts\TagManagerInterface;

/**
 * TagManager — default in-memory implementation of TagManagerInterface.
 *
 * Registered as a singleton so that logAnalyzer()->tag('x') and subsequent
 * calls within the same request all append to the same buffer, which is then
 * flushed once in TrackRequest::terminate().
 *
 * Thread safety / concurrency: PHP-FPM runs one request per process, so a
 * singleton is safe without any locking.
 */
class TagManager implements TagManagerInterface
{
    /** @var string[] */
    private array $pendingTags = [];

    public function tag(string|array $tags): static
    {
        $tags = is_array($tags) ? $tags : [$tags];
        $this->pendingTags = array_values(
            array_unique(array_merge($this->pendingTags, $tags))
        );

        return $this;
    }

    public function flushTags(): array
    {
        $tags = $this->pendingTags;
        $this->pendingTags = [];

        return $tags;
    }

    public function getTags(): array
    {
        return $this->pendingTags;
    }
}
