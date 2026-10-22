<?php

declare(strict_types=1);

/**
 * Generic driver for the procedural bank-import screens.
 *
 * The configuration is handed over as JSON by ScreenProcess:
 *
 *  - script  absolute path of the screen to execute;
 *  - post    the $_POST superglobal;
 *  - get     the $_GET superglobal;
 *  - session the $_SESSION superglobal;
 *  - files   the $_FILES['files'] entries (name/tmp_name/error);
 *  - db_rows the result sets the database double returns, one per query;
 *  - customer_has_branches value returned by db_customer_has_branches();
 *  - method  the HTTP request method.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support\scenarios
 * @license    MIT
 */

$config = $GLOBALS['screen_config'];

$_POST = isset($config['post']) && is_array($config['post']) ? $config['post'] : [];
$_GET = isset($config['get']) && is_array($config['get']) ? $config['get'] : [];
$_SESSION = isset($config['session']) && is_array($config['session']) ? $config['session'] : [];
$_SERVER['REQUEST_METHOD'] = isset($config['method']) ? (string) $config['method'] : 'GET';

$GLOBALS['__screen_customer_has_branches'] = !empty($config['customer_has_branches']);
$GLOBALS['__screen_list_updated'] = isset($config['list_updated']) && is_array($config['list_updated'])
    ? $config['list_updated']
    : [];
$GLOBALS['__screen_branches'] = isset($config['branches']) && is_array($config['branches'])
    ? $config['branches']
    : [];
$GLOBALS['__screen_focus'] = null;
$GLOBALS['__screen_bi_transactions_throws'] = !empty($config['transactions_model_throws']);
$GLOBALS['__screen_display_error_throws'] = !empty($config['display_error_throws']);

// The screen's inner error handler uses print_r($e, tru); the constant is
// undefined, so the harness has to provide it to reach the outer handler.
if (!empty($config['define_tru'])) {
    define('tru', true);
}

if (isset($config['files']) && is_array($config['files'])) {
    $files = [
        'name' => [],
        'tmp_name' => [],
        'error' => [],
        'size' => [],
        'type' => [],
    ];
    foreach ($config['files'] as $file) {
        $files['name'][] = $file['name'];
        $files['tmp_name'][] = $file['tmp_name'];
        $files['error'][] = isset($file['error']) ? $file['error'] : 0;
        $files['size'][] = isset($file['size']) ? $file['size'] : 0;
        $files['type'][] = isset($file['type']) ? $file['type'] : '';
    }
    $_FILES['files'] = $files;
}

if (isset($config['db_rows']) && is_array($config['db_rows'])) {
    foreach ($config['db_rows'] as $entry) {
        if (is_array($entry) && isset($entry['rows'])) {
            screen_db_queue(
                is_array($entry['rows']) ? $entry['rows'] : [],
                isset($entry['match']) ? $entry['match'] : null
            );
            continue;
        }
        screen_db_queue(is_array($entry) ? $entry : []);
    }
}

if (!isset($config['script']) || !is_file($config['script'])) {
    fwrite(STDERR, "The scenario configuration does not name a screen.\n");
    exit(2);
}

include $config['script'];
