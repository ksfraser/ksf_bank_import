<?php

$path_to_root = "../..";
$page_security = 'SA_SALESTRANSVIEW';
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");
include_once($path_to_root . "/includes/ui/ui_globals.inc");
include_once($path_to_root . "/includes/ui/ui_controls.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/data_checks.inc");


include_once($path_to_root . "/modules/bank_import/includes/includes.inc");
include_once($path_to_root . "/modules/bank_import/includes/pdata.inc");

//20240316
//	QE is working.
//	BT is working.

//TODO:
//	Audit routine to ensure that all processed entries match what they are allocated to
//		For example if an entry says it matches JE XXX, ensure that the dates are close, and the amount is exact.
//	Audit that no 2 transactions point to the same type+number.  i.e. recurring payments aren't matched to the same payment.
//		During the insert/update we should make sure this dupe doesn't pre-exist before doing the update.
//	Make sure that all Processing (i.e. bi_transactions is having status set to 1) triggers a reload/re-search
//	Ensure pre_dbwrite and post_dbwrite are called on all updates so that other modules can also be triggered
//		Looking to have related sets of books (i.e. business specific ones) to be matched/updated/created
//			i.e. Marcia's CM payments or commissions should match/create entries in that specific set of books from our household books.
//			i.e. FHS Expenditures and Payments should match/create entries in the FHS books.
//		Is there a way to trap on GL Accounts being created and propogate those between sets of books?
//		If so, is there a way to also trap creation of bank accounts?
//	Add a filter on the search screen to filter by "our account"


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Bank Transactions"), @$_GET['popup'], false, "", $js);

//These optypes are used for labels in the GUI
$optypes = array(
	'SP' => 'Supplier',
	'CU' => 'Customer',
	'QE' => 'Quick Entry',
	'BT' => 'Bank Transfer',
	'MA' => 'Manual settlement',
	'ZZ' => 'Matched',
);

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");
/**
$trans_types_readable = array( 
	ST_JOURNAL => "Journal Entry",
	ST_BANKPAYMENT => "Bank Payment",
	ST_BANKDEPOSIT => "Bank Deposit",
	ST_BANKTRANSFER => "Bank Transfer",
	ST_SALESINVOICE => "Sales Invoice",
	ST_CUSTCREDIT => "Customer Credit",
	ST_CUSTPAYMENT => "Customer Payment",
	ST_CUSTDELIVERY => "Customer Delivery",
	ST_LOCTRANSFER => "Location Transfer",
	ST_INVADJUST => "Inventory Adjustment",
	ST_PURCHORDER => "Purchase Order",
	ST_SUPPINVOICE => "Supplier Invoice",
	ST_SUPPCREDIT => "Supplier Credit",
	ST_SUPPAYMENT => "Supplier Payment",
	ST_SUPPRECEIVE => "Supplier Received",
	ST_WORKORDER => "Work Order",
	ST_MANUISSUE => "Manufacturing Issue",
	ST_MANURECEIVE => "Manufacturing Receive",
	ST_SALESORDER => "Sales Order",
	ST_SALESQUOTE => "Sales Quote",
	ST_COSTUPDATE => "Cost Update",
	ST_DIMENSION => "Dimension",
);
**/


require_once( 'class.bank_import_controller.php' );
	try {
		$bi_controller = new bank_import_controller();	//no vars for constructor.
	} catch( Exception $e )
	{	
		display_error( __LINE__ . "::" . print_r( $e, true ) );
	}


//---------------------------------------------------------------------------------
//--------------Unset (Reset) a Transaction----------------------------------------
//---------------------------------------------------------------------------------
// actions
unset($k, $v);
	 //display_notification( print_r( $_POST, true ) . "\n" );

	 //display_notification( __LINE__ );
