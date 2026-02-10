<?php

/**
 * Bank Import - View Import Logs
 *
 * Read-only viewer for import run logs written under company/#/bank_imports/logs.
 */

$page_security = 'SA_BANKIMPORTLOGVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\FaBankImport\Service\BankImportPathResolver;

page(_($help_context = "Bank Import Logs"));

include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

function bank_import_logs_dir(): string
{
    return BankImportPathResolver::forCurrentCompany()->logsDir();
}

function safe_log_filename(?string $name): ?string
{
    if ($name === null || $name === '') {
        return null;
    }

    $name = basename($name);

    // Only allow our expected naming pattern (defense-in-depth)
    if (!preg_match('/^import_run_[0-9]{8}_[0-9]{6}_[a-zA-Z0-9_-]{6,64}\.jsonl$/', $name)) {
        return null;
    }

    return $name;
}

$dir = bank_import_logs_dir();

start_form();

if (!is_dir($dir)) {
    display_notification(_("No log directory found yet. Logs will appear after the first import run."));
    end_form();
    end_page();
    exit;
}

$view = safe_log_filename($_GET['file'] ?? null);

if ($view !== null) {
    $path = $dir . DIRECTORY_SEPARATOR . $view;
    if (!is_file($path)) {
        display_error(_("Log file not found."));
    } else {
        echo '<h3>' . _("Viewing Log") . ': ' . htmlspecialchars($view) . '</h3>';
        echo '<p class="smalltext">' . _("Read-only view. Logs are written by the import process.") . '</p>';

        $size = filesize($path);
        if ($size !== false && $size > 2_000_000) {
            display_warning(_("Log is large; displaying first ~2MB."));
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            display_error(_("Unable to open log file."));
        } else {
            $content = @fread($fh, 2_000_000);
            @fclose($fh);

            echo '<pre style="white-space:pre-wrap; background:#f8f9fa; padding:12px; border:1px solid #ddd;">';
            echo htmlspecialchars($content ?? '');
            echo '</pre>';
        }
    }

    echo '<p><a href="view_import_logs.php">' . _("Back to log list") . '</a></p>';
    end_form();
    end_page();
    exit;
}

// List logs
$files = glob($dir . DIRECTORY_SEPARATOR . 'import_run_*.jsonl');
if ($files === false || empty($files)) {
    display_notification(_("No logs found yet."));
    end_form();
    end_page();
    exit;
}

// Sort newest first
usort($files, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

echo '<h3>' . _("Import Run Logs") . '</h3>';
start_table(TABLESTYLE2);
$th = array(_("Log File"), _("Modified"), _("Size"), _("Action"));
table_header($th);

foreach ($files as $full) {
    $name = basename($full);
    start_row();

    label_cell(htmlspecialchars($name));
    $mt = filemtime($full);
    label_cell($mt ? date('Y-m-d H:i:s', $mt) : '');

    $sz = filesize($full);
    label_cell($sz !== false ? number_format($sz / 1024, 1) . ' KB' : '');

    $link = '<a href="view_import_logs.php?file=' . urlencode($name) . '">' . _("View") . '</a>';
    label_cell($link);

    end_row();
}

end_table(1);

end_form();
end_page();

