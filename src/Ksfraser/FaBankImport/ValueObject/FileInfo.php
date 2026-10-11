<?php

namespace Ksfraser\FaBankImport\ValueObject;

/**
 * File Information Value Object
 * 
 * Immutable object representing uploaded file information
 * Follows Value Object pattern from DDD
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class FileInfo
{
    private string $originalFilename;
    private string $tmpPath;
    private int $size;
    private string $mimeType;
    
    /**
     * Constructor
     * 
     * @param string $originalFilename Original filename from upload
     * @param string $tmpPath Temporary file path
     * @param int $size File size in bytes
     * @param string $mimeType MIME type
     * 
     * @throws \InvalidArgumentException If validation fails
     */
    public function __construct(string $originalFilename, string $tmpPath, int $size, string $mimeType)
    {
        $this->validateFilename($originalFilename);
        $this->validateSize($size);
        
        $this->originalFilename = $originalFilename;
        $this->tmpPath = $tmpPath;
        $this->size = $size;
        $this->mimeType = $mimeType;
    }
    
    /**
     * Create from $_FILES array entry
     * 
     * @param array $fileData $_FILES array entry
     * @return self
     * 
     * @throws \InvalidArgumentException If file data invalid
     * @throws \RuntimeException If file upload failed
     * 
     * @example
     * ```php
     * $fileInfo = FileInfo::fromUpload($_FILES['file']);
     * ```
     */
    public static function fromUpload(array $fileData): self
    {
        if (!isset($fileData['name'], $fileData['tmp_name'], $fileData['size'], $fileData['type'])) {
            throw new \InvalidArgumentException('Invalid file upload data');
        }
        
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::getUploadErrorMessage($fileData['error']));
        }
        
        return new self(
            $fileData['name'],
            $fileData['tmp_name'],
            $fileData['size'],
            $fileData['type']
        );
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds maximum size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error: ' . $errorCode;
        }
    }
    
    /**
     * Get original filename
     * 
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }
    
    /**
     * Get temporary file path
     * 
     * @return string
     */
    public function getTmpPath(): string
    {
        return $this->tmpPath;
    }
    
    /**
     * Get file size in bytes
     * 
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
    
    /**
     * Get MIME type
     * 
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
    
    /**
     * Get file extension
     * 
     * @return string
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalFilename, PATHINFO_EXTENSION));
    }
    
    /**
     * Get base filename without extension
     * 
     * @return string
     */
    public function getBasename(): string
    {
        return pathinfo($this->originalFilename, PATHINFO_FILENAME);
    }
    
    /**
     * Calculate MD5 hash of file
     * 
     * @return string MD5 hash
     * @throws \RuntimeException If file cannot be read
     */
    public function getMd5Hash(): string
    {
        $hash = @md5_file($this->tmpPath);
        if ($hash === false) {
            throw new \RuntimeException('Failed to calculate MD5 hash');
        }
        
        return $hash;
    }
    
    /**
     * Validate filename
     * 
     * @param string $filename
     * @throws \InvalidArgumentException
     */
    private function validateFilename(string $filename): void
    {
        if (empty($filename)) {
            throw new \InvalidArgumentException('Filename cannot be empty');
        }
        
        if (strlen($filename) > 255) {
            throw new \InvalidArgumentException('Filename too long (max 255 characters)');
        }
    }
    
    /**
     * Validate file size
     * 
     * @param int $size
     * @throws \InvalidArgumentException
     */
    private function validateSize(int $size): void
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('File size must be positive');
        }
        
        if ($size > 100 * 1024 * 1024) { // 100MB
            throw new \InvalidArgumentException('File size exceeds maximum (100MB)');
        }
    }
}
