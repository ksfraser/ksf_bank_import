<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

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
// If you change the list of properties below, ensure that you also modify
// build_write_properties_array
 * */

// require_once( __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php' );
use Ksfraser\common\GenericFaInterface;

// require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );
use Ksfraser\common\Defines;

/**//**************************************************************************************************************
* A class to handle displaying the line item of a statement. 
*
*	TODO:
*		replace if( empty( ->partnerId ) ) with equivalent logic so we can set ->partnerId in the constructor
*
*
******************************************************************************************************************/
class bi_lineitem extends generic_fa_interface_model 
{

/**
	var $id_bi_transactions_model;	//!< Index of table
	protected $id;		  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $smt_id;	      //| int(11)      | NO   |     | NULL    |		|
	protected $valueTimestamp;      //| date	 | YES  |     | NULL    |		|
	protected $entryTimestamp;      //| date	 | YES  |     | NULL    |		|
	protected $account;	     //| varchar(24)  | YES  |     | NULL    |		|
	protected $accountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $transactionType;     //| varchar(3)   | YES  |     | NULL    |		|
	protected $transactionCode;     //| varchar(32)  | YES  |     | NULL    |		|
	protected $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |		|
	protected $transactionDC;       //| varchar(2)   | YES  |     | NULL    |		|
	protected $transactionAmount;   //| double       | YES  |     | NULL    |		|
	protected $transactionTitle;    //| varchar(256) | YES  |     | NULL    |		|
	protected $status;	      //| int(11)      | YES  |     | 0       |		|
	protected $matchinfo;	   //| varchar(256) | YES  |     | NULL    |		|
	protected $fa_trans_type;       //| int(11)      | YES  |     | 0       |		|
	protected $fa_trans_no;	 //| int(11)      | YES  |     | 0       |		|
	protected $fitid;
	protected $acctid;
	protected $merchant;	    //| varchar(64)  | NO   |     | NULL    |		|
	protected $category;	    //| varchar(64)  | NO   |     | NULL    |		|
	protected $sic;		 //| varchar(64)  | NO   |     | NULL    |		|
	protected $memo;		//| varchar(64)  | NO   |     | NULL    |		|
	protected $checknumber;	//!<int
	protected $matched;	//!<bool
	protected $created;	//!<bool
	protected $g_partner;	//!<varchar	Which action (bank/Quick Entry/...
	protected $g_option;	//!<varchar	Which choice - ATB/Groceries/...
*/
	protected $transactionDC;       //| varchar(2)   | YES  |     | NULL    |		|
	protected $our_account; 	//| varchar()   | YES  |     | NULL    |		|
	protected $valueTimestamp;      //| date	 | YES  |     | NULL    |		|
	protected $entryTimestamp;      //| date	 | YES  |     | NULL    |		|
	protected $otherBankaccount;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $otherBankaccountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $transactionTitle;    //| varchar(256) | YES  |     | NULL    |		|
	protected $status;	      //| int(11)      | YES  |     | 0       |		|
	protected $currency;
	protected $fa_trans_type;       //| int(11)      | YES  |     | 0       |		|
	protected $fa_trans_no;	 //| int(11)      | YES  |     | 0       |		|
	protected $id;		  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $has_trans;	//!< bool
	protected $amount;	//!<float
	protected $charge;	//!<float
	protected $transactionTypeLabel;     //!< string
	protected $vendor_list;			//!<array
	protected $partnerType;	//!<string
	protected $partnerId;	//!<int
	protected $partnerDetailId;	//!<int		//Used for Customer Branch
	protected $oplabel;
	protected $matching_trans;	//!<array was arr_arr
	protected $days_spread;
	protected $transactionCode;     //| varchar(32)  | YES  |     | NULL    |		|
	protected $transactionCodeDesc; //| varchar(32)  | YES  |     | NULL    |		|
	protected $optypes;	//!< array
	protected $memo;		//| varchar(64)  | NO   |     | NULL    |		|
	//@todo
	//REFACTOR:
	//		refactor to use class.fa_bank_accounts.php instead of an array!
	protected $ourBankDetails;	//!< array
	protected $ourBankAccount;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $ourBankAccountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $ourBankAccountCode;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $fa_bank_accounts;	//!<object 

	function __construct( $trz, $vendor_list = array(), $optypes = array() )
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
	//	$this->iam = "bi_transactions";
	//	$this->define_table();
		$this->matched = 0;
		$this->created = 0;
		$this->charge = 0;
		$this->days_spread = 2;
		$this->vendor_list = $vendor_list;
		$this->optypes = $optypes;

