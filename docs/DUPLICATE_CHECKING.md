# Duplicate File Upload Detection

**Feature:** Mantis #2708 Enhancement  
**Config Setting:** `upload.check_duplicates`  
**Default:** `false` (disabled)

## Overview

Prevents re-uploading the same bank statement files to save:
- **Disk space** - Large files not stored multiple times
- **Processing time** - Skip re-parsing identical files
- **Database clutter** - Avoid duplicate records

## How It Works

When enabled, the system checks if an uploaded file matches an existing file by:

1. **Original Filename** - Exact match
2. **File Size** - Must be identical (bytes)
3. **MD5 Hash** - Content verification (if file still exists on disk)
4. **Time Window** - Only checks recent uploads (default: 90 days)

If a duplicate is found:
- Upload is skipped
- Existing file ID is reused
- User sees warning message: "Duplicate file detected! Using existing file ID: X"
- Statement import continues normally with existing file

## Configuration

### Enable Duplicate Checking

Edit: `src/Ksfraser/FaBankImport/config/Config.php`

```php
'upload' => [
    'check_duplicates' => true,  // Enable duplicate detection
    'duplicate_window_days' => 90,  // How far back to check (days)
    'duplicate_action' => 'warn'  // Action: 'allow', 'warn', 'block'
]
```

### Configuration Options

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `check_duplicates` | boolean | `false` | Enable/disable duplicate checking |
| `duplicate_window_days` | integer | `90` | How many days back to check for duplicates |
| `duplicate_action` | string | `'warn'` | Action on duplicate: `'allow'`, `'warn'`, `'block'` |

### Duplicate Action Modes

#### `'allow'` - Silent Skip
- Duplicate detected → Skip saving file
- Reuse existing file ID
- Continue processing silently
- User sees: "Duplicate file detected! Using existing file ID: X"
- **Use when:** Duplicates are expected and safe to skip

#### `'warn'` - Soft Deny (User Prompt) ⭐ **RECOMMENDED**
- Duplicate detected → **Stop and ask user**
- Show warning screen with details:
  - Filename, size, upload date
  - Who uploaded it previously
  - Existing file ID
- User chooses:
  - **"Force Upload & Process Anyway"** → Bypass check, save as new file
  - **"Cancel - Do Not Upload"** → Stop, return to upload form
- **Use when:** Want user confirmation before processing duplicates

#### `'block'` - Hard Deny (Reject Upload)
- Duplicate detected → **Reject immediately**
- Show error: "BLOCKED: File is a duplicate. Upload rejected. To upload anyway, rename the file."
- File NOT processed
- User must rename file to bypass check
- **Use when:** Duplicates should never be allowed

### Configuration Examples

**Recommended (Default):**
```php
'duplicate_action' => 'warn'  // Ask user what to do
```

**Lenient:**
```php
'duplicate_action' => 'allow'  // Auto-skip duplicates, no questions
```

**Strict:**
```php
'duplicate_action' => 'block'  // Hard reject, force rename
```

## User Experience

### Mode: 'allow' - Silent Skip

**Upload Screen:**
```
Processing file `CIBC_VISA_2024_Oct.qfx` with format `QFX Parser`
⚠️ Duplicate file detected! Using existing file ID: 42. Skipping upload to save disk space.
statement: 123456789: is valid, 45 transactions
======================================
[Go Back] [Import]
```

**Result:** File not saved, existing file reused, import continues

---

### Mode: 'warn' - Soft Deny (User Prompt) ⭐

**Upload Screen (Step 1 - Duplicate Detected):**
```
Processing file `CIBC_VISA_2024_Oct.qfx` with format `QFX Parser`

┌─────────────────────────────────────────────────────────┐
│ ⚠️ Duplicate Files Detected                             │
├─────────────────────────────────────────────────────────┤
│ The following files appear to be duplicates (same       │
│ filename, size, and content):                           │
│                                                          │
│ ┌───────────────────────────────────────────────────┐  │
│ │ CIBC_VISA_2024_Oct.qfx                            │  │
│ │ Size: 45.23 KB                                    │  │
│ │ Previously uploaded: 2024-10-15 14:32:10          │  │
│ │ By: john.doe                                      │  │
│ │ Existing file ID: 42                              │  │
│ └───────────────────────────────────────────────────┘  │
│                                                          │
│ What would you like to do?                              │
│                                                          │
│ [Force Upload & Process Anyway]  [Cancel - Do Not Upload]│
└─────────────────────────────────────────────────────────┘
```

**User Action:**
- **Click "Force Upload"** → File saved as new, import continues
- **Click "Cancel"** → Return to upload form

**Upload Screen (Step 2 - If Forced):**
```
Processing file `CIBC_VISA_2024_Oct.qfx` with format `QFX Parser`
✓ File saved with ID: 99 (forced upload, duplicate check bypassed)
statement: 123456789: is valid, 45 transactions
======================================
[Go Back] [Import]
```

---

### Mode: 'block' - Hard Deny

