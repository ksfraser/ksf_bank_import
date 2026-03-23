# Contact Management System - Bank Import Module

Comprehensive contact creation, matching, and management integration for bank transaction processing.

## Overview

The Contact Management System provides intelligent contact linking for bank import transactions through:

- **Contact Extraction & Creation** - Automatic extraction from transaction data
- **Smart Deduplication** - Fuzzy matching with configurable thresholds
- **Intelligent Lookup** - Multi-strategy search (name, email, phone, FA IDs)
- **User-Friendly UI** - Match selection, creation, and management interfaces

## Architecture

### Backend Services

#### ContactService (`src/Ksfraser/FaBankImport/Services/ContactService.php`)
Core service for contact model operations.

**Methods:**
- `createContact(ContactData $data)` - Create new contact
- `findByName(string $name)` - Exact name lookup
- `findByEmail(string $email)` - Email search
- `findByPhone(string $phone)` - Phone search
- `findByFACustomerId(int $id)` - FA customer link
- `findByFASupplierId(int $id)` - FA supplier link
- `getAllActiveContacts(int $limit)` - Batch retrieve

#### ContactDeduplicationService (`src/Ksfraser/FaBankImport/Services/ContactDeduplicationService.php`)
Handles duplicate detection and merging logic.

**Methods:**
- `getOrCreateWithDeduplicate(ContactData $data)` - Get or create with duplicate checking
- `findDuplicates(ContactData $data, float $threshold)` - Find potential duplicates
- `findDuplicatesByNameSimilarity(string $name, float $threshold)` - Fuzzy name matching
- `calculateSimilarity(string $str1, string $str2): float` - Levenshtein-based scoring
- `normalizeName(string $name): string` - Normalize for comparison

**Similarity Calculation:**
- Exact match: 1.0
- Levenshtein distance: 70% weight
- Substring matching: 30% weight
- Default threshold: 0.85 (85%)

#### ContactDataFactory (`src/Ksfraser/FaBankImport/Services/ContactDataFactory.php`)
Factory for building ContactData DTOs from various sources.

**Methods:**
- `buildFromSupplier(array $transaction, $counterparty, $faSupplier): ContactData`
- `buildFromCustomer(array $transaction, $counterparty, $faCustomer, $faBranch): ContactData`

**Data Priority (Supplier):**
1. Transaction title/memo
2. Counterparty name
3. FA supplier name

**Data Priority (Customer):**
1. Transaction title/memo
2. Counterparty name
3. FA customer name
4. Customer branch contact name

#### ContactMatchingService (`src/Ksfraser/FaBankImport/Services/ContactMatchingService.php`)
Intelligent multi-strategy contact matching and lookup.

**Methods:**
- `searchByName(string $name, float $threshold, int $limit): array` - Fuzzy name search
- `searchByEmail(string $email, int $limit): array` - Email lookup
- `searchByPhone(string $phone, int $limit): array` - Phone lookup
- `searchByFACustomerId(int $id): ?object` - FA customer ID lookup
- `searchByFASupplierId(int $id): ?object` - FA supplier ID lookup
- `findBestMatch(ContactData $data, array $options): array` - Multi-strategy matching

**Match Strategy (Priority Order):**
1. FA Customer ID (100% confidence)
2. FA Supplier ID (100% confidence)
3. Email exact match (95% confidence)
4. Phone exact match (90% confidence)
5. Name fuzzy match (variable confidence)

**Options:**
```php
[
    'min_score' => 0.75,        // Minimum match score (0-1)
    'limit' => 1,              // Max results
    'include_inactive' => false // Show inactive contacts
]
```

### Frontend Views

#### ContactMatchSelector (`src/Ksfraser/FaBankImport/Views/ContactMatchSelector.php`)
Displays ranked contact matches for user selection.

**Features:**
- Multiple matches with confidence scores
- Match method attribution
- Radio button selection
- Visual score indicators
- Accept/Skip/Create New actions

**Usage:**
```php
$matches = $matchingService->findBestMatch($contactData, ['limit' => 5]);
$selector = new ContactMatchSelector($matches, 'Acme Corp', $transactionId);
echo $selector->getHtml();
```

**Score Colors:**
- Excellent (95%+): Green
- Very Good (85-94%): Blue
- Good (75-84%): Amber
- Fair (Below 75%): Gray

#### ContactSearchForm (`src/Ksfraser/FaBankImport/Views/ContactSearchForm.php`)
Multi-criteria contact search form.

