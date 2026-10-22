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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function blockDuplicate(UploadedFile $existingFile): self
    {
        return new self(true, $existingFile, 'block');
    }
    
    /**
     * Check if duplicate was found
     * 
     * @return bool
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }
    
    /**
     * Get existing file (if duplicate)
     * 
     * @return UploadedFile|null
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getExistingFile(): ?UploadedFile
    {
        return $this->existingFile;
    }
    
    /**
     * Get action to take
     * 
     * @return string 'allow', 'warn', or 'block'
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getAction(): string
    {
        return $this->action;
    }
    
    /**
     * Check if should block upload
     * 
     * @return bool
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function shouldBlock(): bool
    {
        return $this->isDuplicate && $this->action === 'block';
    }
    
    /**
     * Check if should warn user
     * 
     * @return bool
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function shouldWarn(): bool
    {
        return $this->isDuplicate && $this->action === 'warn';
    }
    
    /**
     * Check if should silently allow (reuse existing or not duplicate)
     * 
     * @return bool
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function shouldAllow(): bool
    {
        return $this->action === 'allow' || $this->action === 'none';
    }
}