**Upload Screen:**
```
Processing file `CIBC_VISA_2024_Oct.qfx` with format `QFX Parser`
❌ BLOCKED: File 'CIBC_VISA_2024_Oct.qfx' is a duplicate (same name, 
   size, and content). Upload rejected. To upload anyway, rename the file.
======================================
[Go Back]  (Import button hidden)
```

**Result:** File rejected, no import button shown

**To Bypass:** User must rename file (e.g., `CIBC_VISA_2024_Oct_v2.qfx`)

---

### When Duplicate NOT Detected (All Modes)

**Upload Screen:**
```
Processing file `CIBC_VISA_2024_Oct.qfx` with format `QFX Parser`
✓ File saved with ID: 99
statement: 123456789: is valid, 45 transactions
======================================
[Go Back] [Import]
```

**Result:** Normal processing

## Technical Details

### Duplicate Detection Method

```php
protected function findDuplicate($original_filename, $file_size, $tmp_path = null)
{
    $config = Config::getInstance();
    $window_days = $config->get('upload.duplicate_window_days', 90);
    
    // 1. Query for files matching name and size within time window
    $sql = "SELECT id, filename, file_path, upload_date, upload_user
            FROM 0_bi_uploaded_files
            WHERE original_filename = '$original_filename'
            AND file_size = $file_size
            AND upload_date >= DATE_SUB(NOW(), INTERVAL $window_days DAY)
            ORDER BY upload_date DESC
            LIMIT 1";
    
    // 2. If found, verify with MD5 hash
    if ($row = db_fetch($result)) {
        if ($tmp_path && file_exists($row['file_path'])) {
            $new_hash = md5_file($tmp_path);
            $existing_hash = md5_file($row['file_path']);
            
            if ($new_hash === $existing_hash) {
                return $row;  // Exact match
            }
        }
    }
    
    return null;  // No duplicate found
}
```

### Return Values

The `saveUploadedFile()` method returns:

| Return Value | Meaning | Action Mode |
|--------------|---------|-------------|
| `> 0` | New file saved successfully (file ID) | All modes |
| `< 0` (not -999) | Duplicate detected (negative of existing file ID) | `allow` or `warn` |
| `-999` | Duplicate blocked (hard deny) | `block` |
| `false` | Upload failed (not duplicate related) | All modes |

**Example:**
```php
$file_id = $file_manager->saveUploadedFile($file_info, ...);

if ($file_id > 0) {
    // New file saved
    echo "Saved as ID: $file_id";
} elseif ($file_id === -999) {
    // Hard blocked
    echo "BLOCKED: Duplicate rejected!";
    // Don't allow import
} elseif ($file_id < 0) {
    // Duplicate found (allow or warn mode)
    $existing_id = abs($file_id);
    echo "Duplicate! Using existing ID: $existing_id";
    // In 'warn' mode, user will be prompted
} else {
    // Failed
    echo "Upload failed";
}
```

## Performance Impact

### With Duplicate Checking Enabled

**Additional Operations Per Upload:**
1. SQL query to find potential duplicates (~1ms)
2. MD5 hash of uploaded file (~10-100ms for typical files)
3. MD5 hash of existing file (~10-100ms)

**Total overhead:** ~20-200ms per file

**Benefits:**
- Save disk I/O (no duplicate file write)
- Save database insert
- Save disk space (especially for large files)

### Without Duplicate Checking (Default)

**No additional overhead** - files always saved

**Trade-off:**
- Faster upload processing
- More disk space used
- Possible duplicate files

## Use Cases

### Use 'allow' (Silent Skip) When:

✅ **Automated/batch processing**
- Scripts uploading files automatically
- Want seamless processing without prompts

✅ **Duplicates are expected and harmless**
- Re-uploading monthly statements is normal
- Just want to skip and continue

✅ **User trust level is high**
- Internal users who know what they're doing
- Minimal risk of processing wrong data

---

### Use 'warn' (Soft Deny/Prompt) When: ⭐ **RECOMMENDED**

✅ **Want user awareness**
- User should know they're uploading a duplicate
- Give them choice to proceed or cancel

✅ **Mixed user skill levels**
- Some users might upload duplicates by mistake
- Others might have legitimate reasons to re-upload

✅ **Accountability**
- Need user to explicitly confirm duplicate processing
- Audit trail showing user chose to force upload

✅ **Most common scenario**
- Balance between safety and flexibility
- Best for production environments

---

### Use 'block' (Hard Deny) When:

✅ **Strict duplicate prevention required**
- Financial compliance requires no duplicates
- Data integrity is critical

✅ **Large files**
- Don't want any chance of duplicate storage
- Disk space is precious

✅ **Untrusted users**
- External users or bulk uploaders
- Want to enforce file uniqueness

✅ **Force file renaming**
- If truly need to re-upload, must rename
- Creates different filename in system

**Note:** User can bypass 'block' mode by renaming the file (e.g., add `_v2` to filename)

## Testing

### Test Case 1: Duplicate Detected

