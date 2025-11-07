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

require_once( __DIR__ . '/Views/HTML_ROW_LABELDecorator.php' );

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

// V2 Views with ViewFactory (feature flag controlled)
require_once( __DIR__ . '/Views/ViewFactory.php' );
use KsfBankImport\Views\ViewFactory;

// Feature flag to enable v2 Views (set to true to use ViewFactory)
define('USE_V2_PARTNER_VIEWS', true);

// SettledTransactionDisplay for displaying settled transactions
require_once( __DIR__ . '/src/Ksfraser/SettledTransactionDisplay.php' );
use Ksfraser\SettledTransactionDisplay;

// CommentSubmitView for comment input and submit button
require_once( __DIR__ . '/src/Ksfraser/Views/CommentSubmitView.php' );
use Ksfraser\Views\CommentSubmitView;

// PartnerTypeSelectorView for partner type dropdown
require_once( __DIR__ . '/src/Ksfraser/Views/PartnerTypeSelectorView.php' );
use Ksfraser\Views\PartnerTypeSelectorView;

require_once( __DIR__ . '/src/Ksfraser/HTML/Composites/HTML_ROW.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlString.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlOB.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlRaw.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlTable.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlTd.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlTableRow.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/HtmlElement.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/HtmlAttribute.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlLink.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlA.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/HtmlFragment.php' );
use Ksfraser\HTML\Composites\HTML_ROW;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\{HtmlOB, HtmlRaw, HtmlTable, HtmlTd, HtmlTableRow, HtmlLink, HtmlA};
use Ksfraser\HTML\{HtmlElement, HtmlAttribute, HtmlFragment};



require_once( __DIR__ . '/src/Ksfraser/HTML/Composites/HTML_ROW_LABEL.php' );
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

require_once( __DIR__ . '/src/Ksfraser/PartnerFormData.php' );
require_once( __DIR__ . '/src/Ksfraser/FormFieldNameGenerator.php' );
use Ksfraser\PartnerFormData;

// Models
require_once( __DIR__ . '/src/Ksfraser/FaBankImport/models/BankAccountByNumber.php' );
use Ksfraser\FaBankImport\models\BankAccountByNumber;

require_once( __DIR__ . '/src/Ksfraser/FaBankImport/models/MatchingJEs.php' );
use Ksfraser\FaBankImport\models\MatchingJEs;