if( isset( $_POST['UnsetTrans'] ) )
{
	$bi_controller->unsetTrans();
}
/*----------------------------------------------------------------------------------------------*/
/*------------------------Add Customer----------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

	 //display_notification( __LINE__ );
if (isset($_POST['AddCustomer'])) 
{
	$bi_controller->addCustomer();
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Add Vendor-----------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
	 //display_notification( __LINE__ );
if (isset($_POST['AddVendor'])) 
{
	$bi_controller->addVendor();
}

	 //display_notification( __FILE__ . "::" . __LINE__ );
if (isset($_POST['ToggleTransaction'])) 
{
	$bi_controller->toggleDebitCredit();
	display_notification( __LINE__ . "::" .  print_r( $_POST, true ));
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Process Transaction--------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
	 //display_notification( __LINE__ );
if ( isset( $_POST['ProcessTransaction'] ) ) {
	//display_notification( __LINE__ . "::" .  print_r( $_POST, true ));
//20240208 EACH is depreciated.  Should rewrite with foreach
//Because this comes from a web form and a button press, there will only be 1 k/v
	list($k, $v) = each($_POST['ProcessTransaction']);	//K is index.  V is "process/..."
	if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) 
	{
			//display_notification( __FILE__ . "::" . __LINE__ . ":: k:" . print_r( $k, true ) . " :: v:" . print_r( $v, true ) . ":: Partner Type: " .  print_r( $_POST['partnerType'][$k], true ) );
		//check params
//@todo
//rewrite as a try/catch so we don't have to keep checking error
		$error = false;
		if ( ! isset( $_POST["partnerId_$k"] ) ) 
		{
			$Ajax->activate('doc_tbl');
			display_error('missing partnerId');
			$error = true;
		}
	
		if (!$error) {
			$tid = $k;
			//time to gather data about transaction
			//load $tid
			$trz = get_transaction($tid);
			//display_notification('<pre>'.print_r($trz,true).'</pre>');
	
			//check bank account
			$our_account = get_bank_account_by_number($trz['our_account']);
			if (empty($our_account)) 
			{
				$Ajax->activate('doc_tbl');
				display_error(  __FILE__ . "::" . __LINE__ . "::" . ' the bank account <b>'.$trz['our_account'].'</b> is not defined in Bank Accounts');
				$error = 1;
			}
			//display_notification('<pre>'.print_r($our_account,true).'</pre>');
		}
		if (!$error) {
/*Charges*/
			$charge = $bi_controller->charge = $bi_controller->sumCharges( $tid );
			$bi_controller->set( "charge", $charge );
/*! Charges*/
	
			//display_notification("amount=$amount, charge=$charge");
			//display_notification("partnerType=".$_POST['partnerType'][$k]);
			$pid = "partnerId_" . $k;
			//display_notification( "partner=".$_POST[ $pid ] );
			$partnerId = $_POST[ $pid ];
			$bi_controller->set( "partnerId", $partnerId );
	
				//display_notification( __FILE__ . "::" . __LINE__ );
