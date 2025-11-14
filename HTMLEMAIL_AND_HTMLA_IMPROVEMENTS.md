# HtmlEmail and HtmlA Robustness Improvements

## Summary of Changes

Both `HtmlEmail` and `HtmlA` classes have been enhanced with more robust and user-friendly constructors.

## Class Hierarchy

```
HtmlElement (base class)
  └── HtmlLink (href management, params, targets)
      └── HtmlA (convenient wrapper with robust constructor)
          └── HtmlEmail (specialized for mailto: links with validation)
```

**Design Rationale**: `HtmlEmail` extends `HtmlA` because email links are just specialized anchor tags with `mailto:` URLs. This promotes code reuse and cleaner architecture.

## HtmlEmail Class

**Location**: `Views/HTML/HtmlEmail.php`  
**Extends**: `HtmlA` (which extends `HtmlLink`)

### Constructor Signature
```php
function __construct( 
    string $emailAddress, 
    $linkContent = null,  // HtmlElementInterface|string|null
    bool $validateEmail = true 
)
```

### Features

1. **Flexible Content Types** (inherited from `HtmlA`):
   - `HtmlElementInterface` → Used as-is (any HTML element: text, image, etc.)
   - `string` → Automatically wrapped in `HtmlString`
   - `null` → Uses email address as link text (convenient default)

2. **Email-Specific Features**:
   - Automatically prefixes URL with `mailto:`
   - Email validation using `filter_var(... FILTER_VALIDATE_EMAIL)`
   - Can disable validation with `$validateEmail = false` for custom formats

3. **Inherited from HtmlA/HtmlLink**:
   - All parameter handling methods: `addParam()`, `setParams()`
   - Target attribute: `setTarget()`
   - Type safety and validation

4. **Error Handling**:
   - Throws exception for invalid email (when validation enabled)
   - Throws exception for invalid content types (via parent `HtmlA`)

### Usage Examples

```php
// 1. With HtmlElementInterface
$email = new HtmlEmail("test@example.com", new HtmlString("Email Me"));

// 2. With plain string (auto-wrapped)
$email = new HtmlEmail("info@company.com", "Contact Us");

// 3. With null (email becomes link text)
$email = new HtmlEmail("support@example.com");
// Generates: <a href="mailto:support@example.com">support@example.com</a>

// 4. With query parameters (subject, body, etc.)
$email = new HtmlEmail("help@example.com", "Get Help");
$email->addParam("subject", "Support Request");
$email->addParam("body", "I need help");

// 5. Skip validation for custom formats
$email = new HtmlEmail("custom-format", "Click", false);
```

## HtmlA Class

**Location**: `Views/HTML/HtmlA.php`

### Constructor Signature
```php
function __construct( 
    string $url, 
    $linkContent = null  // HtmlElementInterface|string|null
)
```

### Features

1. **Flexible Content Types**:
   - `HtmlElementInterface` → Used as-is (text, image, div, etc.)
   - `string` → Automatically wrapped in `HtmlString`
   - `null` → Uses URL as link text (convenient default)

2. **Error Handling**:
   - Throws exception for invalid content types

### Usage Examples

```php
// 1. With HtmlElementInterface
$link = new HtmlA("https://example.com", new HtmlString("Visit Site"));

// 2. With plain string (auto-wrapped)
$link = new HtmlA("https://google.com", "Search");

// 3. With null (URL becomes link text)
$link = new HtmlA("https://github.com");
// Generates: <a href="https://github.com">https://github.com</a>

// 4. With raw HTML content
$link = new HtmlA("/page", new HtmlRawString("<strong>Bold</strong> Link"));

// 5. With query params and target
$link = new HtmlA("/search", "Results");
$link->addParam("q", "test");
$link->addParam("page", "2");
$link->setTarget("_blank");
```

## Inheritance Benefits

**HtmlEmail** inherits from **HtmlA**, which inherits from **HtmlLink**:

- ✅ `addParam(string $key, string $value)` - Add single parameter (e.g., subject, body, cc)
- ✅ `setParams(array $params)` - Set multiple parameters at once
- ✅ `setTarget(string $target)` - Set link target (_blank, _self, etc.)
- ✅ `getHtml(): string` - Generate HTML string
- ✅ Proper URL encoding via `http_build_query()`
- ✅ Type safety and validation from `HtmlA` constructor

