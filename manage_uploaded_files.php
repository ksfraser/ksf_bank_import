<?php
/**
 * Mantis #2708: Import Bank Transaction Files - Upload File - Store File
 * 
 * This screen manages uploaded bank statement files
 * - Lists all uploaded files
 * - Shows upload details (user, date, filename)
 * - Links to related statements
 * - Provides download functionality
 * - Allows file deletion
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */

$page_security = 'SA_BANKFILEVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");

// Include the file manager class
require_once(__DIR__ . '/src/Ksfraser/FaBankImport/services/UploadedFileManager.php');

use Ksfraser\FaBankImport\Services\UploadedFileManager;

page(_($help_context = "Manage Uploaded Bank Files"), false, false, "", $js="");

// Display module menu
include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

//-----------------------------------------------------------------------------------

$file_manager = new UploadedFileManager();

// Handle file download
if (isset($_GET['download'])) {
    $file_id = $_GET['download'];
    if ($file_manager->downloadFile($file_id)) {
        exit; // File sent, stop execution
    } else {
        display_error("Failed to download file or file not found.");
    }
}

// Handle file deletion
if (isset($_POST['delete_file'])) {
    $file_id = key($_POST['delete_file']);
    if ($file_manager->deleteFile($file_id)) {
        display_notification("File deleted successfully.");
    } else {
        display_error("Failed to delete file.");
    }
}

// Get filters from form
$filters = [];
if (isset($_POST['filter_user']) && $_POST['filter_user'] != '') {
    $filters['user'] = $_POST['filter_user'];
}
if (isset($_POST['filter_date_from']) && $_POST['filter_date_from'] != '') {
    $filters['date_from'] = date2sql($_POST['filter_date_from']);
}
if (isset($_POST['filter_date_to']) && $_POST['filter_date_to'] != '') {
    $filters['date_to'] = date2sql($_POST['filter_date_to']);
}
if (isset($_POST['filter_parser']) && $_POST['filter_parser'] != '') {
    $filters['parser_type'] = $_POST['filter_parser'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get files
$files = $file_manager->getUploadedFiles($filters, $per_page, $offset);
$total_files = $file_manager->getTotalFileCount($filters);
$total_pages = ceil($total_files / $per_page);

// Get storage stats
$stats = $file_manager->getStorageStats();

//-----------------------------------------------------------------------------------
// Display
//-----------------------------------------------------------------------------------

start_form();

// Storage Statistics
echo "<h3>Storage Statistics</h3>";
start_table(TABLESTYLE);
$th = array(_("Metric"), _("Value"));
table_header($th);

label_row("Total Files:", $stats['total_files']);
label_row("Total Storage:", number_format($stats['total_size'] / 1024 / 1024, 2) . " MB");
if ($stats['latest_upload']) {
    label_row("Latest Upload:", sql2date($stats['latest_upload']));
}
if ($stats['first_upload']) {
    label_row("First Upload:", sql2date($stats['first_upload']));
}

end_table(1);

br();

// Filters
echo "<h3>Filter Files</h3>";
start_table(TABLESTYLE_NOBORDER);

// User filter
$users_sql = "SELECT DISTINCT upload_user, u.real_name 
              FROM " . TB_PREF . "bi_uploaded_files f
              LEFT JOIN " . TB_PREF . "users u ON f.upload_user = u.user_id
              ORDER BY u.real_name";
$users_result = db_query($users_sql);
$users = array('' => "-- All Users --");
while ($user_row = db_fetch($users_result)) {
    $users[$user_row['upload_user']] = $user_row['real_name'] ? $user_row['real_name'] : $user_row['upload_user'];
}

start_row();
label_cells("User:", array_selector('filter_user', @$_POST['filter_user'], $users));
end_row();

// Date range filter
start_row();
date_cells("From Date:", 'filter_date_from', '', @$_POST['filter_date_from']);
date_cells("To Date:", 'filter_date_to', '', @$_POST['filter_date_to']);
end_row();

// Parser type filter
$parsers_sql = "SELECT DISTINCT parser_type 
                FROM " . TB_PREF . "bi_uploaded_files 
                WHERE parser_type IS NOT NULL
                ORDER BY parser_type";
$parsers_result = db_query($parsers_sql);
$parsers = array('' => "-- All Types --");
while ($parser_row = db_fetch($parsers_result)) {
    $parsers[$parser_row['parser_type']] = $parser_row['parser_type'];
}

start_row();
label_cells("Parser Type:", array_selector('filter_parser', @$_POST['filter_parser'], $parsers));
submit_cells('filter', _("Filter"), '', '', 'default');
submit_cells('clear_filter', _("Clear"), '', '', 'default');
end_row();

end_table(1);

br();

// Clear filters
if (isset($_POST['clear_filter'])) {
    unset($_POST['filter_user']);
    unset($_POST['filter_date_from']);
    unset($_POST['filter_date_to']);
    unset($_POST['filter_parser']);
    $filters = [];
    $files = $file_manager->getUploadedFiles($filters, $per_page, $offset);
    $total_files = $file_manager->getTotalFileCount($filters);
    $total_pages = ceil($total_files / $per_page);
}

// Files list
echo "<h3>Uploaded Files (" . $total_files . " total)</h3>";

if (empty($files)) {
    display_notification("No files uploaded yet.");
} else {
    start_table(TABLESTYLE2);
    $th = array(
        _("ID"),
        _("Original Filename"),
        _("Upload Date"),
        _("Uploaded By"),
        _("Size"),
        _("Parser Type"),
        _("Bank Account"),
        _("Statements"),
        _("Actions")
    );
    table_header($th);
    
    foreach ($files as $file) {
        start_row();
        
        // ID
        label_cell($file['id']);
        
        // Original Filename
        label_cell($file['original_filename']);
        
        // Upload Date
        label_cell(sql2date($file['upload_date']));
        
        // Uploaded By
        label_cell($file['uploader_name'] ? $file['uploader_name'] : $file['upload_user']);
        
        // Size
        $size_kb = round($file['file_size'] / 1024, 2);
        label_cell($size_kb . " KB");
        
        // Parser Type
        label_cell($file['parser_type']);
        
        // Bank Account
        $bank_info = $file['bank_account_name'] ? $file['bank_name'] . " - " . $file['bank_account_name'] : "N/A";
        label_cell($bank_info);
        
        // Linked Statements
        label_cell($file['statement_count']);
        
        // Actions
        $actions = "<a href='?download={$file['id']}' class='button'>Download</a> ";
        $actions .= "<a href='manage_uploaded_files.php?view={$file['id']}' class='button'>View Details</a> ";
        $actions .= submit("delete_file[{$file['id']}]", _("Delete"), false, '', 'default');
        label_cell($actions, "nowrap");
        
        end_row();
        
        // Show notes if any
        if (!empty($file['notes'])) {
            start_row();
            label_cell("");
            label_cell("<i>Notes: " . $file['notes'] . "</i>", "colspan=8");
            end_row();
        }
    }
    
    end_table(1);
    
    // Pagination
    if ($total_pages > 1) {
        echo "<div class='pagination'>";
        echo "Page: ";
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo "<b>$i</b> ";
            } else {
                echo "<a href='?page=$i'>$i</a> ";
            }
        }
        echo "</div>";
    }
}

