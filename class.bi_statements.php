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

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * This table should not have any views (forms).
 * */

//display_notification( __FILE__ . "::" . __LINE__ );

require_once( '../ksf_modules_common/class.generic_fa_interface.php' );
//display_notification( __FILE__ . "::" . __LINE__ );
require_once( '../ksf_modules_common/defines.inc.php' );
//display_notification( __FILE__ . "::" . __LINE__ );

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
*	| id           | int(11)     | NO   | PRI | NULL    | auto_increment |
*	| bank         | varchar(22) | YES  | MUL | NULL    |                |
*	| account      | varchar(24) | YES  |     | NULL    |                |
*	| currency     | varchar(3)  | YES  |     | NULL    |                |
*	| startBalance | double      | YES  |     | NULL    |                |
*	| endBalance   | double      | YES  |     | NULL    |                |
*	| smtDate      | date        | YES  |     | NULL    |                |
*	| number       | int(11)     | YES  |     | NULL    |                |
*	| seq          | int(11)     | YES  |     | NULL    |                |
*	| statementId  | varchar(64) | YES  |     | NULL    |                |
*	| acctid       | varchar(64) | YES  |     | NULL    |                |
*	| fitid        | varchar(64) | YES  |     | NULL    |                |
*	| bankid       | varchar(64) | YES  |     | NULL    |                |
*	| intu_bid     | varchar(64) | YES  |     | NULL    |                |
*	+---------------------+--------------+------+-----+---------+----------------+
*	
*
******************************************************************************************************************/

/**
 * Bank Import Statements Model
 * 
 * Extends generic_fa_interface_model which provides magic methods:
 * 
 * @method mixed get(string $property) Get a property value
 * @method void set(string $property, mixed $value) Set a property value
 * @method void obj2obj(object $source) Copy properties from another object
 * @method bool insert() Insert this record into database
 * @method bool update() Update this record in database
 * @method bool delete() Delete this record from database
 */
