<?php
namespace Ksfraser\FaBankImport\Model;

use Ksfraser\common\GenericFaInterface;
use Ksfraser\frontaccounting\FaBankAccounts;
use Ksfraser\frontaccounting\FaCustomerPayment;
use Ksfraser\frontaccounting\FaGl;

/**
 * Model class for handling line item data.
 */
class BiLineItemModel extends GenericFaInterfaceModel
{
	/** @var string */
	protected $transactionDC;	//varchar

	/** @var string */
	protected $our_account;	//varchar

	/** @var string */
	protected $valueTimestamp;	//date

	/** @var string */
	protected $entryTimestamp;	//date

	/** @var string */
	protected $otherBankAccount;	//varchar

	/** @var string */
	protected $otherBankAccountName;	//varchar

	/** @var string */
	protected $transactionTitle;	//varchar

	/** @var int */
	protected $status;		//int

	/** @var string */
	protected $currency;

	/** @var int */
	protected $fa_trans_type;

	/** @var int */
	protected $fa_trans_no;

	/** @var int */
	protected $id;

	/** @var bool */
	protected $has_trans;

	/** @var float */
	protected $amount;

	/** @var float */
	protected $charge;

	/** @var string */
	protected $transactionTypeLabel;

	/** @var array */
	protected $vendor_list;

	/** @var string */
	protected $partnerType;

	/** @var int */
	protected $partnerId;

	/** @var int */
	protected $partnerDetailId;

	/** @var string */
	protected $oplabel;

	/** @var array */
	protected $matching_trans;

	/** @var int */
	protected $days_spread;

	/** @var string */
	protected $transactionCode;

	/** @var string */
	protected $transactionCodeDesc;

	/** @var array */
	protected $optypes;

	/** @var string */
	protected $memo;

	/** @var array */
	protected $ourBankDetails;

	/** @var string */
	protected $ourBankAccount;

	/** @var string */
	protected $ourBankAccountName;

	/** @var string */
	protected $ourBankAccountCode;

	/** @var FaBankAccounts */
	protected $fa_bank_accounts;

	/**
	 * Constructor for BiLineItemModel.
	 *
	 * @param array $trz
	 * @param array $vendor_list
	 * @param array $optypes
	 */
	public function __construct(array $trz, array $vendor_list = [], array $optypes = [])
	{
		parent::__construct(null, null, null, null, null);
		$this->matched = 0;
		$this->created = 0;
		$this->charge = 0;
		$this->days_spread = 2;
		$this->initialize($trz, $vendor_list, $optypes);
	}

	/**
	 * Initialize the model with transaction data.
	 *
	 * @param array $trz
	 * @param array $vendor_list
	 * @param array $optypes
	 */
	private function initialize(array $trz, array $vendor_list, array $optypes): void
	{
		$this->transactionDC = $trz['transactionDC'];
		$this->determineTransactionTypeLabel();
		$this->memo = $trz['memo'];
		$this->our_account = $trz['our_account'];
		$this->valueTimestamp = $trz['valueTimestamp'];
		$this->entryTimestamp = $trz['entryTimestamp'];
		$this->otherBankAccountName = $trz['accountName'];
		$this->transactionTitle = $trz['transactionTitle'];
		if( strlen( $this->transactionTitle ) < 4 )
		{
			$this->transactionTitle .= " " . $this->memo;
		}
		$this->transactionCode = $trz['transactionCode'];
		$this->transactionCodeDesc = $trz['transactionCodeDesc'];
		$this->currency = $trz['currency'];
		$this->status = $trz['status'];
		$this->id = $trz['id'];
		$this->fa_trans_type = $trz['fa_trans_type'];
		$this->fa_trans_no = $trz['fa_trans_no'];
		$this->amount = $trz['transactionAmount'];
		$this->vendor_list = $vendor_list;
		$this->optypes = $optypes;
//@todo migrate shorten_bankAccount_Names 
		try {
			$this->otherBankAccount = shorten_bankAccount_Names( $trz['accountName'] );
		}
		catch( Exception $e )
		{
			$this->otherBankAccount = $trz['accountName'];
		}
//Original code MT370 can have COM lines that add to the transaction
		if ($trz['transactionType'] != 'COM') 
		{
			$this->has_trans = 1;
		} 
/*
		else if ($trz['transactionType'] == 'COM')
		{
			$this->amount += $trz['transactionAmount'];
		}
*/
		if( isset( $_POST["partnerId_" . $this->id] ) )
		{
			$this->partnerId = $_POST["partnerId_" . $this->id];
		}
		else
		{
			//We are using if(empty( ->partnerId ) ) so don't want to make it not empty!!
			//$this->partnerId = "";
		}
	}
	/*****************************************************************//**
	* Set the field if possible
	*
	*	   Tries to set the field in this class as well as in table_interface
	*	   assumption being we are going to do something with the field in
	*	   the database (else why set the model...)
	*
	* @param string field to set
	* @param mixed value to set
	* @param bool should we allow the class to only set __construct time fields
	* @return nothing. (parent) throws exceptions
	**********************************************************************/
	function set( $field, $value = null, $enforce = true )
	{
		$ret = parent::set( $field, $value, $enforce );
		return $ret;
	}
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	*	Did this get put in Origin?
	*	@TODO confirm if in Origin
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	function trz2obj( $trz ) : int
	{
		return $this->obj2obj( $trz );
	}

