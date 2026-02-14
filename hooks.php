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
		$this->ensure_config_tables();

		// Uploaded files tracking tables
		$this->ensure_uploaded_files_tables();

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

	private function ensure_config_tables()
	{
		// These are safe to run repeatedly.
		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_config` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `config_key` varchar(100) NOT NULL,
		  `config_value` text,
		  `config_type` varchar(20) NOT NULL DEFAULT 'string',
		  `description` text,
		  `category` varchar(50) NOT NULL DEFAULT 'general',
		  `is_system` tinyint(1) NOT NULL DEFAULT 0,
		  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  `updated_by` varchar(60) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `config_key` (`config_key`),
		  KEY `category` (`category`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		db_query($sql, 'Failed to ensure bi_config table');

		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_config_history` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `config_key` varchar(100) NOT NULL,
		  `old_value` text,
		  `new_value` text,
		  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `changed_by` varchar(60) NOT NULL,
		  `change_reason` text,
		  PRIMARY KEY (`id`),
		  KEY `config_key` (`config_key`),
		  KEY `changed_at` (`changed_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		db_query($sql, 'Failed to ensure bi_config_history table');

		// Seed defaults idempotently
		$table = TB_PREF . 'bi_config';
		$sql = "INSERT IGNORE INTO `{$table}` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_system`) VALUES
		('upload.check_duplicates', '0', 'boolean', 'Enable duplicate file detection', 'upload', 0),
		('upload.duplicate_window_days', '90', 'integer', 'How many days back to check for duplicates', 'upload', 0),
		('upload.duplicate_action', 'warn', 'string', 'Action on duplicate: allow, warn, block', 'upload', 0),
		('upload.max_file_size', '10485760', 'integer', 'Maximum upload file size in bytes (10MB default)', 'upload', 0),
		('upload.allowed_extensions', 'qfx,ofx,csv,mt940,sta', 'string', 'Comma-separated list of allowed file extensions', 'upload', 0),
		('storage.retention_days', '730', 'integer', 'How many days to retain uploaded files (2 years default)', 'storage', 0),
		('storage.compression_enabled', '0', 'boolean', 'Enable file compression for storage', 'storage', 0),
		('logging.enabled', '1', 'boolean', 'Enable import logging', 'logging', 0),
		('logging.level', 'info', 'string', 'Log level: debug, info, warning, error', 'logging', 0),
		('logging.retention_days', '30', 'integer', 'How many days to retain logs', 'logging', 0),
		('performance.batch_size', '100', 'integer', 'Number of transactions to process per batch', 'performance', 0),
		('performance.memory_limit', '256M', 'string', 'PHP memory limit for imports', 'performance', 0),
		('security.require_permission', 'SA_BANKFILEVIEW', 'string', 'Required permission for file management', 'security', 1),
		('security.htaccess_enabled', '1', 'boolean', 'Protect upload directory with .htaccess', 'security', 1)";
		db_query($sql, 'Failed seeding bi_config defaults');
	}

	private function ensure_uploaded_files_tables()
	{
		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_uploaded_files` (
		    `id`                INTEGER NOT NULL AUTO_INCREMENT,
		    `filename`          VARCHAR(255) NOT NULL,
		    `original_filename` VARCHAR(255) NOT NULL,
		    `file_path`         VARCHAR(512) NOT NULL,
		    `file_size`         INTEGER NOT NULL,
		    `file_type`         VARCHAR(100),
		    `upload_date`       DATETIME NOT NULL,
		    `upload_user`       VARCHAR(60) NOT NULL,
		    `parser_type`       VARCHAR(50),
		    `bank_account_id`   INTEGER,
		    `statement_count`   INTEGER DEFAULT 0,
		    `notes`             TEXT,
		    PRIMARY KEY(`id`),
		    INDEX `idx_upload_date` (`upload_date`),
		    INDEX `idx_upload_user` (`upload_user`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		db_query($sql, 'Failed to ensure bi_uploaded_files table');

		// Keep this table FK-free for maximum compatibility with existing installs.
		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_file_statements` (
		    `file_id`       INTEGER NOT NULL,
		    `statement_id`  INTEGER NOT NULL,
		    PRIMARY KEY(`file_id`, `statement_id`),
		    INDEX `idx_statement_id` (`statement_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		db_query($sql, 'Failed to ensure bi_file_statements table');
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
