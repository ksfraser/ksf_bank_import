# Auto-Migration Feature

## Overview

Both the configuration system and file upload system now support **automatic migration** - database tables are created automatically on first use. No manual SQL execution required!

---

## How It Works

### Lazy Initialization Pattern

1. When a repository is first used, it checks if tables exist
2. If tables don't exist, it creates them automatically
3. Default values are inserted (for config system)
4. Subsequent calls use existing tables

### Implementation

#### Config System (DatabaseConfigRepository)

```php
private function loadCache(): void
{
    if ($this->cacheLoaded) {
        return;
    }
    
    // Auto-migration happens here
    $this->ensureTablesExist();
    
    // Then load cache normally...
}

private function ensureTablesExist(): void
{
    // Check if table exists
    $check = "SHOW TABLES LIKE '" . TB_PREF . "bi_config'";
    $result = db_query($check);
    
    if (db_num_rows($result) === 0) {
        $this->createTables();
        $this->insertDefaultValues();
    }
}
```

**Triggers**:
- First call to ConfigService::getInstance()->get()
- Accessing module_config.php UI
- Any config operation

**Creates**:
- `0_bi_config` table (configuration storage)
- `0_bi_config_history` table (audit trail)
- 13 default configuration entries

---

#### File Upload System (DatabaseUploadedFileRepository)

```php
public function save(UploadedFile $file): int
{
    // Auto-migration happens on every call
    $this->ensureTablesExist();
    
    // Then save normally...
}

private function ensureTablesExist(): void
{
    // Check if table exists
    $check = "SHOW TABLES LIKE '" . TB_PREF . "bi_uploaded_files'";
    $result = db_query($check);
    
    if (db_num_rows($result) === 0) {
        $this->createTables();
    }
}
```

**Triggers**:
- First file upload
- Accessing manage_uploaded_files.php UI
- Any file metadata operation

**Creates**:
- `0_bi_uploaded_files` table (file metadata only)
- `0_bi_file_statements` table (many-to-many links)

---

## Key Design Decisions

### ✅ Metadata Only in Database

**Repository stores**:
- File metadata (filename, size, type, upload date, user, etc.)
- Relationships (which statements came from which file)
- Statistics (counts, sizes, etc.)

**Repository does NOT store**:
- File content (stays on disk in company directory)
- File binary data

### ✅ Files Remain on Disk

Files are stored in the company directory structure as originally designed:
```
company/
└── bank_imports/
    └── uploads/
        ├── ATB_20250119_143022_a8f3e9.qfx
        ├── CIBC_20250119_150345_b4c7d2.qfx
        └── ...
```

**Benefits**:
- Simple backup (copy directory)
- Direct file system access if needed
- No BLOB storage overhead
- Compatible with existing infrastructure

### ✅ Auto-Migration on First Use

**No manual steps required**:
- ❌ No need to run SQL scripts
- ❌ No need to manually create tables
- ❌ No need to insert default values
- ✅ Just use the feature - tables created automatically

**Benefits**:
- Zero-configuration deployment
- Works in dev, test, and prod environments
- Idempotent (safe to call multiple times)
- Backward compatible

---

## Database Schema

### Config Tables

**bi_config** - Configuration storage
```sql
CREATE TABLE `0_bi_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(20) NOT NULL DEFAULT 'string',
  `description` text,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`),
  KEY `category` (`category`),
  KEY `is_system` (`is_system`)
)
```

**bi_config_history** - Audit trail
```sql
CREATE TABLE `0_bi_config_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `old_value` text,
  `new_value` text,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` varchar(60) NOT NULL,
  `change_reason` text,
  PRIMARY KEY (`id`),
  KEY `config_key` (`config_key`),
  KEY `changed_at` (`changed_at`)
)
```

---

### File Upload Tables

**bi_uploaded_files** - File metadata (NOT content)
```sql
CREATE TABLE `0_bi_uploaded_files` (
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
)
COMMENT='File metadata only - actual files stored in company directory'
```

