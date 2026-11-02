# Duplicate Detection Modes - Quick Reference

## Configuration

```php
// src/Ksfraser/FaBankImport/config/Config.php
'upload' => [
    'check_duplicates' => true,       // Enable/disable
    'duplicate_window_days' => 90,     // How far back to check
    'duplicate_action' => 'warn'       // Choose: 'allow', 'warn', 'block'
]
```

---

## Mode Comparison

| Feature | `allow` | `warn` ⭐ | `block` |
|---------|---------|-----------|---------|
| **Stops Processing** | No | Yes | Yes |
| **Shows Warning** | Yes (brief) | Yes (detailed) | Yes (error) |
| **User Prompt** | No | **Yes** | No |
| **Can Force Upload** | N/A (auto-skip) | **Yes** | **No** |
| **Must Rename File** | No | No | **Yes** |
| **Reuses Existing File** | Yes | Optional | No (rejected) |
| **Best For** | Automation | **Production** | Strict compliance |

---

## Visual Flow Diagrams

### Mode: `allow` (Silent Skip)
```
Upload File
    ↓
Duplicate? → NO → Save File → Import → Done ✓
    ↓
   YES
    ↓
Skip Save → Reuse Existing ID → Import → Done ✓
```
**User sees:** "⚠️ Duplicate detected! Using existing file ID: 42"

---

### Mode: `warn` (Soft Deny - User Prompt) ⭐ RECOMMENDED
```
Upload File
    ↓
Duplicate? → NO → Save File → Import → Done ✓
    ↓
   YES
    ↓
Show Warning Screen
    ↓
User Chooses:
    ├─→ "Force Upload" → Save as New File → Import → Done ✓
    └─→ "Cancel" → Return to Upload Form → Done ❌
```
**User sees:** Full warning dialog with file details and action buttons

---

### Mode: `block` (Hard Deny)
```
Upload File
    ↓
Duplicate? → NO → Save File → Import → Done ✓
    ↓
   YES
    ↓
BLOCK → Show Error → Hide Import Button → Done ❌

User must rename file to retry
```
**User sees:** "❌ BLOCKED: Duplicate detected. Rename file to upload."

---

## Configuration Examples

### Development Environment
```php
'check_duplicates' => true,
'duplicate_action' => 'allow'  // Fast, auto-skip duplicates
```

### Production Environment (Recommended)
```php
'check_duplicates' => true,
'duplicate_action' => 'warn'  // Ask user, give choice
```

### High Security / Compliance
```php
'check_duplicates' => true,
'duplicate_action' => 'block'  // No duplicates allowed
```

### Disable Checking (Default)
```php
'check_duplicates' => false,  // No checking, always save
'duplicate_action' => 'warn'  // (ignored when disabled)
```

---

## User Experience Examples

### Mode: `allow`

**Screen Output:**
```
Processing file `bank_statement.qfx`
⚠️ Duplicate file detected! Using existing file ID: 42. Skipping upload to save disk space.
statement: 123456789: is valid, 45 transactions
======================================
Valid statements   : 1
Invalid statements : 0
Total transactions : 45
======================================
[Go Back]  [Import]
```

**What Happens:**
- File NOT saved to disk
- Existing file ID 42 reused
- Processing continues automatically
- Import button available

---

### Mode: `warn` ⭐

**Screen Output (Step 1):**
```
Processing file `bank_statement.qfx`

╔════════════════════════════════════════════════════════╗
║ ⚠️  DUPLICATE FILES DETECTED                           ║
╠════════════════════════════════════════════════════════╣
║ The following files appear to be duplicates:           ║
║                                                        ║
║ ┌──────────────────────────────────────────────────┐ ║
║ │ bank_statement.qfx                               │ ║
║ │ Size: 45.23 KB                                   │ ║
║ │ Previously uploaded: 2024-10-15 14:32:10         │ ║
║ │ By: john.doe                                     │ ║
║ │ Existing file ID: 42                             │ ║
║ └──────────────────────────────────────────────────┘ ║
║                                                        ║
║ What would you like to do?                            ║
║                                                        ║
║ [Force Upload & Process Anyway]  [Cancel Upload]     ║
╚════════════════════════════════════════════════════════╝
```

**If User Clicks "Force Upload":**
```
Processing file `bank_statement.qfx`
✓ File saved with ID: 99 (forced upload, duplicate check bypassed)
statement: 123456789: is valid, 45 transactions
======================================
[Go Back]  [Import]
```

**If User Clicks "Cancel":**
- Returns to upload form
- No file saved
- No processing

---

### Mode: `block`

**Screen Output:**
```
Processing file `bank_statement.qfx`
❌ BLOCKED: File 'bank_statement.qfx' is a duplicate (same name, size, and content). 
   Upload rejected. To upload anyway, rename the file.
======================================
Valid statements   : 0
Invalid statements : 1
Total transactions : 0
======================================
[Go Back]  (Import button hidden)
```

