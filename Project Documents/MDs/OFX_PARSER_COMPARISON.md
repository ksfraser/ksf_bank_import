# OFX Parser Version Comparison & Testing

## Purpose
Compare and test three OFX parser implementations before migrating to ksf_ofxparser.

## Three Versions

### 1. **includes/vendor/asgrim/ofxparser** (CURRENTLY ACTIVE)
- **Path**: `includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser.php`
- **Used by**: `includes/qfx_parser.php` via `require_once (__DIR__ . '/vendor/autoload.php')`
- **Status**: Currently active in production
- **Notes**: Has Parser_orig-mod.php with experimental line ending fixes

### 2. **vendor/asgrim/ofxparser** (UNUSED)
- **Path**: `vendor/asgrim/ofxparser/lib/OfxParser/Parser.php`
- **Used by**: Nothing (orphaned copy)
- **Status**: Should be removed after migration
- **Notes**: Loaded by root composer but not used for OFX parsing

### 3. **lib/ksf_ofxparser** (YOUR FORK - TARGET)
- **Path**: `lib/ksf_ofxparser/lib/OfxParser/Parser.php`
- **Repository**: https://github.com/ksfraser/ksf_ofxparser
- **Used by**: Not yet integrated
- **Status**: Git submodule - needs testing and integration
- **Notes**: Fork with improvements from various maintainers

---

## Comparison Tasks

### Files to Compare
```bash
# Parser.php
includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser.php
vendor/asgrim/ofxparser/lib/OfxParser/Parser.php
lib/ksf_ofxparser/lib/OfxParser/Parser.php

# Modified versions
includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser_orig-mod.php
```

### Comparison Checklist
- [ ] Line ending handling (`convertSgmlToXml`)
- [ ] Tag closing logic (`closeUnclosedXmlTags`)
- [ ] Error handling improvements
- [ ] Bug fixes from other forks
- [ ] Performance optimizations
- [ ] Test coverage

---

## Switching Mechanism

### Option A: Config-Based Switching (Recommended)
Create `config/ofx_parser_config.php`:
```php
<?php
// OFX Parser Version Selection
// Options: 'current', 'ksf_fork', 'test'
define('OFX_PARSER_VERSION', 'current');

// Paths for each version
$ofx_parser_paths = [
    'current' => __DIR__ . '/../includes/vendor/autoload.php',
    'ksf_fork' => __DIR__ . '/../lib/ksf_ofxparser/vendor/autoload.php',
    'test' => __DIR__ . '/../includes/vendor/autoload.php'  // Can point to any test version
];
```

Update `includes/qfx_parser.php`:
```php
<?php
// Load OFX parser configuration
if (file_exists(__DIR__ . '/../config/ofx_parser_config.php')) {
    require_once(__DIR__ . '/../config/ofx_parser_config.php');
    $autoload_path = $ofx_parser_paths[OFX_PARSER_VERSION];
} else {
    // Default to current version
    $autoload_path = __DIR__ . '/vendor/autoload.php';
}

require_once($autoload_path);
include_once('includes.inc');
```

### Option B: Environment Variable
```bash
# In production
export OFX_PARSER_VERSION=current

# For testing
export OFX_PARSER_VERSION=ksf_fork
```

---

## Testing Strategy

### 1. Unit Tests
**Location**: `lib/ksf_ofxparser/tests/`

**Ensure tests cover**:
- [ ] CIBC files (line ending issues)
- [ ] Manulife files (single-line format)
- [ ] ATB files (blank lines)
- [ ] Credit card statements
- [ ] Multiple account files

### 2. Integration Tests
**Test Files**: Use real QFX/OFX files from:
- CIBC Savings/Chequing
- Walmart Credit Card
- Manulife
- ATB

**Test Script**: `tests/integration/test_ofx_parser_versions.php`

### 3. Comparison Script
Create `compare_parsers.php` to parse same file with all 3 versions and diff results.

---

## Migration Plan

### Phase 1: Comparison (Current)
1. ✅ Add ksf_ofxparser as submodule
2. Document differences between versions
3. Identify which improvements are in each version

### Phase 2: Enhancement
1. Port all improvements to ksf_ofxparser
2. Add comprehensive unit tests
3. Test with production QFX files

### Phase 3: Integration Testing
1. Switch to ksf_ofxparser via config
2. Run UAT with real imports
3. Compare results with current parser
4. Fix any issues

### Phase 4: Production Migration
1. Update qfx_parser.php to use ksf_ofxparser
2. Remove includes/vendor/asgrim
3. Remove vendor/asgrim (if not needed)
4. Update composer.json

---

## Quick Commands

### Compare Parser.php Files
```powershell
# Compare includes version vs ksf fork
Compare-Object `
  (Get-Content includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser.php) `
  (Get-Content lib/ksf_ofxparser/lib/OfxParser/Parser.php)

# Compare vendor version vs ksf fork
Compare-Object `
  (Get-Content vendor/asgrim/ofxparser/lib/OfxParser/Parser.php) `
  (Get-Content lib/ksf_ofxparser/lib/OfxParser/Parser.php)
```

### Switch Versions (After config setup)
```php
// In config/ofx_parser_config.php
define('OFX_PARSER_VERSION', 'current');    // Use includes/vendor
define('OFX_PARSER_VERSION', 'ksf_fork');   // Use lib/ksf_ofxparser
```

### Run Tests
```bash
cd lib/ksf_ofxparser
composer install
vendor/bin/phpunit
```

---

## Next Steps
1. Create config/ofx_parser_config.php
2. Update includes/qfx_parser.php to use config
3. Compare Parser.php implementations
4. Document differences
5. Create integration test suite
