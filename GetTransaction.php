<?php

/****************************************************************************************
 * Class for Retrieving ONE transaction. 
 *
 * *************************************************************************************/

$path_to_root = "../..";

require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );
require_once( 'class.bi_transactions.php' );

class GetTransaction extends bi_transactions_model  {
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
		parent::__construct( null, null, null, null, null);
	}
	function insert_transaction()
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function summary_sql( $TransAfterDate, $TransToDate, $statusFilter )
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function reset_transactions($tid, $cids, $trans_no, $trans_type)
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "" ) 
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function update_transactions_account($tid, $account, $accountName )
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function db_prevoid( $type, $trans_no )
	{
	}
	function get_transactions( $status = null, $transAfterDate = null, $transToDate = null, $transactionAmount = null, $transactionTitle = null, $limit = null ) 
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
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
	function getTransaction( $tid )
	{
		return $this->get_transaction( $tid );
	}
	/**//**********************************************************************
	* Get a specific transaction's details
	*
	* @param int index
	* @param bool should we set the internal variables.  Since this is new, defaulting to legacy behaviour
	* @returns array transaction row from db
	***************************************************************************/
	function get_transaction( $tid = null, $bSetInternal = true ) 
	{
		return parent::get_transaction( $tid, true );
	}
	//function trz2obj( $trz )
	//function get_normal_pairing( $account = null) 
	function hand_insert_sql()
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function hand_update_sql()
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function trans_exists()   
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function update()
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
	function toggleDebitCredit()
	{
		throw new Exception( "The role of this function is to retrieve 1 transaction" );
	}
}