	/**//********************************************************************
	 * Determine the transaction type label based on transactionDC.
	*
	* @since 20250409
	*
	* This could probably be merged with setPartnerType
	 */
	public function determineTransactionTypeLabel(): void
	{
		switch ($this->transactionDC) {
			case 'C':
				$this->transactionTypeLabel = "Credit";
				break;
			case 'D':
				$this->transactionTypeLabel = "Debit";
				break;
			case 'B':
				$this->transactionTypeLabel = "Bank Transfer";
				break;
		}
	}

	/**//********************************************************************
	 * Retrieve bank account details.
	 */
	public function getBankAccountDetails(): void
	{
		$this->fa_bank_accounts = new FaBankAccounts($this);
		$this->ourBankDetails = $this->fa_bank_accounts->getByBankAccountNumber($this->our_account);
		$this->ourBankAccountName = $this->ourBankDetails['bank_account_name'];
		$this->ourBankAccountCode = $this->ourBankDetails['account_code'];
	}

	/**//*********************************************************************
	 * Set the partner type and operation label based on transactionDC.
	*
	* This could probably be merged with determineTransactionTypeLabel
	 */
	public function setPartnerType(): void
	{
		switch ($this->transactionDC) {
			case 'C':
				$this->partnerType = 'CU';
				$this->oplabel = "Deposit";
				break;
			case 'D':
				$this->partnerType = 'SP';
				$this->oplabel = "Payment";
				break;
			case 'B':
				$this->partnerType = 'BT';
				$this->oplabel = "Bank Transfer";
				break;
			default:
				$this->partnerType = 'QE';
				$this->oplabel = "Quick Entry";
				break;
		}
//Commenting out this IF resets partnerType to SP ALWAYS doesn't matter what was selected :(
		  	if( !isset( $_POST['partnerType'][$this->id] ) )
		{
			$_POST['partnerType'][$this->id] = $this->partnerType;
		}
		//temporarily return oplabel for process_statement
		return $this->oplabel;
	}

	/**//**************************************************************************
	 * Find matching existing journal entries.
	 *
	 * @return array
	 */
	public function findMatchingExistingJE(): array
	{
		$fa_gl = new FaGl();
		$fa_gl->set("amount_min", $this->amount);
		$fa_gl->set("amount_max", $this->amount);
		$fa_gl->set("transactionDC", $this->transactionDC);
		$fa_gl->set("days_spread", $this->days_spread);
		$fa_gl->set("startdate", $this->valueTimestamp);
		$fa_gl->set("enddate", $this->entryTimestamp);
		$fa_gl->set("accountName", $this->otherBankAccountName);
		$fa_gl->set("transactionCode", $this->transactionCode);

		$this->matching_trans = $fa_gl->find_matching_transactions();
		
		// Automatically determine partner type based on matches
		$this->determinePartnerTypeFromMatches();
		
		return $this->matching_trans;
	}

