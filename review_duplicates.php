<?php

/**
 * Duplicate Transactions Review Dashboard
 * 
 * URL: /modules/bank_import/review_duplicates.php
 * Purpose: Main page for reviewing and resolving flagged duplicate transactions
 * 
 * Features:
 * - Filter duplicates by status, match type, bank account, date range
 * - Side-by-side transaction comparison
 * - Confirm/reject/update duplicate status
 * - Audit trail and notes
 * - Pagination (20 per page)
 */

require_once __DIR__ . '/vendor/autoload.php';

// Set up FA context
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = dirname(__DIR__, 3);  // Go up to FA root

// Load FA includes
include_once($path_to_root . '/includes/session.inc');
include_once($path_to_root . '/includes/ui/ui_input.inc');
include_once($path_to_root . '/includes/ui/ui_lists.inc');
include_once($path_to_root . '/includes/ui/ui_globals.inc');
include_once($path_to_root . '/includes/ui/ui_controls.inc');

// Load bank import config
require_once(__DIR__ . '/config.php');

use Ksfraser\FaBankImport\Views\DuplicateReview\DuplicateReviewView;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateReviewHandler;

// Verify access
$user = $_SESSION['wa_current_user'] ?? null;
if (!$user) {
    redirect_to_login();
}

// Parse query filters
$filters = [
    'status' => sanitize_input($_GET['status'] ?? 'PENDING'),
    'match_type' => sanitize_input($_GET['match_type'] ?? ''),
    'acctid' => sanitize_input($_GET['acctid'] ?? ''),
    'date_from' => sanitize_input($_GET['date_from'] ?? ''),
    'date_to' => sanitize_input($_GET['date_to'] ?? ''),
    'page' => (int)($_GET['page'] ?? 1)
];

// Handle AJAX actions
if (!empty($_POST['action'])) {
    handle_duplicate_action();
    exit;
}

// Load and render duplicates
$view = new DuplicateReviewView();
$view->loadDuplicates($filters);

// Start page
page_run();

function handle_duplicate_action(): void
{
    $action = sanitize_input($_POST['action'] ?? '');
    $dupeId = (int)($_POST['dupe_id'] ?? 0);
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    if ($dupeId <= 0) {
        json_error('Invalid duplicate ID');
        return;
    }
    
    $handler = new DuplicateReviewHandler();
    $user = $_SESSION['wa_current_user']->username ?? 'unknown';
    
    try {
        switch ($action) {
            case 'confirm':
                $handler->updateReviewStatus(
                    $dupeId,
                    'CONFIRMED_DUPE',
                    $user,
                    $notes
                );
                json_success('Duplicate confirmed');
                break;
                
            case 'move':
                $handler->updateReviewStatus(
                    $dupeId,
                    'MOVED_TO_STATEMENT',
                    $user,
                    $notes
                );
                json_success('Transaction moved to statement');
                break;
                
            case 'reject':
                $handler->updateReviewStatus(
                    $dupeId,
                    'REJECTED',
                    $user,
                    $notes
                );
                json_success('Duplicate rejected');
                break;
                
            default:
                json_error('Unknown action: ' . $action);
        }
    } catch (\Exception $e) {
        json_error('Error updating duplicate: ' . $e->getMessage());
    }
}

function sanitize_input(string $input): string
{
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function json_error(string $message): void
{
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
}

function json_success(string $message): void
{
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
}

function page_run(): void
{
    global $path_to_root, $view;
    
    // Output page HTML
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Duplicate Transactions Review</title>
        <style>
            <?php include(__DIR__ . '/styles/duplicate-review.css'); ?>
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <header>
                <h1>Duplicate Transactions Review</h1>
                <p>Review and resolve flagged duplicate bank transactions</p>
            </header>
            
            <main class="content">
                <?php echo $view->render(); ?>
            </main>
        </div>
        
        <script>
            <?php include(__DIR__ . '/js/duplicate-review.js'); ?>
        </script>
    </body>
    </html>
    <?php
}

function redirect_to_login(): void
{
    global $path_to_root;
    header('Location: ' . $path_to_root . '/index.php?logout=1');
    exit;
}