//These are needed for SP.  The others too???
			$bi_controller->set( "trz", $trz );
			$bi_controller->set( "tid", $tid );
			$bi_controller->set( "our_account", $our_account );
			switch(true) 
			{
		            case ($_POST['partnerType'][$k] == 'SP'):
				display_notification( __FILE__ . "::" . __LINE__ . " CALL controller::processSupplierTransaction ");
				try
				{
					$bi_controller->processSupplierTransaction();
				} catch( Exception $e )
				{
					display_error( "Error processing supplier transaction: " . print_r( $e, true ) );
				}
/*****
*				
*					$trans_no = 0;	//NEW.  A number would be an update - leads to voiding of a bunch of stuff and then redo-ing.
*		                    if( $trz['transactionDC'] == 'D' )
*		                    {
*		                            //Normal SUPPLIER PAYMENT
*					$trans_type = ST_SUPPAYMENT;
*		                		do {
*		                    			$reference = $Refs->get_next($trans_type);
*		                		} while(!is_new_reference($reference, $trans_type));
*	
*					//purchasing/includes/db/supp_payment_db.inc
*					// write_supp_payment($trans_no, $supplier_id, $bank_account, $date_, $ref, $supp_amount, $supp_discount, $memo_, $bank_charge=0, $bank_amount=0)
*						$payment_id = write_supp_payment( $trans_no, $partnerId, $our_account['id'], sql2date($trz['valueTimestamp']), $reference, user_numeric($trz['transactionAmount']), 0, $trz['transactionTitle'], user_numeric($charge), 0);
*						//display_notification("payment_id = $payment_id");
*					$counterparty_arr = get_trans_counterparty( $payment_id, $trans_type );
*				display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
*						//update trans with payment_id details
*						if ($payment_id) {
*							update_transactions($tid, $_cids, $status=1, $payment_id, $trans_type, false, true,  "SP", $partnerId );
*							update_partner_data($partner_id = $partnerId, $partner_type = PT_SUPPLIER, $partner_detail_id = ANY_NUMERIC, $account = $trz['account']);
*							display_notification('Supplier Payment Processed:' . $payment_id );
*
*							//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
*							//display_notification("<a target=_blank href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
*							//Display a link to the transaction
*							//	http://192.168.0.66/infra/accounting/gl/view/gl_trans_view.php?type_id=22&trans_no=227
*							display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $payment_id . "'>View Payment</a>" );
*							//display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans[1] . "'>View Entry</a>" );
*		                    		}
*					}
*		                    else
*		                    if( $trz['transactionDC'] == 'C' )
*		                    {
*					//FA Native creates this as a Supplier Credit Note -> BANK DEPOSIT
*					//http://fhsws002.ksfraser.com/infra/accounting/gl/view/gl_deposit_view.php?trans_no=4
*					//vs
*					//http://fhsws002.ksfraser.com/infra/accounting/purchasing/view/view_supp_payment.php?trans_no=183
*					//Needs to be a BANK DEPOSIT in order for the payment to be recognized for allocation.
*					// gl/gl_bank.php?NewDeposit=Yes
*	
*		                            //SUPPLIER REFUND
*					$trans_type = ST_BANKDEPOSIT;
*					$partner_type = PT_SUPPLIER;
*					$partner_detail_id = ANY_NUMERIC;
*					$payment_id = 0;
*	
*		                            $trz['transactionAmount'] = $trz['transactionAmount'] * -1;
*						//display_notification("Reference = $reference");
*					$cart = new items_cart($trans_type);
*						$cart->order_id = $trans_no;
*		            		$cart->tran_date = new_doc_date();
*		            		if (!is_date_in_fiscalyear($cart->tran_date))
*		                    		$cart->tran_date = end_fiscalyear();
*		                		do {
*		                    		$reference = $Refs->get_next( $trans_type );
*							//$Refs->get_next($cart->trans_type, null, $cart->tran_date);
*		                		} while(!is_new_reference($reference, $trans_type ));
*					$cart->reference = $reference;
*	
*					while (count($args) < 10) $args[] = 0;
*		    			$args = (object)array_combine( array( 'trans_no', 'supplier_id', 'bank_account', 'date_', 'ref', 'bank_amount', 'supp_amount', 'supp_discount', 'memo_', 'bank_charge'), $args);
*	
*					begin_transaction();
*					hook_db_prewrite( $args, $trans_type );
*					//$supplier_accounts = get_supplier_accounts($partnerId);
*					$supplier_accounts = get_supplier($partnerId);	//Does this give us the dimensions?
*					$cart->add_gl_item(
*						$supplier_accounts["payable_account"],
*						$supplier_accounts["dimension_id"],
*						$supplier_accounts["dimension2_id"],
*						user_numeric($trz['transactionAmount']),
*						$trz['transactionTitle']
*					);
*	
*					    if ( $cart->count_gl_items() < 1) {
*		            			display_error(_("You must enter at least one payment line."));
*		    			}
*		    			if ( $cart->gl_items_total() == 0.0) {
*		            			display_error(_("The total bank amount cannot be 0."));
*		    			}
*	
*					$payment_id = write_bank_transaction(
*		            			$cart->trans_type, 
*						$cart->order_id, 
*						$our_account['id'],
*		            			$cart, 
*						sql2date( $trz['valueTimestamp'] ),
*		            			$partner_type,
*						$partnerId, 
*						$partner_detail_id,
*		            			$cart->reference,
*						$trz['transactionTitle'],
*						true, 
*						number_format2(abs( $cart->gl_items_total() ) )
*					);
*					//write_bank_trans returns an array with element 0 = trans_type, and 1 = trans_no
*	
*						//update trans with payment_id details
*					if ($payment_id) 
*					{
*						$counterparty_arr = get_trans_counterparty( $payment_id, $trans_type );
*						display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
*						update_transactions($tid, $_cids, $status=1, $payment_id[1], $trans_type, false, true,  "SP", $partnerId );
*						update_partner_data( $partnerId, $partner_type, $partner_detail_id, $account = $trz['account']);
*						display_notification('Supplier Refund Processed:' . print_r( $payment_id, true ) );
*						//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
*						//display_notification("<a target=_blank href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
*						display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $payment_id[1] . "'>View Entry</a>" );
*		                    	}
*					hook_db_postwrite($args, $trans_type );
*					commit_transaction();
*		                    }
***/
			break;
	/*************************************************************************************************************/
				//TODO:
				//	Match customers to records
				//		i.e. E-Transfer from XXYYY (CIBC statements)
			case ($_POST['partnerType'][$k] == 'CU' && $trz['transactionDC'] == 'C'):
					//display_notification( __FILE__ . "::" . __LINE__ . " Index passed in (processTransaction from post): " . $k );
					//display_notification( __FILE__ . "::" . __LINE__ . " Invoice for this Index: " . $_POST['Invoice_' .$k] );
					//display_notification( __FILE__ . "::" . __LINE__ . ":: " .  print_r( $_POST, true ) );
				/*
					display_notification( __FILE__ . "::" . __LINE__ . "Partner ID: " . $partnerId );
					display_notification( __FILE__ . "::" . __LINE__ . "Transaction Data: " . print_r( $trz, true ) );
					display_notification( __FILE__ . "::" . __LINE__ . print_r( $_POST, true ) );
				*/
				//20240211 Works.  Not sure why BANKDEPOSIT vice CUSTPAYMENT in original module.
				$trans_type = ST_BANKDEPOSIT;
				$trans_type = ST_CUSTPAYMENT;
				//insert customer payment into database
				do {
					$reference = $Refs->get_next($trans_type);
				} while(!is_new_reference($reference, $trans_type));
				//20240304 The BRANCH doesn't seem to get selected though.
				if( strlen( $trz['transactionTitle'] ) < 4 )
				{
					if( strlen( $trz['memo'] ) > 0 ) 
					{
						$trz['transactionTitle'] .= " : " . $trz['memo'];
					}
				}
/** Mantis 3018
*	We are trying to allocate Customer Payments against a specific invoice
*		Should we be setting trans_no?   It is currently NULL.
*		partnerId is being set right before the opening of this switch statement
*
* /var/www/html/infra/accounting/modules/bank_import/process_statements.php::376::
*	Array ( 
*		[TransAfterDate] => 10/02/2024 	
*		
*		[TransToDate] => 11/18/2024 
*		[statusFilter] => 0 
*		[vendor_short_33038] => WENDY'S MCKNIGHT 
*		[vendor_long_33038] => WENDY'S MCKNIGHT 
*		[partnerType] => Array ( 
*			[33038] => SP 
*			[33043] => SP 
*			[33053] => CU 
*			[35723] => BT 
*			[32838] => SP ) 
*		[partnerId_33038] => 308 
*		[cids] => Array ( 
*			[33038] => 
*			[33043] => 
*			[33053] => 
*			[32838] => ) 
*		...
*------------
*		[ProcessTransaction] => Array ( 
*			[44438] => Process ) 
*		[vendor_short_44438] => [vendor_long_44438] => [partnerId_44438] => 108 [partnerDetailId_44438] => 128 [customer_44438] => 108 [customer_branch_44438] => [Invoice_44438] => 790 
*		...
*		[_focus] => TransAfterDate 
*		[_modified] => 0 
*		[_confirmed] => 
*		[_token] => VpLSv8r74F9S694oB4NNZQxx 
*		[_random] => 753203.4421414237 )
*/
/*
			if( isset( $_POST['Invoice_$k'] ) )
			{
				//$trans_no = $_POST['Invoice_$k'];
			}
			else
			{
				//$trans_no = 0;
			}
			display_notification( __FILE__ . "::" . __LINE__ . "::" . "Trans ID: " . $trans_no );
*/
	//TODO:
	//	check that we have partnerId	
					//display_notification( __FILE__ . "::" . __LINE__ );

/*** The below isn't working for some reason
*			require_once( '../ksf_modules_common/class.fa_customer_payment.php' );
*			$fcp = new fa_customer_payment( $partnerId );		//customer_id
*					display_notification( __FILE__ . "::" . __LINE__ );
*
*			//WARNING WARNING WARNING
*			//If trans_no is set, the function tries to void/delete that trans number as if it's an update!!!
*					//Set the variables to call write_customer_payment()
*					display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $fcp, true ) );
*						//$fcp->set( "branch_id", $_POST["partnerDetailId_$k"] );	
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "bank_account", $our_account['id'] );	
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "trans_no", 0 );	//NEW
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "trans_date", sql2date($trz['valueTimestamp']) );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "date", sql2date($trz['valueTimestamp']) );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "ref", $reference );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "amount", user_numeric($trz['transactionAmount']) );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "discount", 0 );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "memo_", $trz['transactionTitle'] );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "rate", 0 );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "charge", user_numeric($charge) );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "bank_amount", 0 );
*					display_notification( __FILE__ . "::" . __LINE__ );
*						$fcp->set( "trans_type", $trans_type );
*			display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $fcp, true ) );
**/

/*
**Replaced in the TRY below
*				$deposit_id = my_write_customer_payment(
*					$trans_no = 0, $customer_id=$partnerId, $branch_id=$_POST["partnerDetailId_$k"], $bank_account=$our_account['id'],
*					$date_ = sql2date($trz['valueTimestamp']), $reference, user_numeric($trz['transactionAmount']),
*					$discount=0, $trz['transactionTitle'], $rate=0, user_numeric($charge), $bank_amount=0, $trans_type);
*
*                        $pmtno = write_customer_payment(0, $invoice->customer_id,
*                                $invoice->Branch, $invoice->pos['pos_account'], $date_,
*                                $Refs->get_next(ST_CUSTPAYMENT, null, array('customer' => $invoice->customer_id,
*                                        'branch' => $invoice->Branch, 'date' => $date_)),
*                                $amount-$discount, $discount,
*                                _('Cash invoice').' '.$invoice_no);
*                        add_cust_allocation($amount, ST_CUSTPAYMENT, $pmtno, ST_SALESINVOICE, $invoice_no, $invoice->customer_id, $date_);
*
*                }
*        }
*        reallocate_payments($invoice_no, ST_SALESINVOICE, $date_, $to_allocate, $allocs);
*        hook_db_postwrite($invoice, ST_SALESINVOICE);
*
*
*			display_notification( __FILE__ . "::" . __LINE__ . "::" . "Deposit ID: " . $deposit_id );
*/
			try {
/*
*					$deposit_id = $fcp->write_customer_payment();
*					$invoice_no = $_POST['Invoice_$k'];
*					display_notification("Invoice Number and Deposit Number: $invoice_no :: $deposit_id ");
*/

				$invoice_no = $_POST['Invoice_' . $k];
					//display_notification("Add payment for Invoice $invoice_no");
				$amount = user_numeric($trz['transactionAmount']);
				$deposit_id = my_write_customer_payment(
					$trans_no = 0, $customer_id=$partnerId, $branch_id=$_POST["partnerDetailId_$k"], $bank_account=$our_account['id'],
					$date_ = sql2date($trz['valueTimestamp']), $reference, $amount,
					$discount=0, $trz['transactionTitle'], $rate=0, user_numeric($charge), $bank_amount=0, $trans_type);
				if( $invoice_no )
				{
//sales/allocations/customer_allocate.php?trans_no=521&trans_type=12&debtor_no=108
//$alloc = new allocation($_GET['trans_type'], $_GET['trans_no'], @$_GET['debtor_no'], PT_CUSTOMER);
//$alloc->write();
					//display_notification("Now associate payment $deposit_id to Invoice $invoice_no");
						// /sales/includes/db/custalloc_db.inc
                        		add_cust_allocation($amount, ST_CUSTPAYMENT, $deposit_id, ST_SALESINVOICE, $invoice_no, $customer_id, $date_);
                        		update_debtor_trans_allocation(ST_SALESINVOICE, $invoice_no, $customer_id);
                        		update_debtor_trans_allocation(ST_CUSTPAYMENT, $deposit_id, $customer_id);
				}


					//update trans with payment_id details
					//if( $invoice_no )
					if( $deposit_id )
					{
						//Alolocate payment against an invoice
						$counterparty_arr = get_trans_counterparty( $deposit_id, $trans_type );
						//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
						//$fcp->write_allocation();
						update_transactions($tid, $_cids, $status=1, $deposit_id, $trans_type, false, true,  "CU", $partnerId);
//We want to update fa_trans_type, fa_trans_no, account/accountName, status, matchinfo, matched/created, g_partner
						update_partner_data($partnerId, PT_CUSTOMER, $_POST["partnerDetailId_$k"], $trz['memo']);
						update_partner_data($partnerId, $trans_type, $_POST["partnerDetailId_$k"], $trz['memo']);
						display_notification('Customer Payment/Deposit processed');
						display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View GL Entry</a>" );
						display_notification("<a target=_blank href='../../sales/view/view_receipt.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View Payment and Associated Invoice</a>" );
					}
					else {
						//No allocation - only record (above) the payment
					}
			}
			catch( Exception $e )
			{
				display_notification('Exception' . print_r( $e, true ) );
			}

/** Replaced in TRY above
*				//update trans with payment_id details
*				if ($deposit_id) {
*					if( $invoice_no )
*					{
*	/*** /
*						$counterparty_arr = get_trans_counterparty( $deposit_id, $trans_type );
*						display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
*	/*** /
*						$fcp->set( "trans_date", $valueTimestamp );
*						$fcp->set( "trans_type", $trans_type );
*						$fcp->set( "payment_id", $deposit_id );
*						$fcp->write_allocation();
*					}
*					
*					update_transactions($tid, $_cids, $status=1, $deposit_id, $trans_type, false, true,  "CU", $partnerId);
//We want to update fa_trans_type, fa_trans_no, account/accountName, status, matchinfo, matched/created, g_partner
*					update_partner_data($partnerId, PT_CUSTOMER, $_POST["partnerDetailId_$k"], $trz['memo']);
*					update_partner_data($partnerId, $trans_type, $_POST["partnerDetailId_$k"], $trz['memo']);
*					display_notification('Customer Payment/Deposit processed');
*					display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $deposit_id . "'>View Entry</a>" );
*				} //if deposit_id
*/
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'QE'):
				$trans_type = ($trz['transactionDC'] == 'D')?ST_BANKPAYMENT:ST_BANKDEPOSIT;
				$cart = new items_cart($trans_type);
					$cart->order_id = 0;
					$cart->original_amount = $trz['transactionAmount'] + $charge;
					do {
						$cart->reference = $Refs->get_next($cart->trans_type);
					} while(!is_new_reference($cart->reference, $cart->trans_type));
					$cart->tran_date = sql2date($trz['valueTimestamp']);
					if (!is_date_in_fiscalyear($cart->tran_date)) 
					{
						$cart->tran_date = end_fiscalyear();
					}
					//this loads the QE into cart!!!
				try {
					if( strlen( $trz['transactionTitle'] ) < 4 )
					{
						if( strlen( $trz['memo'] ) > 0 ) 
						{
							$trz['transactionTitle'] .= " : " . $trz['memo'];
						}
					}
					//display_notification('QE TRANS: ' . print_r( $trz, true ) );

					//function qe_to_cart(&$cart, $id, $base, $type, $descr='')
					$qe_memo = "A:" . $our_account['bank_account_name'] . ":" . $trz['account_name'] . " M:" . $trz['account'] . ":" . $trz['transactionTitle'] . ": " . $trz['transactionCode'];
					$rval = qe_to_cart($cart, $partnerId, $trz['transactionAmount'], ($trz['transactionDC']=='C') ? QE_DEPOSIT : QE_PAYMENT, $qe_memo );
				} catch( Exception $e )
				{
					display_notification('RVAL Exception' . print_r( $e, true ) );
				}
				// function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $date=null)
	//TODO:
	//	Config which account to log these in
	//	Conig whether to log these.
				$cart->add_gl_item( '0000', 0, 0, 0.01, 'TransRef::'.$trz['transactionCode'], "Trans Ref");
				$cart->add_gl_item( '0000', 0, 0, -0.01, 'TransRef::'.$trz['transactionCode'], "Trans Ref");
				$total = $cart->gl_items_total();
				if ($total != 0) 
				{
					//need to add the charge to the cart
					$cart->add_gl_item(get_company_pref('bank_charge_act'), 0, 0, $charge, 'Charge/'.$trz['transactionTitle']);
					//process the transaction
					begin_transaction();
	
					$trans = write_bank_transaction(
						$cart->trans_type, $cart->order_id, $our_account['id'],
						$cart, sql2date($trz['valueTimestamp']),
							PT_QUICKENTRY, $partnerId, 0,
							$cart->reference, $qe_memo, true, null);
							//$cart->reference, $trz['transactionTitle'], true, null);
	
					$counterparty_arr = get_trans_counterparty( $trans[1], $trans_type );
					//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				/***
					Array ( 	[0] => 
							Array ( 	[counter] => 171128 
									[type] => 1 
									[type_no] => 602 
									[tran_date] => 2023-06-22 
									[account] => 8523.2 
									[memo_] => Groceries 
									[amount] => 41.78 
									[dimension_id] => 3 
									[dimension2_id] => 10 
									[person_type_id] => 
									[person_id] => 
									[account_name] => Groceries 
									[reference] => 517/2023 
									[real_name] => Administrator 
									[doc_date] => 2023-06-22 
									[supp_reference] => 
							) 
							[1] => Array ( 	... ) 
							[2] => Array ( ...  ) 
							[3] => Array ( ...  ) 
					)
						//partnerID in this case is the QE index.
					set_bank_partner_data( $our_account['id'], $trans_type, $partnerId, $trz['transactionTitle'] );
				***/
					update_transactions($tid, $_cids, $status=1, $trans[1], $trans_type, false, true, "QE", $partnerId );
					commit_transaction();
					//Don't want this preventing the commit!
					set_bank_partner_data( $our_account['id'], $trans_type, $partnerId, $trz['transactionTitle'] );
					//ST_BANKPAYMENT or ST_BANKDEPOSIT
					
					//Let User attach a document
					display_notification("<a target=_blank href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . $trans_type . "&trans_no=" . $trans[1] . "'>Attach Document</a>" );
					//Let the user view the created transaction
					//http://192.168.0.66/infra/accounting/gl/view/gl_trans_view.php?type_id=0&trans_no=10825
					display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans[1] . "'>View Entry</a>" );

	
					} 
				else 
				{
					display_notification('CART4B ' . print_r( $cart, true ) );
					display_notification("QE not loaded: rval=$rval, k=$k, total=$total");
					//display_notification("debug: <pre>".print_r($_POST, true).'</pre>');
					}
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'BT'):
				$inc = require_once( '../ksf_modules_common/class.fa_bank_transfer.php' );
				if( $inc )
				{
					$bttrf = new fa_bank_transfer();
					try
					{
						$bttrf->set( "trans_type", ST_BANKTRANSFER );
						if( $trz['transactionDC'] == 'C' OR $trz['transactionDC'] == 'B' )
						{
							//display_notification( __LINE__ . " :: " . print_r( $our_account, true )  );
							$bttrf->set( "ToBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
							$bttrf->set( "FromBankAccount", $_POST[$pid] );
						}
						else
						if( $trz['transactionDC'] == 'D' )
						{
							//On a Debit, the bank accounts are reversed.
							//display_notification( __LINE__ . " :: " . print_r( $our_account, true )  );
							$bttrf->set( "FromBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
							$bttrf->set( "ToBankAccount", $_POST[$pid] );
						}
						$bttrf->set( "amount", $trz['transactionAmount'] );
						$bttrf->set( "trans_date", $trz['valueTimestamp'] );
						$bttrf->set( "memo_", $trz['transactionTitle'] . "::" . $trz['transactionCode'] . "::" . $trz['memo'] );
						$bttrf->set( "target_amount", $trz['transactionAmount'] );
					}
					catch( Exception $e )
					{
						//display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
						break;
					}
					try
					{
						$bttrf->getNextRef();
						//$bttrf->trans_date_in_fiscal_year();
					}
					catch( Exception $e )
					{
						break;
					}
					begin_transaction();
					//can_process is baked into add_bank_transfer
					$bttrf->add_bank_transfer();
					$counterparty_arr = get_trans_counterparty( $bttrf->get( "trans_no" ), $bttrf->get( "trans_type" ) );
						////display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
					$trans_no = $bttrf->get( "trans_no" );
					$trans_type = $bttrf->get( "trans_type" );
					update_transactions( $tid, $_cids, $status=1, $trans_no, $trans_type, false, true,  "BT", $partnerId );
					//update_transactions( $tid, $_cids, $status=1, $bttrf->get( "trans_no" ), $bttrf->get( "trans_type" ), false, true );

					set_bank_partner_data( $bttrf->get( "FromBankAccount" ), $bttrf->get( "trans_type" ), $bttrf->get( "ToBankAccount" ), $trz['memo'] );	//Short Form
								//memo/transactionTitle holds the reference number, which would be unique :(
					commit_transaction();
					display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $trans_type . "&trans_no=" . $trans_no . "'>View Entry</a>" );
				}
				else
				{
						//display_notification( __LINE__  );
				}
			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'MA'):
				$counterparty_arr = get_trans_counterparty( $_POST['Existing_Entry'], $_POST['Existing_Type'] );
					display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				update_transactions($tid, $_cids, $status=1, $_POST['Existing_Entry'], $_POST['Existing_Type'], true, false, null, "" );
				display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $_POST['Existing_Type'] . "&trans_no=" . $_POST['Existing_Entry'] . "'>View Entry</a>" );
				set_partner_data( $counterparty_arr['person_type'], $_POST['Existing_Type'], $counterparty_arr['person_type_id'], $trz['memo'] );	//Short Form
				display_notification("Transaction was manually settled " . print_r( $_POST['Existing_Type'], true ) . ":" . print_r( $_POST['Existing_Entry'], true ) );
				if( $_POST['Existing_Type'] == 12 )
				{
					display_notification("<a target=_blank href='../../sales/view/view_receipt.php?type_id=" . $_POST['Existing_Type'] . "&trans_no=" . $_POST['Existing_Entry'] . "'>View Payment and Associated Invoice</a>" );
				}
			break;
	/*************************************************************************************************************/
				//TODO:
				//	*When the Match score is too low, switching to MATCH still gets overwritten the next ajax load
				//	*Test what happens if there are 3+ matches
				//		right now it doesn't auto match, because we don't have a way to select the trans type/number
				//		Sort by scoring.  Go with highest?
				//20240214 Matching Works.  As long as score is high enough, can "process".
			case ($_POST['partnerType'][$k] == 'ZZ'):
				//display_notification("Entry Matched against an existing Entry (LE/Cp/SP/...)");
				//display_notification(__FILE__ . "::" . __LINE__ . ":" . " Trans Type and No: ".print_r( $_POST["trans_type_$tid"], true) . ":" . print_r( $_POST["trans_no_$tid"], true ) );
					$counterparty_arr = get_trans_counterparty( $_POST["trans_no_$tid"], $_POST["trans_type_$tid"] );
				//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				if( isset( $_POST["memo_$tid"] ) AND strlen ($_POST["memo_$tid"]) > 0 )
				{
					$memo = $_POST["memo_$tid"];
				}
				else
				if( isset( $_POST["title_$tid"] ) AND strlen ($_POST["title_$tid"]) > 0 )
				{
					$memo = $_POST["title_$tid"];
				}
				else
				{
					$memo = "";
				}
				foreach( $counterparty_arr as $row )
				{
					//display_notification(__FILE__ . "::" . __LINE__  );
					//Any given transaction should only have 1 person associated.
					if( isset( $row['person_id'] ) )
					{
						if( is_numeric( $row['person_id'] ) )
						{
							$person_id = $row['person_id'];
						}
						if( is_numeric( $row['person_type_id'] ) )
						{
							$person_type_id = $row['person_type_id'];
						}
					}
				}
					//display_notification(__FILE__ . "::" . __LINE__  );
					update_transactions( $tid, $_cids, $status=1, $_POST["trans_no_$tid"], $_POST["trans_type_$tid"], true, false,  "ZZ", $partnerId );
					//display_notification(__FILE__ . "::" . __LINE__  );
					display_notification("Transaction was MATCH settled " .  $_POST["trans_type_$tid"] . "::" . $_POST["trans_no_$tid"] . "::" . "<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $_POST["trans_type_$tid"] . "&trans_no=" . $_POST["trans_no_$tid"] . "'>View Entry</a>");
					if( $_POST["trans_no_$tid"] == 12 )
					{
						display_notification("<a target=_blank href='../../sales/view/view_receipt.php?type_id=" . $_POST["trans_type_$tid"] . "&trans_no=" . $_POST["trans_no_$tid"] . "'>View Payment and associated Invoice</a>" );
					}
				set_partner_data( $person_type, $_POST["trans_type_$tid"], $person_type_id, $memo );	
					//display_notification(__FILE__ . "::" . __LINE__  );
			break;
			} // end of switch
			$Ajax->activate('doc_tbl');
		} //end of if !error

	} // end of is set....
} //end of is isset(post[processTranzaction])