	/**//**************************************************************************
	 * Determine the appropriate partner type based on matched transactions.
	 * 
	 * This centralizes the logic for suggesting partner types when matches are found.
	 * Business rule: 
	 * - If matched transaction is an invoice -> SP (Supplier Payment)
	 * - If matched transaction is a Bank Payment/Deposit -> QE (Quick Entry)
	 * - Otherwise -> ZZ (Matched existing transaction)
	 *
	 * @return void
	 */
	protected function determinePartnerTypeFromMatches(): void
	{
		if (count($this->matching_trans) === 0) {
			return; // No matches, nothing to do
		}

		// Handle rewards/split transactions (< 3 line items)
		if (count($this->matching_trans) >= 3) {
			// TODO: Sort by score and take highest scored item
			return;
		}

		// Check if we have a high-confidence match (score >= 50)
		if ($this->matching_trans[0]['score'] < 50) {
			return;
		}

		// High-confidence match found - determine partner type
		if ($this->matching_trans[0]['is_invoice']) {
			// This is a supplier payment matching an invoice exactly
			$_POST['partnerType'][$this->id] = 'SP';
			$this->oplabel = "INVOICE MATCH";
		}
		elseif (isset($this->matching_trans[0]['type']) && 
				($this->matching_trans[0]['type'] == ST_BANKPAYMENT || 
				 $this->matching_trans[0]['type'] == ST_BANKDEPOSIT)) {
			// This is a Quick Entry transaction for recurring expenses
			// like groceries, insurance, utilities, etc.
			$_POST['partnerType'][$this->id] = 'QE';
			$this->oplabel = "Quick Entry MATCH";
		}
		else {
			// Generic match to existing transaction
			$_POST['partnerType'][$this->id] = 'ZZ';
			$this->oplabel = "MATCH";
		}
	}

