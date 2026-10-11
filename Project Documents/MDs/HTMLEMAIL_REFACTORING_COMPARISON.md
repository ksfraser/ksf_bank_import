# HtmlEmail Refactoring: Before vs After

## Before (Extended HtmlLink)

```php
class HtmlEmail extends HtmlLink
{
    function __construct( string $emailAddress, $linkContent = null, bool $validateEmail = true )
    {
        // Validate email
        if( $validateEmail && !filter_var( $emailAddress, FILTER_VALIDATE_EMAIL ) )
        {
            throw new \Exception( "Invalid email address: $emailAddress" );
        }
        
        // DUPLICATE TYPE HANDLING (same as HtmlA)
        if( $linkContent === null )
        {
            $content = new HtmlString( $emailAddress );
        }
        elseif( is_string( $linkContent ) )
        {
            $content = new HtmlString( $linkContent );
        }
        elseif( $linkContent instanceof HtmlElementInterface )
        {
            $content = $linkContent;
        }
        else
        {
            throw new \Exception( "Invalid link content type..." );
        }
        
        // Initialize parent
        parent::__construct( $content );
        
        // Set mailto href
        $mailtoUrl = "mailto:" . $emailAddress;
        $this->addHref( $mailtoUrl );
    }
}
```

**Issues**:
- ❌ Duplicates all the type handling logic from `HtmlA`
- ❌ Violates DRY principle
- ❌ More lines of code (30+ lines)
- ❌ Harder to maintain (type logic in two places)
- ❌ Less clear inheritance hierarchy

---

## After (Extends HtmlA)

```php
class HtmlEmail extends HtmlA
{
    function __construct( string $emailAddress, $linkContent = null, bool $validateEmail = true )
    {
        // Validate email address if requested
        if( $validateEmail && !filter_var( $emailAddress, FILTER_VALIDATE_EMAIL ) )
        {
            throw new \Exception( "Invalid email address: $emailAddress" );
        }
        
        // Build mailto URL
        $mailtoUrl = "mailto:" . $emailAddress;
        
        // If no content provided, use email address as link text
        if( $linkContent === null )
        {
            $linkContent = $emailAddress;
        }
        
        // Call parent HtmlA constructor - it handles string/HtmlElementInterface/null
        parent::__construct( $mailtoUrl, $linkContent );
    }
}
```

**Benefits**:
- ✅ No duplicated type handling logic
- ✅ Follows DRY principle
- ✅ Much shorter (15 lines vs 30+)
- ✅ Type validation in one place (`HtmlA`)
- ✅ Clear semantic hierarchy: Email IS-A specialized link
- ✅ Easier to maintain and test

---

## Inheritance Hierarchy

### Before
```
HtmlElement
  └── HtmlLink
      ├── HtmlA (handles types)
      └── HtmlEmail (handles types again - DUPLICATE)
```

### After
```
HtmlElement
  └── HtmlLink
      └── HtmlA (handles types)
          └── HtmlEmail (adds email-specific validation)
```

---

## Code Reduction

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Lines of code | ~35 | ~20 | -43% |
| Type handling logic | Duplicated | Reused | ✅ DRY |
| Maintainability | Two places | One place | ✅ Better |
| Semantic clarity | Unclear | Clear | ✅ Email IS-A Link |

---

## What HtmlEmail Now Does

1. **Validates** email address (if requested)
2. **Prepends** `mailto:` to the URL
3. **Delegates** everything else to `HtmlA`:
   - Type handling (string/HtmlElementInterface/null)
   - Type validation
   - Parent initialization
   - Error messages

---

## Architecture Win

This refactoring demonstrates good OOP principles:

- **Single Responsibility**: `HtmlEmail` only handles email-specific concerns
- **Open/Closed**: Extended `HtmlA` without modifying it
- **Liskov Substitution**: `HtmlEmail` can be used anywhere `HtmlA` is expected
- **DRY**: No duplicated code
- **Composition over Inheritance**: Actually, proper inheritance here!

---

## User Impact

**Zero breaking changes** - all existing code works exactly the same:

```php
// Still works
$email = new HtmlEmail("test@example.com", new HtmlString("Email Me"));

// Still works
$email = new HtmlEmail("info@company.com", "Contact Us");

// Still works
$email = new HtmlEmail("support@example.com");

// Still works
$email->addParam("subject", "Help");
```

**But now the code is cleaner, more maintainable, and follows better OOP design!**