require_once( __DIR__ . '/Views/LineitemDisplayLeft.php' );

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
	protected $formData;	//!< PartnerFormData - Encapsulates $_POST access


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
		// Initialize form data handler for $_POST access
		$this->formData = new PartnerFormData($this->id);
		
		// Use PartnerFormData instead of direct $_POST access
		if( $this->formData->hasPartnerId() )
		{
			$this->partnerId = $this->formData->getPartnerId();
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
	* Uses HtmlTableRow as a container for left and right TD elements,
	* ensuring proper recursive rendering with automatic opening/closing tags.
	*
	* @return string Complete HTML for transaction row: <tr><td>LEFT</td><td>RIGHT</td></tr>
	**********************************************************************/
	function getHtml(): string
	{
		// Get left and right TD elements (not full HTML strings)
		$leftTd = $this->getLeftTd();
		$rightTd = $this->getRightTd();
		
		// Create HtmlTableRow container with both TDs
		$fragment = new HtmlFragment();
		$fragment->addChild($leftTd);
		$fragment->addChild($rightTd);
		
		$tr = new HtmlTableRow($fragment);
		
		// Recursively renders: <tr><td>LEFT</td><td>RIGHT</td></tr>
		return $tr->getHtml();
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
		echo $this->getLeftTd()->getHtml();
	}
	
	/**//****************************************************************
	* Get left column HTML (backward compatibility wrapper)
	*
	* @deprecated Use getLeftTd() for proper HTML element structure
	* @return string HTML for left column
	**********************************************************************/
	function getLeftHtml(): string
	{
		return $this->getLeftTd()->getHtml();
	}
	
	/**//****************************************************************
	* Get left column TD element (for testability and HTML library integration)
	*
	* Returns HtmlTd element containing the left column content.
	* Uses SRP View classes with recursive string rendering.
	* Uses HtmlOB to capture output from legacy display methods.
	*
	* @return HtmlTd TD element for left column
	**********************************************************************/
	function getLeftTd(): HtmlTd
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
		
		// Build complete HTML structure using HTML library classes
		// Wrap content strings in HtmlRaw since they're pre-generated HTML
		$tableContent = new HtmlRaw($labelRowsHtml . $complexHtml);
		
		$innerTable = new HtmlTable($tableContent);
		$innerTable->addAttribute(new HtmlAttribute('class', TABLESTYLE2));
		$innerTable->addAttribute(new HtmlAttribute('width', '100%'));
		
		$td = new HtmlTd($innerTable);
		$td->addAttribute(new HtmlAttribute('width', '50%'));
		
		return $td;
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
			
			// Vendor ID hidden field
			$vendorIdHidden = new \Ksfraser\HTML\Elements\HtmlHidden('vendor_id', $matchedVendor);
			$vendorIdHidden->toHtml();
			
			// Debug label row (TODO: Consider removing or making conditional)
			label_row("Matched Vendor", print_r( $matchedVendor, true ) . "::" . print_r( $this->vendor_list[$matchedVendor]['supplier_id'], true ) . "::" . print_r( $this->vendor_list[$matchedVendor]['supp_name'], true ) );
		}
		catch( Exception $e )
		{
			$this-> selectAndDisplayButton();
		}
		finally
		{
			// Vendor short and long name hidden fields
			$vendorShortHidden = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_short_$this->id", $this->otherBankAccount);
			$vendorShortHidden->toHtml();
			
			$vendorLongHidden = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_long_$this->id", $this->otherBankAccountName);
			$vendorLongHidden->toHtml();
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
	/**
	 * Get the supplier ID for a matched vendor
	 * 
	 * @param int|string $matchedVendor The vendor array key (from array_search)
	 * @return int The supplier ID
	 * @throws Exception if vendor_list is not set
	 */
	function matchedSupplierId( $matchedVendor ) : int
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
	
	// Use PartnerFormData instead of direct $_POST access
	// Only set if not already set (user may have changed it via form)
	if( !$this->formData->hasPartnerType() )
	{
		$this->formData->setPartnerType($this->partnerType);
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
			
			// Display matching GLs using HtmlLabelRow
			$label = new \Ksfraser\HTML\Elements\HtmlString("Matching GLs.  Ensure you double check Accounts and Amounts");
			$content = new \Ksfraser\HTML\Elements\HtmlRaw($match_html);
			$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $content);
			$labelRow->toHtml();
			//label_row("Matching GLs", print_r( $this->matching_trans, true ) );
		}
		else
		{
			// Display no matches message using HtmlLabelRow
			$label = new \Ksfraser\HTML\Elements\HtmlString("Matching GLs");
			$content = new \Ksfraser\HTML\Elements\HtmlString("No Matches found automatically");
			$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $content);
			$labelRow->toHtml();
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
							$this->formData->setPartnerType('SP');
					}
					else
					{
							$this->formData->setPartnerType('ZZ');
					}
					$this->oplabel = "MATCH";
					
				// Transaction type and number hidden fields
				$transTypeHidden = new \Ksfraser\HTML\Elements\HtmlHidden("trans_type_$this->id", $this->matching_trans[0]['type']);
				$transTypeHidden->toHtml();
				
				$transNoHidden = new \Ksfraser\HTML\Elements\HtmlHidden("trans_no_$this->id", $this->matching_trans[0]['type_no']);
				$transNoHidden->toHtml();

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
	* V2: Uses ViewFactory with dependency injection
	*
	************************************************************************/
	function displaySupplierPartnerType()
	{
		if (USE_V2_PARTNER_VIEWS) {
			// V2: Use ViewFactory with dependency injection
			$view = ViewFactory::createPartnerTypeView(
				ViewFactory::PARTNER_TYPE_SUPPLIER,
				$this->id,
				[
					'otherBankAccount' => $this->otherBankAccount,
					'partnerId' => $this->partnerId
				]
			);
		} else {
			// V1: Direct instantiation (legacy)
			$view = new SupplierPartnerTypeView(
				$this->id,
				$this->otherBankAccount,
				$this->partnerId
			);
		}
		$view->display();
	}
	/**//*******************************************************************
	* Display CUSTOMER partner type
	*
	* REFACTORED: Now uses CustomerPartnerTypeView SRP class
	* V2: Uses ViewFactory with dependency injection
	*
	************************************************************************/
	function displayCustomerPartnerType()
	{
		if (USE_V2_PARTNER_VIEWS) {
			// V2: Use ViewFactory with dependency injection
			$view = ViewFactory::createPartnerTypeView(
				ViewFactory::PARTNER_TYPE_CUSTOMER,
				$this->id,
				[
					'otherBankAccount' => $this->otherBankAccount,
					'valueTimestamp' => $this->valueTimestamp,
					'partnerId' => $this->partnerId,
					'partnerDetailId' => $this->partnerDetailId
				]
			);
		} else {
			// V1: Direct instantiation (legacy)
			$view = new CustomerPartnerTypeView(
				$this->id,
				$this->otherBankAccount,
				$this->valueTimestamp,
				$this->partnerId,
				$this->partnerDetailId
			);
		}
		$view->display();
	}
	/**//*******************************************************************
	* Display Bank Transfer partner type
	*
	* REFACTORED: Now uses BankTransferPartnerTypeView SRP class
	* V2: Uses ViewFactory with dependency injection
	*
	************************************************************************/
	function displayBankTransferPartnerType()
	{
		if (USE_V2_PARTNER_VIEWS) {
			// V2: Use ViewFactory with dependency injection
			$view = ViewFactory::createPartnerTypeView(
				ViewFactory::PARTNER_TYPE_BANK_TRANSFER,
				$this->id,
				[
					'otherBankAccount' => $this->otherBankAccount,
					'transactionDC' => $this->transactionDC,
					'partnerId' => $this->partnerId,
					'partnerDetailId' => $this->partnerDetailId
				]
			);
		} else {
			// V1: Direct instantiation (legacy)
			$view = new BankTransferPartnerTypeView(
				$this->id,
				$this->otherBankAccount,
				$this->transactionDC,
				$this->partnerId,
				$this->partnerDetailId
			);
		}
		$view->display();
		
		// Update partnerId from POST after display
		// Note: With v2 Views, PartnerFormData handles $_POST access
		$this->partnerId = $this->formData->getPartnerId();
	}
	/**//*******************************************************************
	* Display Quick Entry partner type
	*
	* REFACTORED: Now uses QuickEntryPartnerTypeView SRP class
	* V2: Uses ViewFactory with dependency injection
	*
	************************************************************************/
	function displayQuickEntryPartnerType()
	{
		if (USE_V2_PARTNER_VIEWS) {
			// V2: Use ViewFactory with dependency injection
			$view = ViewFactory::createPartnerTypeView(
				ViewFactory::PARTNER_TYPE_QUICK_ENTRY,
				$this->id,
				[
					'transactionDC' => $this->transactionDC
				]
			);
		} else {
			// V1: Direct instantiation (legacy)
			$view = new QuickEntryPartnerTypeView(
				$this->id,
				$this->transactionDC
			);
		}
		$view->display();
	}
	/**//*******************************************************************
	* Display MATCHED partner type
	* 
	* REFACTORED: Uses TransactionTypesRegistry for single source of truth
	* and HTML library classes instead of FA functions.
	*
	************************************************************************/
	function displayMatchedPartnerType()
	{
		require_once(__DIR__ . '/src/Ksfraser/FrontAccounting/TransactionTypes/TransactionTypesRegistry.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/HtmlHidden.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Composites/HtmlLabelRow.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/HtmlString.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/HtmlSelect.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/HtmlOption.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/HtmlInput.php');
		require_once(__DIR__ . '/src/Ksfraser/HTML/HtmlAttribute.php');
		
		// Hidden field for partnerId
		$hidden = new \Ksfraser\HTML\Elements\HtmlHidden("partnerId_$this->id", 'manual');
		$hidden->toHtml();
		
		// Get transaction types with moneyMoved flag (bank-related only)
		$registry = \Ksfraser\FrontAccounting\TransactionTypes\TransactionTypesRegistry::getInstance();
		$transactionTypes = $registry->getLabelsArray(['moneyMoved' => true]);
		
		// Build transaction type selector
		$select = new \Ksfraser\HTML\Elements\HtmlSelect("Existing_Type");
		$select->setClass('combo');
		
		// Add blank option
		$select->addOption(new \Ksfraser\HTML\Elements\HtmlOption(0, _('Select Transaction Type')));
		
		// Add transaction type options
		foreach ($transactionTypes as $code => $label) {
			$select->addOption(new \Ksfraser\HTML\Elements\HtmlOption($code, $label));
		}
		
		// Create label row for transaction type
		$typeLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Existing Entry Type:"));
		$typeLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($typeLabel, $select);
		$typeLabelRow->toHtml();
		
		// Build existing entry input
		$entryInput = new \Ksfraser\HTML\Elements\HtmlInput("text");
		$entryInput->setName("Existing_Entry");
		$entryInput->setValue('0');
		$entryInput->addAttribute(new \Ksfraser\HTML\HtmlAttribute("size", "6"));
		$entryInput->setPlaceholder(_("Existing Entry:"));
		
		// Create label row for entry input
		$entryLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Existing Entry:"));
		$entryLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($entryLabel, $entryInput);
		$entryLabelRow->toHtml();
	}
	/**//*******************************************************************
	* Select the right type of partner to display
	*
	* REFACTORED: Uses Strategy pattern to replace switch statement
	* Follows Martin Fowler's "Replace Conditional with Polymorphism"
	* 
	* Strategy now receives data array instead of $this to eliminate
	* circular dependency and improve testability.
	*
	************************************************************************/
	function displayPartnerType()
	{
		// Use Strategy pattern instead of switch statement
		require_once( __DIR__ . '/Views/PartnerTypeDisplayStrategy.php' );
		
		// Prepare data array for Strategy
		$data = [
			'id' => $this->id,
			'otherBankAccount' => $this->otherBankAccount,
			'valueTimestamp' => $this->valueTimestamp,
			'transactionDC' => $this->transactionDC,
			'partnerId' => $this->partnerId,
			'partnerDetailId' => $this->partnerDetailId,
			'memo' => $this->memo,
			'transactionTitle' => $this->transactionTitle,
			'matching_trans' => $this->matching_trans ?? []
		];
		
		$strategy = new PartnerTypeDisplayStrategy($data);
		$partnerType = $this->formData->getPartnerType();
		
		try {
			$strategy->display($partnerType);
		} catch (Exception $e) {
			// Fallback for unknown partner type
			display_error("Unknown partner type: $partnerType");
		}

		// Common display elements (displayed for all partner types) using CommentSubmitView
		$commentSubmitData = [
			'id' => $this->id,
			'comment' => $this->memo,
			'comment_label' => _("Comment:"),
			'button_name' => "ProcessTransaction[$this->id]",
			'button_label' => _("Process")
		];
		
		$commentSubmitView = new CommentSubmitView($commentSubmitData);
		$commentSubmitView->display();
	}
	/**//*****************************************************************
	* Display as a row
	*
	**********************************************************************/
	function display_right()
	{
		echo $this->getRightTd()->getHtml();
	}
	
	/**//****************************************************************
	* Get right column HTML (backward compatibility wrapper)
	*
	* @deprecated Use getRightTd() for proper HTML element structure
	* @return string HTML for right column
	**********************************************************************/
	function getRightHtml(): string
	{
		return $this->getRightTd()->getHtml();
	}
	
	/**//****************************************************************
	* Get right column TD element (for testability and HTML library integration)
	*
	* Returns HtmlTd element containing the right column content.
	* This method captures output from legacy display methods using HtmlOB,
	* wraps it in proper HTML structure.
	*
	* @return HtmlTd TD element for right column
	**********************************************************************/
	function getRightTd(): HtmlTd
	{
		// Use HtmlOB to capture echoed output from display methods
		$contentHtml = (new HtmlOB(function() {
			//now display stuff: forms and information
			if ($this->status == 1)
			{
				$this->display_settled();
			} else {
				//transaction NOT settled
				// this is a new transaction, but not matched by routine so just display some forms
				$this->setPartnerType();
				$this->getDisplayMatchingTrans();
				
				// Display Operation label using HtmlLabelRow
				$operationLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
				$operationContent = new \Ksfraser\HTML\Elements\HtmlString($this->oplabel);
				$operationLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($operationLabel, $operationContent);
				$operationLabelRow->toHtml();
				
				// Display Partner Type selector using PartnerTypeSelectorView
				$partnerSelectorData = [
					'id' => $this->id,
					'selected_value' => $this->formData->getPartnerType(),
					'options' => $this->optypes,
					'label' => 'Partner:',
					'select_submit' => true
				];
				$partnerSelector = new PartnerTypeSelectorView($partnerSelectorData);
				$partnerSelector->display();
		
				//3rd cell
				if ( !$this->formData->hasPartnerId() )
				{
					$this->formData->setPartnerId(null);
				}

				//Leaving in process_statement
				$this->displayPartnerType();

				//other common info
				//Apparantly cids is just an empty array at this point in the original code
				if( ! isset( $cids ) )
				{
					$cids = array();
				}
				$cids = implode(',', $cids);
				
				// Use HtmlHidden instead of hidden() function
				$hiddenInput = new \Ksfraser\HTML\Elements\HtmlHidden("cids[$this->id]", $cids);
				$hiddenInput->toHtml();
				
				$this->displayMatchingTransArr();
			}
		}))->getHtml();
		
		// Wrap content in HtmlTable with proper attributes
		$tableData = new HtmlRaw($contentHtml);
		$table = new HtmlTable($tableData);
		$table->addAttribute(new HtmlAttribute("class", "tablestyle2"));
		$table->addAttribute(new HtmlAttribute("width", "100%"));
		
		// Create the TD wrapper for right column
		$td = new HtmlTd($table);
		$td->addAttribute(new HtmlAttribute("width", "50%"));
		$td->addAttribute(new HtmlAttribute("valign", "top"));
		
		return $td;
	}
	/**//*****************************************************************
	* We want the ability to edit the raw trans data since some banks don't follow standards
	*************************************************************************************/
	function displayEditTransData()
	{
		// Create submit button using HtmlSubmit class
		$buttonLabel = new \Ksfraser\HTML\Elements\HtmlString(_("ToggleTransaction"));
		$submitButton = new \Ksfraser\HTML\Elements\HtmlSubmit($buttonLabel);
		$submitButton->setName("ToggleTransaction[$this->id]");
		$submitButton->setClass("default");
		
		// Display using HtmlLabelRow
		$labelText = new \Ksfraser\HTML\Elements\HtmlString("Toggle Transaction Type Debit/Credit");
		$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($labelText, $submitButton);
		$labelRow->toHtml();
		
		// Optionally add edit button (commented in original)
		/*
		$editButtonLabel = new \Ksfraser\HTML\Elements\HtmlString(_("EditTransaction"));
		$editButton = new \Ksfraser\HTML\Elements\HtmlSubmit($editButtonLabel);
		$editButton->setName("EditTransaction[$this->id]");
		$editButton->setClass("default");
		
		$editLabelText = new \Ksfraser\HTML\Elements\HtmlString("Edit this Transaction Data");
		$editLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($editLabelText, $editButton);
		$editLabelRow->toHtml();
		*/
		
		// Use HtmlHidden instead of hidden() function
		$hiddenVendorShort = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_short_$this->id", $this->otherBankAccount);
		$hiddenVendorShort->toHtml();
		
		$hiddenVendorLong = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_long_$this->id", $this->otherBankAccountName);
		$hiddenVendorLong->toHtml();
	}
	/**//*****************************************************************
	* Display a settled transaction
	*
	* Uses SettledTransactionDisplay component (Option B - returns HtmlFragment)
	* 
	* @since 20251025 - Refactored to use SettledTransactionDisplay
	**********************************************************************/
	function display_settled()
	{
		// Build transaction data array for SettledTransactionDisplay
		$transactionData = [
			'id' => $this->id,
			'fa_trans_type' => $this->fa_trans_type,
			'fa_trans_no' => $this->fa_trans_no,
		];
		
		// Add type-specific data based on transaction type
		switch ($this->fa_trans_type) {
			case ST_SUPPAYMENT:
				// Note: $minfo is undefined in original code - this was a bug
				// SettledTransactionDisplay handles missing data gracefully
				$transactionData['supplier_name'] = $this->supplier_name ?? 'Unknown Supplier';
				$transactionData['bank_account_name'] = $this->bank_account_name ?? 'Unknown Account';
				break;
				
			case ST_BANKDEPOSIT:
				// Get customer info from FA transaction details
				$fa_trans = get_customer_trans($this->fa_trans_no, $this->fa_trans_type);
				$transactionData['customer_name'] = get_customer_name($fa_trans['debtor_no'] ?? null);
				$transactionData['branch_name'] = get_branch_name($fa_trans['branch_code'] ?? null);
				break;
		}
		
		// Use SettledTransactionDisplay component
		$display = new SettledTransactionDisplay($transactionData);
		
		// Echo HTML directly (display() method handles toHtml())
		$display->display();
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
	
	/**//******************************************************************
	* Getter methods for Strategy pattern (added for PartnerTypeDisplayStrategy)
	*
	* These methods provide controlled access to protected properties,
	* maintaining encapsulation while allowing the Strategy class to
	* access necessary data.
	**********************************************************************/
	
	/**
	 * Get transaction ID
	 * 
	 * @return int Transaction ID
	 */
	public function getId(): int
	{
		return $this->id;
	}
	
	/**
	 * Get memo field
	 * 
	 * @return string Memo text
	 */
	public function getMemo(): string
	{
		return $this->memo ?? '';
	}
	
	/**
	 * Get transaction title
	 * 
	 * @return string Transaction title
	 */
	public function getTransactionTitle(): string
	{
		return $this->transactionTitle ?? '';
	}
	
	/**
	 * Get matching transactions array
	 * 
	 * @return array Array of matching GL transactions
	 */
	public function getMatchingTrans(): array
	{
		return $this->matching_trans ?? [];
	}
	
	/**
	 * Get form data handler
	 * 
	 * @return PartnerFormData Form data access object
	 */
	public function getFormData(): PartnerFormData
	{
		return $this->formData;
	}
}
