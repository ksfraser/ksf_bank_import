<?php
namespace Views;

class ModuleMenuView
{
    public function renderMenu()
    {
        echo '<nav class="module-menu">';
        echo '<ul>';
        echo '<li><a href="process_statements.php">Process Statements</a></li>';
        echo '<li><a href="import_statements.php">Import Statements</a></li>';
        echo '<li><a href="manage_partners_data.php">Manage Partners Data</a></li>';
        echo '<li><a href="view_statements.php">View Statements</a></li>';
        echo '<li><a href="validate_gl_entries.php">Validate GL Entries</a></li>';
        echo '</ul>';
        echo '</nav>';
    }
}
