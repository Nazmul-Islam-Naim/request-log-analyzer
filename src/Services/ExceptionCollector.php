<?php

namespace NIN\RequestLogAnalyzer\Services;

use NIN\RequestLogAnalyzer\Models\Error;
use Throwable;

/**
 * ExceptionCollector — pure in-memory exception buffer with masking.
 *
 * Responsibilities (SRP):
 *  - Collect Throwable instances during a request lifecycle.
 *  - Delegate sensitive-data masking to SensitiveDataMasker.
 *  - Expose the masked buffer via getBuffer() and drain().
 *
 * Persistence is intentionally NOT this class's concern; it is handled by
 * ErrorRepository::bulkInsert() so that the collector can be unit-tested
 * without a database connection and without Eloquent models.
 */
class ExceptionCollector
{
    /** @var array<int, array{exception_class: string, message: string, file: string, line: int, trace: string, context: array|null, severity: string}> */
    private array $buffer = [];

    public function __construct(
        private readonly SensitiveDataMasker $masker,
    ) {}

    /**
     * Push an exception into the buffer.
     * Severity is derived from the exception type map in config
     * (falls back to 'error' for all unrecognised exceptions).
     */
    public function collect(Throwable $e, string $severity = Error::SEVERITY_ERROR): void
    {
        $this->buffer[] = [
            'exception_class' => get_class($e),
            'message' => $this->masker->maskString($e->getMessage()),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->maskTrace($e->getTraceAsString()),
            'context' => null,   // populated via collectWithContext()
            'severity' => $severity,
        ];
    }

    /**
     * Push an exception with extra structured context.
     * Context values are masked before storage.
     */
    public function collectWithContext(Throwable $e, array $context, string $severity = Error::SEVERITY_ERROR): void
    {
        $this->collect($e, $severity);

        // Overwrite context on the last buffered item.
        $idx = count($this->buffer) - 1;
        $this->buffer[$idx]['context'] = $this->masker->maskFields($context);
    }

    /**
     * Return all buffered rows and atomically reset the buffer.
     * Called by TrackRequest::terminate() (sync) or by the async path before
     * dispatching PersistRequestLog. The repository's bulkInsert() handles
     * the actual DB write.
     *
     * @return array<int, array{exception_class: string, message: string, file: string, line: int, trace: string, context: array|null, severity: string}>
     */
    public function drain(): array
    {
        $buffer = $this->buffer;
        $this->buffer = [];

        return $buffer;
    }

    public function reset(): void
    {
        $this->buffer = [];
    }

    public function count(): int
    {
        return count($this->buffer);
    }

    /**
     * Return the raw buffer without clearing it.
     * Useful for testing and for peeking before drain().
     *
     * @return array<int, array>
     */
    public function getBuffer(): array
    {
        return $this->buffer;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Apply masking to each line of a stack trace individually.
     */
    private function maskTrace(string $trace): string
    {
        $lines = explode("\n", $trace);

        foreach ($lines as &$line) {
            $line = $this->masker->maskString($line);
        }
        unset($line);

        return implode("\n", $lines);
    }
}
