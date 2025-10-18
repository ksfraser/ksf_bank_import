<?php

namespace Ksfraser\FaBankImport\View;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * View class for rendering line item data.
 */
class BiLineItemView
{
    /**
     * @var BiLineItemModel
     */
    private $lineItemModel;

    /**
     * Constructor.
     *
     * @param BiLineItemModel $lineItemModel
     */
    public function __construct(BiLineItemModel $lineItemModel)
    {
        $this->lineItemModel = $lineItemModel;
    }

    /**
     * Render the line item as an array.
     *
     * @return array
     */
    public function render(): array
    {
        return [
            'date' => $this->lineItemModel->getDate(),
            'description' => $this->lineItemModel->getDescription(),
            'amount' => $this->lineItemModel->getAmount(),
        ];
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
	/**//*****************************************************************
	* Display as a row
	*
	* @param object BiLineItemModel
	**********************************************************************/
	function display_left( BiLineItemModel $model )
	{
		start_row();
		echo '<td width="50%">';
		start_table(TABLESTYLE2, "width='100%'");
		label_row("Trans Date (Event Date):", $model->valueTimestamp . " :: (" . $model->entryTimestamp . ")" , "width='25%' class='label'");
		label_row("Trans type:", $model->transactionTypeLabel);

		//$model->getBankAccountDetails();	//In controller now

		//label_row("Our Bank Account - (Account Name)(Number):", $model->our_account . ' - ' . $model->ourBankDetails['bank_name'] . " (" . $model->ourBankAccountName . ")(" . $model->ourBankAccountCode . ")"  );
		label_row("Our Bank Account - (Account Name)(Number):", $model->our_account . ' - ' . $model->fa_bank_accounts->get('bank_name') . " (" . $model->fa_bank_accounts->get( "bank_account_name" ) . ")(" . $model->fa_bank_accounts->get( "account_code" ) . ")"  );
		label_row("Other account:", $model->otherBankAccount . ' / '. $model->otherBankAccountName);
		label_row("Amount/Charge(s):", $model->amount.' / '. $model->charge ." (".$model->currency.")");
		label_row("Trans Title:", $model->transactionTitle);
		$this->displayAddVendorOrCustomer();
		$this->displayEditTransData();
		if( $model->isPaired() )
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
	/**//**************************************************************
	* Display our paired transaction
	*
	*******************************************************************/
	function displayPaired()
	{
	}
	/**//***************************************************************
	* Display the entries from the matching_trans array 
	*
	*	REFACTORED to move HTML code into a VIEW class
	*	This means we can't use "this" as the *model* variables
	*	are no longer local
	*
	* @param array the Matching Transactions
	* @param object the FaBankAccounts class 
	********************************************************************/
	function displayMatchingTransArr( array $matching_trans, FaBankAccounts $fa_bank_accounts )
	{
		if( count( $matching_trans ) > 0 )
		{
			$match_html = "";
			$matchcount = 1;
			foreach( $matching_trans as $matchgl )
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
					if( strcasecmp( $this->our_account, $matchgl['account'] ) OR strcasecmp( $fa_bank_accounts->get( "bank_account_name" ), $matchgl['account'] ) )
					{
						$match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
					}
					else
					{
						$match_html .= "MATCH BANK:: ";
						$match_html .=  print_r( $our_account, true );
						$match_html .= "::" . print_r( $fa_bank_accounts->get( "bank_account_name" ), true );
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
			//label_row("Matching GLs", print_r( $matching_trans, true ) );
		}
		else
		{
				label_row("Matching GLs", "No Matches found automatically" );
		}
	}
	/**//***************************************************************
	* Look for transactions with the same date and amount and list them
	*
	* @param object BiLineItemModel
	********************************************************************/
	/**//******************************************************************
	 * Find and display matching transactions.
	 * 
	 * This method delegates to the Model to find matches and determine partner type.
	 * The business logic is now centralized in BiLineItemModel::determinePartnerTypeFromMatches()
	 *
	 * @param BiLineItemModel $model
	 * @return void
	 */
	function getDisplayMatchingTrans( BiLineItemModel $model )
	{
		// Model handles finding matches AND determining partner type
		$model->findMatchingExistingJE();
		
		// If we have a high-confidence match, set hidden fields for processing
		if (count($model->matching_trans) > 0 && 
			count($model->matching_trans) < 3 &&
			$model->matching_trans[0]['score'] >= 50) {
			hidden("trans_type_$model->id", $model->matching_trans[0]['type']);
			hidden("trans_no_$model->id", $model->matching_trans[0]['type_no']);
		}
	}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
        /**//*******************************************************************
        * Display SUPPLIER partner type
        *
	* @param int Id
	* @param array match data
        ************************************************************************/
        function displaySupplierPartnerType( int  $id, array $matched_supplier  )
        {
                //                     supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
		$id = "partnerId_" . $id;
                label_row(_("Payment To:"), supplier_list("$id", $matched_supplier, false, false));
        }
	/**//*******************************************************************
	* Display CUSTOMER partner type
	*
        * @param object BiLineItemModel
	* @param int Id
	* @param array match data
	************************************************************************/
	function displayCustomerPartnerType( BiLineItemModel $model, int  $id, array $matched_supplier )
	{
		$partnerId_string = "partnerId_" . $model->id;
		$partnerDetailId_string = "partnerDetailId_" . $model->id;
		$cust_text = customer_list($partnerId_string, null, false, true);
		if ( db_customer_has_branches( $model->partnerId ) ) {
			$cust_text .= customer_branches_list( $model->partnerId, $partnerDetailId_string, null, false, true, true);
		} else {
			hidden($partnerDetailId_string, ANY_NUMERIC);
			$_POST[$partnerDetailId_string] = ANY_NUMERIC;
		}
		label_row(_("From Customer/Branch:"),  $cust_text);
		hidden( "customer_$model->id", $model->partnerId );
		hidden( "customer_branch_$model->id", $model->partnerDetailId );
		//label_row("debug", "customerid_tid=".$model->partnerId . " branchid[tid]=" . $model->partnerDetailId );
/** Mantis 3018
 *      List FROM and TO invoices needing payment (allocations) 
*/
		$_GET['customer_id'] = $model->partnerId;
		//if( ! @include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
		//use Ksfraser\frontaccounting\FaCustomerPayment;
		if(  @include_once( '../ksf_modules_common/class.fa_customer_payment.php' ) )
		{
			$tr = 0;
			$fcp = new fa_customer_payment();
			$fcp->set( "trans_date", $model->valueTimestamp );
			label_row( "Invoices to Pay", $fcp->show_allocatable() );
			$res = $fcp->get_alloc_details();
				//Array ( [10] => Array ( [trans_no] => 10 [type_no] => 789 [trans_date] => 12/14/2024 [invoice_amount] => 50 [payments] => 0 [unallocated] => 50 ) )
									//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
			foreach( $res as $row )
			{
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $row, true ) );
				$tr = $row['type_no'];
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $tr, true ) );
			}
				//display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $tr, true ) );
			label_row( (_("Allocate Payment to (1) Invoice")), text_input( "Invoice_$model->id", $tr, 6, '', _("Invoice to Allocate Payment:") ) );
		}
/* ! Mantis 3018 */

	}


}

class HTML_atomic extends Origin
{
	protected $tag
	protected $data
	protected $attrib_arr;

	function __construct( $data )
	{
		$this->set( "data", $data );
		//Inheriting classes also need to set TAG
	}
	function get( $field )
	{
		if( is_array( $field ) )
		{
			foreach( $field as $var )
			{
				$ret = $this->get( $var );
			}
		}
		else
		if( is_object( $field ) )
		{
			$ret = $field->toHtml();
		}
		else
		{
			$ret = parent::get( $field );
		}
		return $ret;
	}
	function toHtml()
	{
		$tag = $this->get( "tag" );
		if( strlen( $tag ) > 0 )
		{
			$open = "<" .  $tag . ">";
			$close = "</" .  $tag . ">";
		}
		else
		{
			$open = "";
			$close = "";
		}
		$ret = $open . $this->get( "data" ) . $close;
		return $ret;
	}
}

class HTML_Bold extends HTML_atomic
{
	function __construct( $data )
	{
		$this->set( "tag", "b" );
		parent::__construct( $data );
	}
}

class HTML_Italic extends HTML_atomic
{
	function __construct( $data )
	{
		$this->set( "tag", "i" );
		parent::__construct( $data );
	}
}
