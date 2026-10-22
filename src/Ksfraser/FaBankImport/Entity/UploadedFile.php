<?php

namespace Ksfraser\FaBankImport\Entity;

/**
 * Uploaded File Entity
 * 
 * Represents a file stored in the system
 * Domain entity with identity (ID)
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
 */
class UploadedFile
{
    private  $id;
    //private ?int $id;
    private  $filename;
    //private string $filename;
    private  $originalFilename;
    //private string $originalFilename;
    private  $filePath;
    //private string $filePath;
    private  $fileSize;
    //private int $fileSize;
    private  $fileType;
    //private string $fileType;
    private  $uploadDate;
    //private \DateTime $uploadDate;
    private  $uploadUser;
    //private string $uploadUser;
    private  $parserType;
    //private string $parserType;
    private  $bankAccountId;
    //private ?int $bankAccountId;
    private  $statementCount;
    //private int $statementCount;
    private  $notes;
    //private ?string $notes;
    
    /**
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
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
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
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getId(): ?int { return $this->id; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getFilename(): string { return $this->filename; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getOriginalFilename(): string { return $this->originalFilename; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getFilePath(): string { return $this->filePath; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getFileSize(): int { return $this->fileSize; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getFileType(): string { return $this->fileType; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getUploadDate(): \DateTime { return $this->uploadDate; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getUploadUser(): string { return $this->uploadUser; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getParserType(): string { return $this->parserType; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getBankAccountId(): ?int { return $this->bankAccountId; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getStatementCount(): int { return $this->statementCount; }
    /**
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function getNotes(): ?string { return $this->notes; }
    
    /**
     * Set ID (after database insert)
     * 
     * @param int $id
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * Update statement count
     * 
     * @param int $count
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function setStatementCount(int $count): void
    {
        $this->statementCount = $count;
    }
    
    /**
     * Check if file exists on disk
     * 
     * @return bool
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
     */
    public function exists(): bool
    {
        return file_exists($this->filePath);
    }
    
    /**
     * Get file size in human-readable format
     * 
     * @return string
 * @BABOK FR-IMP-003, FR-IMP-009, FR-UPL-001, FR-UPL-003, FR-UPL-004, FR-UPL-005 | BR-IMP-003 | UAT-IMP-006
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
