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
		$this->ensure_core_tables();

		// Core staging tables
		$this->ensure_column(TB_PREF . 'bi_statements', 'updated_ts', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		$this->ensure_column(TB_PREF . 'bi_statements', 'acctid', "VARCHAR(64) NULL");
		$this->ensure_column(TB_PREF . 'bi_statements', 'fitid', "VARCHAR(64) NULL");
		$this->ensure_column(TB_PREF . 'bi_statements', 'bankid', "VARCHAR(64) NULL");
		$this->ensure_column(TB_PREF . 'bi_statements', 'intu_bid', "VARCHAR(64) NULL");
		$this->ensure_unique_index(TB_PREF . 'bi_statements', 'unique_smt', array('bank', 'statementId'));

		$this->ensure_column(TB_PREF . 'bi_transactions', 'updated_ts', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		$this->ensure_column(TB_PREF . 'bi_transactions', 'matched', "INTEGER DEFAULT 0");
		$this->ensure_column(TB_PREF . 'bi_transactions', 'created', "INTEGER DEFAULT 0");
		$this->ensure_column(TB_PREF . 'bi_transactions', 'g_partner', "VARCHAR(32) NULL");
		$this->ensure_column(TB_PREF . 'bi_transactions', 'g_option', "VARCHAR(32) NULL");

		// Partner keyword table enhancements
		$this->ensure_column(TB_PREF . 'bi_partners_data', 'updated_ts', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		$this->ensure_column(TB_PREF . 'bi_partners_data', 'occurrence_count', "INTEGER DEFAULT 1");
		$this->ensure_index(TB_PREF . 'bi_partners_data', 'idx_partner_type_data', array('partner_type', 'data'));
		$this->ensure_index(TB_PREF . 'bi_partners_data', 'idx_occurrence_count', array('occurrence_count'));
		$this->ensure_partner_keyword_unique_index(TB_PREF . 'bi_partners_data');
		$this->set_default_occurrence_count(TB_PREF . 'bi_partners_data');

		// Configuration tables
		$this->ensure_config_tables();

		// Uploaded files tracking tables
		$this->ensure_uploaded_files_tables();
	}

	private function ensure_core_tables()
	{
		// These are safe to run repeatedly and ensure fresh installs align with current code.
		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_statements` (
		    `id`            INTEGER NOT NULL AUTO_INCREMENT,
		    `updated_ts`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		    `bank`          VARCHAR(64),
		    `account`       VARCHAR(64),
		    `currency`      VARCHAR(3),
		    `startBalance`  DOUBLE,
		    `endBalance`    DOUBLE,
		    `smtDate`       DATE,
		    `number`        INTEGER,
		    `seq`           INTEGER,
		    `statementId`   VARCHAR(64),
		    `acctid`        VARCHAR(64),
		    `fitid`         VARCHAR(64),
		    `bankid`        VARCHAR(64),
		    `intu_bid`      VARCHAR(64),
		    PRIMARY KEY(`id`),
		    CONSTRAINT `unique_smt` UNIQUE(`bank`, `statementId`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		db_query($sql, 'Failed to ensure bi_statements table');

		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_transactions` (
		    `id`                  INTEGER NOT NULL AUTO_INCREMENT,
		    `updated_ts`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		    `smt_id`              INTEGER NOT NULL,
		    `valueTimestamp`      DATE,
		    `entryTimestamp`      DATE,
		    `account`             VARCHAR(24),
		    `accountName`         VARCHAR(60),
		    `transactionType`     VARCHAR(3),
		    `transactionCode`     VARCHAR(255),
		    `transactionCodeDesc` VARCHAR(32),
		    `transactionDC`       VARCHAR(2),
		    `transactionAmount`   DOUBLE,
		    `transactionTitle`    VARCHAR(256),
		    `status`              INTEGER DEFAULT 0,
		    `matchinfo`           VARCHAR(256),
		    `fa_trans_type`       INTEGER DEFAULT 0,
		    `fa_trans_no`         INTEGER DEFAULT 0,
		    `fitid`               VARCHAR(32),
		    `acctid`              VARCHAR(32),
		    `merchant`            VARCHAR(64),
		    `category`            VARCHAR(64),
		    `sic`                 VARCHAR(64),
		    `memo`                VARCHAR(64),
		    `checknumber`         INTEGER,
		    `matched`             INTEGER DEFAULT 0,
		    `created`             INTEGER DEFAULT 0,
		    `g_partner`           VARCHAR(32),
		    `g_option`            VARCHAR(32),
		    PRIMARY KEY(`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		db_query($sql, 'Failed to ensure bi_transactions table');

		$sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . "bi_partners_data` (
		    `updated_ts`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		    `partner_id`        INTEGER NOT NULL,
		    `partner_detail_id` INTEGER NOT NULL,
		    `partner_type`      INTEGER NOT NULL,
		    `data`              VARCHAR(256) NOT NULL,
		    `occurrence_count`  INTEGER DEFAULT 1,
		    CONSTRAINT `idx_partner_keyword` UNIQUE(`partner_id`, `partner_detail_id`, `partner_type`, `data`),
		    INDEX `idx_partner_type_data` (`partner_type`, `data`),
		    INDEX `idx_occurrence_count` (`occurrence_count`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		db_query($sql, 'Failed to ensure bi_partners_data table');
	}

	private function ensure_column($table, $column, $definition)
	{
		if (!$this->table_exists($table)) {
			return;
		}
		if ($this->column_exists($table, $column)) {
			return;
		}
		$sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
		db_query($sql, 'Failed adding column to bank import schema');
	}

	private function table_exists($table)
	{
		$sql = "SHOW TABLES LIKE " . db_escape($table);
		$res = db_query($sql, 'Failed checking table existence');
		return db_num_rows($res) > 0;
	}

	private function column_exists($table, $column)
	{
		$sql = "SHOW COLUMNS FROM `{$table}` LIKE " . db_escape($column);
		$res = db_query($sql, 'Failed checking column existence');
		return db_num_rows($res) > 0;
	}

	private function index_exists($table, $indexName)
	{
		$sql = "SHOW INDEX FROM `{$table}` WHERE Key_name = " . db_escape($indexName);
		$res = db_query($sql, 'Failed checking index existence');
		return db_num_rows($res) > 0;
	}

	private function ensure_unique_index($table, $indexName, $columns)
	{
		if (!$this->table_exists($table) || $this->index_exists($table, $indexName)) {
			return;
		}
		$colsSql = array();
		foreach ($columns as $col) {
			$colsSql[] = "`{$col}`";
		}
		$sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$indexName}` UNIQUE(" . implode(', ', $colsSql) . ")";
		db_query($sql, 'Failed adding unique index for bank import schema');
	}

	private function ensure_index($table, $indexName, $columns)
	{
		if (!$this->table_exists($table) || $this->index_exists($table, $indexName)) {
			return;
		}
		$colsSql = array();
		foreach ($columns as $col) {
			$colsSql[] = "`{$col}`";
		}
		$sql = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (" . implode(', ', $colsSql) . ")";
		db_query($sql, 'Failed adding index for bank import schema');
	}

	private function ensure_partner_keyword_unique_index($table)
	{
		if (!$this->table_exists($table)) {
			return;
		}
		// Prefer the newer unique key used for keyword scoring.
		if ($this->index_exists($table, 'idx_partner_keyword')) {
			return;
		}

		// Drop the legacy unique key if present (partner_id, partner_detail_id, partner_type)
		if ($this->index_exists($table, 'idx')) {
			$sql = "ALTER TABLE `{$table}` DROP INDEX `idx`";
			db_query($sql, 'Failed dropping legacy partner data unique index');
		}

		$sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `idx_partner_keyword` UNIQUE(`partner_id`, `partner_detail_id`, `partner_type`, `data`)";
		db_query($sql, 'Failed adding partner keyword unique index');
	}

	private function set_default_occurrence_count($table)
	{
		if (!$this->table_exists($table) || !$this->column_exists($table, 'occurrence_count')) {
			return;
		}
		$sql = "UPDATE `{$table}` SET `occurrence_count` = 1 WHERE `occurrence_count` IS NULL";
		db_query($sql, 'Failed initializing occurrence_count');
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
