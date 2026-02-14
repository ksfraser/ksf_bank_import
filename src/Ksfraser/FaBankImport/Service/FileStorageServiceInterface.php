<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :FileStorageServiceInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for FileStorageServiceInterface.
 */
namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
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
     * Delete file from disk
     * 
     * @param string $filePath Full path to file
     * @return bool Success
     */
    public function delete(string $filePath): bool;
    
    /**
     * Check if file exists on disk
     * 
     * @param string $filePath Full path to file
     * @return bool True if exists
     */
    public function exists(string $filePath): bool;
    
    /**
     * Get file contents
     * 
     * @param string $filePath Full path to file
     * @return string File contents
     * 
     * @throws \RuntimeException If file doesn't exist or can't be read
     */
    public function getContents(string $filePath): string;
    
    /**
     * Get storage directory path
     * 
     * @return string Full path to upload storage directory
     */
    public function getStorageDirectory(): string;
    
    /**
     * Ensure storage directory exists with proper permissions
     * 
     * @return void
     * @throws \RuntimeException If directory can't be created
     */
    public function ensureStorageDirectoryExists(): void;
}
