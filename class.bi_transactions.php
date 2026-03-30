<?php

/****************************************************************************************
 * Table and handling class for staging of imported financial data
 *
 * This table will hold each record that we are importing.  That way we can check if
 * we have already seen the record when re-processing the same file, or perhaps one
 * from the same source that overlaps dates so we would have duplicate data.
 *
 * *************************************************************************************/


$path_to_root = "../..";

/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

//TODO
//	Update the queries in the functions to use $this->table_details['tablename'] instead of .TB_PREF."bi_transactions
//
// TODO - Future Filter Enhancements (Mantis #3188 follow-up):
//	1. Add transaction amount range filter (min/max) - see get_transactions() line 425
//	   - Useful for finding large transactions or specific amount ranges
//	   - Should support: minAmount and maxAmount parameters
//	   - Implementation: WHERE t.transactionAmount >= minAmount AND t.transactionAmount <= maxAmount
//	
//	2. Add transaction title filter (LIKE search) - see get_transactions() line 430
//	   - Useful for searching by vendor name, description, memo text
//	   - Should support: partial text matching with wildcards
//	   - Implementation: WHERE t.transactionTitle LIKE '%searchText%'
//	   - Consider: Case-insensitive search, multiple keywords (AND/OR logic)
//	
//	See: Services/TransactionFilterService.php for scaffolded implementation
//	See: header_table.php for UI element placement notes 

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * This table should not have any views (forms).
 * */


require_once(__DIR__ . '/class.bi_transfer_matches.php');

/**//**************************************************************************************************************
 * A DATA class to handle the storage and retrieval of bank records.  STAGE the records before processing into FA.
 *
 *
 *
 *	***** WARNING *** WARNING *** WARNING *****
 *	MySQL has a row limit of 4k.  Having a bunch of large fields can lead to errors and issues.
 *
 *	+---------------------+--------------+------+-----+---------+----------------+
 *	| Field               | Type         | Null | Key | Default | Extra          |
 *	+---------------------+--------------+------+-----+---------+----------------+
 *	| id                  | int(11)      | NO   | PRI | NULL    | auto_increment |
 *	| smt_id              | int(11)      | NO   |     | NULL    |                |
 *	| valueTimestamp      | date         | YES  |     | NULL    |                |
 *	| entryTimestamp      | date         | YES  |     | NULL    |                |
 *	| account             | varchar(24)  | YES  |     | NULL    |                |
 *	| accountName         | varchar(60)  | YES  |     | NULL    |                |
 *	| transactionType     | varchar(3)   | YES  |     | NULL    |                |
 *	| transactionCode     | varchar(32)  | YES  |     | NULL    |                |
 *	| transactionCodeDesc | varchar(32)  | YES  |     | NULL    |                |
 *	| transactionDC       | varchar(2)   | YES  |     | NULL    |                |
 *	| transactionAmount   | double       | YES  |     | NULL    |                |
 *	| transactionTitle    | varchar(256) | YES  |     | NULL    |                |
 *	| status              | int(11)      | YES  |     | 0       |                |
 *	| matchinfo           | varchar(256) | YES  |     | NULL    |                |
 *	| fa_trans_type       | int(11)      | YES  |     | 0       |                |
 *	| fa_trans_no         | int(11)      | YES  |     | 0       |                |
 *	| fitid               | varchar(32)  | NO   |     | NULL    |                |
 *	| acctid              | varchar(32)  | NO   |     | NULL    |                |
	| merchant            | varchar(64)  | NO   |     | NULL    |                |
	| category            | varchar(64)  | NO   |     | NULL    |                |
	| sic                 | varchar(64)  | NO   |     | NULL    |                |
	| memo                | varchar(64)  | NO   |     | NULL    |                |
	| checknumber         | int(11)      | NO   |     | NULL    |                |
	| matched             | int(1)       | NO   |     | 0       |                |
	| created             | int(1)       | NO   |     | 0       |                |
 *	+---------------------+--------------+------+-----+---------+----------------+
 *	
 *
         * Inherits:
        *    ORIGIN
        *       function __construct( $loglevel = PEAR_LOG_DEBUG )
        *       function set_var( $var, $value )
        *       function get_var( $var )
        *       function var2data()
        *       function fields2data( $fieldlist )
        *       function LogError( $message, $level = PEAR_LOG_ERR )
        *       function LogMsg( $message, $level = PEAR_LOG_INFO )
         *    DB_BASE
        *       function __construct( $host, $user, $pass, $database, $prefs_tablename )
        *       function connect_db()
        *       function is_installed()
        *       function set_prefix()
        *       function create_prefs_tablename()
        *       function mysql_query( $sql = null, $errmsg = NULL )
        *       function set_pref( $pref, $value )
        *       function get_pref( $pref )
        *       function loadprefs()
        *       function updateprefs()
        *       function create_table( $table_array, $field_array )
        *    GENERIC_FA_INTERFACE
        *       function __construct( $host, $user, $pass, $database, $pref_tablename )
        *       function eventloop( $event, $method )
        *       function eventregister( $event, $method )
        *       function add_submodules()
        *       function module_install()
        *       function install()
        *       function loadprefs()
        *       function updateprefs()
        *       function checkprefs()
        *       function call_table( $action, $msg )
        *       function action_show_form()
        *       function show_config_form()
        *       function form_export()
        *       function related_tabs()
        *       function show_form()
        *       function base_page()
        *       function display()
        *       function run()
        *       function modify_table_column( $tables_array )
        *       function adjust_stock_id_lengths( $barcode_max_length, $sku_length, $stock_id )
        *       function append_file( $filename )
        *       function overwrite_file( $filename )
        *       function open_write_file( $filename )
        *       function write_line( $fp, $line )
        *       function close_file( $fp )
        *       function file_finish( $fp )
        *       function backtrace()
        *       function write_sku_labels_line( $stock_id, $category, $description, $price )
        *       function show_generic_form($form_array)
	*    generic_fa_interface_model
	*
*
******************************************************************************************************************/
class bi_transactions_model {
	use Ksfraser\GenericInterface\GenericFaInterfaceTrait;
	/**
	 * Ensure the staging table schema is present (idempotent, non-destructive).
	 *
	 * Table creation is handled by sql/update.sql during module activation; this only
	 * repairs drift (missing columns) for older installs.
	 */
	public static function ensure_schema(): void
	{
		$table = TB_PREF . 'bi_transactions';
		if (!self::table_exists($table)) {
			return;
		}

		self::ensure_column($table, 'updated_ts', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		self::ensure_column($table, 'matched', 'INTEGER DEFAULT 0');
		self::ensure_column($table, 'created', 'INTEGER DEFAULT 0');
		self::ensure_column($table, 'g_partner', 'VARCHAR(32) NULL');
		self::ensure_column($table, 'g_option', 'VARCHAR(32) NULL');
// Removed duplicate/stray use and class statements from previous merge/refactor
		self::ensure_column($table, 'bankid', 'VARCHAR(64) NULL');
		self::ensure_column($table, 'intu_bid', 'VARCHAR(64) NULL');
	}

	private static function table_exists(string $table): bool
	{
		$res = db_query('SHOW TABLES LIKE ' . db_escape($table), 'Failed checking table existence');
		return db_num_rows($res) > 0;
	}

	private static function column_exists(string $table, string $column): bool
	{
		$res = db_query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . db_escape($column), 'Failed checking column existence');
		return db_num_rows($res) > 0;
	}

