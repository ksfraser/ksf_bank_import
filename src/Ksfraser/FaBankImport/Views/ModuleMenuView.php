<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Views;

/**
 * The module menu of the bank import screens.
 *
 * Migrated from the legacy views/module_menu_view.php (namespace Views) into
 * the PSR-4 tree, so the migrated screens can autoload it.
 *
 * @package Ksfraser\FaBankImport\Views
 * @since 20251020
 * @BABOK FR-PROC-001, FR-IMP-001, FR-INQ-002, FR-UPL-001, FR-VAL-001 | BR-PROC-001 | UAT-PROC-001
 */
class ModuleMenuView
{
    /**
     * Render the menu of the module screens.
     *
     * @return void
 * @BABOK FR-PROC-001, FR-IMP-001, FR-INQ-002, FR-UPL-001, FR-VAL-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function renderMenu()
    {
        echo '<nav class="module-menu">';
        echo '<ul>';
        echo '<li><a href="process_statements.php">Process Statements</a></li>';
        echo '<li><a href="import_statements.php">Import Statements</a></li>';
        echo '<li><a href="manage_partners_data.php">Manage Partners Data</a></li>';
        echo '<li><a href="view_statements.php">View Statements</a></li>';
        echo '<li><a href="manage_uploaded_files.php">Manage Uploaded Files</a></li>';
        echo '<li><a href="view_import_logs.php">View Import Logs</a></li>';
        echo '<li><a href="validate_gl_entries.php">Validate GL Entries</a></li>';
        echo '</ul>';
        echo '</nav>';
    }
}
