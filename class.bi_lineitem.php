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
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

require_once( __DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php' );
require_once( __DIR__ . '/../ksf_modules_common/defines.inc.php' );
//use Ksfraser\common\GenericFaInterface;
//use Ksfraser\common\Defines;

/*
require_once( __DIR__ . '/Views/HTML/HtmlElementInterface.php' );
require_once( __DIR__ . '/Views/HTML/HtmlElement.php' );
require_once( __DIR__ . '/Views/HTML/HtmlTableRow.php' );
*/
//use Ksfraser\HTML\HtmlElementInterface;

//require_once( __DIR__ . '/src/Ksfraser/HTML/HTML_ROW_LABELDecorator.php' );
require_once( __DIR__ . '/Views/HTML_ROW_LABELDecorator.php' );

class HTML_SUBMIT
{
	function __construct()
	{
	}
	function toHTML()
	{
	}
}

require_once( __DIR__ . '/Views/AddCustomerButton.php' );
require_once( __DIR__ . '/Views/AddVendorButton.php' );
require_once( __DIR__ . '/Views/TransactionTypeLabel.php' );

// SRP View classes for label rows
require_once( __DIR__ . '/Views/TransDate.php' );
require_once( __DIR__ . '/Views/TransType.php' );
require_once( __DIR__ . '/Views/OurBankAccount.php' );
require_once( __DIR__ . '/Views/OtherBankAccount.php' );
require_once( __DIR__ . '/Views/AmountCharges.php' );
require_once( __DIR__ . '/Views/TransTitle.php' );

// SRP View classes for partner type displays
require_once( __DIR__ . '/Views/PartnerMatcher.php' );
require_once( __DIR__ . '/Views/SupplierPartnerTypeView.php' );
require_once( __DIR__ . '/Views/CustomerPartnerTypeView.php' );
require_once( __DIR__ . '/Views/BankTransferPartnerTypeView.php' );
require_once( __DIR__ . '/Views/QuickEntryPartnerTypeView.php' );

require_once( __DIR__ . '/src/Ksfraser/HTML/HTML_ROW.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/HtmlString.php' );
require_once( __DIR__ . '/Views/HTML/HtmlRawString.php' );
require_once( __DIR__ . '/Views/HTML/HtmlOB.php' );
require_once( __DIR__ . '/Views/HTML/HtmlTable.php' );
require_once( __DIR__ . '/Views/HTML/HtmlTd.php' );
require_once( __DIR__ . '/Views/HTML/HtmlTableRow.php' );
require_once( __DIR__ . '/Views/HTML/HtmlElement.php' );
require_once( __DIR__ . '/Views/HTML/HtmlAttribute.php' );
require_once( __DIR__ . '/Views/HTML/HtmlLink.php' );
require_once( __DIR__ . '/Views/HTML/HtmlA.php' );
use Ksfraser\HTML\HTML_ROW;
use Ksfraser\HTML\HtmlString;
use Ksfraser\HTML\HTMLAtomic\{HtmlRawString, HtmlOB, HtmlTable, HtmlTd, HtmlTableRow, HtmlElement, HtmlAttribute, HtmlLink, HtmlA};


require_once( __DIR__ . '/src/Ksfraser/HTML/HTML_ROW_LABEL.php' );
use Ksfraser\HTML\HTML_ROW_LABEL;

class HTML_TABLE
{
	protected $rows;
	protected $style;
	protected $width;
	function __construct( $style = TABLESTYLE2, $width=100 )
	{
		$this->style = $style;
		$this->width = $width;
		$this->rows = array();
	}
	function toHTML()
	{
		start_table( $this->style, "width='" . $this->width . "%'" );
		foreach( $rows as $row )
		{
			$row->toHTML();
		}
		end_table();
	}
	function appendRow( $row )
	{
		if( is_object( $row ) )
		{
			//if( is_a( $row, 'ksfraser\HTML\HTML_ROW' ) )	//When using namespaces must be fully spelled out.
			if( is_a( $row, 'HTML_ROW' ) )
			{
				$this->rows[] = $row;
			}
			else
			{
				throw new Exception( "Passed in class is not an HTML_ROW or child type!" );
			}
		}
		else
		if( is_string( $row ) )
		{
			$r = new HTML_ROW( $row );
			$this->rows[] = $r;
		}
		else
		{	
			throw new Exception( "Passed in data for a row is neither a class nor a string" );
		}
	}
}


require_once( __DIR__ . '/Views/LineitemDisplayLeft.php' );
class displayLeft extends LineitemDisplayLeft
{
}

class displayRight
{
}

require_once( __DIR__ . '/Views/TransDate.php' );
require_once( __DIR__ . '/Views/TransType.php' );
require_once( __DIR__ . '/Views/OurBankAccount.php' );
require_once( __DIR__ . '/Views/OtherBankAccount.php' );
require_once( __DIR__ . '/Views/AmountCharges.php' );
require_once( __DIR__ . '/Views/TransTitle.php' );


/**//**************************************************************************************************************
* A class to handle displaying the line item of a statement. 
*
*	TODO:
*		replace if( empty( ->partnerId ) ) with equivalent logic so we can set ->partnerId in the constructor
		REFACTOR to move VIEW code out of this MODEL class
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
//REFACTOR:
//		refactor to use class.fa_bank_accounts.php instead of an array!
	protected $ourBankDetails;	//!< array
	protected $ourBankAccount;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $ourBankAccountName;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $ourBankAccountCode;	 //| varchar(60)  | YES  |     | NULL    |		|
	protected $fa_bank_accounts;	//!<object 
//
	protected $matched;	//!<bool
	protected $created;	//!<bool


	function __construct( $trz, $vendor_list = array(), $optypes = array() )
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		//display_notification( __FILE__ . "::" . __LINE__ );
		parent::__construct( null, null, null, null, null);
		//display_notification( __FILE__ . "::" . __LINE__ );
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
		if ($trz['transactionType'] != 'COM') 
		{
			$this->has_trans = 1;
			//moved amount to above IF
			//$this->amount = $trz['transactionAmount'];
		} 
/*
		else if ($trz['transactionType'] == 'COM')
		{
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
	function determineTransactionTypeLabel(): void
	{
		$tTL = new TransactionTypeLabel( $this->transactionDC );
		$this->transactionTypeLabel = $tTL->getTransactionTypeLabel();
		return;
	}
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display()
	{
		echo $this->getHtml();
	}
	/**//******************************************************************
	* Get complete HTML for line item (for testability and HTML library integration)
	*
	* Returns the complete HTML for a transaction row.
	* This combines left and right columns and enables testing 
	* and future HTML library usage.
	*
	* @return string Complete HTML for transaction row
	**********************************************************************/
	function getHtml(): string
	{
		return $this->getLeftHtml() . $this->getRightHtml();
	}
	/**//******************************************************************
	* Get OUR Bank Account Details
	*
	*	TODO: REFACTOR to use class fa_bank_accounts instead of an array
	*
	**********************************************************************/
	function getBankAccountDetails()
	{
		require_once( __DIR__ . '/src/Ksfraser/FaBankImport/models/BankAccountByNumber.php' );
		$b = new BankAccountByNumber( $this->our_account );
		$this->ourBankDetails =	$b->getBankDetails();
		$this->ourBankAccountName = $this->ourBankDetails['bank_account_name'];
		$this->ourBankAccountCode = $this->ourBankDetails['account_code'];
	}
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display_left()
	{
		echo $this->getLeftHtml();
	}
	/**//****************************************************************
	* Get left column HTML (for testability and HTML library integration)
	*
	* Returns the HTML that display_left() currently echoes.
	* Uses SRP View classes with recursive string rendering (no ob_start).
	* Uses HtmlOB to capture output from legacy display methods.
	*
	* @return string HTML for left column
	**********************************************************************/
	function getLeftHtml(): string
	{
		// Populate bank details first
		$this->getBankAccountDetails();
		
		// Build label rows using SRP View classes (replaces label_row() calls)
		$rows = [];
		$rows[] = new TransDate($this);
		$rows[] = new TransType($this);
		$rows[] = new OurBankAccount($this);
		$rows[] = new OtherBankAccount($this);
		$rows[] = new AmountCharges($this);
		$rows[] = new TransTitle($this);
		
		// Collect HTML strings from View classes
		$labelRowsHtml = '';
		foreach ($rows as $row) {
			$labelRowsHtml .= $row->getHtml();
		}
		
		// Complex components - capture their echoed output using HtmlOB
		// TODO: Refactor these methods to return strings directly
		$complexHtml = (new HtmlOB(function() {
			$this->displayAddVendorOrCustomer();
			$this->displayEditTransData();
			if( $this->isPaired() )
			{
				//TODO: make sure the paired transactions are set to BankTranfer rather than Credit/Debit
				$this->displayPaired();
			}
		}))->getHtml();
		
		// Build complete HTML structure
		// TODO: Replace with HtmlTableRow, HtmlTd, HtmlTable classes
		$html = '<tr>';
		$html .= '<td width="50%">';
		$html .= '<table class="' . TABLESTYLE2 . '" width="100%">';
		$html .= $labelRowsHtml;
		$html .= $complexHtml;
		$html .= '</table>';
		$html .= '</td>';
		
		return $html;
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
			$this-> selectAndDisplayButton();
		}
		finally
		{
			hidden( "vendor_short_$this->id", $this->otherBankAccount );
			hidden( "vendor_long_$this->id", $this->otherBankAccountName );
		}
	}
	function selectAndDisplayButton()
	{
		if( $this->transactionDC=='D' )
		{
			$b = new AddVendorButton( $this->id );
		} else
		if( $this->transactionDC=='C' )
		{
			$b = new AddCustomerButton( $this->id );
		}
		else
		{
			return;
		}
		$b->toHtml();
	}
	function matchedSupplierId( array $matchedVendor ) : int
	{
		if( ! isset( $this->vendor_list ) )
		{
			throw new Exception( "Field not set ->vendor_list", KSF_FIELD_NOT_SET );
		}
//TODO confirm this does what it should!!
		$matchedSupplierId = $this->vendor_list[$matchedVendor]['supplier_id'];
		return $matchedSupplierId;
	}
	function matchedVendor() 
	{
		if( ! isset( $this->vendor_list ) )
		{
			throw new Exception( "Field not set ->vendor_list", KSF_FIELD_NOT_SET );
		}
		if( ! isset( $this->otherBankAccountt ) )
		{
			throw new Exception( "Field not set ->otherBankAccountt", KSF_FIELD_NOT_SET );
		}
		$matchedVendor = array_search( trim($this->otherBankAccount), $this->vendor_list['shortnames'], true );
		return $matchedVendor;
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
			
		}
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
		require_once( __DIR__ . '/src/Ksfraser/FaBankImport/models/MatchingJEs.php' );
		$match = new MatchingJEs( $this );
		$this->matching_trans = $match->getMatchArr();
	        return $this->matching_trans;
	}
	/**//******************************************************************
	* Create a URL link using HtmlA convenience class
	*
	* @param string $URL The base URL
	* @param array $params Array of parameters (each element is ['key' => 'value'])
	* @param string $text The link text (accepts string directly!)
	* @param string $target The target attribute (default "_blank")
	* @return string The HTML for the link
	**********************************************************************/
	function makeURLLink( string $URL, array $params, string $text, $target = "_blank" )
	{
		// Use HtmlA - much simpler! Accepts string directly, no need for HtmlRawString wrapper
		$link = new HtmlA( $URL, $text );
		
		// Flatten nested param array structure and use setParams()
		$flatParams = [];
		foreach( $params as $param )
		{
			foreach( $param as $key => $val )
			{
				$flatParams[$key] = $val;
			}
		}
		
		if( count( $flatParams ) > 0 )
		{
			$link->setParams( $flatParams );
		}
		
		$link->setTarget( $target );
		
		return $link->getHtml();
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
					unset( $param );
					$param = array();
					if( ! @include_once( __DIR__  . "/../ksf_modules_common/defines.inc.php") )
					{
						$param[] = array( "type_id" => $trans_types_readable[$matchgl['type']] );
						$param[] = array( "trans_no" => $matchgl['type_no'] );
						$URL = "../../gl/view/gl_trans_view.php";
						$text = " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
			
						$match_html .= $this->makeURLLink( $URL, $param, $text );
						//$match_html .= " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
					}
					else
					{
						$type = $matchgl['type'];
						$type_no = $matchgl['type_no'];
						$param[] = array( "type_id" => $type );
						$param[] = array( "trans_no" => $type_no );
						$URL = "../../gl/view/gl_trans_view.php";
						$text = " Transaction " . $matchgl['type'] . ":" . $matchgl['type_no'];
			
						$match_html .= $this->makeURLLink( $URL, $param, $text );
						//$match_html .= " Transaction " . $matchgl['type'] . ":" . $matchgl['type_no'];
					}
					$match_html .= " Score " . $matchgl['score'] . " ";
					if( strcasecmp( $this->our_account, $matchgl['account'] ) OR strcasecmp( $this->ourBankDetails['bank_account_name'], $matchgl['account'] ) )
					{
						$match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
					}
					else
					{
						$match_html .= "MATCH BANK:: ";
						$match_html .=  print_r( $our_account, true );
						$match_html .= "::" . print_r( $this->ourBankDetails['bank_account_name'], true );
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
	* REFACTORED: Now uses SupplierPartnerTypeView SRP class
	*
	************************************************************************/
	function displaySupplierPartnerType()
	{
		$view = new SupplierPartnerTypeView(
			$this->id,
			$this->otherBankAccount,
			$this->partnerId
		);
		$view->display();
	}
	/**//*******************************************************************
	* Display CUSTOMER partner type
	*
	* REFACTORED: Now uses CustomerPartnerTypeView SRP class
	*
	************************************************************************/
	function displayCustomerPartnerType()
	{
		$view = new CustomerPartnerTypeView(
			$this->id,
			$this->otherBankAccount,
			$this->valueTimestamp,
			$this->partnerId,
			$this->partnerDetailId
		);
		$view->display();
	}
	/**//*******************************************************************
	* Display Bank Transfer partner type
	*
	* REFACTORED: Now uses BankTransferPartnerTypeView SRP class
	*
	************************************************************************/
	function displayBankTransferPartnerType()
	{
		$view = new BankTransferPartnerTypeView(
			$this->id,
			$this->otherBankAccount,
			$this->transactionDC,
			$this->partnerId,
			$this->partnerDetailId
		);
		$view->display();
		
		// Update partnerId from POST after display
		$this->partnerId = $_POST["partnerId_$this->id"];
	}
	/**//*******************************************************************
	* Display Quick Entry partner type
	*
	* REFACTORED: Now uses QuickEntryPartnerTypeView SRP class
	*
	************************************************************************/
	function displayQuickEntryPartnerType()
	{
		$view = new QuickEntryPartnerTypeView(
			$this->id,
			$this->transactionDC
		);
		$view->display();
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
		echo $this->getRightHtml();
	}
	/**//****************************************************************
	* Get right column HTML (for testability and HTML library integration)
	*
	* Returns the HTML that display_right() currently echoes.
	* This enables testing and future HTML library usage.
	*
	* @return string HTML for right column
	**********************************************************************/
	function getRightHtml(): string
	{
		ob_start();
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
		return ob_get_clean();
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
	function trz2obj( $trz )
	{
		return $this->obj2obj( $trz );
/*
		$cnt = 0;
		foreach( get_object_vars($this) as $key )
		{
			if( isset( $trz->$key ) )
			{
				$this-set( "$key", $trz->$key );	
				$cnt++;
			}
		}
		return $cnt;
*/
	}
}
