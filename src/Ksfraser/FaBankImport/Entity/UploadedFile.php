<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Entity;

/**
 * @BABOK CLASS ROLLUP: UploadedFile — 17 methods; FR-008 (17); DR-001 (17)
 * @BABOK RTM: FR-008 / DR-001 / ARCH-010; file: Entity/UploadedFile.php; coverage: docs/TestCases/ (file rollup)
 * Uploaded File Entity
 * 
 * Represents a file stored in the system
 * Domain entity with identity (ID)
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class UploadedFile
{
    private ?int $id;
    private string $filename;
    private string $originalFilename;
    private string $filePath;
    private int $fileSize;
    private string $fileType;
    private \DateTime $uploadDate;
    private string $uploadUser;
    private string $parserType;
    private ?int $bankAccountId;
    private int $statementCount;
    private ?string $notes;
    
    /**
 * @BABOK FR-008 / DR-001 / ARCH-010 — method: __construct
     * Constructor
     * 
     * @param int|null $id File ID (null for new files)
     * @param string $filename Stored filename
     * @param string $originalFilename Original filename
     * @param string $filePath Full file path
     * @param int $fileSize File size in bytes
     * @param string $fileType MIME type
     * @param \DateTime $uploadDate Upload timestamp
     * @param string $uploadUser Username
     * @param string $parserType Parser type (qfx, mt940, etc.)
     * @param int|null $bankAccountId Bank account ID
     * @param int $statementCount Number of linked statements
     * @param string|null $notes Optional notes
     */
    public function __construct(
        ?int $id,
        string $filename,
        string $originalFilename,
        string $filePath,
        int $fileSize,
        string $fileType,
        \DateTime $uploadDate,
        string $uploadUser,
        string $parserType,
        ?int $bankAccountId = null,
        int $statementCount = 0,
        ?string $notes = null
    ) {
        $this->id = $id;
        $this->filename = $filename;
        $this->originalFilename = $originalFilename;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->fileType = $fileType;
        $this->uploadDate = $uploadDate;
        $this->uploadUser = $uploadUser;
        $this->parserType = $parserType;
        $this->bankAccountId = $bankAccountId;
        $this->statementCount = $statementCount;
        $this->notes = $notes;
    }
    
    // Getters
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getId */
    public function getId(): ?int { return $this->id; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getFilename */
    public function getFilename(): string { return $this->filename; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getOriginalFilename */
    public function getOriginalFilename(): string { return $this->originalFilename; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getFilePath */
    public function getFilePath(): string { return $this->filePath; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getFileSize */
    public function getFileSize(): int { return $this->fileSize; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getFileType */
    public function getFileType(): string { return $this->fileType; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getUploadDate */
    public function getUploadDate(): \DateTime { return $this->uploadDate; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getUploadUser */
    public function getUploadUser(): string { return $this->uploadUser; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getParserType */
    public function getParserType(): string { return $this->parserType; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getBankAccountId */
    public function getBankAccountId(): ?int { return $this->bankAccountId; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getStatementCount */
    public function getStatementCount(): int { return $this->statementCount; }
    /** @BABOK FR-008 / DR-001 / ARCH-010 — method: getNotes */
    public function getNotes(): ?string { return $this->notes; }
    
    /**
 * @BABOK FR-008 / DR-001 / ARCH-010 — method: setId
     * Set ID (after database insert)
     * 
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
 * @BABOK FR-008 / DR-001 / ARCH-010 — method: setStatementCount
     * Update statement count
     * 
     * @param int $count
     */
    public function setStatementCount(int $count): void
    {
        $this->statementCount = $count;
    }
    
    /**
 * @BABOK FR-008 / DR-001 / ARCH-010 — method: exists
     * Check if file exists on disk
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->filePath);
    }
    
    /**
 * @BABOK FR-008 / DR-001 / ARCH-010 — method: getFormattedSize
     * Get file size in human-readable format
     * 
     * @return string
     */
    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        // For bytes, show no decimals. For KB and above, show 2 decimals
        if ($unit === 0) {
            return round($size) . ' ' . $units[$unit];
        } else {
            return number_format($size, 2) . ' ' . $units[$unit];
        }
    }
}