br();

// File details view
if (isset($_GET['view'])) {
    $file_id = $_GET['view'];
    $file_details = $file_manager->getFileDetails($file_id);
    
    if ($file_details) {
        echo "<h3>File Details</h3>";
        start_table(TABLESTYLE);
        
        label_row("ID:", $file_details['id']);
        label_row("Original Filename:", $file_details['original_filename']);
        label_row("Stored Filename:", $file_details['filename']);
        label_row("Upload Date:", sql2date($file_details['upload_date']));
        label_row("Uploaded By:", $file_details['uploader_name'] ? $file_details['uploader_name'] : $file_details['upload_user']);
        label_row("File Size:", number_format($file_details['file_size'] / 1024, 2) . " KB");
        label_row("File Type:", $file_details['file_type']);
        label_row("Parser Type:", $file_details['parser_type']);
        
        if ($file_details['bank_account_name']) {
            label_row("Bank Account:", $file_details['bank_name'] . " - " . $file_details['bank_account_name']);
        }
        
        if ($file_details['notes']) {
            label_row("Notes:", $file_details['notes']);
        }
        
        end_table(1);
        
        // Linked statements
        if (!empty($file_details['statements'])) {
            echo "<h4>Linked Statements (" . count($file_details['statements']) . ")</h4>";
            start_table(TABLESTYLE2);
            $th = array(_("ID"), _("Bank"), _("Account"), _("Statement ID"), _("Date"), _("Balance"));
            table_header($th);
            
            foreach ($file_details['statements'] as $stmt) {
                start_row();
                label_cell("<a href='view_statements.php?id={$stmt['id']}'>{$stmt['id']}</a>");
                label_cell($stmt['bank']);
                label_cell($stmt['account']);
                label_cell($stmt['statementId']);
                label_cell(sql2date($stmt['smtDate']));
                amount_cell($stmt['endBalance']);
                end_row();
            }
            
            end_table(1);
        } else {
            display_notification("No statements linked to this file.");
        }
        
        br();
        echo "<a href='manage_uploaded_files.php' class='button'>Back to List</a>";
    } else {
        display_error("File not found.");
    }
}

end_form();

//-----------------------------------------------------------------------------------

end_page();

?>
