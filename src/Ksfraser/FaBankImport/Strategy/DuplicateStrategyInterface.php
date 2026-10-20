<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * @BABOK RTM: BR-002 / FR-005; file: Strategy/DuplicateStrategyInterface.php; coverage: docs/TestCases/ (file rollup)
 * Duplicate Handling Strategy Interface
 * 
 * Strategy Pattern for handling duplicate files
 * Different strategies: allow, warn, block
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
interface DuplicateStrategyInterface
{
    /**
 * @BABOK BR-002 / FR-005 — method: handle
     * Handle duplicate file upload
     * 
     * @param DuplicateResult $duplicateResult Result from duplicate detection
     * @return array ['success' => bool, 'message' => string, 'existingFileId' => int|null]
     * 
     * @throws \RuntimeException If upload should be blocked
     */
    public function handle(DuplicateResult $duplicateResult): array;
    
    /**
 * @BABOK BR-002 / FR-005 — method: getName
     * Get strategy name
     * 
     * @return string Strategy name (allow, warn, block)
     */
    public function getName(): string;
}
