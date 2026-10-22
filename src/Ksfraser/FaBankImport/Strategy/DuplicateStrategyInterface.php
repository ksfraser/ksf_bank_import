<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * Duplicate Handling Strategy Interface
 * 
 * Strategy Pattern for handling duplicate files
 * Different strategies: allow, warn, block
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
interface DuplicateStrategyInterface
{
    /**
     * Handle duplicate file upload
     * 
     * @param DuplicateResult $duplicateResult Result from duplicate detection
     * @return array ['success' => bool, 'message' => string, 'existingFileId' => int|null]
     * 
     * @throws \RuntimeException If upload should be blocked
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function handle(DuplicateResult $duplicateResult): array;
    
    /**
     * Get strategy name
     * 
     * @return string Strategy name (allow, warn, block)
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getName(): string;
}
