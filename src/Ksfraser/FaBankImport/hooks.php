<?php

define( 'MENU_IMPORT', 'menu_import' );

/**
 * @BABOK CLASS ROLLUP: hooks_bank_import — 3 methods; NFR-016 (1); FR-018 (3); BR-002 (2); FR-008 (2)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: hooks.php; coverage: docs/TestCases/ (file rollup)
 */
class hooks_bank_import extends hooks {
    var $module_name = 'bank_import'; 

    /*
 * @BABOK NFR-016 / FR-018 — method: install_options
    * Install additonal menu options provided by module
    */

    function install_options($app) {
	global $path_to_root;


	switch($app->id) {
	    case 'GL':
/*
		$app->add_lapp_function(0, _("Process Bank Statements"),
			$path_to_root."/modules/".$this->module_name."/process_statements.php", 'SA_BANKACCOUNT', MENU_TRANSACTION);
		$app->add_lapp_function(2, _("Manage Partners Bank Accounts"),
			$path_to_root."/modules/".$this->module_name."/manage_partners_data.php", 'SA_CUSTOMER', MENU_MAINTENANCE);
		$app->add_lapp_function(2, _("Import Bank Statements"),
			$path_to_root."/modules/".$this->module_name."/import_statements.php", 'SA_BANKACCOUNT', MENU_MAINTENANCE);
		$app->add_lapp_function(1, _("Bank Statements Inquiry"),
			$path_to_root."/modules/".$this->module_name."/view_statements.php", 'SA_BANKACCOUNT', MENU_INQUIRY);
*/
		$app->add_lapp_function(3, _("Manage Partners Bank Accounts"),
			$path_to_root."/modules/".$this->module_name."/manage_partners_data.php", 'SA_CUSTOMER', MENU_IMPORT);
		$app->add_lapp_function(3, _("Import Bank Statements"),
			$path_to_root."/modules/".$this->module_name."/import_statements.php", 'SA_BANKACCOUNT', MENU_MAINTENANCE);
		$app->add_lapp_function(3, _("Process Bank Statements"),
			$path_to_root."/modules/".$this->module_name."/process_statements.php", 'SA_BANKACCOUNT', MENU_IMPORT);
		$app->add_lapp_function(3, _("Bank Statements Inquiry"),
			$path_to_root."/modules/".$this->module_name."/view_statements.php", 'SA_BANKACCOUNT', MENU_INQUIRY);

		break;
	}
    }


    /** @BABOK BR-002 / FR-008 / FR-018 — method: activate_extension */
    function activate_extension($company, $check_only=true) {
	$updates = array( 'update.sql' => array($this->module_name) );
	return $this->update_databases($company, $updates, $check_only);
	return true;
    }

    //this is required to cancel bank transactions when a voiding operation occurs
	//@todo refactor to use my eventloop functions
    /** @BABOK BR-002 / FR-008 / FR-018 — method: db_prevoid */
    function db_prevoid($trans_type, $trans_no) {
	    //SET status=0
	$sql = "
	    UPDATE ".TB_PREF."bi_transactions
	    SET status=0, fa_trans_no=0, fa_trans_type=0, created=0, matched=0, g_partner='', g_option=''
	    WHERE
		fa_trans_no=".db_escape($trans_no)." AND
		fa_trans_type=".db_escape($trans_type)." AND
		status = 1";
	display_notification($sql);
	db_query($sql, 'Could not void transaction');

    }


}
?>