**Design Advantage**: By extending `HtmlA`, `HtmlEmail` reuses all the robust type handling and doesn't duplicate the string/null/HtmlElementInterface logic. This follows DRY (Don't Repeat Yourself) and SRP (Single Responsibility Principle).

## Valid HTML - Content Can Be More Than Text

According to HTML5 spec, `<a>` tags can contain:
- ✅ Text (HtmlString, HtmlRawString)
- ✅ Images (`<img>` - HtmlImage)
- ✅ Inline elements (`<span>`, `<strong>`, `<em>`)
- ✅ Block elements (`<div>`, `<p>`) - HTML5 only
- ❌ **Cannot nest `<a>` tags** - This is the main restriction

Examples of valid HTML:
```html
<a href="url"><img src="icon.png"> Click here</a>
<a href="url"><div class="card">Card content</div></a>
<a href="mailto:test@example.com"><strong>Email</strong> us now!</a>
```

### Nested Link Prevention

Both `HtmlA` and `HtmlEmail` validate against **direct** nested links:

```php
// ✓ Valid - string content
$link = new HtmlA("https://example.com", "Click");

// ✓ Valid - HtmlString content  
$link = new HtmlA("https://example.com", new HtmlString("Click"));

// ✓ Valid - HtmlImage content
$link = new HtmlA("https://example.com", new HtmlImage("icon.png"));

// ✗ INVALID - Direct nested link
$inner = new HtmlA("https://inner.com", "Inner");
$outer = new HtmlA("https://outer.com", $inner);
// Throws: "Cannot nest links inside links. Nested <a> tags are not allowed in HTML."

// ✗ INVALID - Email link inside regular link
$email = new HtmlEmail("test@example.com", "Email");
$link = new HtmlA("https://example.com", $email);
// Throws: "Cannot nest links inside links..."
```

**Limitation**: The validation only catches **direct** nesting. It cannot detect links nested inside complex elements without deep tree traversal:

```php
// This won't be caught (developer responsibility):
$div = new HtmlDiv();
$div->addChild(new HtmlA("https://nested.com", "Link")); // nested inside div
$link = new HtmlA("https://outer.com", $div); // Won't throw - can't see inside div
```

**Best Practice**: Avoid putting complex container elements (HtmlDiv, HtmlTable, etc.) inside links unless you're certain they don't contain other links.

## Type Safety

Both constructors validate content types and throw descriptive exceptions:

```php
// Invalid: integer
$email = new HtmlEmail("test@example.com", 123);
// Throws: "Invalid link content type. Expected HtmlElementInterface, string, or null. Got: integer"

// Invalid: array
$link = new HtmlA("https://example.com", ['array']);
// Throws: "Invalid link content type. Expected HtmlElementInterface, string, or null. Got: array"
```

## Backward Compatibility

The changes are **100% backward compatible**:
- Existing code using `HtmlElementInterface` continues to work
- New code can use simpler string or null parameters
- Default parameter values ensure no breaking changes

## Testing

All existing tests pass:
```
BiLineItemDisplayMethodsTest: OK (12 tests, 15 assertions)
```

## Related Files

- `Views/HTML/HtmlEmail.php` - Email link class (extends HtmlA)
- `Views/HTML/HtmlA.php` - General link wrapper (extends HtmlLink)
- `Views/HTML/HtmlLink.php` - Base link class with parameter handling
- `Views/HTML/HtmlString.php` - For escaped text content
- `Views/HTML/HtmlRawString.php` - For unescaped HTML content
- `Views/HTML/HtmlElement.php` - Base HTML element class
- `class.bi_lineitem.php` - Uses HtmlLink in `makeURLLink()` method

## Architecture Notes

The inheritance hierarchy promotes:
1. **Code Reuse**: `HtmlEmail` doesn't duplicate `HtmlA`'s type handling logic
2. **Single Responsibility**: Each class has one clear purpose
3. **Open/Closed Principle**: Easy to extend without modifying base classes
4. **Liskov Substitution**: `HtmlEmail` can be used anywhere `HtmlA` is expected
5. **DRY**: Type validation logic exists in one place (`HtmlA`)
