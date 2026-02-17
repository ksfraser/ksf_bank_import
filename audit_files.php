<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

$page_security = 'SA_BANKACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

require_once __DIR__ . '/vendor/autoload.php';
use Ksfraser\FaBankImport\Service\FileUploadService;

page(_($help_context = "Bank Import File Audit"));

// Handle resolution actions
if (isset($_POST['resolve_file_id']) && isset($_POST['bank_account_id'])) {
    $fileId = (int)$_POST['resolve_file_id'];
    $bankAccountId = (int)$_POST['bank_account_id'];
    
    $service = FileUploadService::create();
    if ($service->resolveMissingAccount($fileId, $bankAccountId)) {
        display_notification(_("File #$fileId successfully associated with bank account."));
    } else {
        display_error(_("Failed to update bank account for file #$fileId."));
    }
}

include_once __DIR__ . "/views/module_menu_view.php";
if (class_exists('\Views\ModuleMenuView')) {
    $menu = new \Views\ModuleMenuView();
    $menu->renderMenu();
}

$service = FileUploadService::create();
$candidates = $service->getAuditCandidates();

start_form();
start_table(TABLESTYLE);

$th = array(
    _("ID"),
    _("Filename"),
    _("Upload Date"),
    _("Parser"),
    _("Current Bank Account"),
    _("Suggested Resolution"),
    ""
);
table_header($th);

$k = 0;
foreach ($candidates as $candidate) {
    $file = $candidate['file'];
    $suggestionId = $candidate['suggestion'];
    
    alt_table_row_color($k);
    
    label_cell($file->getId());
    label_cell($file->getOriginalFilename());
    label_cell($file->getUploadDate()->format('Y-m-d H:i:s'));
    label_cell($file->getParserType());
    
    label_cell('<i style="color:red">' . _("Missing") . '</i>');
    
    if ($suggestionId) {
        $bank_account_name = "";
        // Helper to get bank account name if possible
        $sql = "SELECT bank_account_name FROM " . TB_PREF . "bank_accounts WHERE id = " . (int)$suggestionId;
        $res = db_query($sql, "could not get bank account name");
        $row = db_fetch($res);
        if ($row) {
            $bank_account_name = $row['bank_account_name'];
        }
        
        label_cell("<b>$bank_account_name</b> (ID: $suggestionId)");
        
        echo "<td>";
        echo "<input type='hidden' name='resolve_file_id' value='{$file->getId()}'>";
        echo "<input type='hidden' name='bank_account_id' value='{$suggestionId}'>";
        submit('resolve', _("Resolve"), true, _("Associate this file with this bank account"), 'default');
        echo "</td>";
    } else {
        label_cell(_("No suggestion available (linked statements missing acctid)"));
        label_cell("");
    }
    
    end_row();
}

if (empty($candidates)) {
    start_row();
    label_cell(_("No files found with missing bank account associations."), "colspan=7 style='text-align:center; padding: 20px;'");
    end_row();
}

end_table(1);
end_form();

end_page();
