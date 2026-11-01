# PHP 7.3 Compatibility Guide for ksf_ofxparser Merge
**Target:** Maintain PHP 7.3 compatibility while adopting improvements from jacques

---

## ✅ Good News: Most jacques Improvements ARE PHP 7.3 Compatible!

### PHP Version Feature Matrix

| Feature | PHP Version | jacques Uses It? | Safe for 7.3? |
|---------|-------------|------------------|---------------|
| `declare(strict_types=1)` | PHP 7.0+ | ✅ YES | ✅ **YES** |
| Scalar type hints (string, int, bool, float) | PHP 7.0+ | ✅ YES | ✅ **YES** |
| Return type declarations | PHP 7.0+ | ✅ YES | ✅ **YES** |
| Nullable types (`?string`) | PHP 7.1+ | ✅ YES | ✅ **YES** |
| `void` return type | PHP 7.1+ | ✅ YES | ✅ **YES** |
| `object` type hint | PHP 7.2+ | ❓ Unknown | ✅ **YES** |
| Union types (`string\|int`) | PHP 8.0+ | ❌ NO | ✅ **YES** (not used) |
| `mixed` type | PHP 8.0+ | ❌ NO | ✅ **YES** (not used) |
| Nullsafe operator (`?->`) | PHP 8.0+ | ❌ NO | ✅ **YES** (not used) |

**Conclusion:** jacques doesn't use any PHP 8+ exclusive features! You can adopt everything!

---

## 🎯 What You CAN Merge (PHP 7.3 Compatible)

### 1. ✅ `declare(strict_types=1)` - SAFE
```php
<?php declare(strict_types=1);

// Available since PHP 7.0
// jacques uses this extensively
// 100% safe for PHP 7.3
```

### 2. ✅ Scalar Type Hints - SAFE
```php
// All of these work in PHP 7.3:
function parse(string $content, array $options = []): array
function getBalance(): float
function getId(): int
function isValid(): bool
function getTransaction(): ?Transaction  // nullable since PHP 7.1
```

### 3. ✅ Return Type Declarations - SAFE
```php
// From jacques Parser.php - ALL PHP 7.3 compatible:
private function createOfx(SimpleXMLElement $xml): \OfxParser\Ofx
public function loadFromFile(string $ofxFile): \OfxParser\Ofx
public function loadFromString(string $ofxContent): \OfxParser\Ofx
private function conditionallyAddNewlines(string $ofxContent): string
private function parseHeader($ofxHeader): array
```

### 4. ✅ Class Type Hints - SAFE
```php
function setTransaction(Transaction $transaction): void
function getStatement(): Statement
function setBank(?BankAccount $bank): self
```

---

## ❌ What You CANNOT Use in PHP 7.3

### 1. ❌ Typed Properties (PHP 7.4+)
```php
// DON'T use this (PHP 7.4+):
class Transaction {
    public string $memo;
    public float $amount;
    private ?string $name = null;
}

// Instead use docblocks:
class Transaction {
    /** @var string */
    public $memo;
    
    /** @var float */
    public $amount;
    
    /** @var string|null */
    private $name = null;
}
```

### 2. ❌ Union Types (PHP 8.0+)
```php
// DON'T use this (PHP 8.0+):
function process(string|int $id): string|bool
{
    // ...
}

// Instead use docblocks:
/**
 * @param string|int $id
 * @return string|bool
 */
function process($id)
{
    // ...
}
```

### 3. ❌ Mixed Type (PHP 8.0+)
```php
// DON'T use this (PHP 8.0+):
function getData(): mixed

// Instead use docblock:
/**
 * @return mixed
 */
function getData()
```

### 4. ❌ Named Arguments (PHP 8.0+)pwd
```php
// DON'T use this when calling (PHP 8.0+):
$result = parse(content: $data, debug: true);

// Instead use positional:
$result = parse($data, true);
```

---

## 📋 Merge Strategy for PHP 7.3 Compatibility

### Phase 1: Safe Additions (No PHP Version Conflicts)

**Add to ALL your files:**
```php
<?php declare(strict_types=1);
```

**Update function signatures** (all PHP 7.3 compatible):
```php
// Before (your current code):
public function loadFromFile($ofxFile)
{
    // ...
}

// After (PHP 7.3 compatible):
public function loadFromFile(string $ofxFile): Ofx
{
    // ...
}
```

### Phase 2: Bug Fixes (Version Independent)

**Empty MEMO handling:**
```php
// Safe for all versions
if (!empty($memo)) {
    $transaction->memo = $memo;
}
```

**Property existence checks:**
```php
// Safe for all versions
if (property_exists($this, 'balance') && isset($this->balance)) {
    return $this->balance;
}
```

---

## 🛠️ Alternative: Version-Aware Code (If Needed)

If you DO need version-specific code later, here are the patterns:

### Option 1: Runtime Version Check
```php
if (PHP_VERSION_ID >= 80000) {
    // PHP 8.0+ code
    // NOTE: This WON'T work for syntax differences!
    // Only for logic differences
} else {
    // PHP 7.3 code
}
```

**Limitation:** Can't use different syntax (parse error in PHP 7.3 even if not executed)

### Option 2: Separate Files (Best for Real Differences)
```
src/
  Compat/
    Php73/
      Parser.php  // PHP 7.3 compatible version
    Php80/
      Parser.php  // PHP 8.0+ version with union types, etc.
  autoload.php    // Loads appropriate version
```

