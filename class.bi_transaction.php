<?php

/****************************************************************************************
 * Class for handling the processing of ONE transaction. 
 *
 * This class will hold ONE record that we are importing.
 * 	Extending the _model class so that we can call parent:: calls
 *	Also so we inherit the columns.
 *
 * Overriding most of the SQL functions out of the gate.
 *
 * *************************************************************************************/


$path_to_root = "../..";

//TODO
//	Update the queries in the functions to use $this->table_details['tablename'] instead of .TB_PREF."bi_transactions 

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * */

require_once( __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php' );
require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );
require_once( 'class.bi_transactions.php' );

/**//**************************************************************************************************************
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
******************************************************************************************************************/
//class bi_transactions_model extends generic_fa_interface_model {
class bi_transaction extends bi_transactions_model  {
/** Inherits
*	var $id_bi_transactions_model;	//!< Index of table
*	protected $id;                  //| int(11)      | NO   | PRI | NULL    | auto_increment |
*	protected $smt_id;              //| int(11)      | NO   |     | NULL    |                |
*	protected $valueTimestamp;      //| date         | YES  |     | NULL    |                |
*	protected $entryTimestamp;      //| date         | YES  |     | NULL    |                |
*	protected $account;             //| varchar(24)  | YES  |     | NULL    |                |
*	protected $accountName;         //| varchar(60)  | YES  |     | NULL    |                |
*	protected $transactionType;     //| varchar(3)   | YES  |     | NULL    |                |
*	protected $transactionCode;     //| varchar(32)  | YES  |     | NULL    |                |
*	protected $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |                |
*	protected $transactionDC;       //| varchar(2)   | YES  |     | NULL    |                |
*	protected $transactionAmount;   //| double       | YES  |     | NULL    |                |
*	protected $transactionTitle;    //| varchar(256) | YES  |     | NULL    |                |
*	protected $status;              //| int(11)      | YES  |     | 0       |                |
*	protected $matchinfo;           //| varchar(256) | YES  |     | NULL    |                |
*	protected $fa_trans_type;       //| int(11)      | YES  |     | 0       |                |
*	protected $fa_trans_no;         //| int(11)      | YES  |     | 0       |                |
*	protected $fitid;
*	protected $acctid;
*	protected $merchant;            //| varchar(64)  | NO   |     | NULL    |                |
*	protected $category;            //| varchar(64)  | NO   |     | NULL    |                |
*	protected $sic;                 //| varchar(64)  | NO   |     | NULL    |                |
*	protected $memo;                //| varchar(64)  | NO   |     | NULL    |                |
*	protected $checknumber;	//!<int
*	protected $matched;	//!<bool
*	protected $created;	//!<bool
*	protected $g_partner;	//!<varchar	Which action (bank/Quick Entry/...
*	protected $g_option;	//!<varchar	Which choice - ATB/Groceries/...
*/
	protected $partnerId;
	protected $custBranch;
	protected $invoiceNo;



	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->iam = "bi_transaction";
		$this->matched = 0;
		$this->created = 0;
		//display_notification( __FILE__ . "::" . __LINE__ );
	}
        /*****************************************************************//**
        * Set the field if possible
        *
        *       Tries to set the field in this class as well as in table_interface
        *       assumption being we are going to do something with the field in
        *       the database (else why set the model...)
        *
        * @param string field to set
        * @param mixed value to set
        * @param bool should we allow the class to only set __construct time fields
        * @return nothing. (parent) throws exceptions
        **********************************************************************/
	function set( $field, $value = null, $enforce = true )
	{
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . "Setting $field to $value" );
		$ret = parent::set( $field, $value, $enforce );
		//display_notification( __FILE__ . "::" . __CLASS__ . "::"  . __METHOD__ . ":" . __LINE__, "WARN" );
		return $ret;
	}
	/**//*************************************************************************
	* Extract the variables out of _POST for this id
	*
	* @param in Post ID
	* @returns bool was partnerId set?
	*****************************************************************************/
	function extractPost( $id )
	{
		if( isset( $_POST['cids'][$id] ) )
			$_cids = array_filter(explode(',', $_POST['cids'][$id]));
		else
			$_cids = array();

		if( isset( $_POST["partnerId_$id"] ) )
			$this->set( "partnerId", $_POST["partnerId_$id"] );
		if( isset( $_POST["partnerType"][$id] ) )
			$this->set( "partnerType", $_POST['partnerType'][$id] );
		if( isset( $_POST['Invoice_$id'] ) )
			$this->set( "invoiceNo", $_POST['Invoice_$id'] );
		else if( isset( $_POST['Invoice'] ) )
			$this->set( "invoiceNo", $_POST['Invoice'] );
		if( isset( $_POST["partnerDetailId_$id"] ) )
			$this->set( "custBranch", $_POST["partnerDetailId_$id"] );
		if( isset( $_POST["trans_type_$id"] ) )
			$this->set( "fa_trans_type", $_POST["trans_type_$id"] );
		if( isset( $_POST["trans_no_$id"] ) )
			$this->set( "fa_trans_no", $_POST["trans_no_$id"] );
		if( isset( $_POST["memo_$id"] ) )
			$this->set( "memo", $_POST["memo_$id"] );
		else if( isset( $_POST["title_$id"] ) )
			$this->set( "memo", $_POST["title_$id"] );
		if( isset( $_POST["title_$id"] ) )
			$this->set( "transactionTitle", $_POST["title_$id"] );
		
		return isset( $this->partnerId );
	}
	/**//*************************************************************************
	*
	*
	*****************************************************************************/
	function insert_transaction()
	{
		//parent::insert_data( get_object_vars($this) );
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
		//overriding!
		//parent::reset_transactions($tid, $cids, $trans_no, $trans_type);
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
		//overriding!
		//parent::update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "" );
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
		//overriding!
		//parent::update_transactions_account($tid, $account, $accountName );
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
		//overriding!
		//parent::db_prevoid( $type, $trans_no );
	}
	/**//**********************************************************************
	* Get transactions details for display
	*
	* @param int status
	* @returns array transaction rows sorted
	***************************************************************************/
	function get_transactions( $status = null) 
	{
		//overriding!
		//parent::get_transactions( $status = null);
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
		parent::get_transaction( $tid, true );
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
		//overriding!
		//parent::get_normal_pairing( $account = null);
	}
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	function trz2obj( $trz )
	{
		return parent::obj2obj( $trz );
	}
	/**//************************************************************
	* Hand build the INSERT statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_insert_sql()
	{
		//overriding!
		//parent::hand_insert_sql();
	}
	/**//************************************************************
	* Hand build the UPDATE statement
	*
	* @param none
	* @returns string SQL statement
	*****************************************************************/
	function hand_update_sql()
	{
		//overriding!
		//parent::hand_update_sql();
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
		//overriding!
		//parent::trans_exists();
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
		//overriding!
		//parent::update();
	}
}
