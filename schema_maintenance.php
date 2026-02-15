<?php

/**
 * Bank Import schema maintenance (admin action).
 * Allows re-running all module-level ensure calls on demand.
 */

$page_security = 'SA_SETUPCOMPANY';
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");

require_once __DIR__ . '/src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php';

page(_($help_context = "Bank Import Schema Maintenance"));

include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

$results = null;

if (isset($_POST['run_schema_maintenance'])) {
    try {
        $service = new \Ksfraser\FaBankImport\Service\Schema\BankImportModuleSchemaService();
        $results = $service->ensureAll();
        display_notification('Schema maintenance completed successfully.');
    } catch (\Throwable $e) {
        display_error('Schema maintenance failed: ' . $e->getMessage());
    }
}

start_form();

echo "<h3>Schema Maintenance</h3>";
echo "<p>Run idempotent, non-destructive schema ensure calls for all bank import tables.</p>";

submit('run_schema_maintenance', _('Run Schema Maintenance'));

if (is_array($results)) {
    start_table(TABLESTYLE);
    table_header(array(_('Schema Group'), _('Status')));

    foreach ($results as $key => $ok) {
        start_row();
        label_cell((string)$key);
        label_cell($ok ? _('OK') : _('Skipped'));
        end_row();
    }

    end_table(1);
}

end_form();

end_page();
