<?php
/**
 * Shared Kernel - Consolidated DTOs, Entities, and Value Objects
 * 
 * This file documents the consolidation of all legacy bi_* classes into the Shared kernel.
 * Phase 0 Task 0.2: DTO Consolidation
 * 
 * LEGACY → NEW MAPPING
 * ====================
 */

// Entities (formerly bi_* classes)
namespace Ksfraser\FaBankImport\Shared\Entities;
require_once __DIR__ . '/Entities/Transaction.php';
require_once __DIR__ . '/Entities/BankStatement.php';
require_once __DIR__ . '/Entities/BankAccountMapping.php';
require_once __DIR__ . '/Entities/Counterparty.php';
require_once __DIR__ . '/Entities/PartnerKeyword.php';
require_once __DIR__ . '/Entities/TransferMatch.php';
require_once __DIR__ . '/Entities/LineItem.php';
require_once __DIR__ . '/Entities/Contact.php';
require_once __DIR__ . '/Entities/TransactionTitle.php';

/**
 * ENTITIES CONSOLIDATION MAPPING
 * 
 * Legacy Class                    → New Shared Kernel Class
 * =========================================================
 * bi_transaction                  → Transaction (with enhanced methods)
 * bi_statements_model             → BankStatement
 * bi_bank_accounts_model          → BankAccountMapping
 * bi_counterparty_model           → Counterparty
 * bi_partners_data                → PartnerKeyword
 * bi_transfer_matches_model       → TransferMatch
 * bi_lineitem                     → LineItem
 * bi_contact                      → Contact
 * bi_transactionTitle_model       → TransactionTitle
 */

// DTOs (Data Transfer Objects)
namespace Ksfraser\FaBankImport\Shared\DTOs;
require_once __DIR__ . '/DTOs/UploadFormDTO.php';
require_once __DIR__ . '/DTOs/ParseFilesDTO.php';
require_once __DIR__ . '/DTOs/ImportSummaryDTO.php';
require_once __DIR__ . '/DTOs/MappingConfirmationDTO.php';
require_once __DIR__ . '/DTOs/AccountResolutionDTO.php';
require_once __DIR__ . '/DTOs/DuplicateResolutionDTO.php';

/**
 * DTOs CONSOLIDATION MAPPING
 * 
 * Previous Location                Problem                      → New Location
 * =========================================================================================================================
 * DTOs.php (root)                 Global namespace pollution   → Shared/DTOs/UploadFormDTO.php
 * DTOs.php (root)                 Global namespace pollution   → Shared/DTOs/ParseFilesDTO.php
 * DTOs.php (root)                 Global namespace pollution   → Shared/DTOs/ImportSummaryDTO.php
 * src/Ksfraser/.../DTO/Upload*    Scattered across src/        → Shared/DTOs/UploadFormDTO.php
 * src/Ksfraser/.../DTO/Parse*     Scattered across src/        → Shared/DTOs/ParseFilesDTO.php
 * src/Ksfraser/.../DTO/Mapping*   Scattered across src/        → Shared/DTOs/MappingConfirmationDTO.php
 * src/Ksfraser/.../DTO/Account*   Scattered across src/        → Shared/DTOs/AccountResolutionDTO.php
 * src/Ksfraser/.../DTO/Duplicate* Scattered across src/        → Shared/DTOs/DuplicateResolutionDTO.php
 * src/Ksfraser/.../DTO/Import*    Scattered across src/        → Shared/DTOs/ImportSummaryDTO.php
 */

// Value Objects
namespace Ksfraser\FaBankImport\Shared\ValueObjects;
require_once __DIR__ . '/ValueObjects/PartnerData.php';

/**
 * VALUE OBJECTS CONSOLIDATION MAPPING
 * 
 * Previous Location                               → New Location
 * =====================================================================
 * src/Ksfraser/FaBankImport/Domain/ValueObjects  → Shared/ValueObjects/PartnerData.php
 */

// External Package DTOs (NOT duplicated here)
// ============================================
// Contact DTO: Use external ksfraser/contact-dto package
//   Namespace: Ksfraser\Contact\DTO\ContactData
//   Package: ksfraser/contact-dto (v0.1.0)
//   Import: use Ksfraser\Contact\DTO\ContactData;

// IMPORTANT: FA Classes
// =====================
// Classes prefixed with fa_* should NOT be consolidated here.
// They belong to the separate ksfraser/fa_classes repository.
// 
// Reference them using:
//   use Ksfraser\FA\DTO\BankAccount;  (from ksfraser/fa_classes repo)
//   use Ksfraser\FA\Models\..;
//
// Do NOT import or reference fa_* classes from this module.
// Use interface contracts instead.

return [
    'entities' => [
        'Transaction',
        'BankStatement',
        'BankAccountMapping',
        'Counterparty',
        'PartnerKeyword',
        'TransferMatch',
        'LineItem',
        'Contact',
        'TransactionTitle',
    ],
    'dtos' => [
        'UploadFormDTO',
        'ParseFilesDTO',
        'ImportSummaryDTO',
        'MappingConfirmationDTO',
        'AccountResolutionDTO',
        'DuplicateResolutionDTO',
    ],
    'valueObjects' => [
        'PartnerData',
    ],
];
