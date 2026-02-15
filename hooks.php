<?php


define( 'MENU_IMPORT', 'menu_import' );

//Using the following security:
//	SA_CUSTOMER
//	SA_BANKACCOUNT
//	SA_BANKFILEVIEW
//	SA_BANKTRANSVIEW
//	SA_SETUPCOMPANY

define('SS_BANKIMPORT', 113 << 8);

/**//************************************************************
* Hooks class, called by FA on every page load
*
*/
class hooks_bank_import extends hooks {
	var $module_name = 'bank_import'; 

	/*
	* Install additonal menu options provided by module
	*/

	function install_options($app) 
	{
		global $path_to_root;
	
	
		switch($app->id) {
			case 'GL':
			$app->add_lapp_function(3, _("Manage Partners Bank Accounts"),
				$path_to_root."/modules/".$this->module_name."/manage_partners_data.php", 'SA_CUSTOMER', MENU_IMPORT);
			$app->add_lapp_function(3, _("Import Bank Statements"),
				$path_to_root."/modules/".$this->module_name."/import_statements.php", 'SA_BANKACCOUNT', MENU_MAINTENANCE);
			$app->add_lapp_function(3, _("Process Bank Statements"),
				$path_to_root."/modules/".$this->module_name."/process_statements.php", 'SA_BANKACCOUNT', MENU_IMPORT);
			$app->add_lapp_function(3, _("Bank Statements Inquiry"),
				$path_to_root."/modules/".$this->module_name."/view_statements.php", 'SA_BANKACCOUNT', MENU_INQUIRY);
			$app->add_lapp_function(3, _("Manage Uploaded Files"),
				$path_to_root."/modules/".$this->module_name."/manage_uploaded_files.php", 'SA_BANKFILEVIEW', MENU_INQUIRY);
			$app->add_lapp_function(3, _("Validate GL Entries"),
				$path_to_root."/modules/".$this->module_name."/validate_gl_entries.php", 'SA_BANKTRANSVIEW', MENU_INQUIRY);
			$app->add_lapp_function(3, _("View Import Logs"),
				$path_to_root."/modules/".$this->module_name."/view_import_logs.php", 'SA_BANKIMPORTLOGVIEW', MENU_INQUIRY);
			$app->add_lapp_function(3, _("Module Configuration"),
				$path_to_root."/modules/".$this->module_name."/module_config.php", 'SA_SETUPCOMPANY', MENU_MAINTENANCE);
			$app->add_lapp_function(2, _("Bank Import Settings"),
				$path_to_root."/modules/".$this->module_name."/bank_import_settings.php", 'SA_SETUPCOMPANY', MENU_MAINTENANCE);
	
			break;
		}
	}

	function install_access()
	{
		$security_sections[SS_BANKIMPORT] = _("Bank Files Import");
		$security_areas['SA_BANKIMPORT'] = array(SS_BANKIMPORT | 1, _("Bank Files Import"));
		$security_areas['SA_BANKFILEVIEW'] = array(SS_BANKIMPORT | 2, _("Bank Files View"));
		$security_areas['SA_BANKIMPORTLOGVIEW'] = array(SS_BANKIMPORT | 3, _("Bank Import Logs View"));
		return array($security_areas, $security_sections);
	}


	function activate_extension($company, $check_only=true) 
	{
		$updates = array( 'sql/update.sql' => array($this->module_name) );
		$ok = $this->update_databases($company, $updates, $check_only);
		if ($check_only || !$ok) {
			return $ok;
		}

		// Ensure schema drift does not break fresh installs or older databases.
		// This must be idempotent and non-destructive.
		$this->ensure_bank_import_schema();
		return $ok;
		//return true;
	}

	private function ensure_bank_import_schema()
	{
		// Delegate per-table drift repair to the owning model classes.
		require_once(__DIR__ . '/class.bi_statements.php');
		bi_statements_model::ensure_schema();
		require_once(__DIR__ . '/class.bi_transactions.php');
		bi_transactions_model::ensure_schema();
		require_once(__DIR__ . '/class.bi_partners_data.php');
		bi_partners_data::ensure_schema();

		// Configuration tables
		require_once(__DIR__ . '/src/Ksfraser/FaBankImport/Service/Schema/BiConfigSchemaInstaller.php');
		$configSchemaInstaller = new \Ksfraser\FaBankImport\Service\Schema\BiConfigSchemaInstaller(
			'db_query',
			TB_PREF
		);
		$configSchemaInstaller->ensureTables();

		// Uploaded files tracking tables
		require_once(__DIR__ . '/src/Ksfraser/FaBankImport/Service/Schema/BiUploadedFilesSchemaInstaller.php');
		$uploadedFilesSchemaInstaller = new \Ksfraser\FaBankImport\Service\Schema\BiUploadedFilesSchemaInstaller(
			'db_query',
			TB_PREF
		);
		$uploadedFilesSchemaInstaller->ensureTables();

		// Bank account OFX/Intuit metadata xref (do not modify FA core bank_accounts)
		require_once(__DIR__ . '/src/Ksfraser/FaBankImport/Service/Schema/BiBankAccountsSchemaInstaller.php');
		$bankAccountsSchemaInstaller = new \Ksfraser\FaBankImport\Service\Schema\BiBankAccountsSchemaInstaller(
			'db_query',
			'db_escape',
			'db_num_rows',
			TB_PREF
		);
		$bankAccountsSchemaInstaller->ensureTable();
		require_once(__DIR__ . '/src/Ksfraser/FaBankImport/Service/LegacyBankAccountsMigrator.php');
		$migrator = new \Ksfraser\FaBankImport\Service\LegacyBankAccountsMigrator(
			'db_query',
			'db_escape',
			'db_num_rows',
			TB_PREF
		);
		$migrator->migrate();
	}

	//this is required to cancel bank transactions when a voiding operation occurs
	function db_prevoid($trans_type, $trans_no) 
	{
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
