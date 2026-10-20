<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * @BABOK CLASS ROLLUP: FileStorageServiceInterface — 6 methods; BR-001 (6); BR-002 (6); FR-008 (6)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/FileStorageServiceInterface.php; coverage: docs/TestCases/ (file rollup)
 * File Storage Service Interface
 * 
 * Contract for file system operations
 * Handles physical file storage and retrieval
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
interface FileStorageServiceInterface
{
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: store
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
     */
    public function store(FileInfo $fileInfo, string $parserType): array;
    
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: delete
     * Delete file from disk
     * 
     * @param string $filePath Full path to file
     * @return bool Success
     */
    public function delete(string $filePath): bool;
    
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: exists
     * Check if file exists on disk
     * 
     * @param string $filePath Full path to file
     * @return bool True if exists
     */
    public function exists(string $filePath): bool;
    
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getContents
     * Get file contents
     * 
     * @param string $filePath Full path to file
     * @return string File contents
     * 
     * @throws \RuntimeException If file doesn't exist or can't be read
     */
    public function getContents(string $filePath): string;
    
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: getStorageDirectory
     * Get storage directory path
     * 
     * @return string Full path to upload storage directory
     */
    public function getStorageDirectory(): string;
    
    /**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: ensureStorageDirectoryExists
     * Ensure storage directory exists with proper permissions
     * 
     * @return void
     * @throws \RuntimeException If directory can't be created
     */
    public function ensureStorageDirectoryExists(): void;
}