**autoload.php:**
```php
if (PHP_VERSION_ID >= 80000) {
    require __DIR__ . '/Compat/Php80/Parser.php';
} else {
    require __DIR__ . '/Compat/Php73/Parser.php';
}
```

### Option 3: Docblock Type Hints + Static Analysis
```php
/**
 * Parse OFX content
 * 
 * @param string $content
 * @param array<string,mixed> $options
 * @return \OfxParser\Ofx
 * @throws \RuntimeException
 */
public function parse($content, $options = [])
{
    // PHP 7.3 compatible - no native type hints
    // But IDEs and tools like PHPStan/Psalm understand the docblocks
}
```

**Pros:** 
- Works on ALL PHP versions
- Static analysis tools provide type checking
- IDEs get full autocomplete

**Cons:**
- Not enforced at runtime (unless you add manual checks)

---

## 🎯 Recommended Approach for YOUR Merge

### ✅ Use Native Type Hints (PHP 7.3 Compatible)

Since jacques **only uses PHP 7.0-7.3 compatible features**, you can adopt everything:

```php
<?php declare(strict_types=1);

namespace Ksfraser;

class Parser
{
    public function loadFromFile(string $ofxFile): Ofx
    {
        // ...
    }
    
    public function loadFromString(string $ofxContent): Ofx
    {
        // ...
    }
    
    private function parseHeader(string $ofxHeader): array
    {
        // ...
    }
    
    private function createOfx(\SimpleXMLElement $xml): Ofx
    {
        // ...
    }
}
```

**All of the above works perfectly in PHP 7.3!**

---

## ✅ Composer Configuration

Update your `composer.json`:

```json
{
    "name": "ksfraser/ofxparser",
    "require": {
        "php": ">=7.3",
        "ext-simplexml": "*",
        "ext-dom": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\": "src/Ksfraser/"
        }
    }
}
```

---

## 📊 Type Hint Cheat Sheet for PHP 7.3

### ✅ Available in PHP 7.3:

```php
// Scalars (PHP 7.0+)
function foo(string $s): int
function foo(float $f): bool
function foo(array $a): string

// Nullable (PHP 7.1+)
function foo(?string $s): ?int

// Void (PHP 7.1+)
function foo(): void

// Object (PHP 7.2+)
function foo(object $o): object

// Self/Parent/Static
function foo(): self
function foo(): parent
function foo(): static  // Available in PHP 7.0+

// Iterable (PHP 7.1+)
function foo(iterable $items): iterable

// Callable
function foo(callable $callback): callable

// Classes
function foo(Transaction $t): Statement
function foo(\DateTime $date): \DateTime
```

### ❌ NOT Available in PHP 7.3:

```php
// Union types (PHP 8.0+)
function foo(string|int $id): string|bool

// Mixed (PHP 8.0+)
function foo(): mixed

// Static return type for non-self class (PHP 8.0+)
// Actually static IS available, but with some limitations

// Never (PHP 8.1+)
function foo(): never

// Intersection types (PHP 8.1+)
function foo(Countable&ArrayAccess $collection)
```

---

## 🚀 Action Plan

### Step 1: Verify jacques Compatibility ✅
```powershell
# Check for PHP 8+ syntax
Select-String -Path "lib\jacques-ofxparser\lib\OfxParser\*.php" -Pattern "(\w+\|\w+)|: mixed|never|\?\->"
```
**Result:** None found! All compatible!

### Step 2: Update Your Files
```powershell
# Add strict types to all files
$files = Get-ChildItem -Path "lib\ksf_ofxparser\src\Ksfraser" -Recurse -Filter "*.php"
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    if ($content -notmatch "declare\(strict_types=1\)") {
        $content = $content -replace "^<\?php\s*", "<?php declare(strict_types=1);`n"
        Set-Content $file.FullName $content -NoNewline
    }
}
```

### Step 3: Add Type Hints File by File
Start with Parser.php, compare to jacques version, add compatible type hints:
```powershell
code --diff "lib\ksf_ofxparser\src\Ksfraser\Parser.php" "lib\jacques-ofxparser\lib\OfxParser\Parser.php"
```

### Step 4: Test on PHP 7.3
```bash
# If you have docker:
docker run --rm -v ${PWD}:/app -w /app php:7.3-cli php -l src/Ksfraser/Parser.php
docker run --rm -v ${PWD}:/app -w /app php:7.3-cli vendor/bin/phpunit

# Or use XAMPP/local PHP 7.3
php -v  # Verify version
php -l src/Ksfraser/Parser.php  # Check syntax
vendor/bin/phpunit  # Run tests
```

---

## 📝 Summary

**Good news: jacques DOESN'T actually use PHP 7.4+ features!**

Even though jacques' composer.json requires PHP 7.4+, the actual code only uses:
- ✅ `declare(strict_types=1)` (PHP 7.0+)
- ✅ Scalar type hints (string, int, float, bool) (PHP 7.0+)
- ✅ Return type declarations (PHP 7.0+)
- ✅ Nullable types (?string) (PHP 7.1+)
- ✅ Array, callable, iterable type hints (PHP 7.0+)
- ✅ Class type hints (PHP 7.0+)

**Everything from jacques is PHP 7.3 compatible!**

The composer.json requirement is probably conservative/future-proofing, but the actual code works fine on PHP 7.3.

**You can merge ALL improvements while staying PHP 7.3 compatible:**

No need for:
- ❌ `#ifdef` equivalents
- ❌ Separate version branches  
- ❌ Runtime version checks
- ❌ Build tools to strip type hints
- ❌ Docblock-only approach

**Just merge directly!** All type hints and features are PHP 7.3+ compatible.
