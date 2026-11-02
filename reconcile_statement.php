<?php
/**
 * reconcile_statement.php
 *
 * FrontAccounting module entry point for the PDF CC Statement Reconciliation feature.
 *
 * Bootstrap flow mirrors process_statements.php:
 *   1. chdir to module root
 *   2. Load config.php (falls back to defaults)
 *   3. Resolve $path_to_root to the FA installation
 *   4. Include FA session / UI stubs
 *   5. Autoload Composer classes
 *   6. Build PDO from config or FA constants
 *   7. Wire up repositories, commit service, view, controller
 *   8. Dispatch to controller
 *
 * @package Ksfraser\FaBankImport
 * @author  Kevin Fraser
 */

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView;
use Ksfraser\FaBankImport\StatementReconcile\Application\ReconciliationCommitService;
use Ksfraser\FaBankImport\StatementReconcile\Application\StatementReconcileController;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoReconciliationSessionRepository;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoStatementOcrRepository;

// ---------------------------------------------------------------------------
// 1. Ensure relative paths resolve from THIS directory (FA module root)
// ---------------------------------------------------------------------------
chdir(__DIR__);

// ---------------------------------------------------------------------------
// 2. Load module config
// ---------------------------------------------------------------------------
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    $config = include $config_file;
} else {
    $config = [
        'fa_root'   => '../..',
        'fa_paths'  => ['../..', '../../accounting', '/var/www/html/infra/accounting'],
        'debug'     => true,
        'db_host'   => null,
        'db_name'   => null,
        'db_user'   => null,
        'db_pass'   => null,
    ];
}

// ---------------------------------------------------------------------------
// 3. Resolve $path_to_root (must be a web-relative path, never absolute FS)
// ---------------------------------------------------------------------------
$path_to_root = (string) ($config['fa_root'] ?? '../..');

$fa_session_inc = $path_to_root . '/includes/session.inc';
if (!file_exists($fa_session_inc)) {
    $found = false;
    foreach ((array) ($config['fa_paths'] ?? []) as $test_path) {
        // Skip absolute paths – FA uses $path_to_root as a URL prefix too.
        if (preg_match('/^[A-Za-z]:\\\\|^\//', (string) $test_path)) {
            continue;
        }
        if (file_exists($test_path . '/includes/session.inc')) {
            $path_to_root = $test_path;
            $found        = true;
            break;
        }
    }
    if (!$found) {
        if (!empty($config['debug'])) {
            die(
                'ERROR: FrontAccounting not found. '
                . 'Please check fa_root / fa_paths in config.php. '
                . 'Tried: ' . implode(', ', (array) ($config['fa_paths'] ?? []))
            );
        }
        die('System configuration error. Please contact administrator.');
    }
}

// ---------------------------------------------------------------------------
// 4. FA page security and includes
//    SA_BANKACCOUNT covers bank / statement operations – same as process_statements uses SA_SALESTRANSVIEW;
//    use whichever constant your FA install grants the intended users.
// ---------------------------------------------------------------------------
$page_security = 'SA_BANKACCOUNT';

include_once(__DIR__ . '/vendor/autoload.php');

include_once($path_to_root . '/includes/date_functions.inc');
include_once($path_to_root . '/includes/session.inc');    // starts FA session, defines DB constants
include_once($path_to_root . '/includes/ui/ui_input.inc');
include_once($path_to_root . '/includes/ui/ui_lists.inc');
include_once($path_to_root . '/includes/ui/ui_globals.inc');
include_once($path_to_root . '/includes/ui/ui_controls.inc');
include_once($path_to_root . '/includes/data_checks.inc');

// ---------------------------------------------------------------------------
// 5. FA page header (renders <html><head>... and navigation chrome)
//    The page() call must come AFTER session.inc.
// ---------------------------------------------------------------------------
$help_context = 'Reconcile CC Statement';
$js           = function_exists('get_js_date_picker') ? get_js_date_picker() : '';
page(_($help_context), false, false, '', $js);

// ---------------------------------------------------------------------------
// 6. Build PDO connection
//    Prefer explicit db_* keys in our config.php.
//    Fall back to FA's DB_ constants (defined by FA's session.inc / config.php).
// ---------------------------------------------------------------------------
$db_host = (string)  ($config['db_host'] ?? (defined('DB_HOST')     ? DB_HOST     : ''));
$db_name = (string)  ($config['db_name'] ?? (defined('DB_DATABASE') ? DB_DATABASE : ''));
$db_user = (string)  ($config['db_user'] ?? (defined('DB_USER')     ? DB_USER     : ''));
$db_pass = (string)  ($config['db_pass'] ?? (defined('DB_PASSWORD') ? DB_PASSWORD : ''));

if ($db_host === '' || $db_name === '' || $db_user === '') {
    display_error(
        'Database connection details are not configured for the reconciliation module. '
        . 'Add db_host, db_name, db_user, db_pass to config.php or ensure the FA '
        . 'DB constants (DB_HOST, DB_DATABASE, DB_USER, DB_PASSWORD) are defined.'
    );
    end_page();
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4',
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (\PDOException $e) {
    if (!empty($config['debug'])) {
        display_error('DB connection failed: ' . htmlspecialchars($e->getMessage()));
    } else {
        display_error('Database connection error. Please contact administrator.');
    }
    end_page();
    exit;
}

// ---------------------------------------------------------------------------
// 7. Wire up repositories, services, view, controller
// ---------------------------------------------------------------------------
$ocrRepo       = new PdoStatementOcrRepository($pdo);
$sessionRepo   = new PdoReconciliationSessionRepository($pdo);
$commitService = new ReconciliationCommitService($sessionRepo);
$view          = new ReconcileView();

$controller = new StatementReconcileController(
    $view,
    $ocrRepo,
    $sessionRepo,
    $commitService,
    $config
);

// ---------------------------------------------------------------------------
// 8. Dispatch
//    FA's $user->name is an integer user ID in some versions; cast safely.
// ---------------------------------------------------------------------------
$action = trim((string) ($_POST['action'] ?? ''));
$userId = isset($user) ? (int) $user->name : 0;

$controller->handle($action, $userId);

// ---------------------------------------------------------------------------
// 9. FA page footer
// ---------------------------------------------------------------------------
end_page();