```php
// 1. Enable duplicate checking
Config::getInstance()->set('upload.check_duplicates', true);

// 2. Upload file first time
$file_id1 = $file_manager->saveUploadedFile($file_info, 'qfx', 1);
// Result: $file_id1 = 100 (new file)

// 3. Upload same file again
$file_id2 = $file_manager->saveUploadedFile($file_info, 'qfx', 1);
// Result: $file_id2 = -100 (duplicate, negative ID)

// 4. Verify
assert($file_id2 < 0);
assert(abs($file_id2) == $file_id1);
```

### Test Case 2: Different Files (Same Name)

```php
// Files with same name but different content
$file_info1 = ['name' => 'statement.qfx', 'size' => 1000, ...];
$file_info2 = ['name' => 'statement.qfx', 'size' => 2000, ...];  // Different size

$file_id1 = $file_manager->saveUploadedFile($file_info1, ...);
$file_id2 = $file_manager->saveUploadedFile($file_info2, ...);

// Result: Both saved (different sizes)
assert($file_id1 > 0);
assert($file_id2 > 0);
assert($file_id1 != $file_id2);
```

### Test Case 3: Outside Time Window

```php
// Enable with 30-day window
Config::getInstance()->set('upload.duplicate_window_days', 30);

// Upload file
$file_id1 = $file_manager->saveUploadedFile($file_info, ...);

// Simulate 40 days passing (update upload_date in database)
db_query("UPDATE 0_bi_uploaded_files SET upload_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE id = $file_id1");

// Upload same file again
$file_id2 = $file_manager->saveUploadedFile($file_info, ...);

// Result: NOT a duplicate (outside window)
assert($file_id2 > 0);
assert($file_id2 != $file_id1);
```

## SQL Queries

### Find All Duplicates (Manual Check)

```sql
-- Files with same name and size
SELECT 
    original_filename,
    file_size,
    COUNT(*) as upload_count,
    GROUP_CONCAT(id) as file_ids,
    MIN(upload_date) as first_upload,
    MAX(upload_date) as last_upload
FROM 0_bi_uploaded_files
GROUP BY original_filename, file_size
HAVING COUNT(*) > 1
ORDER BY upload_count DESC;
```

### Calculate Disk Space Saved

```sql
-- Total space that could be saved by removing duplicates
SELECT 
    SUM(wasted_space) / 1024 / 1024 as wasted_mb
FROM (
    SELECT 
        file_size * (COUNT(*) - 1) as wasted_space
    FROM 0_bi_uploaded_files
    GROUP BY original_filename, file_size
    HAVING COUNT(*) > 1
) as duplicates;
```

### Clean Up Old Duplicates

```sql
-- Delete duplicate files, keeping oldest
DELETE f1 FROM 0_bi_uploaded_files f1
INNER JOIN 0_bi_uploaded_files f2 
    ON f1.original_filename = f2.original_filename
    AND f1.file_size = f2.file_size
    AND f1.id > f2.id
WHERE f1.statement_count = 0;  -- Only if no statements linked
```

## Recommendations

### Default Configuration (Recommended)

```php
'upload' => [
    'check_duplicates' => false,  // Start disabled
    'duplicate_window_days' => 90   // 3 months if enabled
]
```

**Rationale:**
- Most users upload unique files
- Small performance overhead not worth it for most cases
- Can enable later if duplicate uploads become a problem

### High-Volume Configuration

```php
'upload' => [
    'check_duplicates' => true,   // Enable for busy systems
    'duplicate_window_days' => 30  // Shorter window for speed
]
```

**Use when:**
- Multiple users uploading same files
- Disk space is limited
- Files are typically large (> 500KB)

## Future Enhancements

1. **Auto-cleanup duplicates** - Scheduled task to merge duplicates
2. **User notification** - Email when duplicate detected
3. **Statistics** - Dashboard showing duplicates and space saved
4. **Configurable hash algorithm** - Choose MD5, SHA1, SHA256
5. **Partial hash** - Only hash first/last N bytes for speed
6. **Database optimization** - Index on (original_filename, file_size)

## Summary

✅ **Three duplicate handling modes:**

| Mode | Behavior | User Prompt | Best For |
|------|----------|-------------|----------|
| **`allow`** | Silent skip, reuse existing file | No | Automated processing |
| **`warn`** ⭐ | Stop and ask user | Yes | Production (recommended) |
| **`block`** | Hard reject | No | Strict environments |

✅ **Smart detection:** Filename + Size + MD5 Hash  
✅ **Time-windowed:** Only checks recent uploads (default 90 days)  
✅ **Configurable:** Choose behavior per environment  
✅ **User-friendly:** Clear messages and options  
✅ **Bypass options:**
- `allow`/`warn`: User can force upload
- `block`: User must rename file

✅ **Default settings (safe):**
```php
'check_duplicates' => false,  // OFF by default
'duplicate_action' => 'warn'   // Ask user if enabled
```

**To enable:** Edit `Config.php` and set:
```php
'check_duplicates' => true,
'duplicate_action' => 'warn'  // or 'allow' or 'block'
```

**Bypassing duplicate check:**
- **All modes:** Rename the file
- **`allow`/`warn` modes:** Use "Force Upload" button (warn mode only)
- **`block` mode:** MUST rename file, no force option
