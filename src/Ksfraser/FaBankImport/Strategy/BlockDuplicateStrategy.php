<?php

namespace Ksfraser\FaBankImport\Strategy;

use Ksfraser\FaBankImport\ValueObject\DuplicateResult;

/**
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
     * Get strategy name
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'block';
    }
}
