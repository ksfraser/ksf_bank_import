<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Service\BankImportPathResolver;

/**
 * UploadedFileManager - Manage uploaded bank statement files
 * 
 * Mantis #2708: Store uploaded files for future reference
 * - Saves uploaded files to secure directory
 * - Records upload metadata (user, date, filename)
 * - Links files to imported statements
 * - Provides download functionality
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */
class UploadedFileManager
{
    protected $upload_dir;
    protected $db;
    
    /**
     * Constructor
     * 
     * @param string|null $upload_dir Custom upload directory (optional)
     */
    public function __construct($upload_dir = null)
    {
        global $db, $path_to_root, $comp_path;
        
        $this->db = $db;
        
        // Use company-specific directory like FA attachments
        if ($upload_dir === null) {
            // Store in company/#/bank_imports/uploads
            $this->upload_dir = BankImportPathResolver::forCurrentCompany()->uploadsDir();
        } else {
            $this->upload_dir = $upload_dir;
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0750, true);
        }
        
        // Protect directory with .htaccess
        $this->protectDirectory();
    }
    
    /**
     * Create .htaccess to protect uploaded files
     */
    protected function protectDirectory()
    {
        $htaccess_file = $this->upload_dir . '/.htaccess';
        
        if (!file_exists($htaccess_file)) {
            $content = "# Protect uploaded bank files\n";
            $content .= "Order Deny,Allow\n";
            $content .= "Deny from all\n";
            $content .= "# Files must be downloaded through PHP script\n";
            
            file_put_contents($htaccess_file, $content);
        }
    }
    
    /**
     * Save an uploaded file
     * 
     * @param array $file_info $_FILES array entry
     * @param string $parser_type Parser type used
     * @param int|null $bank_account_id Bank account ID (optional)
     * @param string|null $notes Additional notes (optional)
     * @param bool $force_upload Force upload even if duplicate (bypass duplicate check)
     * @return int|false File ID on success, false on failure, negative ID if duplicate, -999 if blocked
     */
    public function saveUploadedFile($file_info, $parser_type, $bank_account_id = null, $notes = null, $force_upload = false)
    {
        // Validate file was uploaded
        if (!isset($file_info['tmp_name']) || !is_uploaded_file($file_info['tmp_name'])) {
            return false;
        }
        
        $original_filename = basename($file_info['name']);
        $file_size = $file_info['size'];
        $file_type = $file_info['type'];
        
        // Check for duplicates if enabled in config and not forced
        $config = Config::getInstance();
        if (!$force_upload && $config->get('upload.check_duplicates', false)) {
            $duplicate = $this->findDuplicate($original_filename, $file_size, $file_info['tmp_name']);
            if ($duplicate) {
                $action = $config->get('upload.duplicate_action', 'warn');
                
                switch ($action) {
                    case 'block':
                        // Hard deny - return special code -999
                        return -999;
                    
                    case 'warn':
                        // Soft deny - store warning info in session for user prompt
                        if (!isset($_SESSION['duplicate_warnings'])) {
                            $_SESSION['duplicate_warnings'] = [];
                        }
                        $_SESSION['duplicate_warnings'][] = [
                            'filename' => $original_filename,
                            'size' => $file_size,
                            'existing_id' => $duplicate['id'],
                            'upload_date' => $duplicate['upload_date'],
                            'upload_user' => $duplicate['upload_user'],
                            'file_index' => count($_SESSION['duplicate_warnings']),
                            'file_data' => $file_info
                        ];
                        // Return negative ID to indicate duplicate found
                        return -1 * $duplicate['id'];
                    
                    case 'allow':
                    default:
                        // Allow but skip saving - return existing file ID as negative
                        return -1 * $duplicate['id'];
                }
            }
        }
        
        // Generate unique filename to prevent overwriting
        $filename = $this->generateUniqueFilename($original_filename);
        $file_path = $this->upload_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file_info['tmp_name'], $file_path)) {
            return false;
        }
        
        // Set proper permissions
        chmod($file_path, 0640);
        
        // Record in database
        $upload_date = date('Y-m-d H:i:s');
        $upload_user = $_SESSION['wa_current_user']->username;
        
        $sql = "INSERT INTO " . TB_PREF . "bi_uploaded_files 
                (filename, original_filename, file_path, file_size, file_type, 
                 upload_date, upload_user, parser_type, bank_account_id, notes)
                VALUES (
                    " . db_escape($filename) . ",
                    " . db_escape($original_filename) . ",
                    " . db_escape($file_path) . ",
                    " . db_escape($file_size) . ",
                    " . db_escape($file_type) . ",
                    " . db_escape($upload_date) . ",
                    " . db_escape($upload_user) . ",
                    " . db_escape($parser_type) . ",
                    " . db_escape($bank_account_id) . ",
                    " . db_escape($notes) . "
                )";
        
        if (db_query($sql, "Failed to record uploaded file")) {
            return db_insert_id();
        }
        
        return false;
    }
    
    /**
     * Link an uploaded file to statements
     * 
     * @param int $file_id Uploaded file ID
     * @param array $statement_ids Array of statement IDs
     * @return bool Success
     */
    public function linkFileToStatements($file_id, $statement_ids)
    {
        if (empty($statement_ids)) {
            return true;
        }
        
        $success = true;
        
        foreach ($statement_ids as $statement_id) {
            $sql = "INSERT IGNORE INTO " . TB_PREF . "bi_file_statements 
                    (file_id, statement_id)
                    VALUES (" . db_escape($file_id) . ", " . db_escape($statement_id) . ")";
            
            if (!db_query($sql, "Failed to link file to statement")) {
                $success = false;
            }
        }
        
        // Update statement count
        if ($success) {
            $this->updateStatementCount($file_id);
        }
        
        return $success;
    }
    
    /**
     * Update statement count for a file
     * 
     * @param int $file_id File ID
     */
    protected function updateStatementCount($file_id)
    {
        $sql = "UPDATE " . TB_PREF . "bi_uploaded_files 
                SET statement_count = (
                    SELECT COUNT(*) 
                    FROM " . TB_PREF . "bi_file_statements 
                    WHERE file_id = " . db_escape($file_id) . "
                )
                WHERE id = " . db_escape($file_id);
        
        db_query($sql, "Failed to update statement count");
    }
    
    /**
     * Generate unique filename
     * 
     * @param string $original_filename Original filename
     * @return string Unique filename
     */
    protected function generateUniqueFilename($original_filename)
    {
        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
        $basename = pathinfo($original_filename, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Add timestamp and random component
        $timestamp = date('Ymd_His');
        $random = substr(md5(uniqid()), 0, 8);
        
        return "{$basename}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Find duplicate file upload
     * 
     * Checks for existing file with same:
     * - Original filename
     * - File size
     * - MD5 hash (optional, more expensive but accurate)
     * 
     * @param string $original_filename Original filename
     * @param int $file_size File size in bytes
     * @param string|null $tmp_path Temporary file path for hash check
     * @return array|null Duplicate file record or null
     */
    protected function findDuplicate($original_filename, $file_size, $tmp_path = null)
    {
        $config = Config::getInstance();
        $window_days = $config->get('upload.duplicate_window_days', 90);
        
        // Build query to find potential duplicates
        $sql = "SELECT id, filename, file_path, upload_date, upload_user
                FROM " . TB_PREF . "bi_uploaded_files
                WHERE original_filename = " . db_escape($original_filename) . "
                AND file_size = " . db_escape($file_size) . "
                AND upload_date >= DATE_SUB(NOW(), INTERVAL " . db_escape($window_days) . " DAY)
                ORDER BY upload_date DESC
                LIMIT 1";
        
        $result = db_query($sql, "Failed to check for duplicates");
        
        if ($row = db_fetch($result)) {
            // Found potential duplicate - verify with hash if file still exists
            if ($tmp_path && file_exists($row['file_path'])) {
                $new_hash = md5_file($tmp_path);
                $existing_hash = md5_file($row['file_path']);
                
                if ($new_hash === $existing_hash) {
                    // Exact duplicate found
                    return $row;
                }
            } else {
                // No hash check - assume duplicate based on name and size
                return $row;
            }
        }
        
        return null;
    }
    
    /**
     * Get all uploaded files
     * 
     * @param array $filters Optional filters (user, date_from, date_to, parser_type)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Uploaded files
     */
    public function getUploadedFiles($filters = [], $limit = 100, $offset = 0)
    {
        $sql = "SELECT f.*, 
                       u.real_name as uploader_name,
                       b.bank_account_name,
                       b.bank_name,
                       COUNT(DISTINCT fs.statement_id) as linked_statements
                FROM " . TB_PREF . "bi_uploaded_files f
                LEFT JOIN " . TB_PREF . "users u ON f.upload_user = u.user_id
                LEFT JOIN " . TB_PREF . "bank_accounts b ON f.bank_account_id = b.id
                LEFT JOIN " . TB_PREF . "bi_file_statements fs ON f.id = fs.file_id
                WHERE 1=1";
        
        // Apply filters
        if (!empty($filters['user'])) {
            $sql .= " AND f.upload_user = " . db_escape($filters['user']);
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND f.upload_date >= " . db_escape($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND f.upload_date <= " . db_escape($filters['date_to']);
        }
        
        if (!empty($filters['parser_type'])) {
            $sql .= " AND f.parser_type = " . db_escape($filters['parser_type']);
        }
        
        $sql .= " GROUP BY f.id
                  ORDER BY f.upload_date DESC
                  LIMIT " . db_escape($limit) . " OFFSET " . db_escape($offset);
        
        $result = db_query($sql, "Failed to retrieve uploaded files");
        
        $files = [];
        while ($row = db_fetch($result)) {
            $files[] = $row;
        }
        
        return $files;
    }
    
    /**
     * Get file details
     * 
     * @param int $file_id File ID
     * @return array|null File details
     */
    public function getFileDetails($file_id)
    {
        $sql = "SELECT f.*, 
                       u.real_name as uploader_name,
                       b.bank_account_name,
                       b.bank_name
                FROM " . TB_PREF . "bi_uploaded_files f
                LEFT JOIN " . TB_PREF . "users u ON f.upload_user = u.user_id
                LEFT JOIN " . TB_PREF . "bank_accounts b ON f.bank_account_id = b.id
                WHERE f.id = " . db_escape($file_id);
        
        $result = db_query($sql, "Failed to retrieve file details");
        
        if ($row = db_fetch($result)) {
            // Get linked statements
            $row['statements'] = $this->getLinkedStatements($file_id);
            return $row;
        }
        
        return null;
    }
    
    /**
     * Get statements linked to a file
     * 
     * @param int $file_id File ID
     * @return array Statements
     */
    public function getLinkedStatements($file_id)
    {
        $sql = "SELECT s.*, fs.file_id
                FROM " . TB_PREF . "bi_statements s
                JOIN " . TB_PREF . "bi_file_statements fs ON s.id = fs.statement_id
                WHERE fs.file_id = " . db_escape($file_id) . "
                ORDER BY s.smtDate DESC";
        
        $result = db_query($sql, "Failed to retrieve linked statements");
        
        $statements = [];
        while ($row = db_fetch($result)) {
            $statements[] = $row;
        }
        
        return $statements;
    }
    
    /**
     * Download a file
     * 
     * @param int $file_id File ID
     * @return bool Success
     */
    public function downloadFile($file_id)
    {
        $file = $this->getFileDetails($file_id);
        
        if (!$file || !file_exists($file['file_path'])) {
            return false;
        }
        
        // Security check - user must have permission
        if (!has_access($_SESSION['wa_current_user']->access, 'SA_BANKFILEVIEW')) {
            return false;
        }
        
        // Send file to browser
        header('Content-Type: ' . $file['file_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($file['file_path']);
        
        return true;
    }
    
    /**
     * Delete an uploaded file
     * 
     * @param int $file_id File ID
     * @return bool Success
     */
    public function deleteFile($file_id)
    {
        $file = $this->getFileDetails($file_id);
        
        if (!$file) {
            return false;
        }
        
        // Delete physical file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete from database (cascade will delete links)
        $sql = "DELETE FROM " . TB_PREF . "bi_uploaded_files 
                WHERE id = " . db_escape($file_id);
        
        return db_query($sql, "Failed to delete file record");
    }
    
    /**
     * Get total file count
     * 
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function getTotalFileCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as cnt
                FROM " . TB_PREF . "bi_uploaded_files f
                WHERE 1=1";
        
        // Apply same filters as getUploadedFiles
        if (!empty($filters['user'])) {
            $sql .= " AND f.upload_user = " . db_escape($filters['user']);
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND f.upload_date >= " . db_escape($filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND f.upload_date <= " . db_escape($filters['date_to']);
        }
        
        if (!empty($filters['parser_type'])) {
            $sql .= " AND f.parser_type = " . db_escape($filters['parser_type']);
        }
        
        $result = db_query($sql, "Failed to count files");
        $row = db_fetch($result);
        
        return $row['cnt'];
    }
    
    /**
     * Get storage statistics
     * 
     * @return array Statistics
     */
    public function getStorageStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    MAX(upload_date) as latest_upload,
                    MIN(upload_date) as first_upload
                FROM " . TB_PREF . "bi_uploaded_files";
        
        $result = db_query($sql, "Failed to get storage stats");
        return db_fetch($result);
    }
}
