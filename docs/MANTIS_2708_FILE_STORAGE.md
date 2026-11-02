# Mantis #2708: Import Bank Transaction Files - Upload File - Store File

**Implementation Date:** October 18, 2025  
**Author:** Kevin Fraser / ChatGPT  
**Status:** ✅ Complete

## Overview

This feature retains uploaded bank statement files for future reference and examination. It reuses FrontAccounting's document attachment concept to store uploaded files securely with full metadata tracking.

## Requirements (from Mantis ticket)

> Retain the uploaded file so that it can be examined.
>
> We can attach documents to various transactions in FA. Re-use/clone that facility to retain the uploaded bank file.
> * Create a new table to record the upload details
>   ** file name
>   ** upload date
>   ** upload user
>   ** Related Statement(s)
>
> Have a screen listing the uploaded bank files
> * Be able to download the file

## Implementation

### 1. Database Schema

#### Table: `0_bi_uploaded_files`

Stores metadata about uploaded bank statement files.

```sql
CREATE TABLE `0_bi_uploaded_files` (
    `id`                INTEGER NOT NULL AUTO_INCREMENT,
    `filename`          VARCHAR(255) NOT NULL,          -- Unique stored filename
    `original_filename` VARCHAR(255) NOT NULL,          -- Original upload filename
    `file_path`         VARCHAR(512) NOT NULL,          -- Full path to file
    `file_size`         INTEGER NOT NULL,               -- Size in bytes
    `file_type`         VARCHAR(100),                   -- MIME type
    `upload_date`       DATETIME NOT NULL,              -- When uploaded
    `upload_user`       VARCHAR(60) NOT NULL,           -- Who uploaded
    `parser_type`       VARCHAR(50),                    -- Parser used (qfx, csv, mt940)
    `bank_account_id`   INTEGER,                        -- FK to bank account
    `statement_count`   INTEGER DEFAULT 0,              -- Count of linked statements
    `notes`             TEXT,                           -- Additional notes
    PRIMARY KEY(`id`),
    INDEX `idx_upload_date` (`upload_date`),
    INDEX `idx_upload_user` (`upload_user`)
);
```

#### Table: `0_bi_file_statements`

Links uploaded files to imported statements (many-to-many).

```sql
CREATE TABLE `0_bi_file_statements` (
    `file_id`       INTEGER NOT NULL,
    `statement_id`  INTEGER NOT NULL,
    PRIMARY KEY(`file_id`, `statement_id`),
    FOREIGN KEY (`file_id`) REFERENCES `0_bi_uploaded_files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`statement_id`) REFERENCES `0_bi_statements`(`id`) ON DELETE CASCADE
);
```

**Relationships:**
- One uploaded file can contain multiple statements
- One statement came from one uploaded file
- Cascade delete ensures orphaned records are removed

### 2. File Storage

#### Storage Location

Files are stored in a company-specific directory following FrontAccounting conventions:

```
{company_path}/{company_number}/bank_imports/
```

Example:
```
/var/www/frontaccounting/company/0/bank_imports/
```

#### Security

1. **Directory Protection**
   - `.htaccess` file prevents direct HTTP access
   - Files must be downloaded through PHP script
   - Permission checks enforced (`SA_BANKFILEVIEW`)

2. **Unique Filenames**
   - Format: `{basename}_{timestamp}_{random}.{extension}`
   - Example: `cibc_visa_20251018_143052_a3f9d2e1.qfx`
   - Prevents overwrites and filename conflicts

3. **File Permissions**
   - Directories: `0750` (rwxr-x---)
   - Files: `0640` (rw-r-----)

### 3. Service Class

**File:** `src/Ksfraser/FaBankImport/services/UploadedFileManager.php`

**Class:** `UploadedFileManager`

**Key Methods:**

```php
// Save an uploaded file
public function saveUploadedFile($file_info, $parser_type, $bank_account_id, $notes)

// Link file to statements
public function linkFileToStatements($file_id, $statement_ids)

// Get all files with filtering
public function getUploadedFiles($filters, $limit, $offset)

// Get file details
public function getFileDetails($file_id)

// Download file (with security check)
public function downloadFile($file_id)

// Delete file
public function deleteFile($file_id)

// Get storage statistics
public function getStorageStats()
```

### 4. Integration with Import Process

#### Modified Files

**`import_statements.php`** - Enhanced to save files during upload

**Changes:**
1. Added `UploadedFileManager` initialization
2. Save each uploaded file in `parse_uploaded_files()` function
3. Store file IDs in session for linking
4. Pass file IDs to `importStatement()` function
5. Link files to statements after import

**Code Flow:**

```
1. User uploads file(s)
   ↓
