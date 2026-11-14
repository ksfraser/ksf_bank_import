<?php

/**

 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006

 * File Storage Service Interface
 * 
 * Contract for file system operations
 * Handles physical file storage and retrieval
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
 */
interface FileStorageServiceInterface
{
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Store uploaded file to disk
     * 
     * Moves file from temporary location to permanent storage
     * Generates unique filename to avoid collisions
     * Creates directory structure if needed
     * 
     * @param FileInfo $fileInfo File information from upload
     * @param string $parserType Parser type (qfx, mt940, csv, etc.)
     * @return array ['filename' => unique name, 'path' => full path]
     * 
     * @throws \RuntimeException If storage fails
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function store(FileInfo $fileInfo, string $parserType): array;
    
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Delete file from disk
     * 
     * @param string $filePath Full path to file
     * @return bool Success
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function delete(string $filePath): bool;
    
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Check if file exists on disk
     * 
     * @param string $filePath Full path to file
     * @return bool True if exists
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function exists(string $filePath): bool;
    
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Get file contents
     * 
     * @param string $filePath Full path to file
     * @return string File contents
     * 
     * @throws \RuntimeException If file doesn't exist or can't be read
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getContents(string $filePath): string;
    
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Get storage directory path
     * 
     * @return string Full path to upload storage directory
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getStorageDirectory(): string;
    
    /**
 * @BABOK FR-IMP-003 | BR-IMP-003 | UAT-IMP-006
     * Ensure storage directory exists with proper permissions
     * 
     * @return void
     * @throws \RuntimeException If directory can't be created
 * @BABOK FR-IMP-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function ensureStorageDirectoryExists(): void;
}
