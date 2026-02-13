<?php

/**
 * ViewBILineItems - Legacy View Class for Bank Import Line Items
 * 
 * @deprecated This class is deprecated and should not be used in new code.
 *             The bi_lineitem class now handles its own view logic using proper
 *             HTML library classes. See class.bi_lineitem.php methods:
 *             - display() - Outputs complete HTML row
 *             - getHtml() - Returns complete HTML row as string
 *             - getLeftTd() / getRightTd() - Returns HtmlTd elements
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 * @deprecated 20251106 - Replaced by bi_lineitem's own display methods
 * 
 * Replacement Pattern:
 * OLD: $view = new ViewBILineItems($lineitem); $view->display();
 * NEW: $lineitem->display();
 */
class ViewBILineItems
{
	protected $bi_lineitem;
	function __construct( $bi_lineitem )
	{
		$this->bi_lineitem = $bi_lineitem;
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
	**********************************************************************/
	function display_left()
	{
		$this->bi_lineitem->getBankAccountDetails();
		start_row();
		echo '<td width="50%">';
		$table = new HTML_TABLE( null, 100 );

		$table->appendRow( new TransDate( $this->bi_lineitem ) );
		$table->appendRow( new TransType( $this->bi_lineitem ) );
		$table->appendRow( new OurBankAccount( $this->bi_lineitem ) );
		$table->appendRow( new OtherBankAccount( $this->bi_lineitem ) );
		$table->appendRow( new AmountCharges( $this->bi_lineitem ) );
		$table->appendRow( new TransTitle( $this->bi_lineitem ) );

		$this->displayAddVendorOrCustomer();
		$this->displayEditTransData();
		if( $this->isPaired() )
		{
			//TODO: make sure the paired transactions are set to BankTranfer rather than Credit/Debit
			$this->displayPaired();
		}
		$table->toHTML();

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
	function addCustomerButton()
	{
		$b = new AddCustomerButton( $this->id );
		$b->toHtml();
		//label_row("Add Customer", submit("AddCustomer[$this->id]",_("AddCustomer"),false, '', 'default'));
	}
	function addVendorButton()
	{
		$b = new AddVendorButton( $this->id );
		$b->toHtml();
		//label_row("Add Vendor", submit("AddVendor[$this->id]",_("AddVendor"),false, '', 'default'));
	}
	/**//**************************************************************
	* Display our paired transaction
	*
	*******************************************************************/
	function displayPaired()
	{
	}
	function makeURLLink( string $URL, array $params, string $text, $target = "_blank" )
	{
		$link = HtmlLink( new HtmlString( $text ) );
		$link->setTarget( $target );
		$link->addAttribute( new HtmlAttribute( "href", $URL ) );
		foreach( $params as $param )
		{
			foreach( $param as $key=>$val )
			{
				$link->addAttribute( new HtmlAttribute( $key, $value ) );
			}
		}
		$ret = $link->getHtml();
		return $ret;
/*

		$ret = "<a ";
		if( strlen( $target ) > 1 )
		{
			$ret .= " target=" . $target;
		}
		$ret .= " href='" . $URL;
		if( count( $params ) > 1 )
		{
			$ret .= "?";
			$parcount = 0;
			foreach( $params as $param )
			{
				foreach( $param as $key=>$val )
				{
					if( $parcount > 0 )
					{
						$ret .= "&";
					}
					$ret .= "$key=$val";
					$parcount++;
				}
			}
		}
		$ret.="'>";
		$ret .= $text;
		$ret .= "</a>";
		return $ret;
*/
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
						$cdet = new CustomerTransactionDetails( $matchgl['type'], $matchgl['type_no'] );
	//					$match_html .= $cdet->getLineitemMatchedCustomerDetails();
/*
						$cdet = get_customer_details_from_trans(	$matchgl['type'],
												$matchgl['type_no']
							);
						$match_html .= " //Person " . $cdet['name'] . "/" . $cdet["br_name"];
*/
					}
					$match_html .= "<br />";
					$matchcount++;
				} //if isset
			} //foreach
			label_row("Matching GLs.  Ensure you double check Accounts and Amounts", $match_html );
		}
		else
		{
				label_row("Matching GLs", "No Matches found automatically" );
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
			$rowlabel = "Transfer to <i>Our Bank Account</i> <b>from (OTHER ACCOUNT</b>):";
		}
		else
		{
			$rowlabel = "Transfer from <i>Our Bank Account</i> <b>To (OTHER ACCOUNT</b>):";
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
		// Use standalone HTML instead of FA's start_table() - for independence from FA
		echo '<table class="tablestyle2" width="100%">';
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
		// Use standalone HTML instead of FA's end_table() - for independence from FA
		echo '</table>';
		echo "</td>";
		// Note: end_row() is handled by parent context

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
}
