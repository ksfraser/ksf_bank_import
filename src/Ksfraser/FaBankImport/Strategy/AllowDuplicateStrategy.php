<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * @BABOK RTM: BR-002 / FR-005; file: Strategy/AllowDuplicateStrategy.php; coverage: docs/TestCases/ (file rollup)
 * Allow Duplicate Strategy
 * 
 * Silently allows duplicate uploads by reusing existing file
 * No error, no warning - just returns existing file ID
 * 
 * Use case: Development/testing environments
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class AllowDuplicateStrategy implements DuplicateStrategyInterface
{
    /**
 * @BABOK BR-002 / FR-005 — method: handle
     * Handle duplicate - allow and reuse existing file
     * 
     * @param DuplicateResult $duplicateResult
     * @return array Success with existing file ID
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
 * @BABOK BR-002 / FR-005 — method: getName
     * Get strategy name
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'allow';
    }
}