/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

/*
// check whether a transaction is ignored
unset($k, $v);
list($k, $v) = each($_POST['IgnoreTrans']);
if (isset($k) && isset($v)) {
		//display_notification( __FILE__ . "::" . __LINE__ );
	updateTrans($_POST['trans_id'][$k], $_POST['charge_id'][$k], TR_MAN_SETTLED);
	$Ajax->activate('doc_tbl');
	display_notification('Manually processed');
}
*/
/************************************************************************************************************************/
/**********************************************  GUI  *******************************************************************/
/************************************************************************************************************************/

// search button pressed
if (get_post('RefreshInquiry')) {
	$Ajax->activate('doc_tbl');
}

//SC: check whether a customer has been changed, so that we can update branch as well
// as there a user can click on one submit button only, there is no need for multiple check
unset($k, $v);
if (isset($_POST['partnerId'])) {
			//display_notification( __FILE__ . "::" . __LINE__ );
	list($k, $v) = each($_POST['partnerId']);
	if (isset($k) && isset($v)) {
		$Ajax->activate('doc_tbl');
	}
}

//SC: 05.10.2012: whether post['partnerType'] exists, refresh
if (isset($_POST['partnerType'])) {
	$Ajax->activate('doc_tbl');
}


start_form();

div_start('doc_tbl');
$custinv = array();

