<?php
// Admin screen for enabling/disabling parsers
require_once __DIR__ . '/modules/bank_import/includes/session.inc';
require_once __DIR__ . '/modules/bank_import/includes/ui.inc';
require_once __DIR__ . '/includes/parsers.inc';
require_once __DIR__ . '/src/Ksfraser/FaBankImport/Views/ParserConfigAdminView.php';

if (!user_access('SA_ADMINPARSERS')) {
    display_error('You do not have permission to access this page.');
    end_page();
    exit;
}


require_once __DIR__ . '/src/Ksfraser/FaBankImport/config/ParserConfig.php';
$parsersDir = __DIR__ . '/Parsers';
$parsers = array();
$dirs = scandir($parsersDir);
foreach ($dirs as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    $parserJson = $parsersDir . '/' . $dir . '/parser.json';
    if (is_file($parserJson)) {
        $config = json_decode(file_get_contents($parserJson), true);
        $config['enabled'] = \Ksfraser\FaBankImport\Config\ParserConfig::isEnabled($dir);
        $parsers[$dir] = $config;
    }
}

if (isset($_POST['save_parsers'])) {
    $states = [];
    foreach ($parsers as $pid => $pdata) {
        $states[$pid] = !empty($_POST['enable'][$pid]);
    }
    \Ksfraser\FaBankImport\Config\ParserConfig::setAll($states);
    display_notification('Parser configuration updated.');
}

$view = new \Ksfraser\FaBankImport\Views\ParserConfigAdminView();
$view->render($parsers);
end_page();