2. parse_uploaded_files()
   - Save physical file to disk
   - Record in bi_uploaded_files table
   - Store file_id in $uploaded_file_ids array
   ↓
3. Parse file content (existing logic)
   ↓
4. Store file_ids in $_SESSION
   ↓
5. User clicks "Import"
   ↓
6. import_statements()
   - Loop through statements
   - Call importStatement($smt, $file_id)
     ↓
7. importStatement()
   - Insert/update statement (existing logic)
   - Link file to statement via bi_file_statements table
```

### 5. User Interface

**File:** `manage_uploaded_files.php`

**Features:**

#### A. File Listing
- Paginated list of all uploaded files (50 per page)
- Sortable columns:
  - ID
  - Original Filename
  - Upload Date
  - Uploaded By (user)
  - File Size
  - Parser Type
  - Bank Account
  - Statement Count
  - Actions

#### B. Filtering
- Filter by user
- Filter by date range (from/to)
- Filter by parser type
- Clear filters button

#### C. Storage Statistics
- Total files count
- Total storage used (MB)
- Latest upload date
- First upload date

#### D. File Actions
- **Download** - Download original file
- **View Details** - See full file information
- **Delete** - Remove file and database records

#### E. File Details View
Clicking "View Details" shows:
- Complete file metadata
- List of linked statements with links
- Statement details (bank, account, date, balance)

### 6. Menu Integration

#### FrontAccounting Main Menu

Added to `hooks.php`:
```php
$app->add_lapp_function(3, _("Manage Uploaded Files"),
    $path_to_root."/modules/".$this->module_name."/manage_uploaded_files.php", 
    'SA_BANKFILEVIEW', MENU_INQUIRY);
```

**Location:** GL → Inquiry → Manage Uploaded Files

#### Module Navigation Menu

Added to `views/module_menu_view.php`:
```html
<li><a href="manage_uploaded_files.php">Manage Uploaded Files</a></li>
```

## Usage Examples

### Example 1: Upload and Store Files

```php
$file_manager = new UploadedFileManager();

// Save uploaded file
$file_id = $file_manager->saveUploadedFile(
    $_FILES['statement'],
    'CibcQfxParser',
    $bank_account_id = 5,
    "Monthly statement upload"
);

// Import and link to statements
foreach ($statements as $statement) {
    $statement_id = import_statement($statement);
    $file_manager->linkFileToStatements($file_id, [$statement_id]);
}
```

### Example 2: List Files with Filtering

```php
$file_manager = new UploadedFileManager();

// Get files from specific user in date range
$filters = [
    'user' => 'jsmith',
    'date_from' => '2025-10-01',
    'date_to' => '2025-10-31',
    'parser_type' => 'CibcQfxParser'
];

$files = $file_manager->getUploadedFiles($filters, $limit = 50, $offset = 0);
```

### Example 3: Download File

```php
// In manage_uploaded_files.php
if (isset($_GET['download'])) {
    $file_manager = new UploadedFileManager();
    $file_manager->downloadFile($_GET['download']);
    exit;
}
```

### Example 4: Get Storage Stats

```php
$file_manager = new UploadedFileManager();
$stats = $file_manager->getStorageStats();

echo "Total Files: " . $stats['total_files'];
echo "Total Size: " . ($stats['total_size'] / 1024 / 1024) . " MB";
```

## SQL Queries

### Find Files for a Statement

```sql
SELECT f.*
FROM 0_bi_uploaded_files f
JOIN 0_bi_file_statements fs ON f.id = fs.file_id
WHERE fs.statement_id = 123;
```

### Find Statements from a File

```sql
SELECT s.*
FROM 0_bi_statements s
JOIN 0_bi_file_statements fs ON s.id = fs.statement_id
WHERE fs.file_id = 456;
```

### Get Files Uploaded by User

```sql
SELECT f.*, COUNT(fs.statement_id) as stmt_count
FROM 0_bi_uploaded_files f
LEFT JOIN 0_bi_file_statements fs ON f.id = fs.file_id
WHERE f.upload_user = 'jsmith'
GROUP BY f.id
ORDER BY f.upload_date DESC;
```

### Storage Usage by Parser Type

```sql
SELECT parser_type, 
       COUNT(*) as file_count,
       SUM(file_size) as total_size
