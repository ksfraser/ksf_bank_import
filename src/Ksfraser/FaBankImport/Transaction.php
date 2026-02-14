<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :Transaction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for Transaction.
 */
namespace Ksfraser\FaBankImport;

//use Ksfraser\FaBankImport\TransactionTypeLabel.php;
use \Exception;

// Legacy compatibility: some code paths may run without Composer autoload.
// Keep this include safe so tools like PHPUnit coverage can load this file.
if (!class_exists(__NAMESPACE__ . '\\TransactionTypeLabel', false)) {
	$transactionTypeLabelCandidates = [
		__DIR__ . '/views/TransactionTypeLabel.php',
		__DIR__ . '/../../../views/TransactionTypeLabel.php',
	];

	foreach ($transactionTypeLabelCandidates as $candidate) {
		if (is_file($candidate)) {
			require_once $candidate;
			break;
		}
	}
}

abstract class Transaction
{
	public $transactionDC;       //| varchar(2)   | YES  |     | NULL    |		|
	public $our_account; 	//| varchar()   | YES  |     | NULL    |		|
	public $valueTimestamp;      //| date	 | YES  |     | NULL    |		|
	public $entryTimestamp;      //| date	 | YES  |     | NULL    |		|
	public $otherBankaccount;	 //| varchar(60)  | YES  |     | NULL    |		|
	public $otherBankAccount;
	public $otherBankaccountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	public $otherBankAccountName;
	public $transactionTitle;    //| varchar(256) | YES  |     | NULL    |		|
	public $status;	      //| int(11)      | YES  |     | 0       |		|
	public $currency;
	public $fa_trans_type;       //| int(11)      | YES  |     | 0       |		|
	public $fa_trans_no;	 //| int(11)      | YES  |     | 0       |		|
	public $id;		  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	public $has_trans;	//!< bool
	public $amount;	//!<float
	public $charge;	//!<float
	public $transactionTypeLabel;     //!< string
	public $partnerType;	//!<string
	public $partnerId;	//!<int
	public $partnerDetailId;	//!<int		//Used for Customer Branch
	public $oplabel;
	public $matching_trans;	//!<array was arr_arr
	public $transactionCode;     //| varchar(32)  | YES  |     | NULL    |		|
	public $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |		|
	public $optypes;	//!< array
	public $memo;		//| varchar(64)  | NO   |     | NULL    |		|
	public $vendor_list = [];
//REFACTOR:
//		refactor to use class.fa_bank_accounts.php instead of an array!
	public $ourBankDetails;	//!< array
	public $ourBankAccount;	 //| varchar(60)  | YES  |     | NULL    |		|
	public $ourBankAccountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	public $ourBankAccountCode;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $fa_bank_accounts;	//!<object 
//
	protected $matched;	//!<bool
	protected $created;	//!<bool
	protected $HasPair;	//!<bool

