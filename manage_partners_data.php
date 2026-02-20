<?php
/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
$page_security = 'SA_BANKACCOUNT';

include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");
include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/ui/ui_controls.inc");
include_once($path_to_root . "/includes/db/branches_db.inc");
include_once($path_to_root . "/modules/bank_import/includes/pdata.inc");
//require_once 'HTML/Table.php';
use Ksfraser\HTML\Composites\HTML_TABLE;
use Ksfraser\HTML\Composites\HTML_ROW;

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Manage Partners Bank Accounts"), false, false, '', $js);


        include_once "Views/module_menu_view.php"; // Include the ModuleMenuView class
        $menu = new \Views\ModuleMenuView();
        $menu->renderMenu(); // Render the module menu



$types = array (
    PT_CUSTOMER => _("Customer"),
    PT_SUPPLIER => _("Supplier"),
);


//----------------------------------------------------------------------------------------
if (list_updated('partner_detail_id')) {
    $br = get_branch(get_post('partner_detail_id'));
    $_POST['partner_id'] = $br['debtor_no'];
    $Ajax->activate('partner_id');
}

if (isset($_POST['_partner_type_update'])) {
    $_POST['partner_id'] = '';
    $_POST['data'] = '';
    $Ajax->activate('partner');
    set_focus('data');

}

//-----------------------------------------------------------------------------------------------
if (isset($_POST['process'])) {
    set_partner_data($_POST['partner_id'], $_POST['partner_type'], $_POST['partner_detail_id'], $_POST['data']);
    display_notification("Partner data updated!");
}


//-----------------------------------------------------------------------------------------------

start_form();

div_start('partner');


$table = new HTML_TABLE(2, 90);



// First section of the table
//$table->addRow();
//$table->addCell("<label>" . _( "Choose: " ) . "</label>", ['class' => 'label']);
//$table->addCell(array_selector('partner_type', $_POST['partner_type'], $types, ['select_submit' => true]));
$row1 = new HTML_ROW("<label>" . _( "Choose: " ) . "</label>" . array_selector('partner_type', $_POST['partner_type'], $types, ['select_submit' => true]));
$table->appendRow($row1);


switch ($_POST['partner_type']) {
    case PT_SUPPLIER:
        $row2 = new HTML_ROW(supplier_list_row(_("Supplier:"), 'partner_id', null, false, true, false, true));
        $table->appendRow($row2);
        $_POST['partner_detail_id'] = ANY_NUMERIC;
        hidden('partner_detail_id', ANY_NUMERIC);
        break;
    case PT_CUSTOMER:
        $row3 = new HTML_ROW(customer_list_row(_("Customer:"), 'partner_id', null, false, true, false, true));
        $table->appendRow($row3);

        if (db_customer_has_branches($_POST['partner_id'])) {
            $row4 = new HTML_ROW(customer_branches_list_row(_("Branch:"), $_POST['partner_id'], 'partner_detail_id', null, false, true, true, true));
            $table->appendRow($row4);
        } else {
            $_POST['partner_detail_id'] = ANY_NUMERIC;
            hidden('partner_detail_id', ANY_NUMERIC);
        }
        break;
    default:
        $row5 = new HTML_ROW("something else");
        $table->appendRow($row5);
        break;
}


// Second section of the table
$data = get_partner_data($_POST['partner_id'], $_POST['partner_type'], $_POST['partner_detail_id']);
if (!empty($data)) {
    $_POST['data'] = $data['data'];
}

$row6 = new HTML_ROW(textarea_row(_("IBAN(S):"), 'data', @$_POST['data'], 50, 3));
$table->appendRow($row6);

echo $table->toHtml();

div_end();

submit_center_first('process', _( "Update" ), 'default');

end_form();

//------------------------------------------------------------------------------------------------

end_page();

?>

