<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\DTOs\ImportSessionDTO;

/**
 * Contract for orchestrating import pipeline
 *
 * Orchestrator is the main entry point that:
 * - Coordinates handlers through the pipeline
 * - Manages session state through all steps
 * - Handles errors and rollback
 * - Reports progress
 *
 * Pipeline steps: upload → parse → validate → transform → detect-duplicates → review → complete
 */
interface OrchestratorInterface
{
    /**
     * Execute complete import pipeline
     *
     * @param ImportSessionDTO $session The import session
     * @return ImportSessionDTO Updated session after pipeline completion
     */
    public function executeImport(ImportSessionDTO $session): ImportSessionDTO;

    /**
     * Cancel an import in progress
     *
     * @param string $sessionId Session to cancel
     * @param string $reason Cancellation reason
     * @return void
     */
    public function cancelImport(string $sessionId, string $reason): void;

    /**
     * Get orchestrator name
     *
     * @return string
     */
    public function getName(): string;
}
