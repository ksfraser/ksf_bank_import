<?php
/**
 * Bank Import Module Configuration
 *
 * This file contains configuration settings for the bank import module.
 * Copy this file to config.php and adjust the settings as needed.
 */

// FrontAccounting Installation Path
// This should point to the root directory of your FrontAccounting installation
// Default assumes the module is installed at FA_ROOT/modules/bank_import/
$config['fa_root'] = '../..';

// Alternative FA paths to try if the default doesn't work
// Add your FA installation paths here
$config['fa_paths'] = [
    '../..',                           // Default: up two levels
    '../../accounting',               // If FA is in accounting/ subdirectory
    '/var/www/html/infra/accounting', // Production path from error logs
    '/opt/frontaccounting',           // Common Linux installation
];

// Database settings (if different from FA)
$config['db_host'] = null;  // null = use FA settings
$config['db_name'] = null;
$config['db_user'] = null;
$config['db_pass'] = null;

// Debug mode
$config['debug'] = true;  // Set to false in production

// ---------------------------------------------------------------------------
// PDF CC Statement Reconciliation (Ollama)
// ---------------------------------------------------------------------------
// URL of your running Ollama instance (no trailing slash).
$config['ollama_base_url']         = 'http://localhost:11434';  // Required

// Optional Bearer token for authenticated Ollama deployments (null = no auth).
$config['ollama_api_key']          = null;

// HTTP timeout in milliseconds for Ollama API calls.
$config['ollama_timeout_ms']       = 30000;

// Ollama model used for PDF OCR / text extraction (reads raw pages).
$config['ollama_ocr_model']        = 'glm-ocr';

// Ollama model used for structured data extraction from the OCR text.
$config['ollama_extraction_model'] = 'gemma4';

// Minimum confidence score (0.0–1.0) for the auto-matcher to accept a pair.
// Pairs below this threshold are left unmatched for manual review.
$config['sr_match_threshold']      = 0.70;

// ±N calendar days used when cross-referencing unmatched statement lines against the
// bi_transactions staging table (REQ-009).  Must be an integer >= 0.
// Default 2 mirrors the existing hardcoded window in BiLineItemModel::get_transactions().
// This key is also read by that model so both code paths stay in sync.
$config['sr_bi_tx_date_tolerance_days'] = 2;

// Maximum absolute difference (in account currency) between the running cleared balance
// and the statement closing balance that is still treated as "balanced" for the purpose of
// enabling the Approve button and suppressing balance-mismatch / OCR-sanity warnings.
// Set to 0.00 to require an exact match.  Default 0.01 absorbs common rounding differences.
$config['sr_approve_tolerance'] = 0.01;

// How duplicate 0_bank_trans entries (same amount + date + bank account) are surfaced.
//   'alert'   — amber highlight + count notice only; no workflow impact (default).
//               Use this when legitimate duplicates are common (grocery runs, transit tickets, etc.).
//   'warning' — amber banner on review screen; Approve button is NOT disabled.
//   'confirm' — Approve button requires an explicit "I confirm duplicates are legitimate" checkbox.
$config['sr_dupe_check_mode'] = 'alert';

// Minimum score (0.0–1.0) for the bank account auto-detect service to pre-select a candidate
// on the account confirmation screen.  Scores below this show no pre-selection and require
// the user to pick manually.  Scoring rules:
//   1.0  exact suffix match (account_identifier digits == trailing digits of bank_account_number)
//   0.85 substring match
//  +0.10 bonus if OCR bank name partially matches FA bank_name
//  +0.15 bonus if a prior reconciliation session used this account_identifier → same account
$config['sr_account_match_min_score'] = 0.50;

return $config;