**Features:**
- Name, email, phone, FA ID search
- Auto-detect search type
- Adjustable match sensitivity (threshold)
- Search suggestions
- Loading indicators

**Usage:**
```php
$form = new ContactSearchForm($transactionId, $searchTerm, ['Previous Search 1', 'Previous Search 2']);
echo $form->getHtml();
```

#### ContactDetailsDisplay (`src/Ksfraser/FaBankImport/Views/ContactDetailsDisplay.php`)
Detailed contact information display.

**Features:**
- Contact name, ID, reference
- Email, phone with action links
- FA system integration links
- Match context (score, method, transaction)
- Edit/history action buttons

**Usage:**
```php
$display = new ContactDetailsDisplay(
    $contact,
    ['match_score' => 0.95, 'match_method' => 'email']
);
echo $display->getHtml();
```

## Integration with Bank Import Controller

Contact linking is automatically triggered after transaction processing:

```php
// In processSupplierTransaction()
if ($payment_id) {
    // ... transaction processing ...
    
    // Extract and link contact
    $contactData = $this->buildSupplierContactData($this->trz);
    if ($contactData) {
        $contactId = $this->createOrLinkContact($contactData);
        if ($contactId) {
            $this->linkTransactionToContact($this->tid, $contactId);
            display_notification('Contact linked: ID ' . $contactId);
        }
    }
}
```

## Data Flow

### Transaction Processing with Contact Linking

```
Bank Transaction (QFX/QIF/MT940/CSV)
    ↓
BiLineItem created
    ↓
User selects processing method (SP/CU/QE/MA/ZZ)
    ↓
Transaction processed:
    - GL entries created
    - FA transaction recorded
    - Match details stored
    ↓
Contact Extraction:
    - Extract name from memo/title
    - Get email, phone from counterparty
    - Gather FA reference if available
    ↓
Contact Deduplication:
    - Check for exact name match
    - Search for fuzzy name similarity
    - Check for email/phone match
    - Look up FA IDs
    ↓
Decision:
    ├─ Exact match found → Link automatically
    ├─ Multiple fuzzy matches → Show user selector
    └─ No matches → Create new or skip
    ↓
Link to Transaction:
    - Update bi_transactions.contact_id
    - Store match metadata
    ↓
Display Confirmation
```

### Search & Match Flow

```
User Search Input
    ↓
ContactMatchingService::findBestMatch()
    ├─ Check FA IDs (priority 1)
    ├─ Check Email (priority 2)
    ├─ Check Phone (priority 3)
    └─ Fuzzy name search (priority 4)
    ↓
Score & Rank Results
    ↓
ContactMatchSelector displays results
    ↓
User selects match
    ↓
Link to transaction
```

## Usage Examples

### Basic Contact Linking
```php
// In controller after transaction processing
$contactData = ContactDataFactory::buildFromSupplier($transactionData);
$dedupeService = new ContactDeduplicationService($contactService);
$contact = $dedupeService->getOrCreateWithDeduplicate($contactData);

if ($contact && !empty($contact->id)) {
    $this->linkTransactionToContact($transactionId, $contact->id);
}
```

### Advanced Search with UI
```php
// In AJAX endpoint
$matchingService = new ContactMatchingService($dedupeService, $contactService);

$contactData = new ContactData();
$contactData->name = $_POST['contact_search'];
$contactData->email = $_POST['contact_email'] ?? null;

$matches = $matchingService->findBestMatch($contactData, [
    'min_score' => (int)$_POST['threshold'] / 100,
    'limit' => 10
]);

$selector = new ContactMatchSelector($matches, $_POST['contact_search'], $transactionId);

echo json_encode([
    'success' => true,
    'matches' => $matches,
    'html' => $selector->getHtml(),
    'count' => count($matches)
]);
```

### Batch Processing
```php
// Process multiple transactions for contacts
$transactions = getUnprocessedTransactions();
$matchingService = new ContactMatchingService($dedupeService, $contactService);

foreach ($transactions as $trans) {
    $contactData = ContactDataFactory::buildFromSupplier($trans);
    $matches = $matchingService->findBestMatch($contactData, ['limit' => 1]);
    
    if (!empty($matches)) {
        $contactId = $matches[0]['contact']->id;
        linkTransactionToTransaction($trans['id'], $contactId);
    }
}
```

## CSS & JavaScript

### Stylesheet
File: `src/Ksfraser/FaBankImport/Views/contact-management.css`

