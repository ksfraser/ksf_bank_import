<?php

namespace Ksfraser\FaBankImport\ValueObject;

/**
 * Upload Result Value Object
 * 
 * Immutable result from file upload operation
 * Uses named constructors (factory methods) for different outcomes
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class UploadResult
{
    /** @var bool Upload successful */
    private bool $success;
    
    /** @var string Result message */
    private string $message;
    
    /** @var int|null File ID if successful */
    private ?int $fileId;
    
    /** @var string|null Unique filename if successful */
    private ?string $filename;
    
    /** @var string Result type: success, error, duplicate, reused */
    private string $type;
    
    /** @var bool Allow force override for duplicates */
    private bool $allowForce;
    
    /**
     * Private constructor - use static factory methods
     * 
     * @param bool $success
     * @param string $message
     * @param string $type
     * @param int|null $fileId
     * @param string|null $filename
     * @param bool $allowForce
     */
    private function __construct(
        bool $success,
        string $message,
        string $type,
        ?int $fileId = null,
        ?string $filename = null,
        bool $allowForce = false
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->type = $type;
        $this->fileId = $fileId;
        $this->filename = $filename;
        $this->allowForce = $allowForce;
    }
    
    /**
     * Create success result
     * 
     * @param int $fileId File ID
     * @param string $filename Unique filename
     * @param string $message Success message
     * @return self
     */
    public static function success(int $fileId, string $filename, string $message): self
    {
        return new self(true, $message, 'success', $fileId, $filename);
    }
    
    /**
     * Create error result
     * 
     * @param string $message Error message
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(false, $message, 'error');
    }
    
    /**
     * Create duplicate result (warn or block)
     * 
     * @param string $message Duplicate message
     * @param bool $allowForce Allow force override
     * @return self
     */
    public static function duplicate(string $message, bool $allowForce = false): self
    {
        return new self(false, $message, 'duplicate', null, null, $allowForce);
    }
    
    /**
     * Create reused result (duplicate allowed, existing file reused)
     * 
     * @param int $fileId Existing file ID
     * @param string $message Message
     * @return self
     */
    public static function reused(int $fileId, string $message): self
    {
        return new self(true, $message, 'reused', $fileId);
    }
    
    /**
     * Check if upload was successful
     * 
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * Get result message
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Get file ID (if successful)
     * 
     * @return int|null
     */
    public function getFileId(): ?int
    {
        return $this->fileId;
    }
    
    /**
     * Get unique filename (if successful)
     * 
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }
    
    /**
     * Get result type
     * 
     * @return string success, error, duplicate, or reused
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Check if this is a duplicate result
     * 
     * @return bool
     */
    public function isDuplicate(): bool
    {
        return $this->type === 'duplicate';
    }
    
    /**
     * Check if force upload is allowed
     * 
     * @return bool
     */
    public function allowForce(): bool
    {
        return $this->allowForce;
    }
    
    /**
     * Check if file was reused (duplicate with allow strategy)
     * 
     * @return bool
     */
    public function isReused(): bool
    {
        return $this->type === 'reused';
    }
    
    /**
     * Convert to array for JSON/API responses
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'type' => $this->type,
            'fileId' => $this->fileId,
            'filename' => $this->filename,
            'allowForce' => $this->allowForce
        ];
    }
}
