<?php

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Entity\UploadedFile;
use Ksfraser\FaBankImport\ValueObject\FileInfo;

/**
 * Database Implementation of Uploaded File Repository
 * 
 * Handles ONLY file metadata (not file content)
 * Files are stored on disk in company directory
 * Follows Repository Pattern with SRP
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DatabaseUploadedFileRepository implements UploadedFileRepositoryInterface
{
    private const TABLE_FILES = 'bi_uploaded_files';
    private const TABLE_LINKS = 'bi_file_statements';
    
    /**
     * Ensure database tables exist (auto-migration on first use)
     * 
     * @return void
     */
    private function ensureTablesExist(): void
    {
        // Check if tables exist
        $check = "SHOW TABLES LIKE '" . TB_PREF . self::TABLE_FILES . "'";
        $result = db_query($check);
        
        if (db_num_rows($result) === 0) {
            // Tables don't exist, create them
            $this->createTables();
        }
    }
    
    /**
     * Create database tables for file metadata
     * 
     * @return void
     */
    private function createTables(): void
    {
        // Create bi_uploaded_files table
        $sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . self::TABLE_FILES . "` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `filename` varchar(255) NOT NULL COMMENT 'Stored filename (unique)',
          `original_filename` varchar(255) NOT NULL COMMENT 'Original upload filename',
          `file_path` varchar(500) NOT NULL COMMENT 'Full path on disk',
          `file_size` bigint(20) NOT NULL COMMENT 'File size in bytes',
          `file_type` varchar(100) NOT NULL COMMENT 'MIME type',
          `upload_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `upload_user` varchar(60) NOT NULL,
          `parser_type` varchar(50) NOT NULL COMMENT 'qfx, mt940, csv, etc.',
          `bank_account_id` int(11) DEFAULT NULL,
          `statement_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of linked statements',
          `notes` text,
          PRIMARY KEY (`id`),
          KEY `upload_date` (`upload_date`),
          KEY `upload_user` (`upload_user`),
          KEY `parser_type` (`parser_type`),
          KEY `bank_account_id` (`bank_account_id`),
          KEY `original_filename` (`original_filename`, `file_size`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='File metadata only - actual files stored in company directory'";
        
        db_query($sql, "Failed to create bi_uploaded_files table");
        
        // Create bi_file_statements link table
        $sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . self::TABLE_LINKS . "` (
          `file_id` int(11) NOT NULL,
          `statement_id` int(11) NOT NULL,
          PRIMARY KEY (`file_id`, `statement_id`),
          KEY `statement_id` (`statement_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Links uploaded files to imported statements (many-to-many)'";
        
        db_query($sql, "Failed to create bi_file_statements table");
    }
    
    /**
     * Save file metadata to database
     * 
     * NOTE: Does NOT save file content - only metadata
     * File must already be saved to disk
     * 
     * @param UploadedFile $file File entity with metadata
     * @return int File ID
     * 
     * @throws \RuntimeException If save fails
     */
    public function save(UploadedFile $file): int
    {
        $this->ensureTablesExist();
        
        $sql = "INSERT INTO " . TB_PREF . self::TABLE_FILES . " 
                (filename, original_filename, file_path, file_size, file_type, 
                 upload_date, upload_user, parser_type, bank_account_id, notes)
                VALUES (
                    " . db_escape($file->getFilename()) . ",
                    " . db_escape($file->getOriginalFilename()) . ",
                    " . db_escape($file->getFilePath()) . ",
                    " . db_escape($file->getFileSize()) . ",
                    " . db_escape($file->getFileType()) . ",
                    " . db_escape($file->getUploadDate()->format('Y-m-d H:i:s')) . ",
                    " . db_escape($file->getUploadUser()) . ",
                    " . db_escape($file->getParserType()) . ",
                    " . db_escape($file->getBankAccountId()) . ",
                    " . db_escape($file->getNotes()) . "
                )";
        
        if (!db_query($sql, "Failed to save file metadata")) {
            throw new \RuntimeException("Failed to save file metadata to database");
        }
        
        $fileId = db_insert_id();
        $file->setId($fileId);
        
        return $fileId;
    }
    
    /**
     * Find file metadata by ID
     * 
     * @param int $id File ID
     * @return UploadedFile|null File entity or null if not found
     */
    public function findById(int $id): ?UploadedFile
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT * FROM " . TB_PREF . self::TABLE_FILES . "
                WHERE id = " . db_escape($id);
        
        $result = db_query($sql, "Failed to find file by ID");
        
        if ($row = db_fetch($result)) {
            return $this->hydrateEntity($row);
        }
        
        return null;
    }
    
    /**
     * Find potential duplicate file
     * 
     * Checks: original filename, file size, and optionally MD5 hash
     * Only checks within specified time window
     * 
     * @param FileInfo $fileInfo File information
     * @param int $windowDays How many days back to check
     * @return UploadedFile|null Duplicate file or null
     */
    public function findDuplicate(FileInfo $fileInfo, int $windowDays): ?UploadedFile
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT * FROM " . TB_PREF . self::TABLE_FILES . "
                WHERE original_filename = " . db_escape($fileInfo->getOriginalFilename()) . "
                AND file_size = " . db_escape($fileInfo->getSize()) . "
                AND upload_date >= DATE_SUB(NOW(), INTERVAL " . (int)$windowDays . " DAY)
                ORDER BY upload_date DESC
                LIMIT 1";
        
        $result = db_query($sql, "Failed to check for duplicates");
        
        if ($row = db_fetch($result)) {
            return $this->hydrateEntity($row);
        }
        
        return null;
    }
    
    /**
     * Link file to statements (many-to-many relationship)
     * 
     * @param int $fileId File ID
     * @param array $statementIds Array of statement IDs
     * @return bool Success
     */
    public function linkToStatements(int $fileId, array $statementIds): bool
    {
        $this->ensureTablesExist();
        
        if (empty($statementIds)) {
            return true;
        }
        
        $success = true;
        
        foreach ($statementIds as $statementId) {
            $sql = "INSERT IGNORE INTO " . TB_PREF . self::TABLE_LINKS . " 
                    (file_id, statement_id)
                    VALUES (" . db_escape($fileId) . ", " . db_escape($statementId) . ")";
            
            if (!db_query($sql, "Failed to link file to statement")) {
                $success = false;
            }
        }
        
        // Update statement count
        if ($success) {
            $this->updateStatementCount($fileId);
        }
        
        return $success;
    }
    
    /**
     * Get statements linked to a file
     * 
     * @param int $fileId File ID
     * @return array Array of statement data
     */
    public function getLinkedStatements(int $fileId): array
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT s.* 
                FROM " . TB_PREF . "bi_statements s
                JOIN " . TB_PREF . self::TABLE_LINKS . " fs ON s.id = fs.statement_id
                WHERE fs.file_id = " . db_escape($fileId) . "
                ORDER BY s.smtDate DESC";
        
        $result = db_query($sql, "Failed to get linked statements");
        
        $statements = [];
        while ($row = db_fetch($result)) {
            $statements[] = $row;
        }
        
        return $statements;
    }
    
    /**
     * Update statement count for a file
     * 
     * @param int $fileId File ID
     * @return bool Success
     */
    public function updateStatementCount(int $fileId): bool
    {
        $this->ensureTablesExist();
        
        $sql = "UPDATE " . TB_PREF . self::TABLE_FILES . " 
                SET statement_count = (
                    SELECT COUNT(*) 
                    FROM " . TB_PREF . self::TABLE_LINKS . " 
                    WHERE file_id = " . db_escape($fileId) . "
                )
                WHERE id = " . db_escape($fileId);
        
        return db_query($sql, "Failed to update statement count");
    }
    
    /**
     * Delete file metadata (CASCADE deletes links)
     * 
     * NOTE: Does NOT delete physical file - caller must handle that
     * 
     * @param int $fileId File ID
     * @return bool Success
     */
    public function delete(int $fileId): bool
    {
        $this->ensureTablesExist();
        
        // Delete links first
        $sql = "DELETE FROM " . TB_PREF . self::TABLE_LINKS . "
                WHERE file_id = " . db_escape($fileId);
        db_query($sql, "Failed to delete file links");
        
        // Delete file metadata
        $sql = "DELETE FROM " . TB_PREF . self::TABLE_FILES . "
                WHERE id = " . db_escape($fileId);
        
        return db_query($sql, "Failed to delete file metadata");
    }
    
    /**
     * Get all files with optional filters
     * 
     * @param array $filters Optional filters (user, date_from, date_to, parser_type)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of UploadedFile entities
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT * FROM " . TB_PREF . self::TABLE_FILES . " WHERE 1=1";
        
        // Apply filters
        if (!empty($filters['user'])) {
            $sql .= " AND upload_user = " . db_escape($filters['user']);
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND upload_date >= " . db_escape($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND upload_date <= " . db_escape($filters['date_to']);
        }
        
        if (!empty($filters['parser_type'])) {
            $sql .= " AND parser_type = " . db_escape($filters['parser_type']);
        }
        
        $sql .= " ORDER BY upload_date DESC
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $result = db_query($sql, "Failed to retrieve uploaded files");
        
        $files = [];
        while ($row = db_fetch($result)) {
            $files[] = $this->hydrateEntity($row);
        }
        
        return $files;
    }
    
    /**
     * Get total file count with optional filters
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function count(array $filters = []): int
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . self::TABLE_FILES . " WHERE 1=1";
        
        // Apply same filters as findAll
        if (!empty($filters['user'])) {
            $sql .= " AND upload_user = " . db_escape($filters['user']);
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND upload_date >= " . db_escape($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND upload_date <= " . db_escape($filters['date_to']);
        }
        
        if (!empty($filters['parser_type'])) {
            $sql .= " AND parser_type = " . db_escape($filters['parser_type']);
        }
        
        $result = db_query($sql, "Failed to count files");
        $row = db_fetch($result);
        
        return (int)$row['cnt'];
    }
    
    /**
     * Get storage statistics
     * 
     * @return array Statistics (total_files, total_size, etc.)
     */
    public function getStatistics(): array
    {
        $this->ensureTablesExist();
        
        $sql = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    MAX(upload_date) as latest_upload,
                    MIN(upload_date) as first_upload
                FROM " . TB_PREF . self::TABLE_FILES;
        
        $result = db_query($sql, "Failed to get storage statistics");
        $stats = db_fetch($result);
        
        return [
            'total_files' => (int)$stats['total_files'],
            'total_size' => (int)($stats['total_size'] ?? 0),
            'latest_upload' => $stats['latest_upload'],
            'first_upload' => $stats['first_upload']
        ];
    }
    
    /**
     * Hydrate database row into UploadedFile entity
     * 
     * @param array $row Database row
     * @return UploadedFile
     */
    private function hydrateEntity(array $row): UploadedFile
    {
        return new UploadedFile(
            (int)$row['id'],
            $row['filename'],
            $row['original_filename'],
            $row['file_path'],
            (int)$row['file_size'],
            $row['file_type'],
            new \DateTime($row['upload_date']),
            $row['upload_user'],
            $row['parser_type'],
            $row['bank_account_id'] ? (int)$row['bank_account_id'] : null,
            (int)$row['statement_count'],
            $row['notes']
        );
    }
}