if (1) {
	//------------------------------------------------------------------------------------------------
	// this is filter table

	require_once( 'header_table.php' );
	$headertable = new ksf_modules_table_filter_by_date();
	$headertable->bank_import_header();

	//if (!@$_GET['popup'])
	//	end_form();


/*************************************************************************************************************/
/***********************************  Transactions  **********************************************************/
/*************************************************************************************************************/
	//------------------------------------------------------------------------------------------------
	// this is data display table
	$trzs = array();
	$vendor_list = get_vendor_list();	//array

	error_reporting(E_ALL);

	require_once( 'class.bi_transactions.php' );
	$bit = new bi_transactions_model();
	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$trzs = $bit->get_transactions( $_POST['statusFilter'] );
	}
	else
	{
		$trzs = $bit->get_transactions();
	}
	
/*************************************************************************************************************/
	start_table(TABLESTYLE, "width='100%'");
	table_header(array("Transaction Details", "Operation/Status"));

	//load data
	
	//This foreach loop should probably be rolled up into the WHILE loop above.
	foreach($trzs as $trz_code => $trz_data) 
	{
		//try to match something, interpreting saved info if status=TR_SETTLED
		//$minfo = doMatching($myrow, $coyBankAccounts, $custBankAccounts, $suppBankAccounts);
	
		//now group data from tranzaction
		$tid = 0;
		$cids = array();
	
		//bring trans details
		$has_trz = 0;
		$amount = 0;
		$charge = 0;
	
		foreach($trz_data as $idx => $trz) 
		{
//LOGIC ERROR?
//We are handling line items, but then ->display out of the loop?
//I assume this is for lines with charges, etc which could be in the MT940 format but not QFX and therefore I'm not seeing  an issue?
			require_once( 'class.bi_lineitem.php' );
			$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes );
		}	//foreach trz_data

		//cids is an empty array at this point.
		$cids = implode(',', $cids);
		$bi_lineitem->display();

 		//Now part of lineitem->display
               //$arr_arr = find_matching_existing( $trz, $bank );


	} //Foreach
	end_table();
/*************************************************************************************************************/
}

div_end();
end_form();

end_page(@$_GET['popup'], false, false);
?>