**bi_file_statements** - Many-to-many relationship
```sql
CREATE TABLE `0_bi_file_statements` (
  `file_id` int(11) NOT NULL,
  `statement_id` int(11) NOT NULL,
  PRIMARY KEY (`file_id`, `statement_id`),
  KEY `statement_id` (`statement_id`)
)
COMMENT='Links uploaded files to imported statements'
```

---

## Default Configuration Values

Inserted automatically on first use:

### Upload Configuration
- `check_duplicates` = true
- `duplicate_action` = 'warn'
- `duplicate_window_days` = 90
- `max_upload_size_mb` = 100
- `allowed_extensions` = 'qfx,ofx,mt940,sta,csv'

### Storage Configuration
- `retention_days` = 365
- `auto_delete_old_files` = false

### Logging Configuration
- `enable_audit_log` = true
- `log_duplicate_attempts` = true

### Performance Configuration
- `batch_size` = 1000
- `memory_limit_mb` = 256

### Security Configuration (System-Protected)
- `require_file_permission` = true
- `allow_file_download` = true

---

## Deployment Steps

### Development Environment
1. Update code files
2. Access any feature (e.g., module_config.php)
3. Tables created automatically ✅

### Production Environment
1. Upload new/updated files:
   - `src/Ksfraser/FaBankImport/Repository/DatabaseConfigRepository.php`
   - `src/Ksfraser/FaBankImport/Repository/DatabaseUploadedFileRepository.php`
   - Other Phase 2 files
2. Access module_config.php or upload a file
3. Tables created automatically ✅
4. Zero downtime!

### Verification
```sql
-- Check tables exist
SHOW TABLES LIKE '0_bi_config%';
SHOW TABLES LIKE '0_bi_uploaded_files%';

-- Check default config values
SELECT config_key, config_value, category 
FROM 0_bi_config 
ORDER BY category, config_key;

-- Check file metadata (after first upload)
SELECT id, original_filename, file_size, upload_date, upload_user
FROM 0_bi_uploaded_files
ORDER BY upload_date DESC;
```

---

## Benefits

### For Developers
- ✅ No manual SQL scripts to run
- ✅ Works across all environments automatically
- ✅ Easy to test locally
- ✅ Idempotent (safe to call multiple times)

### For Deployment
- ✅ Zero-configuration deployment
- ✅ No database migration steps
- ✅ No downtime required
- ✅ Rollback friendly (just revert code)

### For Maintenance
- ✅ Self-documenting (schema in code)
- ✅ Version controlled (in PHP files)
- ✅ Easy to modify (just update createTables())
- ✅ Audit trail built-in

---

## Safety Features

### Idempotent Operations
```php
// Safe to call multiple times
$this->ensureTablesExist();  // Only creates if missing
$this->ensureTablesExist();  // Does nothing (tables exist)
```

### Uses CREATE TABLE IF NOT EXISTS
```sql
CREATE TABLE IF NOT EXISTS `0_bi_config` (...);
-- Safe even if table exists
```

### Check Before Create
```php
// Explicit check
$check = "SHOW TABLES LIKE '" . TB_PREF . "bi_config'";
$result = db_query($check);

if (db_num_rows($result) === 0) {
    // Only create if truly missing
    $this->createTables();
}
```

---

## Future Enhancements

### Migration Version Tracking (Optional)
```php
// Could add version tracking later
private function getMigrationVersion(): int {
    // Check for version column or separate migrations table
}

private function runMigrationsIfNeeded(): void {
    $current = $this->getMigrationVersion();
    
    if ($current < 2) {
        $this->migrateToVersion2();
    }
    
    if ($current < 3) {
        $this->migrateToVersion3();
    }
}
```

### Schema Changes (Optional)
```php
// Could add columns without breaking existing installs
private function ensureColumnsExist(): void {
    // Check for specific columns
    // Add if missing (ALTER TABLE ADD COLUMN IF NOT EXISTS)
}
```

---

## Summary

**Key Points**:
1. ✅ **Metadata only in database** - files stay on disk
2. ✅ **Auto-migration on first use** - no manual SQL
3. ✅ **Idempotent and safe** - can't break existing tables
4. ✅ **Zero-configuration deployment** - just upload code
5. ✅ **Default values included** - ready to use immediately

**Result**: A modern, maintainable, production-ready system that "just works"!