class bi_statements_model extends generic_fa_interface_model 
{
	/**
	 * Ensure the staging table schema is present (idempotent, non-destructive).
	 *
	 * Table creation is handled by sql/update.sql during module activation; this only
	 * repairs drift (missing columns/index) for older installs.
	 */
	public static function ensure_schema(): void
	{
		$table = TB_PREF . 'bi_statements';
		if (!self::table_exists($table)) {
			return;
		}

		self::ensure_column($table, 'updated_ts', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
		self::ensure_column($table, 'acctid', "VARCHAR(64) NULL");
		self::ensure_column($table, 'fitid', "VARCHAR(64) NULL");
		self::ensure_column($table, 'bankid', "VARCHAR(64) NULL");
		self::ensure_column($table, 'intu_bid', "VARCHAR(64) NULL");
		self::ensure_unique_index($table, 'unique_smt', array('bank', 'statementId'));
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

	private static function index_exists(string $table, string $indexName): bool
	{
		$res = db_query('SHOW INDEX FROM `' . $table . '` WHERE Key_name=' . db_escape($indexName), 'Failed checking index existence');
		return db_num_rows($res) > 0;
	}

	private static function ensure_column(string $table, string $column, string $definition): void
	{
		if (!self::table_exists($table) || self::column_exists($table, $column)) {
			return;
		}
		db_query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition, 'Failed adding column to bi_statements');
	}

	/**
	 * Adds a UNIQUE constraint if missing.
	 *
	 * @param array<int,string> $columns
	 */
	private static function ensure_unique_index(string $table, string $indexName, array $columns): void
	{
		if (!self::table_exists($table) || self::index_exists($table, $indexName)) {
			return;
		}
		$colsSql = array();
		foreach ($columns as $col) {
			$colsSql[] = '`' . $col . '`';
		}
		db_query('ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $indexName . '` UNIQUE(' . implode(', ', $colsSql) . ')', 'Failed adding unique index to bi_statements');
	}

	/**
	 * Cache of table columns to keep INSERT generation schema-tolerant.
	 *
	 * @var array<string, array<string, bool>>
	 */
	private static $tableColumnsCache = [];

	protected $id;                  	//| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $bank;		// varchar(22) | YES  | MUL | NULL    |                |
	protected $account;		// varchar(24) | YES  |     | NULL    |                |
	protected $currency;		// varchar(3)  | YES  |     | NULL    |                |
	protected $startBalance;	// double      | YES  |     | NULL    |                |
	protected $endBalance;		// double      | YES  |     | NULL    |                |
	protected $smtDate;		// date        | YES  |     | NULL    |                |
	protected $number;		// int(11)     | YES  |     | NULL    |                |
	protected $seq;			// int(11)     | YES  |     | NULL    |                |
	protected $statementId;		// varchar(64) | YES  |     | NULL    |                |
	protected $acctid;		// varchar(64) | YES  |     | NULL    |                |
	protected $fitid;		// varchar(64) | YES  |     | NULL    |                |
	protected $bankid;		// varchar(64) | YES  |     | NULL    |                |
	protected $intu_bid;		// varchar(64) | YES  |     | NULL    |                |



	function __construct()
	{
//display_notification( __FILE__ . "::" . __LINE__ );
//		parent::__construct();
//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "bi_statements";
//display_notification( __FILE__ . "::" . __LINE__ );
		$this->define_table();
//display_notification( __FILE__ . "::" . __LINE__ );
	}
	function define_table()
	{
		$ind = "id";
		//$ind = "id_" . $this->iam;
//All of these array assignments error out:
//	Indirect modification of overloaded property bi_transactions_model::$fields_array has no effect in file
//Cause would be -> varaibles below are temporary - craeted by __get so that __set might set them.
//Looks like these fields aren't declared anywhere in the inheritance chain, hence the temporary (created by __get)
		$this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read' );
		$this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read' );
		if( strlen( $this->company_prefix ) < 2 )
		{
			$this->company_prefix = TB_PREF;
		}
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details, true ) );
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this, true ) );
		$this->table_details['tablename'] = $this->company_prefix . $this->iam;
		$this->table_details['primarykey'] = $ind;
		//$this->table_details['orderby'] = 'valueTimestamp, id';
		//$this->table_details['orderby'] = 'transaction_date, transaction_id';
/*
		$this->table_details['index'][0]['type'] = 'unique';
		$this->table_details['index'][0]['columns'] = "transaction_id";
		$this->table_details['index'][0]['keyname'] = "transaction_id";
*/
		//$sidl = 'varchar(' . STOCK_ID_LENGTH . ')';
		//$descl = 'varchar(' . DESCRIPTION_LENGTH . ')';

		$this->fields_array[] = array('name'=> 'id', 'label' => 'ID', 	'type' => 'int(11)', 	'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );
		$this->fields_array[] = array('name'=> 'bank', 'label' => 'Bank	', 'type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'account', 'label' => 'Account', 'type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'currency', 'label' => 'Currency', 'type' => ' varchar(3)   ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'startBalance', 'label' => 'Start Balance','type' => ' double ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'endBalance', 'label' => 'End Balance',	'type' => ' double ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'smtDate', 'label' => 'Statement Date',	'type' => ' date   ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'number', 'label' => 'Number','type' => ' int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'seq', 'label' => 'Sequence', 'type' => ' int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'statementId', 'label' => 'Statement ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'acctid', 'label' => 'Account ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'fitid', 'label' => 'Financial Institute Transaction ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'bankid', 'label' => 'Bank ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		$this->fields_array[] = array('name'=> 'intu_bid', 'label' => 'Institute ID','type' => ' varchar(64)  ', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL' );    
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details['tablename'], true ) );
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $this->table_details, true ) );
	}
	function insert_transaction()
	{
		$this->insert_data( get_object_vars($this) );
	}
	/**//*************************************************************************
	* Update bi_trans with the related info to FA gl transactions
	*
	*       Hooks db_prevoid does similar
	*
	* @param none
	* @returns none
	******************************************************************************/
	function update_statement()
	{
		$sql = "
			UPDATE " . $this->table_details['tablename'] .
			" SET startBalance=". db_escape( $this->startBalance ) . ", " .
				"endBalance=" . db_escape($this->endBalance) . 
			" WHERE id=" . db_escape( $this->id );
		//display_notification( __FILE__ . "::" . __LINE__ . " : " . $sql);
		db_query($sql, 'Could not update trans');
	}
	/**//*************************************************************************
	* Reset TBD           when the related FA gl transaction is voided
	*
	* @param int the Transaction Type (JE/BP/SP/...)
	* @param int The transaction number
	* @returns none
	******************************************************************************/
	function db_prevoid( $trans_type, $trans_no )
	{
	}
	/**//**********************************************************************
	* Get a specific transaction's details
	*
	* @param int index
	* @returns array transaction row from db
	***************************************************************************/
	function get_statement( $tid = null) 
	{
		if( $tid == null )
		{
			if( isset( $this->id ) )
				$tid = $this->id;
			else
				throw new Exception( "No ID set to search for" );
		}
	        $sql = "
	              SELECT * FROM ". $this->table_details['tablename'] .
	             "WHERE id=" . db_escape($tid);
	        $result = db_query($sql, "could not get statement with id $tid");
	        return db_fetch($result);
	}
	/**//************************************************************
	* Hand build the INSERT statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_insert_sql()
	{
		$table = $this->table_details['tablename'];
		$cols = $this->get_table_columns($table);

		// Build candidate values from whatever properties exist on this model/statement.
		$smtDateValue = null;
		if (isset($this->smtDate)) {
			$smtDateValue = $this->smtDate;
		} elseif (isset($this->timestamp)) {
			$smtDateValue = $this->timestamp;
		}

		$seqValue = null;
		if (isset($this->seq)) {
			$seqValue = $this->seq;
		} elseif (isset($this->sequence)) {
			$seqValue = $this->sequence;
		}

		$candidates = [
			'bank' => $this->bank ?? null,
			'account' => $this->account ?? null,
			'currency' => $this->currency ?? null,
			'startBalance' => $this->startBalance ?? null,
			'endBalance' => $this->endBalance ?? null,
			'smtDate' => $smtDateValue,
			'number' => $this->number ?? null,
			'seq' => $seqValue,
			'statementId' => $this->statementId ?? null,
			'acctid' => $this->acctid ?? null,
			'fitid' => $this->fitid ?? null,
			'bankid' => $this->bankid ?? null,
			'intu_bid' => $this->intu_bid ?? null,
		];

		$fields = [];
		$values = [];
		foreach ($candidates as $field => $value) {
			if (!isset($cols[$field])) {
				continue;
			}
			$fields[] = $field;
			$values[] = db_escape($value);
		}

		if (empty($fields)) {
			throw new Exception('No matching columns found for bi_statements insert.');
		}

		return "INSERT IGNORE INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_table_columns(string $tableName): array
	{
		if (isset(self::$tableColumnsCache[$tableName])) {
			return self::$tableColumnsCache[$tableName];
		}

		$cols = [];
		try {
			$res = db_query("SHOW COLUMNS FROM {$tableName}", 'Could not read table columns');
			while ($row = db_fetch($res)) {
				if (!empty($row['Field'])) {
					$cols[(string)$row['Field']] = true;
				}
			}
		} catch (Exception $e) {
			// If introspection fails, fall back to the historical column set.
			foreach (['bank','account','currency','startBalance','endBalance','smtDate','number','seq','statementId','acctid','fitid','bankid','intu_bid'] as $f) {
				$cols[$f] = true;
			}
		}

		self::$tableColumnsCache[$tableName] = $cols;
		return $cols;
	}
	/**//************************************************************
	* Determine if this particular statement already exists in the staging table
	*
	*	We need to do duplicate checking.  Most banks allow a person to select a date range for their transactions.
	*
	*	We can have the same total for the same vendor on the same date and not automatically be a duplicate
	*		Example when I buy flats of coke on sale limit 4 per transaction. 
	*
	*
	* @param none
	* @returns bool already exists? 
	*****************************************************************/
	function statement_exists()
	{
                $sql = "
                      SELECT * FROM ". $this->table_details['tablename'] .
                     " WHERE bank=".db_escape($this->bank)." AND statementId=".db_escape($this->statementId);
		$tid = $this->statementId;
		//display_notification( __FILE__ . "::" . __LINE__ . " $sql" );
		try
		{
	                $result = db_query($sql, "could not get statement with id $tid");
			$myrow = db_fetch($result);
			if( empty($myrow)) 
			{
				return false;
			}
			else
			{
				$this->arr2obj( $myrow );
				//display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
			}
		}
		catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . $e->getMessage() );
		}
		return true;
	}

}
////I've copied this into ORIGIN.
//	/**//**********************************************************************
//	* Convert Statement class to this object
//	*
//	* @param class
//	* @returns int how many fields did we copy
//	**************************************************************************/
//	function obj2obj( $obj )
//	{
//		//display_notification( __FILE__ . "::" . __LINE__ . print_r( $obj, true ) );
//		if( is_array( $obj ) )
//			return $this->arr2obj( $obj );
//		if( ! is_object( $obj ) )
//			throw new Exception( "Passed in data is neither an array nor an object.  We can't handle here!" );
//
//		$cnt = 0;
//		foreach( get_object_vars($this) as $key => $value )
//		{
//			//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $key, true ) );
//			if( isset( $obj->$key ) )
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key $obj->$key" );
//				//$this->$key = $obj->$key;	
//				$this->set( $key, $obj->$key );	
//				//	display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $this->$key, true ) );
//				$cnt++;
//			}
//			else
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $obj, true ) );
//			}
//		}
//		//	display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
//		return $cnt;
//	}
//	/**//**********************************************************************
//	* Convert Transaction array to this object
//	*
//	* @param array
//	* @returns int how many fields did we copy
//	**************************************************************************/
//	function arr2obj( $arr )
//	{
//		//display_notification( __FILE__ . "::" . __LINE__ . print_r( $arr, true ) );
//		if( is_object( $arr ) )
//			return $this->obj2obj( $arr );
//		if( ! is_array( $arr ) )
//			throw new Exception( "Passed in data is neither an array nor an object.  We can't handle here!" );
//
//		$cnt = 0;
//		foreach( get_object_vars($this) as $key => $value )
//		{
//			//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $key, true ) );
//			if( isset( $arr[$key] ) )
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key $arr[$key]" );
//				//$this->$key = $arr[$key];	
//				$this->set( $key, $arr[$key] );	
//				//	display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $this->$key, true ) );
//				$cnt++;
//			}
//			else
//			{
//				//display_notification( __FILE__ . "::" . __LINE__ . " $key not set in " . print_r( $arr, true ) );
//			}
//		}
//		//	display_notification( __FILE__ . "::" . __LINE__ . print_r( $this, true ) );
//		return $cnt;
//	}
////display_notification( __FILE__ . "::" . __LINE__ );
