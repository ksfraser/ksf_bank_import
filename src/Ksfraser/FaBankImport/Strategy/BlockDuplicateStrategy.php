<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
 * @BABOK CLASS ROLLUP: BlockDuplicateStrategy — 2 methods; BR-002 (2); FR-005 (2)
 * @BABOK RTM: BR-002 / FR-005; file: Strategy/BlockDuplicateStrategy.php; coverage: docs/TestCases/ (file rollup)
 * Block Duplicate Strategy
 * 
 * Hard block - rejects duplicate uploads completely
 * No force override allowed
 * User must rename or modify file
 * 
 * Use case: Strict production environments
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class BlockDuplicateStrategy implements DuplicateStrategyInterface
{
    /**
 * @BABOK BR-002 / FR-005 — method: handle
     * Handle duplicate - block upload completely
     * 
     * @param DuplicateResult $duplicateResult
     * @return array Error message with existing file details
     * 
     * @throws \RuntimeException Always throws to block upload
     */
    public function handle(DuplicateResult $duplicateResult): array
    {
        $existingFile = $duplicateResult->getExistingFile();
        
        $message = sprintf(
            'Error: Duplicate file detected. This file was already uploaded on %s by %s. ' .
            'Original file: %s (Size: %s). ' .
            'Duplicate uploads are not allowed. Please rename the file or verify it is different.',
            $existingFile->getUploadDate()->format('Y-m-d H:i:s'),
            $existingFile->getUploadUser(),
            $existingFile->getOriginalFilename(),
            $existingFile->getFormattedSize()
        );
        
        return [
            'success' => false,
            'message' => $message,
            'existingFileId' => $existingFile->getId(),
            'action' => 'block',
            'allowForce' => false
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
        return 'block';
    }
}