FROM 0_bi_uploaded_files
GROUP BY parser_type
ORDER BY total_size DESC;
```

## Security

### Permission Required

`SA_BANKFILEVIEW` - Bank Statement File View/Download

This permission is separate from transaction viewing (`SA_BANKTRANSVIEW`). Access to download/manage uploaded bank files should be more restricted since files may contain sensitive information.

### Access Control

1. **File Download** - Permission check (SA_BANKFILEVIEW) before sending file
2. **File Delete** - Permission check (SA_BANKFILEVIEW) before deletion
3. **Directory Access** - Protected by `.htaccess`
4. **Path Traversal** - Prevented by using database-stored paths only

### File Validation

- File uploads validated via `is_uploaded_file()`
- Filenames sanitized before storage
- No direct user input in file paths

## Maintenance

### Cleanup Old Files

Manual cleanup script (to be scheduled via cron):

```php
// Delete files older than 2 years with no linked statements
$sql = "DELETE f FROM 0_bi_uploaded_files f
        LEFT JOIN 0_bi_file_statements fs ON f.id = fs.file_id
        WHERE f.upload_date < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        AND fs.file_id IS NULL";
```

### Disk Space Monitoring

```php
$stats = $file_manager->getStorageStats();
$size_mb = $stats['total_size'] / 1024 / 1024;

if ($size_mb > 1000) {  // Alert if > 1GB
    send_alert("Bank import storage exceeds 1GB: {$size_mb} MB");
}
```

## Testing

### Test Cases

1. **Upload Single File**
   - Upload QFX file
   - Verify file saved to disk
   - Verify database record created
   - Verify unique filename generated

2. **Upload Multiple Files**
   - Upload 3 files at once
   - Verify all saved correctly
   - Verify all have different filenames

3. **Link to Statements**
   - Import file with 2 statements
   - Verify both statements linked to file
   - Verify statement_count updated to 2

4. **Download File**
   - Click download link
   - Verify original filename in download
   - Verify file content matches upload

5. **Delete File**
   - Delete file
   - Verify physical file removed
   - Verify database record removed
   - Verify links removed (cascade)

6. **Filter Files**
   - Filter by user
   - Filter by date range
   - Filter by parser type
   - Verify correct results

7. **Security**
   - Try downloading without permission → Denied
   - Try direct URL access to file → Denied (403)
   - Try deleting without permission → Denied

## Deployment

### 1. Run SQL Migration

```bash
mysql fa_database < sql/mantis_2708_uploaded_files.sql
```

### 2. Create Storage Directory

```bash
mkdir -p /var/www/frontaccounting/company/0/bank_imports
chmod 750 /var/www/frontaccounting/company/0/bank_imports
chown www-data:www-data /var/www/frontaccounting/company/0/bank_imports
```

### 3. Deploy Files

Upload:
- `src/Ksfraser/FaBankImport/services/UploadedFileManager.php`
- `manage_uploaded_files.php`
- Modified `import_statements.php`
- Modified `hooks.php`
- Modified `views/module_menu_view.php`

### 4. Clear FA Cache

```php
// In FrontAccounting admin
System Setup → Software Upgrade → Clear Cache
```

### 5. Test

1. Upload test file
2. Check file saved: `ls -la company/0/bank_imports/`
3. Check database: `SELECT * FROM 0_bi_uploaded_files;`
4. Visit "Manage Uploaded Files" page
5. Download test file

## Benefits

✅ **Audit Trail** - Complete history of all uploaded files  
✅ **Troubleshooting** - Re-examine original files when issues arise  
✅ **Compliance** - Retain source documents for auditing  
✅ **Recovery** - Re-import if database corrupted  
✅ **Analysis** - Compare multiple uploads from same account  
✅ **User Tracking** - Know who uploaded what and when  

## Future Enhancements

1. **Automatic Cleanup**
   - Cron job to delete files older than X years
   - Configurable retention policy

2. **Duplicate Detection** ✅ **IMPLEMENTED**
   - Optional checking for duplicate file uploads
   - Saves disk space by reusing existing files
   - See: `docs/DUPLICATE_CHECKING.md`
   - Config: `upload.check_duplicates` (default: false)

3. **File Versioning**
   - Track multiple uploads of same statement
   - Show version history

4. **Bulk Download**
   - Download multiple files as ZIP
   - Export file list to CSV

5. **Email Notifications**
   - Notify users when import complete
   - Alert on storage threshold exceeded

6. **File Preview**
   - Show file content without downloading
   - Syntax highlighting for XML/SGML

7. **Search**
   - Full-text search in filenames
   - Search by statement content

## Summary

✅ **All requirements from Mantis #2708 implemented:**

1. ✅ Create table to record upload details (filename, date, user, statements)
2. ✅ Store uploaded files securely
3. ✅ Screen listing uploaded bank files
4. ✅ Ability to download files
5. ✅ Link files to related statements

**Files Created:** 3  
**Files Modified:** 5  
**Database Tables:** 2  
**Lines of Code:** ~800  

**Status:** Ready for deployment and testing!