	/**//******************************************************************
	* Get OUR Bank Account Details
	*
	*	DONE: REFACTOR to use class fa_bank_accounts instead of an array
	* @todo clean up the refactored code for fa_bank_accounts removing commented out old code once tested
	*
	**********************************************************************/
	function getBankAccountDetails()
	{
		// require_once( '../ksf_modules_common/class.fa_bank_accounts.php' );
		use Ksfraser\frontaccounting\FaBankAccounts;
		$this->fa_bank_accounts = new FaBankAccounts( $this );
		$this->ourBankDetails =	$this->fa_bank_accounts->getByBankAccountNumber( $this->our_account );
		//$this->ourBankDetails = get_bank_account_by_number( $this->our_account );
		$this->ourBankAccountName = $this->ourBankDetails['bank_account_name'];
		$this->ourBankAccountCode = $this->ourBankDetails['account_code'];
	}
	/**//***************************************************************
	* Find paired transactions i.e. bank transfers from one account to another
	* such as Savings <- -> HISA or CC payments
	*
	*	Because of the extra processing time, this function needs to be run
	*	as a maintenance activity rather than as a real time search.
	*
	* @TODO finish coding this!
	*
	* @param none
	* @return array matching transactions
	*********************************************************************/
	function findPaired()
	{
		return [];
		// TODO Finish coding
		require_once( 'class.bi_transactions.php' );
		$bi_t = new bi_transactions_model();
		//Since we are only doing a +2 days and not -2, we should only find the first of a paired set of transactions
		$trzs = $bi_t->get_transactions( 0, $this->valueTimestamp, add_days( $this->valueTimestamp, 2 ), $this->amount, null );	//This will be matching dollar amounts within 2 days.  
		$count = 0;
		foreach( $trzs as $trans )
		{
			if( ! strcmp( trim( $trans['our_account'] ) , trim( $this->our_account ) ) )
			{
				continue;	//Can't match within same bank account
			} 
			if( ! strcmp( trim( $trans['transactionDC'] ) , trim( $this->transactionDC ) ) )
			{
				continue;	//Paired transactions will have opposing DC values.
			} 	
		//We haven't implemented logic here to check that it is the paired transaction i.e. bank transfer
			$matching[] = $trans;
		}
		return $matching;
	}
	/**//***************************************************************
	* Check if the transaction has a pair.
	*
	*	findPaired will set a flag so we can just check the flag
	*	the "paired" transaction should also still be unprocessed
	*	if we are showing the pairing.  Otherwise it should show
	*	in the matching GLs.
	*
	*********************************************************************/
	function isPaired()
	{
		return false;
	}
	/**//***************************************************************
	* Find any transactions that alraedy exist that look like this one
	*
	* @param NONE
	* @returns array GL record(s) that match
	********************************************************************/
	function findMatchingExistingJE()
	{
	  	//display_notification( __FILE__ . "::" . __LINE__ );
		//The transaction is imported into a bank account, with the counterparty being trz['accountName']
		//	  Existing transactions will have 2+ line items.  1 should match the bank, one should match the counterparty.
		//	  Currently we are matching and scoring each of the line items, rather than matching/scoring the GL itself.

		//Check for matching into the accounts
		// JE# / Date / Account / (Credit/Debit) / Memo in the GL Account (gl/inquiry/gl_account_inquiry.php)
	
		$new_arr = array();
		// $inc = include_once( __DIR__ . '/../ksf_modules_common/class.fa_gl.php' );
		use Ksfraser\frontaccounting\FaGl;
/* NAMESPACE
		if( $inc )
		{
*/
 	 		//display_notification( __FILE__ . "::" . __LINE__ );
			$fa_gl = new FaGl();
 			$fa_gl->set( "amount_min", $this->amount );
			$fa_gl->set( "amount_max", $this->amount );
			$fa_gl->set( "amount", $this->amount );
			$fa_gl->set( "transactionDC", $this->transactionDC );
			$fa_gl->set( "days_spread", $this->days_spread );
			$fa_gl->set( "startdate", $this->valueTimestamp );	 //Set converts using sql2date
			$fa_gl->set( "enddate", $this->entryTimestamp );	   //Set converts using sql2date
			$fa_gl->set( "accountName", $this->otherBankAccountName );
			$fa_gl->set( "transactionCode", $this->transactionCode );

			//Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
			//			  E-TRANSFER 010667466304;CUSTOMER NAME;...
			//	  function add_days($date, $days) // accepts negative values as well
			try {
					$new_arr = $fa_gl->find_matching_transactions();
							//display_notification( __FILE__ . "::" . __LINE__ );
			} catch( Exception $e )
			{
					display_notification(  __FILE__ . "::" . __LINE__ . "::" . $e->getMessage() );
			}
/* NAMESPACE
									//display_notification( __FILE__ . "::" . __LINE__ );
		}
		else
		{
				display_notification( __FILE__ . "::" . __LINE__ . ": Require_Once failed." );
		}
*/
		$this->matching_trans = $new_arr;
		return $new_arr;
	}
	/**//*******************************************************************
	* seek SUPPLIER partner type
	*
	* Returns match row from table
	* Sets partnerId
	*
	* @param int Partner Type (PT_SUPPLIER or PT_CUSTOMER)
	* @returns array bi_partners_data
	************************************************************************/
	function seekPartnerByBankAccount( int $parterType = PT_SUPPLIER )
	{
		$matched_supplier = array();
/*
		if ( empty( $this->partnerId ) )
		{
*/
			require_once( class.bi_partners_data.php );
			$pd = new bi_partners_data();
			$matched_supplier = $pd->search_partner_by_bank_account( $parterType, $this->otherBankAccount);
				//$matched_supplier = search_partner_by_bank_account(PT_SUPPLIER, $this->otherBankAccount);
			if (!empty($matched_supplier))
			{
				$this->partnerId = $_POST["partnerId_$this->id"] = $matched_supplier['partner_id'];
				$this->partnerDetailId = $_POST["partnerDetailId_$this->id"] = $match['partner_detail_id'];
			}
/*
		}
*/
		return $matched_supplier;
	}

}
