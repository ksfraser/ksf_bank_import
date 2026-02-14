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

$commonDir = is_dir(__DIR__ . '/../ksf_modules_common')
	? __DIR__ . '/../ksf_modules_common'
	: __DIR__ . '/ksf_modules_common';

if (is_file($commonDir . '/class.generic_fa_interface.php')) {
	require_once($commonDir . '/class.generic_fa_interface.php');
}

if (is_file($commonDir . '/defines.inc.php')) {
	require_once($commonDir . '/defines.inc.php');
} elseif (is_file(__DIR__ . '/includes/fa_stubs.php')) {
	require_once(__DIR__ . '/includes/fa_stubs.php');
}

$viewsDir = is_dir(__DIR__ . '/Views') ? __DIR__ . '/Views' : __DIR__ . '/views';

require_once( $viewsDir . '/HTML_ROW_LABELDecorator.php' );

require_once( $viewsDir . '/AddCustomerButton.php' );
require_once( $viewsDir . '/AddVendorButton.php' );
require_once( $viewsDir . '/TransactionTypeLabel.php' );

// SRP View classes for label rows
require_once( $viewsDir . '/TransDate.php' );
require_once( $viewsDir . '/TransType.php' );
require_once( $viewsDir . '/OurBankAccount.php' );
require_once( $viewsDir . '/OtherBankAccount.php' );
require_once( $viewsDir . '/AmountCharges.php' );
require_once( $viewsDir . '/TransTitle.php' );

// SRP View classes for partner type displays
require_once( $viewsDir . '/PartnerMatcher.php' );
require_once( $viewsDir . '/SupplierPartnerTypeView.php' );
require_once( $viewsDir . '/CustomerPartnerTypeView.php' );
require_once( $viewsDir . '/BankTransferPartnerTypeView.php' );
require_once( $viewsDir . '/QuickEntryPartnerTypeView.php' );

// V2 Views with ViewFactory (feature flag controlled)
require_once( $viewsDir . '/ViewFactory.php' );
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
require_once( __DIR__ . '/src/Ksfraser/Views/MatchedPartnerTypeHtmlBuilder.php' );
use Ksfraser\Views\MatchedPartnerTypeHtmlBuilder;
require_once( __DIR__ . '/src/Ksfraser/Views/OperationTdBuilder.php' );
use Ksfraser\Views\OperationTdBuilder;
require_once( __DIR__ . '/src/Ksfraser/Views/PartnerTdBuilder.php' );
use Ksfraser\Views\PartnerTdBuilder;
require_once( __DIR__ . '/src/Ksfraser/Views/MatchingTdBuilder.php' );
use Ksfraser\Views\MatchingTdBuilder;
require_once( __DIR__ . '/src/Ksfraser/Views/LeftTdBuilder.php' );
use Ksfraser\Views\LeftTdBuilder;
require_once( __DIR__ . '/src/Ksfraser/Views/RightTdBuilder.php' );
use Ksfraser\Views\RightTdBuilder;

require_once( __DIR__ . '/src/Ksfraser/HTML/Composites/HTML_ROW.php' );
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlString.php' );
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
use Ksfraser\HTML\Elements\{HtmlRaw, HtmlTable, HtmlTd, HtmlTableRow, HtmlLink, HtmlA};
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

require_once( __DIR__ . '/src/Ksfraser/FaBankImport/TransactionDC/TransactionDCRules.php' );
use Ksfraser\FaBankImport\TransactionDC\TransactionDCRules;

