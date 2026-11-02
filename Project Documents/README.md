bank_import
===========

FA bank import

The aim of this module is to be able import and process bank statements into FrontAccounting.

This started out as XXX's module.  I've enhanced to import QFX/OFX files as well as Walmart Mastercard CSVs.

The original version only used Band Deposits and Bank Payments.  I've enhanced to also include:
	Display possible matching transactions - same date and dollar amount.  SCORE the match
	matching against existing transactions
	Manually matching 
	Create a Supplier Payment
	Create a Customer Payment
	Create a Bank Transfer (i.e. pay the CC from the bank account)
	Create a Vendor from the other party (i.e. merchants in a CC upload)
	Create a Customer from the other party (i.e. a customer that deposited (a.k.a. e-transfer) into your bank account.

## NEW: Paired Transfer Processing (v1.0.0)

**As of January 2025**, the bank import module has been significantly refactored to support **automatic paired transfer processing** using clean SOLID architecture and PSR standards.

### Key Features

- ✅ **Automatic Bank Transfer Creation** - Detects paired transactions (e.g., Manulife → CIBC) and creates bank transfers in FrontAccounting
- ✅ **Smart Direction Analysis** - Analyzes debit/credit codes to determine correct FROM/TO accounts
- ✅ **Handler Auto-Discovery** - Zero-configuration extensibility via filesystem-based handler registration
- ✅ **Reference Number Service** - Eliminated code duplication with centralized unique reference generation
- ✅ **Configurable Transaction Logging** - Type-safe configuration management via BankImportConfig
- ✅ **Fine-Grained Exception Handling** - HandlerDiscoveryException with named constructors for precise error reporting
- ✅ **Session Caching** - 95% performance improvement via vendor list and operation types caching
- ✅ **Plugin Architecture** - Extensible operation types system
- ✅ **100% Test Coverage** - 79 unit tests, 146 assertions, 100% passing
- ✅ **PSR Compliant** - Follows PSR-1, PSR-2, PSR-4, PSR-5, PSR-12 standards

### Documentation

#### User & Technical Documentation
- **[docs/USER_GUIDE.md](docs/USER_GUIDE.md)** - Complete end-user guide for paired transfer processing
- **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** - Technical architecture and system design (updated October 2025)
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Production deployment instructions
- **[SETUP.md](SETUP.md)** - First-time setup and dependency management
- **[UML_DIAGRAMS.md](UML_DIAGRAMS.md)** - System architecture diagrams

#### Recent Feature Documentation (October 2025)
- **[docs/REQUIREMENTS_RECENT_FEATURES.md](docs/REQUIREMENTS_RECENT_FEATURES.md)** - Requirements for FR-048 through FR-051
- **[docs/OCTOBER_2025_IMPLEMENTATION_SUMMARY.md](docs/OCTOBER_2025_IMPLEMENTATION_SUMMARY.md)** - Complete implementation summary
- **[docs/YOUR_REQUEST_COMPLETE.md](docs/YOUR_REQUEST_COMPLETE.md)** - Documentation package completion status
- **[docs/ACTION_PLAN.md](docs/ACTION_PLAN.md)** - Remaining tasks and priorities

#### BABOK-Compliant Business Analysis Documentation
- **[docs/BUSINESS_REQUIREMENTS_DOCUMENT.md](docs/BUSINESS_REQUIREMENTS_DOCUMENT.md)** - Business case, objectives, and stakeholder analysis
- **[docs/REQUIREMENTS_SPECIFICATION.md](docs/REQUIREMENTS_SPECIFICATION.md)** - 47 numbered requirements (FR, NFR, IR, DR, BR) with acceptance criteria
- **[docs/REQUIREMENTS_TRACEABILITY_MATRIX.csv](docs/REQUIREMENTS_TRACEABILITY_MATRIX.csv)** - Bidirectional traceability between requirements, design, code, and tests
- **[docs/USE_CASE_SPECIFICATIONS.md](docs/USE_CASE_SPECIFICATIONS.md)** - 8 detailed use cases with actors, flows, and business rules
- **[docs/QA_TEST_PLAN.md](docs/QA_TEST_PLAN.md)** - Comprehensive QA strategy, test levels, and quality metrics
- **[docs/UAT_PLAN.md](docs/UAT_PLAN.md)** - 30 UAT test scenarios with acceptance criteria and sign-off process
- **[docs/INTEGRATION_TEST_PLAN.md](docs/INTEGRATION_TEST_PLAN.md)** - 30 integration test cases covering database, services, and FrontAccounting
- **[docs/CHANGE_MANAGEMENT_PLAN.md](docs/CHANGE_MANAGEMENT_PLAN.md)** - Change control process, approval matrix, and templates

#### Test & Integration Documentation
- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Integration test details
- **[TEST_RESULTS_SUMMARY.md](TEST_RESULTS_SUMMARY.md)** - Test coverage and results
- **[PROJECT_COMPLETION_SUMMARY.md](PROJECT_COMPLETION_SUMMARY.md)** - Complete refactoring summary

> **BABOK Compliance Note:** This project includes comprehensive business analysis work products following the BABOK® Guide v3 standards, including complete requirements traceability, use case specifications, quality assurance planning, and change management processes.

### Quick Start

1. **Install dependencies:** `composer install` (automated via Git hooks - see [SETUP.md](SETUP.md))
2. **Import statements:** Go to Banking → Import Bank Statements
3. **Configure settings (optional):** Go to Banking → Bank Import Settings
   - Enable/disable transaction reference logging
   - Set GL account for reference logging (default: 0000)
4. **Process paired transfers:**
   - Find two related transactions (debit from account A, credit to account B, same date ±2 days)
   - Select "Process Both Sides" from the dropdown
   - Click "Process" button
   - System automatically creates bank transfer in FrontAccounting

See **[docs/USER_GUIDE.md](docs/USER_GUIDE.md)** for detailed instructions with screenshots and examples.

### PHP Compatibility

- **Minimum:** PHP 7.4
- **Recommended:** PHP 7.4 or 8.0+
- **Tested:** PHP 7.4 compatible (union types removed, proper type hints maintained)

### Configuration

The module provides a centralized configuration system via the `BankImportConfig` class:

```php
use KsfBankImport\Configuration\BankImportConfig;

// Enable transaction reference logging
BankImportConfig::setTransRefLoggingEnabled(true);

// Set GL account for reference logging
BankImportConfig::setTransRefAccount('1550');

// Check if logging is enabled
$enabled = BankImportConfig::getTransRefLoggingEnabled(); // returns bool

// Get all current settings
$settings = BankImportConfig::getAllSettings(); // returns associative array

// Reset to defaults
BankImportConfig::resetToDefaults();
```

**UI Configuration:**
- Go to **Banking → Bank Import Settings** in FrontAccounting
- Enable/disable transaction reference logging
- Select GL account for reference logging (default: 0000)
- Save settings or reset to defaults

### Recent Changes (October 2025)

**v1.1.0 - October 21, 2025** - Code Quality & Extensibility Enhancements
- ✅ **Handler Auto-Discovery** (FR-049): Zero-configuration handler registration via filesystem scanning
  - Eliminates manual registration code
  - Automatically discovers classes implementing `TransactionHandlerInterface`
  - Performance: ~50ms to discover 6 handlers
  - 14 unit tests, 44 integration tests passing

- ✅ **ReferenceNumberService** (FR-048): Eliminated code duplication
  - Consolidated 18 duplicate lines across 3 handlers
  - Single method: `getUniqueReference(int $transType): string`
  - 8 unit tests with mock FA functions

- ✅ **BankImportConfig** (FR-051): Type-safe configuration management
  - Centralized settings storage in FA company preferences
  - Static API with boolean/string getters and setters
  - 20 unit tests covering all scenarios
  - UI integration in `bank_import_settings.php`

- ✅ **HandlerDiscoveryException** (FR-050): Fine-grained error handling
  - Named constructors for specific error scenarios
  - Clear exception messages with class names and reasons
  - Integrated into handler discovery process
  - 7 unit tests covering all error paths

**Test Coverage:**
- 79 unit tests, 146 assertions, 100% passing
- 44 integration tests passing
- ~98% code coverage for new features

**Documentation:**
- Complete requirements specification (FR-048 through FR-051)
- Updated architecture documentation with October 2025 enhancements
- Traceability matrix updated with 10 new requirements
- Comprehensive implementation summary (600+ lines)

See **[docs/OCTOBER_2025_IMPLEMENTATION_SUMMARY.md](docs/OCTOBER_2025_IMPLEMENTATION_SUMMARY.md)** for complete details.


INSTALLATION
------------
1. Extract the archive or copy all files into /modules/bank_import folder.
2. Install/activate module from FrontAccount as you do with other modules
3. after installation, 4 new menu links will appear into "Banking and General Ledger" section:
- Transaction/Process Bank Statements
- Inquiry/Bank Statemens Inquiry
- Maintenance/Import Bank Statements
- Maintenance/Manage Partners Bank Accounts

USAGE
-----
1. import one or more statements using the Import Bank Statements link
- select the correct format for your file
- check the output for any errors

2. process each transaction with Process Bank Statements link
- you will be presented a list of transactions with all the transaction details
- you have the option to process each transaction as a Customer Deposit, a Supplier payment, Manual settlement  or a Quick Entry (you will have to define Quick Entries as needed)
- after pressing "process", the transaction will be recorded into FA and the bank transaction will be marked as "settled"
- if some human error occurs, by voiding the FA transaction, the corresponding bank transaction is "unsettled" as well and becomes "processable" again


FOR DEVELOPERS
--------------
The module has two parts:
- a bank statement parser and importer
- the required frontend screens for transaction processing

The module uses MT940 bank statement format for keeping transactions - all the fields from MT940 format are mapped in 2 database tables.
The parser/importer sub-functions parse either a MT940 .STA file, either a .CSV file, mapping all parsable fields from .CSV into MT940 fields.

Apparently, each bank implements MT940 with some variations from standard. As such, a base MT940 parser has been developed, plus an additional parser that extends and modifies the base parser
This is adapted for Romanian BRD bank (named "RO-BRD-MT940")

As with the CSV files, so far I implemented for two banks (RO-BCR and RO-ING). Obviously, their format is different, as well as with the data contained.
There is no recipe here, just map the CSV fields onto MT940 fields as you can.

WARNING: normally, each transaction has been assigned an unique transaction id. In some CSV files, the transaction identfier is missing.
So you have to be creative enough to create a transaction id string (eg. date + amount = transaction id string)


FILES and CLASSES
-----------------
banking.inc - contains base MT940 transaction and statement classes
parser.inc - contains base class for a parser
mt940_parser.php - contains the base MT940 parser class
ro_brd_mt940_parser.php - contains the specific mt940 parser for BRD bank
ro_wmmc_csv_parser.php - contains the specific parser for Walmart Mastercard (Canada) 
qfx_parser.php - contains the specific parser for QFX/OFX files.
parsers.inc - contains the "getParsers" function, used by UI to control what is presented to the user. self explanatory


test_parser.php - a CLI executable that allows testing and development
