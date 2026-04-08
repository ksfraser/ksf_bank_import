<?php

namespace Ksfraser\FaBankImport\Service;

/**
 * FileStorageService - Manages file storage for imported statements
 * 
 * Handles file operations: store, delete, retrieve, copy, with automatic
 * directory creation and security headers (.htaccess protection).
 */
class FileStorageService
{
    private string $storageDirectory;

    /**
     * Constructor
     * 
     * @param string $storageDirectory Directory path for storing files
     */
    public function __construct(string $storageDirectory)
    {
        $this->storageDirectory = rtrim($storageDirectory, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the storage directory path
     */
    public function getStorageDirectory(): string
    {
        return rtrim($this->storageDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Ensure storage directory exists with security headers
     */
    public function ensureStorageDirectoryExists(): bool
    {
        if (!is_dir($this->storageDirectory)) {
            if (!mkdir($this->storageDirectory, 0750, true)) {
                return false;
            }
        }

        // Create .htaccess file for web security
        $htaccessPath = $this->storageDirectory . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }

        return true;
    }

    /**
     * Store a file with unique naming
     * 
     * @param object $fileInfo FileInfo value object with originalFilename, tmpPath, size, mimeType
     * @param string $prefix Filename prefix (e.g., 'QFX', 'CSV')
     * @return array ['filename' => string, 'path' => string]
     */
    public function store(object $fileInfo, string $prefix): array
    {
        $this->ensureStorageDirectoryExists();

        // Generate unique filename: PREFIX_BASENAME_TIMESTAMP_RANDOM.ext
        $originalName = $fileInfo->getOriginalFilename();
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: $prefix;
        $timestamp = time();
        $random = substr(uniqid(), -6);
        
        $filename = sprintf(
            '%s_%s_%d_%s.%s',
            strtoupper($prefix),
            substr(md5($basename), 0, 8),
            $timestamp,
            $random,
            ltrim($ext, '.')
        );

        $filepath = $this->storageDirectory . $filename;

        // Copy uploaded file to storage
        copy($fileInfo->getTmpPath(), $filepath);

        return [
            'filename' => $filename,
            'path' => $filepath,
        ];
    }

    /**
     * Check if file exists (files only, not directories)
     */
    public function exists(string $filepath): bool
    {
        return is_file($filepath);
    }

    /**
     * Delete a file
     * 
     * @return bool True if deleted, false if not found or not a file
     */
    public function delete(string $filepath): bool
    {
        if (!is_file($filepath)) {
            return false;
        }
        return @unlink($filepath);
    }

    /**
     * Get file contents
     * 
     * @throws \RuntimeException If file does not exist
     */
    public function getContents(string $filepath): string
    {
        if (!is_file($filepath)) {
            throw new \RuntimeException("File not found: {$filepath}");
        }
        return file_get_contents($filepath);
    }

    /**
     * Get file size in bytes
     * 
     * @throws \RuntimeException If file does not exist
     */
    public function getFileSize(string $filepath): int
    {
        if (!is_file($filepath)) {
            throw new \RuntimeException("File not found: {$filepath}");
        }
        return (int)filesize($filepath);
    }

    /**
     * Get file modification time as unix timestamp
     */
    public function getModificationTime(string $filepath): ?int
    {
        if (!is_file($filepath)) {
            return null;
        }
        return (int)filemtime($filepath);
    }

    /**
     * Copy a file to new location
     * 
     * @return bool True if copied, false if source not found
     */
    public function copy(string $sourcePath, string $destinationPath): bool
    {
        if (!is_file($sourcePath)) {
            return false;
        }
        return (bool)@copy($sourcePath, $destinationPath);
    }
}