		$this->transactionDC = $trz['transactionDC'];
		$this->determineTransactionTypeLabel();
		$this->memo = $trz['memo'];
		$this->our_account = $trz['our_account'];
		$this->valueTimestamp = $trz['valueTimestamp'];
		$this->entryTimestamp = $trz['entryTimestamp'];
		try {
			//display_notification( __FILE__ . "::" . __LINE__ );
			$this->otherBankAccount = shorten_bankAccount_Names( $trz['accountName'] );
			//display_notification( __FILE__ . ":" .  __LINE__ . " " . print_r( $this->otherBankAccount, true )  );
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
		//display_notification( __FILE__ . "::" . __LINE__ );
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
		if ($trz['transactionType'] != 'COM') 
		{
			//display_notification( __FILE__ . "::" . __LINE__ );
			$this->has_trans = 1;
			//moved amount to above IF
			//$this->amount = $trz['transactionAmount'];
		} 
/*
		else if ($trz['transactionType'] == 'COM')
		{
			//display_notification( __FILE__ . "::" . __LINE__ );
			$this->amount += $trz['transactionAmount'];
		}
*/
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

	}
	/**//*****************************************************************
	* Determine which label to apply
	*
	*@since 20250409
	*
	* @param none uses internal
	* @returns none sets internal
	**********************************************************************/
	function determineTransactionTypeLabel() : null
	{
		//This could probably be moved to a getTransactionTypeLabel function
		switch( $this->transactionDC )
		{
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
		return null;
	}
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display()
	{
		$this->display_left();
		$this->display_right();
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
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display_left()
	{
		start_row();
		echo '<td width="50%">';
		start_table(TABLESTYLE2, "width='100%'");
		label_row("Trans Date (Event Date):", $this->valueTimestamp . " :: (" . $this->entryTimestamp . ")" , "width='25%' class='label'");
		label_row("Trans type:", $this->transactionTypeLabel);
		$this->getBankAccountDetails();

		//label_row("Our Bank Account - (Account Name)(Number):", $this->our_account . ' - ' . $this->ourBankDetails['bank_name'] . " (" . $this->ourBankAccountName . ")(" . $this->ourBankAccountCode . ")"  );
		label_row("Our Bank Account - (Account Name)(Number):", $this->our_account . ' - ' . $this->fa_bank_accounts->get('bank_name') . " (" . $this->fa_bank_accounts->get( "bank_account_name" ) . ")(" . $this->fa_bank_accounts->get( "account_code" ) . ")"  );
		label_row("Other account:", $this->otherBankAccount . ' / '. $this->otherBankAccountName);
		label_row("Amount/Charge(s):", $this->amount.' / '. $this->charge ." (".$this->currency.")");
		label_row("Trans Title:", $this->transactionTitle);
		$this->displayAddVendorOrCustomer();
		$this->displayEditTransData();
		if( $this->isPaired() )
		{
			//TODO: make sure the paired transactions are set to BankTranfer rather than Credit/Debit
			$this->displayPaired();
		}
		end_table();

	}
	/**//****************************************************************
	* Add a display button to add a Customer or a Vendor
	*
	**********************************************************************/
	function displayAddVendorOrCustomer()
	{
	      if( ! in_array(  trim($this->otherBankAccount), $this->vendor_list['shortnames'] ) )
		{

			//display_notification( __FILE__ . "::" . __LINE__ . "::" . " Looked for: //" . trim($this->otherBankAccount) . "// but didn't fint it.  :: " . print_r( $this->vendor_list['shortnames'], true )  );

					 //function submit($name, $value, $echo=true, $title=false, $atype=false, $icon=false)
			if( $this->transactionDC=='D' )
			{
				label_row("Add Vendor", submit("AddVendor[$this->id]",_("AddVendor"),false, '', 'default'));
			} else
			if( $this->transactionDC=='C' )
			{
				label_row("Add Customer", submit("AddCustomer[$this->id]",_("AddCustomer"),false, '', 'default'));
			}
			hidden( "vendor_short_$this->id", $this->otherBankAccount );
			hidden( "vendor_long_$this->id", $this->otherBankAccountName );
			$matched_supplier = null;
		}
		else
		{
			//IS INARRAY
			$matched_vendor = array_search( trim($this->otherBankAccount), $this->vendor_list['shortnames'], true );
			$matched_supplier = $this->vendor_list[$matched_vendor]['supplier_id'];
			hidden( 'vendor_id', $matched_vendor );
			label_row("Matched Vendor", print_r( $matched_vendor, true ) . "::" . print_r( $this->vendor_list[$matched_vendor]['supplier_id'], true ) . "::" . print_r( $this->vendor_list[$matched_vendor]['supp_name'], true ) );
		}
	}
	/**//*****************************************************************
	* Set partnerType
	*
	**********************************************************************/
	function setPartnerType()
	{
		switch( $this->transactionDC )
		{
			case 'C':
				$this->partnerType = 'CU';
				$this->oplabel = "Depost";
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
	/**//**************************************************************
	* Display our paired transaction
	*
	*******************************************************************/
	function displayPaired()
	{
	}
	/**//***************************************************************
	* Find paired transactions i.e. bank transfers from one account to another
	* such as Savings <> HISA or CC payments
	*
	*	Because of the extra processing time, this function needs to be run
	*	as a maintenance activity rather than as a real time search.
	*
	* @returns array of transactions that are paired with this one.	 
	*********************************************************************/
	function findPaired()
	{
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
	        //      Existing transactions will have 2+ line items.  1 should match the bank, one should match the counterparty.
	        //      Currently we are matching and scoring each of the line items, rather than matching/scoring the GL itself.
	
	        //Check for matching into the accounts
	        // JE# / Date / Account / (Credit/Debit) / Memo in the GL Account (gl/inquiry/gl_account_inquiry.php)
	
	        $new_arr = array();
	        // $inc = include_once( __DIR__ . '/../ksf_modules_common/class.fa_gl.php' );
	        use Ksfraser\frontaccounting\FaGl;
			/** NAMESPACE
	        if( $inc )
			*/
	        {
	  		//display_notification( __FILE__ . "::" . __LINE__ );
	                $fa_gl = new FaGl();
 					$fa_gl->set( "amount_min", $this->amount );
                	$fa_gl->set( "amount_max", $this->amount );
                	$fa_gl->set( "amount", $this->amount );
                	$fa_gl->set( "transactionDC", $this->transactionDC );
                	$fa_gl->set( "days_spread", $this->days_spread );
                	$fa_gl->set( "startdate", $this->valueTimestamp );     //Set converts using sql2date
                	$fa_gl->set( "enddate", $this->entryTimestamp );       //Set converts using sql2date
                	$fa_gl->set( "accountName", $this->otherBankAccountName );
                	$fa_gl->set( "transactionCode", $this->transactionCode );

	        //      $fa_gl = new \KSFRASER\FA\fa_gl();
	                //Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
	                //              E-TRANSFER 010667466304;CUSTOMER NAME;...
	                //      function add_days($date, $days) // accepts negative values as well
	                try {
	                        $new_arr = $fa_gl->find_matching_transactions();
	                                //display_notification( __FILE__ . "::" . __LINE__ );
	                } catch( Exception $e )
	                {
	                        display_notification(  __FILE__ . "::" . __LINE__ . "::" . $e->getMessage() );
	                }
			/** Namespace
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
	/**//***************************************************************
	* Display the entries from the matching_trans array 
	*
	*	REFACTOR to move HTML code into a VIEW class
	*
	********************************************************************/
	function displayMatchingTransArr()
	{
		if( count( $this->matching_trans ) > 0 )
		{
			$match_html = "";
			$matchcount = 1;
			foreach( $this->matching_trans as $matchgl )
			{
				//[type] => 0 [type_no] => 8811 [tran_date] => 2023-01-03 [account] => 2620.frontier [memo_] => 025/2023 
				//	[amount] => 432.41 [person_type_id] => [person_id] => [account_name] => Auto Loan Frontier (Nissan Finance) [reference] => 025/2023 [score] => 111 [is_invoice]
				if( isset( $matchgl['tran_date'] ) )
				{
					/******************************************************************************************
					* In Savings Account, Customer Payment is a DEBIT.
					* NISSAN is a DEBIT out of Savings in the IMPORT file.  So amount in example should be -
					*
					*Customer Payment is a CREDIT from import file.  Amount should match exact the Bank trans.
					*
					* so if the bank account number matches and adjusted amount matches...
					*****************************************************************************************/
					$match_html .= "<b>$matchcount</b>: ";
					if( ! @include_once( __DIR__  . "/../ksf_modules_common/defines.inc.php") )
					{
						$match_html .= " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
					}
					else
					{
						$match_html .= " Transaction " . $matchgl['type'] . ":" . $matchgl['type_no'];
					}
					$match_html .= " Score " . $matchgl['score'] . " ";
					//if( strcasecmp( $this->our_account, $matchgl['account'] ) OR strcasecmp( $this->ourBankDetails['bank_account_name'], $matchgl['account'] ) )
					if( strcasecmp( $this->our_account, $matchgl['account'] ) OR strcasecmp( $this->fa_bank_accounts->get( "bank_account_name" ), $matchgl['account'] ) )
					{
						$match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
					}
					else
					{
						$match_html .= "MATCH BANK:: ";
						$match_html .=  print_r( $our_account, true );
						$match_html .= "::" . print_r( $this->fa_bank_accounts->get( "bank_account_name" ), true );
						//$match_html .= "::" . print_r( $this->ourBankDetails['bank_account_name'], true );
						$match_html .= " Matching " . print_r( $matchgl, true );
						$match_html .= "Account " . $matchgl['account'] . "---";
					}
					$match_html .= " " . $matchgl['account_name'] . " ";
					if( $this->transactionDC == 'D' )
					{
						$scoreamount = -1 * $this->amount;
					}
					else
					{
						$scoreamount = 1 * $this->amount;
					}
					if( $scoreamount == $matchgl['amount'] )
					{
						$match_html .= "<b> " . $matchgl['amount'] . "</b> ";
					}
					else
					{
						$match_html .= $matchgl['amount'];
					}
					if( isset( $matchgl["person_type_id"] ) )
					{
						$cdet = get_customer_details_from_trans(	$matchgl['type'],
												$matchgl['type_no']
							);
						$match_html .= " //Person " . $cdet['name'] . "/" . $cdet["br_name"];
						//$match_html .= " //Person " . print_r( $cdet, true ) . "/" . $matchgl["person_id"];
						//$match_html .= " //Person " . $matchgl['person_type_id'] . "/" . $matchgl["person_id"];
					}
					$match_html .= "<br />";
					$matchcount++;
				} //if isset
			} //foreach
			label_row("Matching GLs.  Ensure you double check Accounts and Amounts", $match_html );
			//label_row("Matching GLs", print_r( $this->matching_trans, true ) );
		}
		else
		{
				label_row("Matching GLs", "No Matches found automatically" );
		}
	}
	/**//***************************************************************
	* Look for transactions with the same date and amount and list them
	*
	********************************************************************/
	function getDisplayMatchingTrans()
	{
		//our find_... sets matching_trans
		//$this->matching_trans = $this->findMatchingExistingJE();
		$this->findMatchingExistingJE();
		if( count( $this->matching_trans ) > 0 )
		{
			//Rewards (points) might have a split so only 1 count
			//      Walmart
			//      PC Points
			//      Gift Cards (Restaurants, Amazon)
			//but everyone else should have 2

			if( count( $this->matching_trans ) < 3 )
			{

				//We matched A JE and the score is high.  Suggest trans type.
				if( 50 <= $this->matching_trans[0]['score'] )
				{
					//var_dump( __LINE__ );
					//It was an excellent match
					if( $this->matching_trans[0]['is_invoice'] )
					{
						//This TRZ is a supplier payment
						//that matches an invoice exactly.
							$_POST['partnerType'][$this->id] = 'SP';
					}
					else
					{
							$_POST['partnerType'][$this->id] = 'ZZ';
					}
					$this->oplabel = "MATCH";
				hidden("trans_type_$this->id", $this->matching_trans[0]['type'] );
				hidden("trans_no_$this->id", $this->matching_trans[0]['type_no'] );

				}
				else
					var_dump( __LINE__ );
			}
			else
			{
				//If there are 3+ then we need to sort by score and take the highest scored item!
			}
		}
	}
	/**//*******************************************************************
	* Display SUPPLIER partner type
	*
	************************************************************************/
	function displaySupplierPartnerType() 
	{
				//propose supplier
		$matched_supplier = array();
		if ( empty( $this->partnerId ) )
		{
			$matched_supplier = search_partner_by_bank_account(PT_SUPPLIER, $this->otherBankAccount);
			if (!empty($matched_supplier))
			{
				$this->partnerId = $_POST["partnerId_$this->id"] = $matched_supplier['partner_id'];
			}
		}
		//		       supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
		label_row(_("Payment To:"), supplier_list("partnerId_$this->id", $matched_supplier, false, false));
	}
	/**//*******************************************************************
	* Display CUSTOMER partner type
	*
	************************************************************************/
	function displayCustomerPartnerType()
	{
		//propose customer
		if ( empty( $this->partnerId ) ) 
		{
			$match = search_partner_by_bank_account(PT_CUSTOMER, $this->otherBankAccount);
			if (!empty($match)) {
				$this->partnerId = $_POST["partnerId_$this->id"] = $match['partner_id'];
				$this->partnerDetailId = $_POST["partnerDetailId_$this->id"] = $match['partner_detail_id'];
			}
		}
/* ->partnerId already set
		else
		{
			$this->partnerId = $_POST["partnerId_$this->id"];
		}
*/
		$cust_text = customer_list("partnerId_$this->id", null, false, true);
		if ( db_customer_has_branches( $this->partnerId ) ) {
			$cust_text .= customer_branches_list( $this->partnerId, "partnerDetailId_$this->id", null, false, true, true);
		} else {
			hidden("partnerDetailId_$this->id", ANY_NUMERIC);
			$_POST["partnerDetailId_$this->id"] = ANY_NUMERIC;
		}
		label_row(_("From Customer/Branch:"),  $cust_text);
		hidden( "customer_$this->id", $this->partnerId );
		hidden( "customer_branch_$this->id", $this->partnerDetailId );
		//label_row("debug", "customerid_tid=".$this->partnerId . " branchid[tid]=" . $this->partnerDetailId );
/** Mantis 3018
 *      List FROM and TO invoices needing payment (allocations) 
*/
		$_GET['customer_id'] = $this->partnerId;
		//if( ! @include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
		use Ksfraser\frontaccounting\FaCustomerPayment;
		if(  @include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
		{
			$tr = 0;
			$fcp = new fa_customer_payment();
			$fcp->set( "trans_date", $this->valueTimestamp );
			label_row( "Invoices to Pay", $fcp->show_allocatable() );
			$res = $fcp->get_alloc_details();
				//Array ( [10] => Array ( [trans_no] => 10 [type_no] => 789 [trans_date] => 12/14/2024 [invoice_amount] => 50 [payments] => 0 [unallocated] => 50 ) )
			//label_row( "Invoices to Pay", print_r( $res, true) );
									//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
			//label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice_$this->id", 0, 6, '', _("Invoice to Allocate Payment:") ) );
			foreach( $res as $row )
			{
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $row, true ) );
				$tr = $row['type_no'];
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $tr, true ) );
			}
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $tr, true ) );
			label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice_$this->id", $tr, 6, '', _("Invoice to Allocate Payment:") ) );
		}
/* ! Mantis 3018 */
			//label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice_$this->id", 0, 6, '', _("Invoice to Allocate Payment:") ) );

	}
	/**//*******************************************************************
	* Display Bank Transfer partner type
	*
	************************************************************************/
	function displayBankTransferPartnerType()
	{
		if (empty($_POST["partnerId_$this->id"]))
		{
			$match = search_partner_by_bank_account(ST_BANKTRANSFER, $this->otherBankAccount);
			if (!empty($match))
			{
				$_POST["partnerId_$this->id"] = $match['partner_id'];
				$_POST["partnerDetailId_$this->id"] = $match['partner_detail_id'];
			}
			else
			{
				$_POST["partnerId_$this->id"] = ANY_NUMERIC;
			}
		}
	//function bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
		 //bank_accounts_list_row( _("From:") , 'bank_account', null, true);
/** Mantis 2963
*       Bank Transfer To/From label 
*/
		if( $this->transactionDC == 'C' )
		{
			$rowlabel = "Transfer to <i>Our Bank Account</i> from (<b>OTHER ACCOUNT</b>):";
		}
		else
		{
			$rowlabel = "Transfer from <i>Our Bank Account</i> To (<b>OTHER ACCOUNT</b>):";
		}
/** ! Mantis 2963 */
		//bank_accounts_list_row( _("From:") , 'bank_account', null, false)
		label_row(      _( $rowlabel ),
			bank_accounts_list( "partnerId_$this->id", $_POST["partnerId_$this->id"], null, false)
		);
		$this->partnerId = $_POST["partnerId_$this->id"];
	}
	/**//*******************************************************************
	* Display Quick Entry partner type
	*
	************************************************************************/
	function displayQuickEntryPartnerType()
	{
		//label_row("Option:", "<b>Process via quick entry</b>");
		$qe_text = quick_entries_list("partnerId_$this->id", null, (($this->transactionDC=='C') ? QE_DEPOSIT : QE_PAYMENT), true);
		$qe = get_quick_entry(get_post("partnerId_$this->id"));
		$qe_text .= " " . $qe['base_desc'];
		label_row("Quick Entry:", $qe_text);
	}
	/**//*******************************************************************
	* Display MATCHED partner type
	*
	************************************************************************/
	function displayMatchedPartnerType()
	{
		hidden("partnerId_$this->id", 'manual');
		//function array_selector($name, $selected_id, $items, $options=null)
			//value => description
		$opts_arr = array(
				ST_JOURNAL => "Journal Entry",
				ST_BANKPAYMENT => "Bank Payment",
				ST_BANKDEPOSIT => "Bank Deposit",
				ST_BANKTRANSFER => "Bank Transfer",
				//ST_SALESINVOICE => "Sales Invoice",
				ST_CUSTCREDIT => "Customer Credit",
				ST_CUSTPAYMENT => "Customer Payment",
				//ST_CUSTDELIVERY => "Customer Delivery",
				//ST_LOCTRANSFER => "Location Transfer",
				//ST_INVADJUST => "Inventory Adjustment",
				//ST_PURCHORDER => "Purchase Order",
				//ST_SUPPINVOICE => "Supplier Invoice",
				ST_SUPPCREDIT => "Supplier Credit",
				ST_SUPPAYMENT => "Supplier Payment",
				//ST_SUPPRECEIVE => "Supplier Receiving",
			);
		$name="Existing_Type";
			label_row(_("Existing Entry Type:"), array_selector( $name, 0, $opts_arr ) );
		//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
		label_row(
			(_("Existing Entry:")),
			text_input( "Existing_Entry", 0, 6, '', _("Existing Entry:") )
		);
	}
	/**//*******************************************************************
	* Select the right type of partner to display
	*
	************************************************************************/
	function displayPartnerType()
	{
		switch( $_POST['partnerType'][$this->id] ) 
		{
			case 'SP':
				$this->displaySupplierPartnerType();		
				break;
			case 'CU':
				$this->displayCustomerPartnerType();		
				break;
			case 'BT':      //partnerType
				$this->displayBankTransferPartnerType();		
				break;
			// quick entry
			case 'QE':      //partnerType
				$this->displayQuickEntryPartnerType();		
				break;
			case 'MA':
				$this->displayMatchedPartnerType();		
				break;
			case 'ZZ':      //partnerType
				//Matched an existing item
				if( isset( $this->matching_trans[0] ) )
				{
					//if( isset( $this->matching_trans[0] ) )
					//{
						hidden("partnerId_$this->id", $this->matching_trans[0]['type'] );
					//}
					//if( isset( $this->matching_trans[0]['type_no'] ) )
					//{
						hidden("partnerDetailId_$this->id", $this->matching_trans[0]['type_no'] );
						hidden("trans_no_$this->id", $this->matching_trans[0]['type_no'] );
					//}
					//if( isset( $this->matching_trans[0]['type'] ) )
					//{
						hidden("trans_type_$this->id", $this->matching_trans[0]['type'] );
					//}
					//if( isset( $this->memo ) )
					//{
						hidden("memo_$this->id", $this->memo );
					//}
					//if( isset( $this->transactionTitle ) )
					//{
						hidden("title_$this->id", $this->transactionTitle );
					//}
				}
				break;
		}

			// text_input( "Invoice_$this->id", 0, 6, '', _("Invoice to Allocate Payment:") ) );
		label_row(
			(_("Comment:")),
			text_input( "comment_$this->id", $this->memo, strlen($this->memo), '', _("Comment:") )
		);
 		label_row("", submit("ProcessTransaction[$this->id]",_("Process"),false, '', 'default'));
	}
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display_right()
	{
	 	echo "</td><td width='50%' valign='top'>";
		start_table(TABLESTYLE2, "width='100%'");
		//now display stuff: forms and information

		if ($this->status == 1)
		{
			$this->display_settled();
	 	} else {
			//transaction NOT settled
			// this is a new transaction, but not matched by routine so just display some forms
/*
			if( $this->transactionDC == 'C' )
				$this->oplabel = "Depost";
			else
				$this->oplabel = "Payment";
*/
			//display_notification( __FILE__ . "::" . __LINE__ . ": ->id and  ->partnerType (EMPTY?) and _POST['partnerType']: " . $this->id . "::" . $this->partnerType . "::" . $_POST['partnerType'][$this->id] );
			$this->setPartnerType();
			//display_notification( __FILE__ . "::" . __LINE__ . ": ->partnerType and _POST['partnerType']: "  . $this->id . "::" . $this->partnerType . "::" . $_POST['partnerType'][$this->id] );
			//Leaving in process_statement
			$this->getDisplayMatchingTrans();
			//display_notification( __FILE__ . "::" . __LINE__ . ": ->partnerType and _POST['partnerType']: " . $this->id . "::"  . $this->partnerType . "::" . $_POST['partnerType'][$this->id] );
			label_row("Operation:", $this->oplabel, "width='25%' class='label'");
			////label_row("Operation:", (($transactionDC=='C') ? "Deposit" : "Payment"), "width='25%' class='label'");
//Something is clobbering $this->partnerType but not $_POST['partnerType'][$this->id]
			//label_row("Partner:", array_selector("partnerType[$this->id]", $this->partnerType, $this->optypes, array('select_submit'=> true)));
			label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], $this->optypes, array('select_submit'=> true)));
	/*************************************************************************************************************/
		//3rd cell
			if ( !isset( $_POST["partnerId_$this->id"] ) )
			{
				$_POST["partnerId_$this->id"] = '';
			}

