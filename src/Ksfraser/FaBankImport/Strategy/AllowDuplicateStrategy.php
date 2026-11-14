<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003

 * Allow Duplicate Strategy
 * 
 * Silently allows duplicate uploads by reusing existing file
 * No error, no warning - just returns existing file ID
 * 
 * Use case: Development/testing environments
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
class AllowDuplicateStrategy implements DuplicateStrategyInterface
{
    /**
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     * Handle duplicate - allow and reuse existing file
     * 
     * @param DuplicateResult $duplicateResult
     * @return array Success with existing file ID
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function handle(DuplicateResult $duplicateResult): array
    {
        $existingFile = $duplicateResult->getExistingFile();
        
        return [
            'success' => true,
            'message' => 'Duplicate file detected. Using existing file (ID: ' . 
                        $existingFile->getId() . ').',
            'existingFileId' => $existingFile->getId(),
            'action' => 'reused'
        ];
    }
    
    /**
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     * Get strategy name
     * 
     * @return string
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getName(): string
    {
        return 'allow';
    }
}