**What Happens:**
- File NOT saved
- Processing STOPPED
- Import button HIDDEN
- User must rename file (e.g., `bank_statement_v2.qfx`)

---

## Decision Tree: Which Mode to Use?

```
Do you want to allow duplicate file uploads?
│
├─→ NO (Strict)
│   └─→ Use 'block' mode
│       • Hard reject duplicates
│       • User must rename to proceed
│       • Best for: Compliance, strict environments
│
├─→ SOMETIMES (Ask User)
│   └─→ Use 'warn' mode ⭐ RECOMMENDED
│       • Stop and ask user
│       • User decides: force or cancel
│       • Best for: Production, mixed users
│
└─→ YES (Allow)
    ├─→ Want user to know?
    │   └─→ Use 'allow' mode
    │       • Show brief warning
    │       • Auto-skip, reuse existing
    │       • Best for: Automation, trusted users
    │
    └─→ Don't care about duplicates?
        └─→ Disable checking
            • Set check_duplicates = false
            • No performance overhead
            • Best for: Small files, unique uploads
```

---

## Bypass Methods

### How to Upload a Duplicate File

| Mode | Method 1 | Method 2 |
|------|----------|----------|
| `allow` | Rename file | N/A (auto-skips) |
| `warn` | Click "Force Upload" button | Rename file |
| `block` | **Rename file (only option)** | N/A |

**Examples of Renaming:**
- Original: `CIBC_VISA_Oct2024.qfx`
- Renamed: `CIBC_VISA_Oct2024_v2.qfx`
- Renamed: `CIBC_VISA_Oct2024_REPROCESS.qfx`
- Renamed: `CIBC_VISA_Oct2024_2.qfx`

**Note:** Changing filename bypasses duplicate check in ALL modes (detection is based on filename + size + content)

---

## Testing Each Mode

### Test Mode: `allow`
```bash
# 1. Set config
'duplicate_action' => 'allow'

# 2. Upload file: test.qfx
# Expected: "File saved with ID: 1"

# 3. Upload SAME file again
# Expected: "⚠️ Duplicate detected! Using existing file ID: 1"
# Expected: Import button VISIBLE
```

### Test Mode: `warn`
```bash
# 1. Set config
'duplicate_action' => 'warn'

# 2. Upload file: test.qfx
# Expected: "File saved with ID: 1"

# 3. Upload SAME file again
# Expected: Warning dialog with two buttons
# Expected: No import button yet (waiting for user decision)

# 4a. Click "Force Upload"
# Expected: "File saved with ID: 2 (forced)"
# Expected: Import button VISIBLE

# 4b. Click "Cancel"
# Expected: Return to upload form
```

### Test Mode: `block`
```bash
# 1. Set config
'duplicate_action' => 'block'

# 2. Upload file: test.qfx
# Expected: "File saved with ID: 1"

# 3. Upload SAME file again
# Expected: "❌ BLOCKED: File is a duplicate..."
# Expected: Import button HIDDEN

# 4. Rename file to: test_v2.qfx
# 5. Upload renamed file
# Expected: "File saved with ID: 2"
```

---

## FAQ

**Q: What if I change the mode after files are uploaded?**  
A: Old uploads are unaffected. New mode applies to new uploads only.

**Q: Can I disable checking for specific users?**  
A: Not currently. Config applies to all users. (Future enhancement)

**Q: Does renaming bypass ALL modes?**  
A: Yes. Duplicate detection is based on original filename. Different filename = different file.

**Q: What if the file content changed but name/size are same?**  
A: MD5 hash check will detect this. Not a duplicate if content differs.

**Q: In 'warn' mode, what if user closes browser?**  
A: Session data lost. User must re-upload and decide again.

**Q: Can I log who forces duplicate uploads?**  
A: Yes. Check notifications - shows "forced upload, duplicate check bypassed". (Future: Add to database log)

**Q: Performance impact of each mode?**  
A: All modes have same performance (MD5 check). Difference is only in UI flow.

---

## Summary Cheat Sheet

| If you want... | Use this mode |
|----------------|---------------|
| No duplicate checking | `check_duplicates = false` |
| Auto-skip duplicates | `'allow'` |
| Ask user each time ⭐ | `'warn'` |
| Reject all duplicates | `'block'` |
| Bypass temporarily | Click "Force Upload" (`warn` mode) |
| Bypass permanently | Rename file (all modes) |

**Default Recommendation:**
```php
'check_duplicates' => true,
'duplicate_action' => 'warn',
'duplicate_window_days' => 90
```

This gives you:
- ✓ Duplicate detection enabled
- ✓ User awareness and control
- ✓ Flexibility to force when needed
- ✓ Protection against accidental duplicates
- ✓ 90-day window (reasonable history)