			//Leaving in process_statement
			$this->displayPartnerType();

			//label_row("", submit("ProcessTransaction[$this->id]",_("Process"),false, '', 'default'));

			//other common info
			//Apparantly cids is just an empty array at this point in the original code
			if( ! isset( $cids ) )
			{
				$cids = array();
			}
			$cids = implode(',', $cids);
			hidden("cids[$this->id]",$cids);
			$this->displayMatchingTransArr();
		}
		end_table();
		echo "</td>";
		end_row();

	}
	/**//*****************************************************************
	* We want the ability to edit the raw trans data since some banks don't follow standards
	*************************************************************************************/
	function displayEditTransData()
	{
		//label_row("Edit this Transaction Data", submit("EditTransaction[$this->id]",_("EditTransaction"),false, '', 'default'));
		label_row("Toggle Transaction Type Debit/Credit", submit("ToggleTransaction[$this->id]",_("ToggleTransaction"),false, '', 'default'));
/*
		label_row("Edit this Transaction Data", submit("EditTransaction[$this->id]",_("EditTransaction"),false, '', 'default'));
		hidden( "vendor_short_$this->id", $this->otherBankAccount );
		hidden( "vendor_long_$this->id", $this->otherBankAccountName );
*/
	}
	/**//*****************************************************************
	* Display a settled transaction
	*
	**********************************************************************/
	function display_settled()
	{
		// the transaction is settled, we can display full details
		label_row("Status:", "<b>Transaction is settled!</b>", "width='25%' class='label'");
		switch ($this->fa_trans_type)
		{
			case ST_SUPPAYMENT:
				label_row("Operation:", "Payment");
				// get supplier info
				label_row("Supplier:", $minfo['supplierName']);
				label_row("From bank account:", $minfo['coyBankAccountName']);
			break;
			case ST_BANKDEPOSIT:
				label_row("Operation:", "Deposit");
				//get customer info from transaction details
//TODO: Refactor to use fa_customer
				$fa_trans = get_customer_trans($this->fa_trans_no, $this->fa_trans_type);
				label_row("Customer/Branch:", get_customer_name($fa_trans['debtor_no']) . " / " . get_branch_name($fa_trans['branch_code']));
			break;
			case 0:
				label_row("Operation:", "Manual settlement");
			break;
			default:
				label_row("Status:", "other transaction type; no info yet " . print_r( $this, true ) );
			break;
	      	}
		label_row( "Unset Transaction Association", submit( "UnsetTrans[$this->id]", _( "Unset Transaction $this->fa_trans_no"), false, '', 'default' ));
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
	/**//**********************************************************************
	* Convert Transaction array to this object
	*
	* @param class
	* @returns int how many fields did we copy
	**************************************************************************/
	function trz2obj( $trz ) : int
	{
		return $this->obj2obj( $trz );
	}
}