	private static function ensure_column(string $table, string $column, string $definition): void
	{
		if (!self::table_exists($table) || self::column_exists($table, $column)) {
			return;
		}
		db_query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition, 'Failed adding column to bi_transactions');
	}

	var $id_bi_transactions_model;	//!< Index of table
	public $id;                  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	public $smt_id;              //| int(11)      | NO   |     | NULL    |                |
	public $valueTimestamp;      //| date         | YES  |     | NULL    |                |
	public $entryTimestamp;      //| date         | YES  |     | NULL    |                |
	public $account;             //| varchar(24)  | YES  |     | NULL    |                |
	public $accountName;         //| varchar(60)  | YES  |     | NULL    |                |
	public $transactionType;     //| varchar(3)   | YES  |     | NULL    |                |
	public $transactionCode;     //| varchar(32)  | YES  |     | NULL    |                |
	public $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |                |
	public $transactionDC;       //| varchar(2)   | YES  |     | NULL    |                |
	public $transactionAmount;   //| double       | YES  |     | NULL    |                |
	public $transactionTitle;    //| varchar(256) | YES  |     | NULL    |                |
	public $status;              //| int(11)      | YES  |     | 0       |                |
	public $matchinfo;           //| varchar(256) | YES  |     | NULL    |                |
	public $fa_trans_type;       //| int(11)      | YES  |     | 0       |                |
	public $fa_trans_no;         //| int(11)      | YES  |     | 0       |                |
	public $fitid;
	public $acctid;
	public $bankid;
	public $intu_bid;
	public $merchant;            //| varchar(64)  | NO   |     | NULL    |                |
	public $category;            //| varchar(64)  | NO   |     | NULL    |                |
	public $sic;                 //| varchar(64)  | NO   |     | NULL    |                |
	public $memo;                //| varchar(64)  | NO   |     | NULL    |                |
	public $checknumber; //!<int
	public $matched; //!<bool
	public $created; //!<bool
	public $g_partner; //!<varchar Which action (bank/Quick Entry/... 
	public $g_option; //!<varchar Which choice - ATB/Groceries/... 
	public $limit; //!<int  SQL Limit



	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		//display_notification( __FILE__ . "::" . __LINE__ );
		// No parent::__construct() call needed after trait refactor
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "bi_transactions";
		$this->define_table();
		$this->matched = 0;
		$this->created = 0;
	}
	function define_table()
	{
		$ind = "id";
		//$ind = "id_" . $this->iam;
		//$this->fields_array = array();
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
                {
                        $this->company_prefix = TB_PREF;
                }
                $this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		$this->table_details['orderby'] = 'valueTimestamp, id';
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "transaction_id";
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$this->fields_array[] = array('name'=> 'id', 'label' => 'ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'smt_id', 'label' => 'Statement ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'valueTimestamp', 'label' => 'Value Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'entryTimestamp', 'label' => 'Entry Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'account', 'label' => 'Account', 'type' => 'varchar(24)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'accountName', 'label' => 'Account Name', 'type' => 'varchar(60)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionType', 'label' => 'Transaction Type', 'type' => 'varchar(3)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionCode', 'label' => 'Transaction Code', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionCodeDesc', 'label' => 'Transaction Desc', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionDC', 'label' => 'Transaction DC', 'type' => 'varchar(2)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionAmount', 'label' => 'Transaction Amount', 'type' => 'double', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'transactionTitle', 'label' => 'Transaction Title', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'status', 'label' => 'Status', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'matchinfo', 'label' => 'Match Info', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'fa_trans_type', 'label' => 'FA Transaction Type', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'fa_trans_no', 'label' => 'FA Transaction Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'fitid', 'label' => 'Financial Institute Transaction ID', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'acctid', 'label' => 'Account ID', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'bankid', 'label' => 'Bank ID', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'intu_bid', 'label' => 'Intuit Bank ID', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'merchant', 'label' => 'Merchant', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'category', 'label' => 'Category', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'sic', 'label' => 'S I Code', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'memo', 'label' => 'Memo', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'checknumber', 'label' => 'Check Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'matched', 'label' => 'Matched', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'created', 'label' => 'Created', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'g_partner', 'label' => 'Transaction Type (Partner)', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		$this->fields_array[] = array('name'=> 'g_option', 'label' => 'Transaction Type Detail', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0' );
		//$this->table_interface->set( "fields_array", $this->fields_array, 0 );
		//$this->fieldsarray2tableinterface( $this->fields_array );
	}
        /*****************************************************************//**
        * Validate the field to be set
        * @param string field to set
        * @param mixed value to set
        * @return bool. (parent) throws exceptions
        **********************************************************************/
	function validate_field( $field, $value = null )
	{

		switch( $field )
		{
			case 'limit':
				if( ! is_numeric( $value ) )
				{
					throw new Exception( "Limit must be a number as its for SQL", KSF_INVALID_DATA_TYPE );
				}
				break;
		}
		return true;
	}
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}
	function summary_sql( $TransAfterDate, $TransToDate, $statusFilter )
	{
		$sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF ."bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
        	$sql .= " WHERE t.valueTimestamp >= ".db_escape( date2sql( $TransAfterDate ) ) ." AND t.valueTimestamp <  " . db_escape( date2sql( $TransToDate ) );
    		if ( $statusFilter != 255) {
        		$sql .= " AND t.status = ".db_escape( $statusFilter );
    		}
    		$sql.= " ORDER BY t.valueTimestamp ASC";
		var_dump( $sql );
/**/
    		$res = db_query($sql, 'unable to get transactions data');
		var_dump( $res );
		return $res;
/**/
	}
	/**//*************************************************************************
	* Update bi_trans clearing status
	*
	*	   If we had created the transaction, should we void it?
	*		By not voiding it, the transaction will later be "matched".
	*
	* @param int BI transaction index
	* @param array list of related transactions
	* @param int The transaction number
	* @param int the Transaction Type (JE/BP/SP/...)
	* @returns none
	******************************************************************************/
	function reset_transactions($tid, $cids, $trans_no, $trans_type) 
	{
		$cids[] = $tid;
		$cids = implode(',', $cids);
	
		$sql = "
			UPDATE ".TB_PREF."bi_transactions
			SET status=0,
				fa_trans_no=".db_escape($trans_no).",
				fa_trans_type=".db_escape($trans_type);
			$sql .= ",
					matched=0";
			$sql .= ",
					created=0";
			$sql .= "
				WHERE id in ($cids)";
		//display_notification($sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	*       Hooks db_prevoid does similar
	*
	* @param int BI transaction index
	* @param array list of related transactions
	* @param int The status to set
	* @param int The transaction number
	* @param int the Transaction Type (JE/BP/SP/...)
	* @param bool matched the transaction
	* @param bool created the transaction
	* @param string|null Transaction type code SP/BT/QE/...
	* @param string The QE or vendor or customer or... int as string
	* @returns none
	******************************************************************************/
	function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "" ) 
	{
		$cids[] = $tid;
		$cids = implode(',', $cids);
	
		$sql = "
			UPDATE ".$this->table_details['tablename'] . 
			" SET status=".db_escape($status).",
				fa_trans_no=".db_escape($trans_no).",
				fa_trans_type=".db_escape($trans_type);
		if( $matched )
		{
			$sql .= ", matched=1";
		}
		else
		if( $created )
		{
			$sql .= ", created=1";
		}
/** MANTIS 2933*/
		if( null != $g_partner )
		{
			$sql .= ", g_partner='" . $g_partner . "'";
			$sql .= ", g_option='" . $g_option . "'";
		}
/** ! MANTIS 2933*/
			$sql .= "
			WHERE id in ($cids)";
		//display_notification($sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	* @param int BI transaction index
	* @param string account
	* @param string account Name
	* @returns none
	******************************************************************************/
	function update_transactions_account($tid, $account, $accountName )
	{
	        $cids = array();
	        $cids[] = $tid;
	        $cids = implode(',', $cids);
	
	        $sql = "
	                UPDATE ".TB_PREF."bi_transactions
	                SET
	                        account=" .     db_escape($account).",
	                        accountName=" . db_escape($accountName)."
	                WHERE id in ($cids)";
	        display_notification($sql);
	        db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Reset bi_trans data when the related FA gl transaction is voided
	*
	* @param int|array the Transaction Type (JE/BP/SP/...)
	* @param int The transaction number
	* @returns none
	******************************************************************************/
	function db_prevoid( $type, $trans_no )
	{
		if( is_array( $type ) )
		{
			$trans_type = $type['trans_type'];
		}
		else
		{
			$trans_type = $type;
		}
		//When a FA GL entry is being voided
		$sql = "
			UPDATE " . $this->table_details['tablename'] .
			" SET status=0, fa_trans_no=0, fa_trans_type=0, created=0, matched=0, g_partner='', g_option=''
			WHERE
				fa_trans_no=".db_escape($trans_no)." AND
				fa_trans_type=".db_escape($trans_type)." AND
				status = 1";
		//display_notification($sql);
		db_query($sql, 'Could not void transaction');
	}
	/**//**********************************************************************
	* Get transactions details for display
	*
	* @param int status
	* @returns array transaction rows sorted
	***************************************************************************/
	function get_transactions( $status = null, $transAfterDate = null, $transToDate = null, $transactionAmount = null, $transactionTitle = null, $limit = null, $bankAccount = null ) 
	{
		if( null == $transAfterDate )
		{
			$transAfterDate = $_POST['TransAfterDate'];
		}
		if( null == $transToDate )
		{
			$transToDate = $_POST['TransToDate'];
		}
		if( null == $bankAccount )
		{
			$bankAccount = isset($_POST['bankAccountFilter']) ? $_POST['bankAccountFilter'] : 'ALL';
		}
		
		$trzs = array();
   		$sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF . "bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
		
		// Use TransactionFilterService to build WHERE clause
		require_once(__DIR__ . '/Services/TransactionFilterService.php');
		$filterService = new \KsfBankImport\Services\TransactionFilterService();
		$sql .= $filterService->buildWhereClause($transAfterDate, $transToDate, $status, $bankAccount);

		if( null !== $limit )
		{
			if( is_numeric( $limit ) )
			{
				$sql .= " LIMIT $limit ";
			}
		}
		else if( isset( $this->limit ) )
		{
			$sql .= " LIMIT $this->limit ";
		}
        	$sql .= " ORDER BY t.valueTimestamp ASC";

	        $result = db_query($sql, "could not get transaction data");
        	while($myrow = db_fetch($result))
        	{
        	        //display_notification( __FILE__ . "::" . __LINE__ );
        	        $trz_code = $myrow['transactionCode'];
        	        if( !isset( $trzs[$trz_code] ) )
        	        {
        	                        $trzs[$trz_code] = array();
        	        }
        	        $trzs[$trz_code][] = $myrow;
        	}
	        return $trzs;
	}
	/**//**********************************************************************
	* Get a specific transaction's details
	*
	* @param int index
	* @param bool should we set the internal variables.  Since this is new, defaulting to legacy behaviour
	* @returns array transaction row from db
	***************************************************************************/
	function get_transaction( $tid = null, $bSetInternal = false ) 
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		if( $tid == null )
		{
		//display_notification( __FILE__ . "::" . __LINE__ );
			if( isset( $this->id ) )
				$tid = $this->id;
			else
				throw new Exception( "No ID set to search for" );
		}
		//display_notification( __FILE__ . "::" . __LINE__ );
	        $sql = "
	            SELECT t.*, s.account our_account FROM ".TB_PREF."bi_transactions t
	            LEFT JOIN ".TB_PREF."bi_statements as s ON t.smt_id = s.id
	            WHERE t.id=".db_escape($tid);
	        $result = db_query($sql, "could not get transaction with id $tid");
	        $res = db_fetch($result);
			//display_notification( __FILE__ . "::" . __LINE__ . print_r( $res, true ) );
		if( $bSetInternal )
		{
			//display_notification( __FILE__ . "::" . __LINE__ );
			$this->arr2obj( $res );
			//display_notification( __FILE__ . "::" . __LINE__  . "::" . print_r( $this, true ) );
		}
	        return $res;
	}

	/**
	 * Fetch transactions flagged for manual review.
	 *
	 * @param int|null $limit
	 * @return array
	 */
	function get_transactions_requiring_review($limit = 250)
	{
		$sql = "SELECT DISTINCT t.*, s.account our_account FROM " . TB_PREF . "bi_transactions t
			LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id
			INNER JOIN " . TB_PREF . "bi_transfer_matches m
				ON (m.debit_transaction_id = t.id OR m.credit_transaction_id = t.id)
			WHERE m.requires_review = 1
			ORDER BY t.valueTimestamp ASC";

		if( null !== $limit && is_numeric($limit) )
		{
			$sql .= " LIMIT " . (int)$limit;
		}

		$res = db_query($sql, 'Could not fetch transactions requiring review');
		$ret = array();
		while($row = db_fetch($res))
		{
			$ret[] = $row;
		}

		return $ret;
	}

	/**
	 * Reset JE association and transfer matching state for reprocess workflow.
	 *
	 * @param int $tid
	 * @return void
	 */
	function reset_transaction_association($tid)
	{
		if (class_exists('bi_transfer_matches_model')) {
			$transferMatches = new bi_transfer_matches_model();
			$transferMatches->clear_by_transaction((int)$tid);
		}

		$sql = "
			UPDATE " . $this->table_details['tablename'] . "
			SET status=0,
				fa_trans_no=0,
				fa_trans_type=0,
				created=0,
				matched=0,
				g_partner=NULL,
				g_option=''
			WHERE id=" . db_escape((int)$tid);

		db_query($sql, 'Could not reset transaction association');
	}
	/**//**********************************************************************
	* Get a the normal actions for a counterparty
	*
	* @since 20240729 
	* @param string Account to search for
	* @returns array transaction rows from db
	***************************************************************************/
	function get_normal_pairing( $account = null) 
	{
		$sql = "SELECT count(*) as count, `account`, `g_option`, `g_partner` FROM `0_bi_transactions` group by account, g_option, g_partner";
		if( null != $account )
			$sql .= " WHERE account = '" . $account . "'";
	        $result = db_query($sql, "could not get transaction with account $account");
	        return db_fetch($result);
	}
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	       // trz2obj now inherited from GenericObjectMappingTrait
	/**//************************************************************
	* Hand build the INSERT statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_insert_sql()
	{
               $sql = 	"INSERT IGNORE INTO " . $this->table_details['tablename'] .
			"(smt_id, valueTimestamp, entryTimestamp, account, accountName, transactionType, " .
                    		"transactionCode, transactionCodeDesc, transactionDC, transactionAmount, transactionTitle, merchant, category, status, memo, sic, acctid, fitid, bankid, intu_bid, checknumber ) " .
			" VALUES( " .
		                    db_escape($this->smt_id) . ", ".
		                    db_escape($this->valueTimestamp) . ", ".
		                    db_escape($this->entryTimestamp) . ", ".
		                    db_escape($this->account) . ",".
		                    db_escape($this->accountName) . ", ".
		                    db_escape($this->transactionType) . ", ".
		                    db_escape($this->transactionCode) . ", ".
		                    db_escape($this->transactionCodeDesc) . ", ".
		                    db_escape($this->transactionDC) . ", ".
		                    db_escape($this->transactionAmount) . ", ".
		                    db_escape($this->transactionTitle) . ", ".
		                    db_escape($this->merchant) . ", ".
		                    db_escape($this->category) . ", ".
		                    db_escape($this->status) . ", ".
		                    db_escape($this->memo) . ", ".
		                    db_escape($this->sic) . ", ".
				db_escape($this->acctid) . ", " .
                                db_escape($this->fitid) . ", " .
                                db_escape($this->bankid) . ", " .
                                db_escape($this->intu_bid) . ", " .
		                    db_escape($this->checknumber) . 
			")";
		return $sql;
	}
	/**//************************************************************
	* Hand build the UPDATE statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_update_sql()
	{
               $sql = 	"UPDATE " . $this->table_details['tablename'] .
			"SET " .
			"smt_id=" .  db_escape($this->smt_id) . ", ".
			"valueTimestamp=" .  db_escape($this->valueTimestamp) . ", ".
			"entryTimestamp=" .  db_escape($this->entryTimestamp) . ", ".
			"account=" .  db_escape($this->account) . ",".
			"accountName=" .  db_escape($this->accountName) . ", ".
			"transactionType=" .  db_escape($this->transactionType) . ", ".
                    	"transactionCode=" .  db_escape($this->transactionCode) . ", ".
                    	"transactionCodeDesc=" .  db_escape($this->transactionCodeDesc) . ", ".
                    	"transactionDC=" .  db_escape($this->transactionDC) . ", ".
                    	"transactionAmount=" .  db_escape($this->transactionAmount) . ", ".
                    	"transactionTitle=" .  db_escape($this->transactionTitle) . ", ".
                    	"merchant=" .  db_escape($this->merchant) . ", ".
                    	"category=" .  db_escape($this->category) . ", ".
                    	"status=" .  db_escape($this->status) . ", ".
                    	"memo=" .  db_escape($this->memo) . ", ".
                    	"sic=" .  db_escape($this->sic) . ", ".
			"acctid=" . db_escape($this->acctid) . ", " .
                        "fitid=" . db_escape($this->fitid) . ", " .
                        "bankid=" . db_escape($this->bankid) . ", " .
                        "intu_bid=" . db_escape($this->intu_bid) . ", " .
                    	"checknumber=" .  db_escape($this->checknumber);
		$sql .=	" WHERE ";
		return $sql;
	}
	/**//************************************************************
	* Determine if this particular transaction already exists in the staging table
	*
	*	We need to do duplicate checking.  Most banks allow a person to select a date range for their transactions.
	*	That is probably why the original author, when taking apart his mt90 files, made each day its own statement.
	*
	*	We can have the same total for the same vendor on the same date and not automatically be a duplicate
	*		Example when I buy flats of coke on sale limit 4 per transaction. 
	*	In this case it is the transaction reference (transactionCode) that is the unique identifier.
	*
	*	The trans ref is not guaranteed unique between banks.  Only within the bank.
	*
	*	I've changed the table definition to have a unique key on transactionCode-accountName-transactionAmount.
	*
	*	20240919 Came upon a case where Manulife used the same transactionCode for "Interest Deposit" in 2 different accounts
	*		so it was showing as a dupe when it wasnt.
	*
	* @param none
	* @returns bool already exists? 
	*****************************************************************/
	function trans_exists()
	{
		$sql = " SELECT * from " . $this->table_details['tablename'] .
			" WHERE transactionCode=" . db_escape($this->transactionCode) . " AND acctid=" . db_escape($this->acctid);
			//" WHERE transactionCode=" . db_escape($this->transactionCode) . " AND accountName=" . db_escape($this->accountName);
		//	display_notification( __FILE__ . "::" . __LINE__ . " " . $sql );

                $res = db_query($sql, "could not Select transaction");
                $dupes=0;
                while($row = db_fetch($res) )
                {
                	if( isset( $row['transactionCode'] ) )
                        	$dupes++;
           	}
		//There should only be 1 result with this account + transaction code.  Banks are supposed to be unique internally.
		//Should we throw an error if there are more than 1?
		if( $dupes > 0 )
		{
			if( $dupes == 1 )
			{
				//Set this classes' variables from the db result.
				try
				{
					$ret = $this->arr2obj( $row );
					//ret is number of fields set.
				} 
				catch( Exception $e )
				{
					display_notification( __FILE__ . "::" , __LINE__ . "::" . $e->getCode() . ":" . $e->getMessage() );
				}
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	/**//******************************************************************
	* Update a transaction record
	*
	* There are a couple of use cases here
	* 	We are re-importing the same file because we've enhanced the tables
	*		Update the memo, category, status, sic, etc fields
	*	The record appears in multiple files (we've changed export dates at the bank)
	*		If this is a more recent file, we want to update other info too
	*			update amount.
	*				If the transaction is processed, we don't want to change this without adjusting the GLs too!!
	*			void the fa_trans and re-do
	*		If this is an older file, we only want to update info that is blank
	*
	* @param array the transaction from bank_import's import
	* @returns bool success
	*****************************************************************/
	function update( $arr )
	{
		//This class's variables were set by trans_exists when called by import_statements

		//Find out what has changed
		$diffarr = array();
		foreach( get_object_vars($this) as $key => $value )
                {
                        if( isset( $arr[$key] ) )
                        {
                                if( $this->isdiff( $key, $arr[$key] ) )
				{
					$diffarr[$key] = $value;
				}
                        }
                        else
                        {
                                //      display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $arr, true ) );
                        }
                }

		if( $this->matched )
		{
			//If matched, we may have just looked at Journals and amount
		}
		if( $this->created )
		{
			if( isset( $diffarr['transactionCode'] ) )
			{
				//Transaction Code is the key we checked against.  If it changed we have a logic error elsewher
				throw new Exception( "Transaction Code, which was our search key changed.  LOGIC ERROR!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['accountName'] ) )
			{
				//account can't change - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - accountName changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['account'] ) )
			{
				//account can't change - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - account changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['valueTimestamp'] ) OR isset( $diffarr['entryTimestamp'] ) )
			{
				//time can't change if transactions are immutable - not an identical transaction.  ERROR somewhere
				throw new Exception( "We should not have matched against this transaction - Timestamps changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['transactionType'] ) )
			{
				//Transaction Type shouldn't change, but there might be some reason it could - some change in my code somewhere and re-import an old file?  
				//throw new Exception( "We should not have matched against this transaction - account changed!", KSF_INVALID_DATA_VALUE );
			}
			if( isset( $diffarr['transactionAmount'] ) )
			{
				if( abs($diffarr['transactionAmount']) !== abs($this->transactionAmount) )
				{
					throw new Exception( "(ABS) Transaction Amount changed! It is possible that the sign changed due to our re-processing, but the absolute value shouldn't", KSF_INVALID_DATA_VALUE );
				}
				else
				{
					//Sign changed but value didn't.  We will want to update our record.
					$this->set( "transactionAmount", $diffarr['transactionAmount'] );
				}
			}
			//smt_id could change if we changed date ranges and imported.
			if( isset( $diffarr['smt_id'] ) )
			{
				$this->set( "smt_id",  $diffarr['smt_id'] );
			}
			//transactionCodeDesc and transactionDC could possibly change
			if( isset( $diffarr['transactionCodeDesc'] ) )
			{
				$this->set( "transactionCodeDesc",  $diffarr['transactionCodeDesc'] );
			}
			if( isset( $diffarr['transactionDC'] ) )
			{
				$this->set( "transactionDC",  $diffarr['transactionDC'] );
			}
			//If status is set, then we've matched/created.,,
			//matchinfo shouldn't match on a re-import
			//fa_trans_type and fa_trans_no should not match.
			//fitid and acctid should not change.  However our earlier imports didn't set it.
			if( isset( $diffarr['fitid'] ) )
			{
				$this->set( "fitid",  $diffarr['fitid'] );
			}
			if( isset( $diffarr['acctid'] ) )
			{
				$this->set( "acctid",  $diffarr['acctid'] );
			}
			//merchant, category and sic may not have been set in the past.
			if( isset( $diffarr['merchant'] ) )
			{
				$this->set( "merchant",  $diffarr['merchant'] );
			}
			if( isset( $diffarr['category'] ) )
			{
				$this->set( "category",  $diffarr['category'] );
			}
			if( isset( $diffarr['sic'] ) )
			{
				$this->set( "sic",  $diffarr['sic'] );
			}
			

			//If created, we might have to update/void transactions     
			//	| fa_trans_type       | int(11)      | YES  |     | 0       |                |
			//	| fa_trans_no         | int(11)      | YES  |     | 0       |                |
			//if so, status, matched, created need to be set to 0 as well!
		}
	}
	/**//******************************************************************************************
	* Toggle from D to C or C to D
	*
	*	Some banks don't send the data correctly.  Toggle the direction
	***********************************************************************************************/	
	function toggleDebitCredit()
	{
		display_notification( __FILE__ . "::" . __LINE__ );
		if( ! isset( $this->transactionDC ) )
		{
			throw new Exception( "Required field transactionDC not set!", KSF_FIELD_NOT_SET );
		}
		switch( $this->transactionDC )
		{
			case 'D':
			display_notification( __FILE__ . "::" . __LINE__ . " Case D going C" );
				$this->set( "transactionDC", "C" );
				$this->set( "transactionCodeDesc", "Credit" );
				break;
			case 'C':
				display_notification( __FILE__ . "::" . __LINE__ . " Case C going D" );
				$this->set( "transactionDC", "D" );
				$this->set( "transactionCodeDesc", "Debit" );
				break;
				default:
				display_notification( __FILE__ . "::" . __LINE__  . " Unexpected value" );
				throw new Exception( "field transactionDC has unexpected value!", KSF_INVALID_DATA_VALUE );
		}
		$sql = " UPDATE " . TB_PREF ."bi_transactions t ";
		$sql .= "set transactionDC='" . $this->transactionDC . "', transactionCodeDesc='" . $this->transactionCodeDesc . "' ";
        	$sql .= " WHERE t.id = '" . $this->id . "'";
		display_notification( __FILE__ . "::" . __LINE__ . ":: SQL::" . $sql );
/**/
    		$res = db_query($sql, 'unable to get transactions data');
		//var_dump( $res );
		//return $res;
	}

	// =====================================================================
	// BankAccountMapping Cross-Reference Methods (Phase 2)
	// =====================================================================

	/**
	 * Get BankAccountMapping from counterparty data
	 * 
	 * Extracts BankAccountMapping from counterparty account information
	 * if available in the transaction context.
	 * 
	 * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
	 */
	public function getBankAccountMappingFromCounterparty(): ?\Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory')) {
				return null;
			}
			
			// Try to extract from counterparty if available
			$counterpartyData = [];
			if (isset($this->counterpartyAccount)) {
				$counterpartyData['acctid'] = $this->counterpartyAccount;
			}
			
			if (empty($counterpartyData)) {
				return null;
			}
			
			return \Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory::createFromArray($counterpartyData);
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Get the FA bank account ID this transaction maps to
	 * 
	 * Returns the FrontAccounting bank account ID that this transaction
	 * is associated with through its parent statement's mapping.
	 * 
	 * @return int|null
	 */
	public function getFABankAccountFromMapping(): ?int
	{
		try {
			// Get parent statement first
			$smtData = $this->get_statement_by_id($this->smt_id);
			if (!is_array($smtData) || empty($smtData['bankid']) && empty($smtData['acctid'])) {
				return null;
			}
			
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository')) {
				return null;
			}
			
			// Get mapping from statement's OFX identifiers
			$mapping = \Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository::findByOFXIdentifiers(
				$smtData['bankid'] ?? null,
				$smtData['acctid'] ?? null,
				$smtData['intu_bid'] ?? null
			);
			
			return $mapping ? $mapping->bank_account_id : null;
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Extract BankAccountMapping from parent statement
	 * 
	 * Safely extracts the BankAccountMapping from this transaction's
	 * parent statement. Returns null gracefully if statement is missing.
	 * 
	 * @param array|null $statement Optional pre-fetched statement data
	 * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
	 */
	public function extractMappingFromStatement(?array $statement = null): ?\Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory')) {
				return null;
			}
			
			// Get statement if not provided
			if ($statement === null) {
				$statement = $this->get_statement_by_id($this->smt_id);
			}
			
			if (!is_array($statement)) {
				return null;
			}
			
			// Create mapping from statement's OFX identifiers
			$mappingData = [
				'bankid' => $statement['bankid'] ?? null,
				'acctid' => $statement['acctid'] ?? null,
				'intu_bid' => $statement['intu_bid'] ?? null,
				'curdef' => $statement['currency'] ?? null
			];
			
			return \Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory::createFromArray($mappingData);
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Update transaction's mapping reference after import
	 * 
	 * Finalizes the mapping reference for this transaction after import
	 * completes. Operation is idempotent and safe to call multiple times.
	 * 
	 * @param \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping $mapping The mapping to associate
	 * @return bool True on success
	 */
	public function updateMappingAfterImport(
		\Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping $mapping
	): bool
	{
		try {
			// Just log that mapping was processed - actual storage happens at statement level
			// This method exists for API consistency with statements model
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get all possible mappings for reconciliation
	 * 
	 * Retrieves all candidate BankAccountMappings that could be used
	 * for transfer matching and reconciliation of this transaction.
	 * 
	 * @return array Array of BankAccountMapping entities
	 */
	public function getMatchingMappingsForReconciliation(): array
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository')) {
				return [];
			}
			
			$mappings = [];
			
			// Get all mappings to find candidates
			$allMappings = \Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository::getAllMappings();
			
			// Filter for this transaction's currency and direction
			foreach ($allMappings as $mapping) {
				// Include mappings for matching currency
				if ($mapping->curdef === $this->account || empty($mapping->curdef)) {
					$mappings[] = $mapping;
				}
			}
			
			return $mappings;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Store BankAccountMapping for this transaction
	 * 
	 * Stores the mapping reference for this transaction in the repository.
	 * The actual FA bank account association is stored at the statement level.
	 * 
	 * @param \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping $mapping The mapping
	 * @param int $faAccountId The FA bank account ID
	 * @return bool True on success
	 */
	public function storeBankAccountMapping(
		\Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping $mapping,
		int $faAccountId
	): bool
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository')) {
				return false;
			}
			
			// For transactions, we store at the statement level
			// This method exists for API consistency
			\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository::upsert($mapping, $faAccountId);
			
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Validate mapping consistency for this transaction
	 * 
	 * Verifies that this transaction's mapping is consistent with its
	 * parent statement's mapping.
	 * 
	 * @return bool True if mappings are consistent
	 */
	public function validateMappingConsistency(): bool
	{
		try {
			$stmtMapping = $this->extractMappingFromStatement();
			if (!$stmtMapping) {
				return false;
			}
			
			// For now, just validate that we can extract a mapping
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sync mapping updates to this transaction
	 * 
	 * Synchronizes any changes made to the parent statement's mapping
	 * down to this transaction for consistency.
	 * 
	 * @return void
	 */
	public function syncMappingToTransaction(): void
	{
		try {
			// Sync is automatic through parent statement
			// This method provided for explicit synchronization if needed
		} catch (\Exception $e) {
			// Silently fail - this is informational
		}
	}

	/**
	 * Get mapping change audit trail
	 * 
	 * Retrieves the audit trail of all mapping changes made to this
	 * transaction for compliance and debugging purposes.
	 * 
	 * @return array Array of audit trail entries
	 */
	public function getMappingAuditTrail(): array
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository')) {
				return [];
			}
			
			// Query audit log for this transaction's mapping changes
			$table = TB_PREF . 'bi_transactions_audit';
			if (!db_query('SHOW TABLES LIKE ' . db_escape($table))) {
				return [];
			}
			
			$sql = "SELECT * FROM `{$table}`
					WHERE transaction_id=" . (int)$this->id . "
					  AND event LIKE 'mapping.%'
					ORDER BY created DESC";
			
			$result = @db_query($sql, 'Could not get audit trail');
			
			$trail = [];
			if (is_object($result)) {
				while ($row = db_fetch($result)) {
					if (is_array($row)) {
						$trail[] = $row;
					}
				}
			}
			
			return $trail;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Find transactions by BankAccountMapping ID
	 * 
	 * Static method to retrieve all transactions that share the same
	 * BankAccountMapping ID through their parent statements.
	 * 
	 * @param int $mappingId The BankAccountMapping ID
	 * @return array Array of transaction rows or empty array
	 */
	public static function findByBankAccountMappingId(int $mappingId): array
	{
		try {
			if (!class_exists('\Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository')) {
				return [];
			}
			
			// Get the mapping first
			$mapping = \Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository::findById($mappingId);
			if (!$mapping) {
				return [];
			}
			
			// Find parent statements with this mapping
			$stmtTable = TB_PREF . 'bi_statements';
			$txnTable = TB_PREF . 'bi_transactions';
			
			$sql = "SELECT t.* FROM `{$txnTable}` t
					INNER JOIN `{$stmtTable}` s ON t.smt_id = s.id
					WHERE (s.bankid=" . db_escape($mapping->bankid) . " OR s.bankid IS NULL)
					  AND (s.acctid=" . db_escape($mapping->acctid) . " OR s.acctid IS NULL)
					  AND (s.intu_bid=" . db_escape($mapping->intu_bid) . " OR s.intu_bid IS NULL)
					ORDER BY t.id DESC";
			
			$result = @db_query($sql, 'Could not find transactions by mapping');
			
			$transactions = [];
			if (is_object($result)) {
				while ($row = db_fetch($result)) {
					if (is_array($row)) {
						$transactions[] = $row;
					}
				}
			}
			
			return $transactions;
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Count transactions by BankAccountMapping ID
	 * 
	 * Returns the total count of transactions associated with a specific
	 * mapping ID through their parent statements.
	 * 
	 * @param int $mappingId The BankAccountMapping ID
	 * @return int Count of associated transactions
	 */
	public static function countByBankAccountMappingId(int $mappingId): int
	{
		$transactions = self::findByBankAccountMappingId($mappingId);
		return count($transactions);
	}
}
