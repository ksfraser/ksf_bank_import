<?php

namespace Ksfraser\FaBankImport\ValueObject;

use Ksfraser\FaBankImport\Entity\UploadedFile;

/**
 * Duplicate Detection Result Value Object
 * 
 * Immutable result from duplicate detection
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateResult
{
    private  $isDuplicate;
    //private bool $isDuplicate;
    private  $existingFile;
    //private ?UploadedFile $existingFile;
    private  $action; // 'allow', 'warn', 'block'
    //private string $action; // 'allow', 'warn', 'block'
    
    /**
     * Constructor
     * 
     * @param bool $isDuplicate
     * @param UploadedFile|null $existingFile
     * @param string $action
     */
    private function __construct(bool $isDuplicate, ?UploadedFile $existingFile, string $action)
    {
        $this->isDuplicate = $isDuplicate;
        $this->existingFile = $existingFile;
        $this->action = $action;
    }
    
    /**
     * Create result for no duplicate found
     * 
     * @return self
     */
    public static function notDuplicate(): self
    {
        return new self(false, null, 'none');
    }
    
    /**
     * Create result for duplicate with allow action
     * 
     * @param UploadedFile $existingFile
     * @return self
     */
    public static function allowDuplicate(UploadedFile $existingFile): self
    {
        return new self(true, $existingFile, 'allow');
    }
    
    /**
     * Create result for duplicate with warn action
     * 
     * @param UploadedFile $existingFile
     * @return self
     */
    public static function warnDuplicate(UploadedFile $existingFile): self
    {
        return new self(true, $existingFile, 'warn');
    }
    
    /**
     * Create result for duplicate with block action
     * 
     * @param UploadedFile $existingFile
     * @return self
     */
    public static function blockDuplicate(UploadedFile $existingFile): self
    {
        return new self(true, $existingFile, 'block');
    }
    
    /**
     * Check if duplicate was found
     * 
     * @return bool
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }
    
    /**
     * Get existing file (if duplicate)
     * 
     * @return UploadedFile|null
     */
    public function getExistingFile(): ?UploadedFile
    {
        return $this->existingFile;
    }
    
    /**
     * Get action to take
     * 
     * @return string 'allow', 'warn', or 'block'
     */
    public function getAction(): string
    {
        return $this->action;
    }
    
    /**
     * Check if should block upload
     * 
     * @return bool
     */
    public function shouldBlock(): bool
    {
        return $this->isDuplicate && $this->action === 'block';
    }
    
    /**
     * Check if should warn user
     * 
     * @return bool
     */
    public function shouldWarn(): bool
    {
        return $this->isDuplicate && $this->action === 'warn';
    }
    
    /**
     * Check if should silently allow (reuse existing or not duplicate)
     * 
     * @return bool
     */
    public function shouldAllow(): bool
    {
        return $this->action === 'allow' || $this->action === 'none';
    }
}
