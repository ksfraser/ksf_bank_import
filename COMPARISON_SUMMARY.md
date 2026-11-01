# Comparison: Windows Dev vs Linux Production

## Key Differences Found

### 1. import_statements.php

**Linux Production has:**
- ini_set('display_errors', 1); error_reporting(E_ALL); at the top
- Mantis #2708 FileUploadService code COMMENTED OUT
- Old upload mechanism still active
- Removed emergency debug logging we added
- Removed shutdown handler

**Windows Dev (prod-bank-import-2025) has:**
- Emergency debug logging
- Shutdown handler for fatal errors  
- File upload service active

### 2. includes/qfx_parser.php

**Linux Production:**
- Direct require to includes/vendor/autoload.php
- NO config-based parser switching

**Windows Dev:**
- Config-based parser switching (config/ofx_parser_config.php)
- Can switch between parsers easily

### 3. Key Files Missing in Linux Production
- config/ directory (config-based parser switching)
- OFX_PARSER_ANALYSIS.md
- OFX_PARSER_COMPARISON.md
- compare_parsers.php
- test_*.php files
- lib/ksf_ofxparser/ submodule
- .gitmodules

## Conclusion

Linux production is using OLDER code that:
1. Does NOT have Mantis #2708 file upload service
2. Does NOT have config-based parser switching
3. Does NOT have our recent debugging improvements
4. Uses the same includes/vendor parser directly

The blank screen issue might be because Linux production
is missing error display configuration or has different PHP settings.

