# OFX Parser Analysis Results

**Date**: 2026-01-12  
**Analysis Tools**: compare_parsers.php, test_raw_parser.php

## Summary

Compared four OFX parser implementations to identify which has the best fixes for CIBC QFX files with malformed XML (newlines within tags). Found that:

1. **includes/vendor** (11,850 bytes) - PRODUCTION, WORKING
2. **ksf_fork** (12,436 bytes) - Has same fixes but **CONTAINS CRITICAL BUGS**
3. **vendor** (3,393 bytes) - Basic version, missing fixes
4. **includes/modified** (5,508 bytes) - Experimental with str_replace fixes

## Critical Finding: ksf_fork Has Syntax Errors

While analyzing ksf_fork, discovered **syntax error** in lib/ksf_ofxparser/src/Ksfraser/Ofx.php:61:

```php
$xml = 00self::createTags($xml);  // INVALID: 00self should be just self
```

This prevents ksf_fork from running. Additional issues:
- PSR-0 autoload mapping is incorrect (files in src/Ksfraser/ but should map to src/)
- Untested with PHP 8.4.6
- May have additional bugs

## Parser Comparison Results

### Function Coverage

**includes/vendor** and **ksf_fork** have identical function lists:
- createOfx() - Factory for OFX objects
- loadFromFile() - Load from file path
- loadFromString() - Load from string content
- conditionallyAddNewlines() - Format SGML
- xmlLoadString() - XML parsing wrapper
- closeUnclosedXmlTags_preg_match() - Extra tag closer (preg_match variant)
- closeUnclosedXmlTags() - Standard tag closer
- convertSgmlToXml() - Main SGML→XML converter
- parseHeader() - OFX header parser

**vendor** is missing:
- createOfx()
- closeUnclosedXmlTags_preg_match()
- parseHeader()

**includes/modified** has:
- convertSgmlToXml_orig() - Backup of original function
- Missing same functions as vendor

### Key Method Implementations

#### convertSgmlToXml() - Critical for CIBC Files

**Group 1 (125 lines)**: includes/vendor, ksf_fork
- Most comprehensive implementation
- Handles complex SGML variations
- Likely contains fixes for CIBC line-ending issues
- **This is the working production version**

**Group 2 (13 lines)**: vendor
- Basic implementation
- Minimal SGML handling
- Missing CIBC fixes

**Group 3 (31 lines)**: includes/modified
- Experimental version
- Has str_replace approach: `$sgml = str_replace("<", "\n<", $sgml);`
- Adds newlines before all tags to normalize format

#### closeUnclosedXmlTags()

**Group 1 (21 lines)**: includes/vendor, ksf_fork
- Enhanced tag closing logic
- Handles edge cases

**Group 2 (14 lines)**: vendor, includes/modified
- Basic tag closing
- Simpler pattern matching

## Recommendations

### Short Term (Immediate)

1. **Continue using includes/vendor** (current production)
   - Proven to work with most CIBC files
   - Has 125-line convertSgmlToXml() with comprehensive fixes
   - Stable and tested

2. **Document config switching capability**
   - config/ofx_parser_config.php allows easy version switching
   - Can test alternative parsers without code changes

3. **Fix ksf_fork bugs before use**
   - Fix line 61 in Ofx.php: `00self` → `self`
   - Fix composer.json autoload (change PSR-0 to PSR-4)
   - Run full test suite to identify other issues

### Medium Term

1. **Create unit tests for CIBC files**
   - Test files with newlines in MEMO tags
   - Test files with unclosed tags
   - Regression tests for future changes

2. **Fix and validate ksf_fork**
   ```bash
   # In lib/ksf_ofxparser/
   # 1. Fix Ofx.php line 61
   # 2. Update composer.json:
   "autoload": {
       "psr-4": {
           "OfxParser\\": "src/Ksfraser/"
       }
   }
   # 3. Run tests:
   composer dump-autoload
   vendor/bin/phpunit
   ```

3. **Consolidate improvements**
   - Port includes/vendor 125-line convertSgmlToXml() to ksf_fork (if not already there)
   - Compare implementations line-by-line
   - Ensure all CIBC fixes are preserved

### Long Term

1. **Migrate to ksf_fork** (after fixes)
   - Update config/ofx_parser_config.php default to 'ksf_fork'
   - Remove includes/vendor copy
   - Remove unused vendor/ copy
   - Use single source of truth from git submodule

2. **Contribute fixes upstream**
   - If ksf_fork is your public repository, fix bugs there
   - Document CIBC-specific issues and solutions
   - Help other users facing similar problems

## File Sizes & MD5 Hashes

```
includes/vendor: 11,850 bytes (MD5: a749abf1...)
ksf_fork:        12,436 bytes (MD5: 0b8511fe...)
vendor:           3,393 bytes (MD5: 33ad9402...)
includes/modified: 5,508 bytes (MD5: d05daa6b...)
```

## Testing Infrastructure

Created tools for ongoing analysis:

1. **compare_parsers.php**
   - Compares all parser implementations
   - Extracts functions and methods
   - Shows implementation differences
   - Can test with real QFX files

2. **test_raw_parser.php**
   - Direct OFX parser testing
   - Bypasses FrontAccounting dependencies
   - Shows detailed parsing results
   - Checks for newline issues in transactions

3. **test_ksf_fork.php**
   - Tests via qfx_parser.php wrapper
   - Full integration test
   - Currently fails due to path dependencies

4. **config/ofx_parser_config.php**
   - Version switching mechanism
   - Easy A/B testing
   - Production-safe (defines constant once)

## Next Steps

1. Fix ksf_fork syntax errors
2. Run `php compare_parsers.php includes/test.qfx` to test parsing with real file
3. Compare actual parsing results between includes/vendor and ksf_fork (once fixed)
4. Create unit test suite for regression testing
5. Document CIBC-specific OFX format issues

## CIBC QFX Issues

The core problem with CIBC exports:
- Newlines appear within XML tag content: `<MEMO>value\n more</MEMO>`
- Standard XML parsers fail: "Opening and ending tag mismatch"
- The 125-line convertSgmlToXml() in includes/vendor handles this
- Need to preserve these fixes in any migration

---

*Generated by compare_parsers.php analysis on 2026-01-12*
