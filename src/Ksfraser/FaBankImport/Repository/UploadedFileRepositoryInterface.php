<?php

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * @BABOK RTM: FR-008 / ARCH-001; file: Repository/UploadedFileRepositoryInterface.php; coverage: docs/TestCases/ (file rollup)
 * Uploaded File Repository Interface
 * 
 * Defines contract for file metadata storage
 * Follows Repository Pattern
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
interface UploadedFileRepositoryInterface
{
    /**
 * @BABOK FR-008 / ARCH-001 — method: save
     * Save file metadata to database
     * 
     * @param UploadedFile $file File entity
     * @return int File ID
     * 
     * @throws \RuntimeException If save fails
     */
    public function save(UploadedFile $file): int;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: findById
     * Find file by ID
     * 
     * @param int $id File ID
     * @return UploadedFile|null File entity or null if not found
     */
    public function findById(int $id): ?UploadedFile;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: findDuplicate
     * Find potential duplicate files
     * 
     * @param FileInfo $fileInfo File information
     * @param int $windowDays How many days back to check
     * @return UploadedFile|null Duplicate file or null
     */
    public function findDuplicate(FileInfo $fileInfo, int $windowDays): ?UploadedFile;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: linkToStatements
     * Link file to statements
     * 
     * @param int $fileId File ID
     * @param array $statementIds Array of statement IDs
     * @return bool Success
     */
    public function linkToStatements(int $fileId, array $statementIds): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: getLinkedStatements
     * Get statements linked to a file
     * 
     * @param int $fileId File ID
     * @return array Array of statement data
     */
    public function getLinkedStatements(int $fileId): array;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: updateStatementCount
     * Update statement count for a file
     * 
     * @param int $fileId File ID
     * @return bool Success
     */
    public function updateStatementCount(int $fileId): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: delete
     * Delete file metadata
     * 
     * @param int $fileId File ID
     * @return bool Success
     */
    public function delete(int $fileId): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: findAll
     * Get all files with optional filters
     * 
     * @param array $filters Optional filters (user, date_from, date_to, parser_type)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of UploadedFile entities
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: count
     * Get total file count
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function count(array $filters = []): int;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: getStatistics
     * Get storage statistics
     * 
     * @return array Statistics (total_files, total_size, etc.)
     */
    public function getStatistics(): array;
}
