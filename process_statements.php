<?php

$path_to_root = "../..";
$page_security = 'SA_SALESTRANSVIEW';
include_once( __DIR__  . "/vendor/autoload.php");
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


	include_once "Views/module_menu_view.php"; // Include the ModuleMenuView class
    	$menu = new \Views\ModuleMenuView();
    	$menu->renderMenu(); // Render the module menu

$optypes = array(
	'SP' => 'Supplier',
	'CU' => 'Customer',
	'QE' => 'Quick Entry',
	'BT' => 'Bank Transfer',
	'MA' => 'Manual settlement',
	'ZZ' => 'Matched',
);

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");	//$trans_types_readable

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
if ( isset( $_POST['ProcessTransaction'] ) ) {
//20240208 EACH is depreciated.  Should rewrite with foreach
	list($k, $v) = each($_POST['ProcessTransaction']);	//K is index.  V is "process/..."
	if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) 
	{
		//check params
		$error = 0;
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
		        $bit = new bi_transactions_model();
        		$trz = $bit->get_transaction( $tid );
				//Setting internal so we can refactor further later to use Object rather than Array
        			//$trz = $bit->get_transaction( $tid, true );

			//check bank account
			$our_account = get_bank_account_by_number($trz['our_account']);
			if (empty($our_account)) 
			{
				$Ajax->activate('doc_tbl');
				display_error(  __FILE__ . "::" . __LINE__ . "::" . ' the bank account <b>'.$trz['our_account'].'</b> is not defined in Bank Accounts');
				$error = 1;
			}
		}
		if (!$error) {
/*Charges*/
			//get charges
			$chgs = array();
//How are CIDS set in the first place?
			$_cids = array_filter(explode(',', $_POST['cids'][$tid]));
			foreach($_cids as $cid) {
				$chgs[] = get_transaction($cid);
			}
			//display_notification("tid=$tid, cids=`".$_POST['cids'][$tid]."`");
			//display_notification("cids_array=".print_r($_cids,true));
	
		//now sum up
		//now group data from tranzaction
			$amount = $trz['transactionAmount'];
/**
*			$charge = 0;
*			foreach($chgs as $t) 
*			{
*				$charge += $t['transactionAmount'];
*			}
*/
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
*/
	//TODO:
	//	check that we have partnerId	
					//display_notification( __FILE__ . "::" . __LINE__ );


			try {
/*
*					$deposit_id = $fcp->write_customer_payment();
*					$invoice_no = $_POST['Invoice_$k'];
*					display_notification("Invoice Number and Deposit Number: $invoice_no :: $deposit_id ");
*/

				$invoice_no = $_POST['Invoice_' . $k];
				$amount = user_numeric($trz['transactionAmount']);
				$deposit_id = my_write_customer_payment(
					$trans_no = 0, $customer_id=$partnerId, $branch_id=$_POST["partnerDetailId_$k"], $bank_account=$our_account['id'],
					$date_ = sql2date($trz['valueTimestamp']), $reference, $amount,
					$discount=0, $trz['transactionTitle'] . "::" . $_POST['comment_' . $tid], $rate=0, user_numeric($charge), $bank_amount=0, $trans_type);
				if( $invoice_no )
				{
//sales/allocations/customer_allocate.php?trans_no=521&trans_type=12&debtor_no=108
//$alloc = new allocation($_GET['trans_type'], $_GET['trans_no'], @$_GET['debtor_no'], PT_CUSTOMER);
//$alloc->write();
						// /sales/includes/db/custalloc_db.inc
                        		add_cust_allocation($amount, ST_CUSTPAYMENT, $deposit_id, ST_SALESINVOICE, $invoice_no, $customer_id, $date_);
                        		update_debtor_trans_allocation(ST_SALESINVOICE, $invoice_no, $customer_id);
                        		update_debtor_trans_allocation(ST_CUSTPAYMENT, $deposit_id, $customer_id);
				}


					//update trans with payment_id details
					if( $deposit_id )
					{
						//Alolocate payment against an invoice
						$counterparty_arr = get_trans_counterparty( $deposit_id, $trans_type );
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

			break;
	/*************************************************************************************************************/
			case ($_POST['partnerType'][$k] == 'QE'):
				$trans_type = ($trz['transactionDC'] == 'D')?ST_BANKPAYMENT:ST_BANKDEPOSIT;
				//gl_bank uses items_cart from includes/ui/items_cart.inc
				//gl_bank creates the cart using ST_BANKPAYMENT.  But later a check on that uses QE_DEPOSIT or QE_PAYMENT
				//  Then Refs, tran_date, checks in fiscal year
				// Then sets _POST fr memo, ref, date, and sets _SESSION['pay_items_ to &cart
				$cart = new items_cart($trans_type);
					$cart->order_id = 0;
					$cart->original_amount = $trz['transactionAmount'] + $charge;
					do {
						$cart->reference = $Refs->get_next($cart->trans_type);
					} while(!is_new_reference($cart->reference, $cart->trans_type));
					$cart->tran_date = sql2date($trz['valueTimestamp']);
				//While if I am up todate with the books this should be fine,
				//what about catching up on historical?  Should be throw an error and a link
				//to set the date?
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
				//gl_bank uses handle_new_item to call cart->add_gl_item but that's within the edit screen, which we don't have.
				//GL_BANK PERSON_ID is the index of which QE was seleteded.  totamount is the master dollar amount.
					$qe_memo = $_POST['comment_' . $tid] . " A:" . $our_account['bank_account_name'] . ":" . $trz['account_name'] . " M:" . $trz['account'] . ":" . $trz['transactionTitle'] . ": " . $trz['transactionCode'];
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
					update_transactions($tid, $_cids, $status=1, $trans[1], $trans_type, false, true, "QE", $partnerId );
					commit_transaction();
					//Don't want this preventing the commit!
					set_bank_partner_data( $our_account['id'], $trans_type, $partnerId, $trz['transactionTitle'] );
					
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
                                                if( $trz['transactionDC'] ==  'B' )
                                                {
                                                        $pid = 'partnerId_' . $tid;
                                                        if( $trz['transactionCodeDesc'] ==  'CREDIT' )
                                                        {
                                                                $bttrf->set( "ToBankAccount", $our_account['id'] );
                                                                $bttrf->set( "FromBankAccount", $_POST[$pid] );
                                                        }
                                                        else
                                                        {
                                                                $bttrf->set( "FromBankAccount", $our_account['id'] );
                                                                $bttrf->set( "ToBankAccount", $_POST[$pid] );
                                                        }
                                                }
                                                else
                                                if( $trz['transactionDC'] == 'C' )
						{
							$bttrf->set( "ToBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							$bttrf->set( "FromBankAccount", $_POST[$pid] );
						}
						else
						if( $trz['transactionDC'] == 'D' )
						{
							//On a Debit, the bank accounts are reversed.
							$bttrf->set( "FromBankAccount", $our_account['id'] );
							$pid = 'partnerId_' . $tid;
							$bttrf->set( "ToBankAccount", $_POST[$pid] );
						}
						$bttrf->set( "amount", $trz['transactionAmount'] );
						$bttrf->set( "trans_date", $trz['valueTimestamp'] );
//$_POST['comment_' . $tid]
						$bttrf->set( "memo_", $_POST['comment_' . $tid] . " :: " . $trz['transactionTitle'] . "::" . $trz['transactionCode'] . "::" . $trz['memo'] );
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
	/*
	*	//now group data from tranzaction
	*	$tid = 0;
	*	$cids = array();
	*
	*	//bring trans details
	*	$has_trz = 0;
	*	$amount = 0;
	*	$charge = 0;
	*/
	
		require_once( 'class.bi_lineitem.php' );
		foreach($trz_data as $idx => $trz) 
		{
			//LOGIC ERROR?
				//We are handling line items, but then ->display out of the loop?
				//I assume this is for lines with charges, etc which could be in the MT940 format but not QFX and therefore I'm not seeing  an issue?
			$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes );
		}	//foreach trz_data
	/*
	*	//cids is an empty array at this point.
	*	$cids = implode(',', $cids);
	*/
		$bi_lineitem->display();
	} //Foreach
	end_table();
/*************************************************************************************************************/
}

div_end();
end_form();

end_page(@$_GET['popup'], false, false);
?>
