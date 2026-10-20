<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * @BABOK RTM: BR-002 / FR-005; file: Strategy/WarnDuplicateStrategy.php; coverage: docs/TestCases/ (file rollup)
 * Warn Duplicate Strategy
 * 
 * Warns user about duplicate but allows override with "force" flag
 * Shows prompt: "Force Upload" or "Cancel"
 * 
 * Use case: Production with flexibility
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class WarnDuplicateStrategy implements DuplicateStrategyInterface
{
    /**
 * @BABOK BR-002 / FR-005 — method: handle
     * Handle duplicate - warn user, allow force override
     * 
     * @param DuplicateResult $duplicateResult
     * @return array Warning message with existing file details
     */
    public function handle(DuplicateResult $duplicateResult): array
    {
        $existingFile = $duplicateResult->getExistingFile();
        
        $message = sprintf(
            'Warning: This file appears to be a duplicate of an existing file uploaded on %s by %s. ' .
            'Original file: %s (Size: %s). ' .
            'You can force upload if this is intentional.',
            $existingFile->getUploadDate()->format('Y-m-d H:i:s'),
            $existingFile->getUploadUser(),
            $existingFile->getOriginalFilename(),
            $existingFile->getFormattedSize()
        );
        
        return [
            'success' => false,
            'message' => $message,
            'existingFileId' => $existingFile->getId(),
            'action' => 'warn',
            'allowForce' => true
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
        return 'warn';
    }
}