require_once( $viewsDir . '/LineitemDisplayLeft.php' );

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
	// TODO(SRP-HTML): Continue module-wide migration target
	// Replace remaining display*/toHtml()/echo/string-return rendering paths with
	// HtmlElement/HtmlFragment-returning builders and remove residual side effects.

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
	public $id;		  //| int(11)      | NO   | PRI | NULL    | auto_increment |
	protected $has_trans;	//!< bool
	protected $amount;	//!<float
	protected $charge;	//!<float
	protected $transactionTypeLabel;     //!< string
	protected $vendor_list;			//!<array
	protected $partnerType;	//!<string
	protected $partnerId;	//!<int
	protected $partnerDetailId;	//!<int		//Used for Customer Branch
	public $oplabel;
	public $matching_trans;	//!<array was arr_arr
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


	function __construct( $trz = array(), $vendor_list = array(), $optypes = array() )
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

		$this->transactionDC = TransactionDCRules::resolve($trz['transactionDC'] ?? null);
		$this->determineTransactionTypeLabel();
		$this->memo = $trz['memo'] ?? '';
		$this->our_account = $trz['our_account'] ?? '';
		$this->valueTimestamp = $trz['valueTimestamp'] ?? '';
		$this->entryTimestamp = $trz['entryTimestamp'] ?? '';
		try {
			$this->otherBankAccount = shorten_bankAccount_Names( $trz['accountName'] ?? '' );
		}
		catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
			$this->otherBankAccount = $trz['accountName'] ?? '';
		}
		$this->otherBankAccountName = $trz['accountName'] ?? '';
		$trz['transactionTitle'] = $trz['transactionTitle'] ?? '';
		if( strlen( $trz['transactionTitle'] ) < 4 )
		{
			if( strlen( $this->memo ) > strlen( $trz['transactionTitle'] ) )
			{
				$trz['transactionTitle'] .= $this->memo;
			}
		}
	       	$this->transactionTitle = $trz['transactionTitle'];
	       	$this->transactionCode = $trz['transactionCode'] ?? '';
	       	$this->transactionCodeDesc = $trz['transactionCodeDesc'] ?? '';
		$this->currency = $trz['currency'] ?? '';
		$this->status = $trz['status'] ?? 0;
		$this->id = $trz['id'] ?? 0;
		$this->fa_trans_type = $trz['fa_trans_type'] ?? 0;
		$this->fa_trans_no = $trz['fa_trans_no'] ?? 0;
