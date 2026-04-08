<?php

namespace Ksfraser\FaBankImport\Service;

use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * File Storage Service
 * 
 * Handles physical file storage operations on disk
 * Follows Single Responsibility Principle - ONLY file I/O, no database
 * 
 * Files stored in: company/bank_imports/uploads/
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class FileStorageService implements FileStorageServiceInterface
{
    /** @var string Base storage directory */
    private string $storageDir;
    
    /** @var int File permissions (0640 = rw-r-----) */
    private const FILE_PERMISSIONS = 0640;
    
    /** @var int Directory permissions (0750 = rwxr-x---) */
    private const DIR_PERMISSIONS = 0750;
    
    /**
     * Constructor
     * 
     * @param string|null $storageDir Optional custom storage directory
     */
    public function __construct(?string $storageDir = null)
    {
        if ($storageDir !== null) {
            $this->storageDir = rtrim($storageDir, '/\\');
        } else {
            // Default: company/bank_imports/uploads
            $this->storageDir = $this->getDefaultStorageDirectory();
        }
    }
    
    /**
     * Store uploaded file to disk
     * 
     * @param FileInfo $fileInfo File information from upload
     * @param string $parserType Parser type (qfx, mt940, csv, etc.)
     * @return array ['filename' => unique name, 'path' => full path]
     * 
     * @throws \RuntimeException If storage fails
     */
    public function store(FileInfo $fileInfo, string $parserType): array
    {
        // Ensure storage directory exists
        $this->ensureStorageDirectoryExists();
        
        // Generate unique filename
        $uniqueFilename = $this->generateUniqueFilename(
            $fileInfo->getBasename(),
            $fileInfo->getExtension(),
            $parserType
        );
        
        // Full path
        $destinationPath = $this->storageDir . DIRECTORY_SEPARATOR . $uniqueFilename;
        
        // Move file from temp to permanent storage
        // Use move_uploaded_file if it's a real upload, otherwise use rename for testing
        $moved = is_uploaded_file($fileInfo->getTmpPath())
            ? move_uploaded_file($fileInfo->getTmpPath(), $destinationPath)
            : rename($fileInfo->getTmpPath(), $destinationPath);
            
        if (!$moved) {
            throw new \RuntimeException(
                "Failed to move uploaded file to: {$destinationPath}"
            );
        }
        
        // Set secure permissions
        chmod($destinationPath, self::FILE_PERMISSIONS);
        
        return [
            'filename' => $uniqueFilename,
            'path' => $destinationPath
        ];
    }
    
    /**
     * Delete file from disk
     * 
     * @param string $filePath Full path to file
     * @return bool Success
     */
    public function delete(string $filePath): bool
    {
        if (!$this->exists($filePath)) {
            return false;
        }
        
        return @unlink($filePath);
    }
    
    /**
     * Check if file exists on disk
     * 
     * @param string $filePath Full path to file
     * @return bool True if exists
     */
    public function exists(string $filePath): bool
    {
        return file_exists($filePath) && is_file($filePath);
    }
    
    /**
     * Get file contents
     * 
     * @param string $filePath Full path to file
     * @return string File contents
     * 
     * @throws \RuntimeException If file doesn't exist or can't be read
     */
    public function getContents(string $filePath): string
    {
        if (!$this->exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }
        
        $contents = @file_get_contents($filePath);
        
        if ($contents === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }
        
        return $contents;
    }
    
    /**
     * Get storage directory path
     * 
     * @return string Full path to upload storage directory
     */
    public function getStorageDirectory(): string
    {
        return $this->storageDir;
    }
    
    /**
     * Ensure storage directory exists with proper permissions
     * 
     * @return void
     * @throws \RuntimeException If directory can't be created
     */
    public function ensureStorageDirectoryExists(): void
    {
        if (is_dir($this->storageDir)) {
            return;
        }
        
        // Create directory recursively
        if (!@mkdir($this->storageDir, self::DIR_PERMISSIONS, true)) {
            throw new \RuntimeException(
                "Failed to create storage directory: {$this->storageDir}"
            );
        }
        
        // Create .htaccess to protect directory
        $this->createHtaccessFile();
    }
    
    /**
     * Generate unique filename to avoid collisions
     * 
     * Format: PARSER_YYYYMMDD_HHMMSS_RANDOM.ext
     * Example: QFX_20250119_143022_a8f3e9.qfx
     * 
     * @param string $basename Original filename without extension
     * @param string $extension File extension
     * @param string $parserType Parser type (qfx, mt940, csv)
     * @return string Unique filename
     */
    private function generateUniqueFilename(
        string $basename,
        string $extension,
        string $parserType
    ): string {
        // Sanitize basename (remove special chars, max 50 chars)
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = substr($basename, 0, 50);
        
        // Date/time stamp
        $timestamp = date('Ymd_His');
        
        // Random suffix (6 chars)
        $random = substr(md5(uniqid('', true)), 0, 6);
        
        // Parser type uppercase
        $parser = strtoupper($parserType);
        
        // Combine: PARSER_BASENAME_TIMESTAMP_RANDOM.ext
        return "{$parser}_{$basename}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Get default storage directory
     * 
     * @return string Full path to company/bank_imports/uploads
     */
    private function getDefaultStorageDirectory(): string
    {
        // Get FrontAccounting company directory
        // Typically: /path/to/frontaccounting/company/X/
        $companyPath = company_path();
        
        return $companyPath . 'bank_imports' . DIRECTORY_SEPARATOR . 'uploads';
    }
    
    /**
     * Create .htaccess file to protect upload directory
     * 
     * Prevents direct web access to uploaded files
     * Files must be served through PHP with permission checks
     * 
     * @return void
     */
    private function createHtaccessFile(): void
    {
        $htaccessPath = $this->storageDir . DIRECTORY_SEPARATOR . '.htaccess';
        
        if (file_exists($htaccessPath)) {
            return; // Already exists
        }
        
        $htaccessContent = <<<HTACCESS
# Protect uploaded bank statement files
# Files must be accessed through manage_uploaded_files.php with permission checks

# Deny all direct access
Order Deny,Allow
Deny from all

# Alternative for Apache 2.4+
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
HTACCESS;
        
        @file_put_contents($htaccessPath, $htaccessContent);
        @chmod($htaccessPath, self::FILE_PERMISSIONS);
    }
    
    /**
     * Get file size in bytes
     * 
     * @param string $filePath Full path to file
     * @return int File size in bytes
     * 
     * @throws \RuntimeException If file doesn't exist
     */
    public function getFileSize(string $filePath): int
    {
        if (!$this->exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }
        
        $size = @filesize($filePath);
        
        if ($size === false) {
            throw new \RuntimeException("Failed to get file size: {$filePath}");
        }
        
        return $size;
    }
    
    /**
     * Get file modification time
     * 
     * @param string $filePath Full path to file
     * @return int Unix timestamp
     * 
     * @throws \RuntimeException If file doesn't exist
     */
    public function getModificationTime(string $filePath): int
    {
        if (!$this->exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }
        
        $mtime = @filemtime($filePath);
        
        if ($mtime === false) {
            throw new \RuntimeException("Failed to get file mtime: {$filePath}");
        }
        
        return $mtime;
    }
    
    /**
     * Copy file to new location
     * 
     * Useful for creating backups or archives
     * 
     * @param string $sourcePath Source file path
     * @param string $destinationPath Destination file path
     * @return bool Success
     */
    public function copy(string $sourcePath, string $destinationPath): bool
    {
        if (!$this->exists($sourcePath)) {
            return false;
        }
        
        // Ensure destination directory exists
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            @mkdir($destDir, self::DIR_PERMISSIONS, true);
        }
        
        if (!@copy($sourcePath, $destinationPath)) {
            return false;
        }
        
        @chmod($destinationPath, self::FILE_PERMISSIONS);
        
        return true;
    }
}