Includes:
- Search form styling
- Match selector cards
- Contact details display
- Responsive design (mobile-optimized)
- Accessible color contrasts
- Smooth animations

**Key Classes:**
- `.contact-search-form` - Search container
- `.matches-container` - Match results list
- `.match-card` - Individual match item
- `.contact-details-display` - Details panel
- `.btn-primary/.btn-secondary/.btn-outline` - Button styles

### JavaScript Handlers
File: `src/Ksfraser/FaBankImport/Views/contact-management.js`

**Public Functions:**
- `contactSearchSubmit(form, transactionId)` - Form submission
- `contactMatchAccept(transactionId, inputName)` - Accept match
- `contactMatchSkip(transactionId)` - Skip matching
- `contactMatchCreateNew(transactionId)` - Create new contact
- `linkContactToTransaction(transactionId, contactId)` - Link contact
- `showNotification(message, type, duration)` - Toast message

## Configuration

### Similarity Thresholds
Configure in application settings or pass per-request:

```php
// Default: 0.85 (85% match required)
$dedupeService->setMatchThreshold(0.75); // More lenient
```

### Search Limits
```php
// Default: 10 results per search
$matches = $matchingService->searchByName('Acme', 0.75, 20); // Get 20 results
```

### Match Strategy Priorities
Configured in `ContactMatchingService::findBestMatch()`:
1. FA IDs (1.0 score)
2. Email (0.95 score)
3. Phone (0.90 score)
4. Name fuzzy (variable score)

## Testing

Comprehensive unit tests in `tests/Unit/Services/ContactMatchingServiceTest.php`:
- 18 test methods
- Mock ContactService and ContactDeduplicationService
- Covers all search strategies
- Tests result limiting and threshold enforcement

**Run tests:**
```bash
php vendor/bin/phpunit tests/Unit/Services/ContactMatchingServiceTest.php
```

## Error Handling

All services include error handling:

```php
try {
    $contact = $dedupeService->getOrCreateWithDeduplicate($contactData);
    if (!$contact) {
        error_log('Contact creation failed: ' . json_encode($contactData));
    }
} catch (\Throwable $e) {
    error_log('Contact service error: ' . $e->getMessage());
    // Gracefully continue processing without contacts
}
```

## Performance Considerations

- **Caching**: Consider caching `getAllActiveContacts()` result
- **Batch Operations**: Use `batchFindMatches()` for multiple contacts
- **Search Limits**: Default 500 contacts scanned per search
- **DB Queries**: Each search strategy makes 1-2 queries

**Optimization Tips:**
- Index contact.name, contact.email, contact.phone
- Limit search scope with date ranges if available
- Cache deduplication threshold settings

## Security Notes

- HTML escape all user input in views
- Validate email addresses and phone numbers
- Implement rate limiting on AJAX endpoints
- Verify user permissions before modifying contacts
- Log all contact creations and linkages
- Sanitize search terms for database queries

## Future Enhancements

1. **Machine Learning Matching** - Train model on historical matches
2. **Contact Merge UI** - Visual interface for merging duplicate contacts
3. **Bulk Operations** - Import/export contact matching rules
4. **Custom Fields** - Support additional contact attributes
5. **Webhook Notifications** - Notify external systems of new contacts
6. **Audit Trail** - Full history of contact modifications
7. **API Endpoints** - RESTful contact management API

## File Structure

```
src/Ksfraser/FaBankImport/
├── Services/
│   ├── ContactService.php
│   ├── ContactDeduplicationService.php
│   ├── ContactDataFactory.php
│   ├── ContactMatchingService.php
│   └── ...
├── Views/
│   ├── ContactMatchSelector.php
│   ├── ContactSearchForm.php
│   ├── ContactDetailsDisplay.php
│   ├── contact-management.css
│   └── contact-management.js
└── ...

tests/Unit/Services/
└── ContactMatchingServiceTest.php
```

## Support & Troubleshooting

**Issue: No contacts found on search**
- Check threshold setting (too high?)
- Verify contacts exist in database
- Check search term formatting

**Issue: Duplicates not being detected**
- Lower similarity threshold
- Check name normalization (case, punctuation)
- Verify email/phone exact matching works

**Issue: Performance degradation**
- Reduce `getAllActiveContacts()` limit
- Add database indexes
- Cache search results

**Issue: UI not loading**
- Verify CSS/JS file paths included
- Check browser console for JavaScript errors
- Ensure HtmlElementInterface implementation

## License

MIT - See LICENSE.txt

## Author

Kevin Fraser - 2025
