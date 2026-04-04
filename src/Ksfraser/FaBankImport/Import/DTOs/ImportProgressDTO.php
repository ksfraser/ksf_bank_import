<?php

namespace Ksfraser\FaBankImport\Import\DTOs;

/**
 * Data transfer object for import progress tracking
 *
 * Tracks the progress of an import through the pipeline.
 * Immutable - use create() or withStep() for updates.
 */
final class ImportProgressDTO
{
    /**
     * Create import progress DTO
     *
     * @param string $sessionId Unique import session identifier
     * @param string $step Current step (upload, parsing, validation, transform, duplicate, review)
     * @param int $totalItems Total items to process
     * @param int $processedItems Number of items processed so far
     * @param int $successItems Number of successfully processed items
     * @param int $errorItems Number of items with errors
     * @param string $status Overall status (in_progress, success, error, cancelled)
     * @param array<int, string> $messages Progress messages
     * @param int $percentComplete Percentage of completion (0-100)
     * @param int $startTime Unix timestamp of import start
     * @param int $elapsedSeconds Seconds elapsed
     */
    private function __construct(
        public readonly string $sessionId,
        public readonly string $step,
        public readonly int $totalItems,
        public readonly int $processedItems,
        public readonly int $successItems,
        public readonly int $errorItems,
        public readonly string $status,
        public readonly array $messages = [],
        public readonly int $percentComplete = 0,
        public readonly int $startTime = 0,
        public readonly int $elapsedSeconds = 0
    ) {
    }

    /**
     * Create new import progress session
     *
     * @param string $sessionId Session identifier
     * @param int $totalItems Total items to process
     * @return self
     */
    public static function create(string $sessionId, int $totalItems = 0): self
    {
        return new self(
            $sessionId,
            'uploaded',
            $totalItems,
            0,
            0,
            0,
            'in_progress',
            [],
            0,
            time(),
            0
        );
    }

    /**
     * Create with updated step
     *
     * @param string $newStep The new step
     * @param array<string, mixed> $updates Additional updates
     * @return self
     */
    public function withStep(string $newStep, array $updates = []): self
    {
        $processed = $updates['processedItems'] ?? $this->processedItems;
        $success = $updates['successItems'] ?? $this->successItems;
        $errors = $updates['errorItems'] ?? $this->errorItems;
        $percent = $this->totalItems > 0 ? (int)(($processed / $this->totalItems) * 100) : 0;
        $messages = array_merge($this->messages, $updates['messages'] ?? []);
        $elapsed = time() - $this->startTime;

        return new self(
            $this->sessionId,
            $newStep,
            $this->totalItems,
            $processed,
            $success,
            $errors,
            $updates['status'] ?? $this->status,
            $messages,
            $percent,
            $this->startTime,
            $elapsed
        );
    }

    /**
     * Create with item processed
     *
     * @param bool $successful Whether item was processed successfully
     * @param string|null $message Optional progress message
     * @return self
     */
    public function withItemProcessed(bool $successful = true, ?string $message = null): self
    {
        $success = $this->successItems + ($successful ? 1 : 0);
        $errors = $this->errorItems + ($successful ? 0 : 1);
        $processed = $this->processedItems + 1;

        return $this->withStep($this->step, [
            'processedItems' => $processed,
            'successItems' => $success,
            'errorItems' => $errors,
            'messages' => $message ? [$message] : [],
        ]);
    }

    /**
     * Get completion percentage
     *
     * @return int 0-100
     */
    public function getPercentComplete(): int
    {
        if ($this->totalItems === 0) {
            return 0;
        }
        return (int)(($this->processedItems / $this->totalItems) * 100);
    }

    /**
     * Get formatted time elapsed
     *
     * @return string e.g., "1h 23m 45s"
     */
    public function getFormattedElapsed(): string
    {
        $seconds = time() - $this->startTime;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }
        $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Convert to array for storage/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'step' => $this->step,
            'totalItems' => $this->totalItems,
            'processedItems' => $this->processedItems,
            'successItems' => $this->successItems,
            'errorItems' => $this->errorItems,
            'status' => $this->status,
            'messages' => $this->messages,
            'percentComplete' => $this->getPercentComplete(),
            'elapsedSeconds' => time() - $this->startTime,
            'formattedElapsed' => $this->getFormattedElapsed(),
        ];
    }
}
