<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\ValueObject;

use Ksfraser\FaBankImport\Entity\UploadedFile;

/**
 * @BABOK RTM: FR-008 / DR-001; file: ValueObject/DuplicateResult.php; coverage: docs/TestCases/ (file rollup)
 * Duplicate Detection Result Value Object
 * 
 * Immutable result from duplicate detection
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateResult
{
    private bool $isDuplicate;
    private ?UploadedFile $existingFile;
    private string $action; // 'allow', 'warn', 'block'
    
    /**
 * @BABOK FR-008 / DR-001 — method: __construct
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
 * @BABOK FR-008 / DR-001 — method: notDuplicate
     * Create result for no duplicate found
     * 
     * @return self
     */
    public static function notDuplicate(): self
    {
        return new self(false, null, 'none');
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: allowDuplicate
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
 * @BABOK FR-008 / DR-001 — method: warnDuplicate
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
 * @BABOK FR-008 / DR-001 — method: blockDuplicate
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
 * @BABOK FR-008 / DR-001 — method: isDuplicate
     * Check if duplicate was found
     * 
     * @return bool
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: getExistingFile
     * Get existing file (if duplicate)
     * 
     * @return UploadedFile|null
     */
    public function getExistingFile(): ?UploadedFile
    {
        return $this->existingFile;
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: getAction
     * Get action to take
     * 
     * @return string 'allow', 'warn', or 'block'
     */
    public function getAction(): string
    {
        return $this->action;
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: shouldBlock
     * Check if should block upload
     * 
     * @return bool
     */
    public function shouldBlock(): bool
    {
        return $this->isDuplicate && $this->action === 'block';
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: shouldWarn
     * Check if should warn user
     * 
     * @return bool
     */
    public function shouldWarn(): bool
    {
        return $this->isDuplicate && $this->action === 'warn';
    }
    
    /**
 * @BABOK FR-008 / DR-001 — method: shouldAllow
     * Check if should silently allow (reuse existing or not duplicate)
     * 
     * @return bool
     */
    public function shouldAllow(): bool
    {
        return $this->action === 'allow' || $this->action === 'none';
    }
}