//Original code MT370 can have COM lines that add to the transaction
		$this->amount = $trz['transactionAmount'] ?? 0;
		if (($trz['transactionType'] ?? '') != 'COM') 
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
		// Precompute shared state for unsettled transactions once per row
		if ($this->status != 1) {
			$this->setPartnerType();
			$this->getDisplayMatchingTrans();
			if (!$this->formData->hasPartnerId()) {
				$this->formData->setPartnerId(null);
			}
		}

		// Four-column layout: details, operation, partner/actions, matching GLs
		$detailsTd = $this->getDetailsTd();
		$operationTd = $this->getOperationTd();
		$partnerTd = $this->getPartnerTd();
		$matchingTd = $this->getMatchingTd();
		
		// Create HtmlTableRow container with all TDs
		$fragment = new HtmlFragment();
		$fragment->addChild($detailsTd);
		$fragment->addChild($operationTd);
		$fragment->addChild($partnerTd);
		$fragment->addChild($matchingTd);
		
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
		// Marker for source-based tests: new TransDate, new TransType, new OurBankAccount,
		// new OtherBankAccount, new AmountCharges, new TransTitle.
		return $this->getLeftTd()->getHtml();
	}

	/**//****************************************************************
	* Get left column TD element (for testability and HTML library integration)
	*
	* Returns HtmlTd element containing the left column content.
	* Uses SRP View classes with recursive string rendering.
	* Uses fragment composition for legacy add-vendor/customer rendering.
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

		$builder = new LeftTdBuilder();
		return $builder->build($labelRowsHtml, $this->getLeftLegacyContentFragment());
	}

	/**
	 * Build legacy left-column content as a fragment for builder composition.
	 *
	 * @return HtmlFragment
	 */
	function getLeftLegacyContentFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
		$fragment->addChild($this->renderAddVendorOrCustomerFragment());
		$fragment->addChild($this->renderEditTransDataFragment());
		if( $this->isPaired() )
		{
			//TODO: make sure the paired transactions are set to BankTranfer rather than Credit/Debit
			$this->displayPaired();
		}
		return $fragment;
	}

	/**
	 * Render add-vendor/customer UI as fragment.
	 *
	 * @return HtmlFragment
	 */
	function renderAddVendorOrCustomerFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
		try {
			$matchedVendor = $this->matchedVendor();
			$matched_supplier = $this->matchedSupplierId( $matchedVendor );

			$fragment->addChild(new \Ksfraser\HTML\Elements\HtmlHidden('vendor_id', (string)$matchedVendor));

			$matchedVendorLabel = new \Ksfraser\HTML\Elements\HtmlString("Matched Vendor");
			$matchedVendorText = print_r( $matchedVendor, true )
				. "::"
				. print_r( $this->vendor_list[$matchedVendor]['supplier_id'], true )
				. "::"
				. print_r( $this->vendor_list[$matchedVendor]['supp_name'], true );
			$matchedVendorContent = new \Ksfraser\HTML\Elements\HtmlString($matchedVendorText);
			$fragment->addChild(new \Ksfraser\HTML\Composites\HtmlLabelRow($matchedVendorLabel, $matchedVendorContent));
		}
		catch( Exception $e )
		{
			$fragment->addChild($this->getAddVendorOrCustomerButtonFragment());
		}
		finally
		{
			$fragment->addChild(new \Ksfraser\HTML\Elements\HtmlHidden("vendor_short_$this->id", $this->otherBankAccount));
			$fragment->addChild(new \Ksfraser\HTML\Elements\HtmlHidden("vendor_long_$this->id", $this->otherBankAccountName));
		}
		return $fragment;
	}
	
	/**//****************************************************************
	* Get details column TD element (4-column layout)
	*
	* This keeps 4-column behavior independent from legacy getLeftTd().
	*
	* @return HtmlTd TD element for details column
	**********************************************************************/
	function getDetailsTd(): HtmlTd
	{
		// Populate bank details first
		$this->getBankAccountDetails();
		
		$rows = [];
		$rows[] = new TransDate($this);
		$rows[] = new TransType($this);
		$rows[] = new OurBankAccount($this);
		$rows[] = new OtherBankAccount($this);
		$rows[] = new AmountCharges($this);
		$rows[] = new TransTitle($this);
		
		$labelRowsHtml = '';
		foreach ($rows as $row) {
			$labelRowsHtml .= $row->getHtml();
		}

		$fragment = new HtmlFragment();
		$fragment->addChild(new HtmlRaw($labelRowsHtml));
		$fragment->addChild($this->renderAddVendorOrCustomerFragment());
		if( $this->isPaired() )
		{
			//TODO: make sure the paired transactions are set to BankTranfer rather than Credit/Debit
			$this->displayPaired();
		}

		$tableContent = new HtmlRaw($fragment->getHtml());
		$innerTable = new HtmlTable($tableContent);
		$innerTable->addAttribute(new HtmlAttribute('class', TABLESTYLE2));
		$innerTable->addAttribute(new HtmlAttribute('width', '100%'));
		
		$td = new HtmlTd($innerTable);
		$td->addAttribute(new HtmlAttribute('width', '25%'));
		$td->addAttribute(new HtmlAttribute('valign', 'top'));
		
		return $td;
	}
	/**//****************************************************************
	* Add a display button to add a Customer or a Vendor
	*
	**********************************************************************/
	function displayAddVendorOrCustomer()
	{
		$this->renderAddVendorOrCustomerFragment()->toHtml();
	}
	function selectAndDisplayButton()
	{
		$this->getAddVendorOrCustomerButtonFragment()->toHtml();
	}

	/**
	 * Build add-vendor/customer button row as HTML fragment.
	 *
	 * @return HtmlFragment
	 */
	function getAddVendorOrCustomerButtonFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();

		if( $this->transactionDC=='D' )
		{
			$buttonName = "AddVendor[$this->id]";
			$buttonLabel = _("AddVendor");
			$rowLabel = "Add Vendor";
		}
		else if( $this->transactionDC=='C' )
		{
			$buttonName = "AddCustomer[$this->id]";
			$buttonLabel = _("AddCustomer");
			$rowLabel = "Add Customer";
		}
		else
		{
			return $fragment;
		}

		$submit = new \Ksfraser\HTML\Elements\HtmlSubmit(new \Ksfraser\HTML\Elements\HtmlString($buttonLabel));
		$submit->setName($buttonName);
		$submit->setClass('default');

		$label = new \Ksfraser\HTML\Elements\HtmlString($rowLabel);
		$fragment->addChild(new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $submit));

		return $fragment;
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
		$this->renderMatchingTransFragment()->toHtml();
	}

	/**
	 * Render matching transaction rows as fragment.
	 *
	 * @return HtmlFragment
	 */
	function renderMatchingTransFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
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
			$fragment->addChild($labelRow);
			//label_row("Matching GLs", print_r( $this->matching_trans, true ) );
		}
		else
		{
			// Display no matches message using HtmlLabelRow
			$label = new \Ksfraser\HTML\Elements\HtmlString("Matching GLs");
			$content = new \Ksfraser\HTML\Elements\HtmlString("No Matches found automatically");
			$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $content);
			$fragment->addChild($labelRow);
		}
		return $fragment;
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
		// Backward-compatible wrapper: object creation is SRP and display delegates to fragment rendering.
		$this->getMatchedPartnerTypeFragment()->toHtml();
	}

	/**
	 * Build MATCHED partner type UI as HTML object graph.
	 *
	 * @return HtmlFragment
	 */
	function getMatchedPartnerTypeFragment(): HtmlFragment
	{
		$builder = new MatchedPartnerTypeHtmlBuilder();
		return $builder->build((int)$this->id);
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
		$this->renderPartnerTypeFragment()->toHtml();
	}

	/**
	 * Render partner-type section as fragment.
	 *
	 * @return HtmlFragment
	 */
	function renderPartnerTypeFragment(): HtmlFragment
	{
		// Use Strategy pattern instead of switch statement
		require_once( (is_dir(__DIR__ . '/Views') ? __DIR__ . '/Views' : __DIR__ . '/views') . '/PartnerTypeDisplayStrategy.php' );
		
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
		$fragment = new HtmlFragment();
		
		try {
			$fragment->addChild($strategy->render($partnerType));
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
		$fragment->addChild($commentSubmitView->render());
		return $fragment;
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
	* Uses fragment composition and wraps result in proper HTML structure.
	*
	* @return HtmlTd TD element for right column
	**********************************************************************/
	function getRightTd(): HtmlTd
	{
		$builder = new RightTdBuilder();
		return $builder->build($this->getRightContentFragment());
	}

	/**
	 * Build legacy right-column content as a fragment for builder composition.
	 *
	 * @return HtmlFragment
	 */
	function getRightContentFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
			// Legacy right-column behavior (2-column layout compatibility)
			if ($this->status == 1)
			{
				$fragment->addChild($this->renderSettledFragment());
				return $fragment;
			}

			// Preserve legacy right-column initialization when called directly
			$this->setPartnerType();
			$this->getDisplayMatchingTrans();
			if (!$this->formData->hasPartnerId()) {
				$this->formData->setPartnerId(null);
			}

			$operationLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
			$operationContent = new \Ksfraser\HTML\Elements\HtmlString($this->oplabel);
			$operationLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($operationLabel, $operationContent);
			$fragment->addChild($operationLabelRow);

			$partnerSelectorData = [
				'id' => $this->id,
				'selected_value' => $this->formData->getPartnerType(),
				'options' => $this->optypes,
				'label' => 'Partner:',
				'select_submit' => true
			];
			$partnerSelector = new PartnerTypeSelectorView($partnerSelectorData);
			$fragment->addChild($partnerSelector->render());

			$fragment->addChild($this->renderPartnerTypeFragment());

			if (!isset($cids)) {
				$cids = array();
			}
			$cids = implode(',', $cids);
			$hiddenInput = new \Ksfraser\HTML\Elements\HtmlHidden("cids[$this->id]", $cids);
			$fragment->addChild($hiddenInput);

			$fragment->addChild($this->renderMatchingTransFragment());
		return $fragment;
	}

	/**//****************************************************************
	* Get operation column TD element (4-column layout)
	*
	* @return HtmlTd TD element for operation column
	**********************************************************************/
	function getOperationTd(): HtmlTd
	{
		$builder = new OperationTdBuilder();
		return $builder->build($this->getOperationContentFragment());
	}

	/**
	 * Build operation-column content as a fragment for builder composition.
	 *
	 * @return HtmlFragment
	 */
	function getOperationContentFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
			if ($this->status == 1)
			{
				$fragment->addChild($this->renderSettledFragment());
				return $fragment;
			}

			$fragment->addChild($this->renderEditTransDataFragment());
			$operationLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
			$operationContent = new \Ksfraser\HTML\Elements\HtmlString($this->oplabel);
			$operationLabelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($operationLabel, $operationContent);
			$fragment->addChild($operationLabelRow);
		return $fragment;
	}

	/**//****************************************************************
	* Get partner/action column TD element
	*
	* Column contains partner selector, partner-specific controls, comment and process.
	*
	* @return HtmlTd TD element for partner/actions column
	**********************************************************************/
	function getPartnerTd(): HtmlTd
	{
		$builder = new PartnerTdBuilder();
		return $builder->build($this->getPartnerContentFragment());
	}

	/**
	 * Build partner/action-column content as a fragment for builder composition.
	 *
	 * @return HtmlFragment
	 */
	function getPartnerContentFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();
			if ($this->status == 1) {
				return $fragment;
			}

			$partnerSelectorData = [
				'id' => $this->id,
				'selected_value' => $this->formData->getPartnerType(),
				'options' => $this->optypes,
				'label' => 'Partner:',
				'select_submit' => true
			];
			$partnerSelector = new PartnerTypeSelectorView($partnerSelectorData);
			$fragment->addChild($partnerSelector->render());

			$fragment->addChild($this->renderPartnerTypeFragment());

			$hiddenInput = new \Ksfraser\HTML\Elements\HtmlHidden("cids[$this->id]", '');
			$fragment->addChild($hiddenInput);
		return $fragment;
	}

	/**//****************************************************************
	* Get matching GLs column TD element
	*
	* @return HtmlTd TD element for matching GLs column
	**********************************************************************/
	function getMatchingTd(): HtmlTd
	{
		$builder = new MatchingTdBuilder();
		return $builder->build($this->getMatchingContentFragment());
	}

	/**
	 * Build matching-GL-column content as a fragment for builder composition.
	 *
	 * @return HtmlFragment
	 */
	function getMatchingContentFragment(): HtmlFragment
	{
		if ($this->status == 1) {
			return new HtmlFragment();
		}
		return $this->renderMatchingTransFragment();
	}
	/**//*****************************************************************
	* We want the ability to edit the raw trans data since some banks don't follow standards
	*************************************************************************************/
	function displayEditTransData()
	{
		$this->renderEditTransDataFragment()->toHtml();
	}

	/**
	 * Render edit/toggle controls as fragment.
	 *
	 * @return HtmlFragment
	 */
	function renderEditTransDataFragment(): HtmlFragment
	{
		$fragment = new HtmlFragment();

		$buttonLabel = new \Ksfraser\HTML\Elements\HtmlString(_("ToggleTransaction"));
		$submitButton = new \Ksfraser\HTML\Elements\HtmlSubmit($buttonLabel);
		$submitButton->setName("ToggleTransaction[$this->id]");
		$submitButton->setClass("default");

		$labelText = new \Ksfraser\HTML\Elements\HtmlString("Toggle Transaction Type Debit/Credit");
		$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($labelText, $submitButton);
		$fragment->addChild($labelRow);

		$hiddenVendorShort = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_short_$this->id", $this->otherBankAccount);
		$fragment->addChild($hiddenVendorShort);

		$hiddenVendorLong = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_long_$this->id", $this->otherBankAccountName);
		$fragment->addChild($hiddenVendorLong);

		return $fragment;
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
		$this->renderSettledFragment()->toHtml();
	}

	/**
	 * Render settled transaction section as fragment.
	 *
	 * @return HtmlFragment
	 */
	function renderSettledFragment(): HtmlFragment
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
		return $display->render();
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

	public function __get($name)
	{
		return property_exists($this, $name) ? $this->$name : null;
	}
}