	function __construct( array $trz )
	{
		$this->matched = 0;
		$this->created = 0;
		//$this->optypes = $optypes;  //passed into bi_lineitems
//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $trz, true ) );
		$this->transactionDC = $trz['transactionDC'];
		$this->memo = $trz['memo'];
		$this->our_account = $trz['our_account'];
		$this->valueTimestamp = $trz['valueTimestamp'];
		$this->entryTimestamp = $trz['entryTimestamp'];
		try {
			$this->otherBankAccount = shorten_bankAccount_Names( $trz['accountName'] );
		}
		catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
			$this->otherBankAccount = $trz['accountName'];
		}
		$this->otherBankAccountName = $trz['accountName'];
		if( strlen( $trz['transactionTitle'] ) < 4 )
		{
			if( strlen( $this->memo ) > strlen( $trz['transactionTitle'] ) )
			{
				$trz['transactionTitle'] .= $this->memo;
			}
		}
	       	$this->transactionTitle = $trz['transactionTitle'];
	       	$this->transactionCode = $trz['transactionCode'];
	       	$this->transactionCodeDesc = $trz['transactionCodeDesc'];
		$this->currency = $trz['currency'];
		$this->status = $trz['status'];
		$this->id = $trz['id'];
		$this->fa_trans_type = $trz['fa_trans_type'];
		$this->fa_trans_no = $trz['fa_trans_no'];
//Original code MT370 can have COM lines that add to the transaction
		$this->amount = $trz['transactionAmount'];
		$this->charge = 0;
		if ($trz['transactionType'] != 'COM') 
		{
			$this->has_trans = 1;
		} 
		$this->determineTransactionTypeLabel();
	//The following keeps coming up partnerId_xxxxx doesn't exist
		if( isset( $_POST["partnerId_" . $this->id] ) )
		{
			$this->partnerId = $_POST["partnerId_" . $this->id];
		}
		else
		{
			//We are using if(empty( ->partnerId ) ) so don't want to make it not empty!!
			//$this->partnerId = "";
		}
//TODO: Until we have this as a config variable
		$this->days_spread = DEFAULT_DAYS_SPREAD;
		$this->retrieveMatchingExistingJE();
		$_POST['partnerType'][ $this->id ] = $this->partnerType;
	}
	/**//*****************************************************************
	* Determine which label to apply
	*
	*@since 20250409
	*
	* @param none uses internal
	* @returns none sets internal
	**********************************************************************/
	function determineTransactionTypeLabel(): void
	{
		$tTL = new \TransactionTypeLabel( $this->transactionDC );
		$this->transactionTypeLabel = $tTL->getTransactionTypeLabel();
		return;
	}
	/**//*******************************************************
	*
	************************************************************/
	abstract function displayPartner();
	/**//******************************************************************
	* Get OUR Bank Account Details
	*
	*	TODO: REFACTOR to use class fa_bank_accounts instead of an array
	*
	**********************************************************************/
	function retrieveBankAccountDetails()
	{
		require_once( '../ksf_modules_common/class.fa_bank_accounts.php' );
		$fa_bank_accounts = new \fa_bank_accounts( $this );
		$this->ourBankDetails =	$fa_bank_accounts->getByBankAccountNumber( $this->our_account );
		/*
			Array ( [account_code] => 1061
				[account_type] => 0
				[bank_account_name] => CIBC Savings account
				[bank_account_number] => 00449 12-93230
				[bank_name] => CIBC
				[bank_address] =>
				[bank_curr_code] => CAD
				[dflt_curr_act] => 1
				[id] => 1
				[bank_charge_act] => 5690
				[last_reconciled_date] => 0000-00-00 00:00:00
				[ending_reconcile_balance] => 0
				[inactive] => 0 )
		*/
		$this->ourBankAccountName = $this->ourBankDetails['bank_account_name'];
		$this->ourBankAccountCode = $this->ourBankDetails['account_code'];
	}
	/**//***************************************************************
	* Find any transactions that alraedy exist that look like this one
	*
	* @param NONE
	* @returns array GL record(s) that match
	********************************************************************/
	function retrieveMatchingExistingJE()
	{
		if( ! isset( $this->matching_trans ) )
		{
		        //The transaction is imported into a bank account, with the counterparty being trz['accountName']
		        //      Existing transactions will have 2+ line items.  1 should match the bank, one should match the counterparty.
		        //      Currently we are matching and scoring each of the line items, rather than matching/scoring the GL itself.
		
		        //Check for matching into the accounts
		        // JE# / Date / Account / (Credit/Debit) / Memo in the GL Account (gl/inquiry/gl_account_inquiry.php)
		
/***********************************
		        $new_arr = array();
			// ** Namespace *
			// *	use Ksfraser\frontaccounting\FaGl;
		        // *		Will need to adjust he if( $inc )
			// **
		        $inc = include_once( __DIR__ . '/../ksf_modules_common/class.fa_gl.php' );
		        if( $inc )
		        {
			// ** Namespace *
		        // *       $fa_gl = new FaGl();
		        // *       $fa_gl = new \KSFRASER\FA\fa_gl();
			// *
		                $fa_gl = new fa_gl();
	 			$fa_gl->set( "amount_min", $this->amount );
	                	$fa_gl->set( "amount_max", $this->amount );
	                	$fa_gl->set( "amount", $this->amount );
	                	$fa_gl->set( "transactionDC", $this->transactionDC );
	                	$fa_gl->set( "days_spread", $this->days_spread );
	                	$fa_gl->set( "startdate", $this->valueTimestamp );     //Set converts using sql2date
	                	$fa_gl->set( "enddate", $this->entryTimestamp );       //Set converts using sql2date
	                	$fa_gl->set( "accountName", $this->otherBankAccountName );
	                	$fa_gl->set( "transactionCode", $this->transactionCode );
	                	$fa_gl->set( "memo_", $this->memo );
			
	
		                //Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
		                //              E-TRANSFER 010667466304;CUSTOMER NAME;...
		                //      function add_days($date, $days) // accepts negative values as well
		                try {
		                        $new_arr = $fa_gl->find_matching_transactions( $this->memo );
		                                //display_notification( __FILE__ . "::" . __LINE__ );
		                } catch( Exception $e )
		                {
		                        display_notification(  __FILE__ . "::" . __LINE__ . "::" . $e->getMessage() );
		                }
		                                //display_notification( __FILE__ . "::" . __LINE__ );
		        }
		        else
		        {
		                display_notification( __FILE__ . "::" . __LINE__ . ": Require_Once failed." );
		        }
			$this->matching_trans = $new_arr;
********************************************/
			try
			{
				$fa = new FaGLWrapper( $this );
				$this->matching_trans = $fa->retrieveMatchingTransactions();
			}
			catch( Exception $e )
			{
		        	display_notification(  __FILE__ . "::" . __LINE__ . "::" . $e->getMessage() );
			}
		}
	        return $this->matching_trans;
	}
	/**//****************************************************************
	* Add a display button to add a Customer or a Vendor
	*
	**********************************************************************/
	function displayAddVendorOrCustomer()
	{
		try {
			$matchedVendor = $this->matchedVendor();
			$matched_supplier = $this->matchedSupplierId( $matchedVendor );
			hidden( 'vendor_id', $matchedVendor );
			label_row("Matched Vendor", print_r( $matchedVendor, true ) . "::" . print_r( $this->vendor_list[$matchedVendor]['supplier_id'], true ) . "::" . print_r( $this->vendor_list[$matchedVendor]['supp_name'], true ) );
		}
		catch( Exception $e )
		{
			$this->selectAndDisplayButton();
		}
		finally
		{
			hidden( "vendor_short_$this->id", $this->otherBankAccount );
			hidden( "vendor_long_$this->id", $this->otherBankAccountName );
		}
	}
	abstract function selectAndDisplayButton();
	function matchedVendor() 
	{
		if( ! isset( $this->vendor_list ) )
		{
			throw new Exception( "Field not set ->vendor_list", KSF_FIELD_NOT_SET );
		}
		if( ! isset( $this->otherBankAccount ) )
		{
			throw new Exception( "Field not set ->otherBankAccount", KSF_FIELD_NOT_SET );
		}
		$matchedVendor = array_search( trim($this->otherBankAccount), $this->vendor_list['shortnames'], true );
		return $matchedVendor;
	}

	protected function matchedSupplierId($matchedVendor): ?int
	{
		if (!is_array($this->vendor_list) || !isset($this->vendor_list[$matchedVendor]['supplier_id'])) {
			throw new Exception("Field not set ->vendor_list[supplier_id]", KSF_FIELD_NOT_SET);
		}

		return (int)$this->vendor_list[$matchedVendor]['supplier_id'];
	}
	function ToggleTransactionTypeButton()
	{
		$b = new ToggleTransactionTypeButton( $this->id );
		$b->toHtml();
	}
	/**//***************************************************************
	* Check if the transaction has a pair.
	*
	*	Will set a flag so we can just check the flag
	*	the "paired" transaction should also still be unprocessed
	*	if we are showing the pairing.  Otherwise it should show
	*	in the matching GLs.
	*
	*********************************************************************/
	function isPaired()
	{
		$this->HasPair = false;
		return $this->HasPair;
	}
	function tellPartnerType()
	{
	}
}


