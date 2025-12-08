# Systematic File Replacement Tracker
# This file tracks which files we've replaced from the working version
# and why we replaced them.

## Replacement Categories:
# 1. BROKEN_FUNCTIONALITY - Files with bugs that prevent basic operation
# 2. MISSING_FEATURES - Files missing required functionality
# 3. COMPATIBILITY_ISSUES - PHP version or environment compatibility
# 4. REFACTORING_IMPROVEMENTS - Better code structure (keep if working)
# 5. NEW_FEATURES - Files with new functionality (evaluate if causing issues)

## Files Replaced:

### BROKEN_FUNCTIONALITY
# Files that had bugs preventing the application from working
- **CommandDispatcher.php**: BROKEN_FUNCTIONALITY - UTF-16 BOM encoding corruption prevented PHP parsing

### COMPATIBILITY_ISSUES
# Files with PHP 7.3 compatibility issues
- **Command classes**: COMPATIBILITY_ISSUES - Removed typed parameters for PHP 7.3 compatibility

## Current Status:
# ✅ CommandDispatcher: FIXED - Encoding issue resolved, basic instantiation works
# 🔄 Next Priority: POST action handling in process_statements.php
# 🔄 Next: Identify which of the 96 different files actually need replacement

## Process:
1. Use `replace.bat list` to see all differing files
2. Use `replace.bat diff <file>` to examine differences
3. Use `replace.bat replace <file> <category> "reason"` to replace broken files
4. Use `replace.bat commit` to commit batches of replacements
5. Test after each replacement to ensure it fixes the issue

## Important Notes:
- Only replace files that are actually broken
- Keep new features that work correctly
- Document why each file was replaced
- Test frequently to isolate which replacements fix issues