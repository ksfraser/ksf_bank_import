<?php

use Ksfraser\FaBankImport\Handlers\AddVendor;

/*
require_once (__DIR__ . '/vendor/autoload.php' );
        $ofxParser = new OfxParser\Parser();
        $ofx = $ofxParser->loadFromString( $content );
        //$ofx = $ofxParser->loadFromFile('test.qfx');
*/

require_once( __DIR__ . '/../class.bi_transactions.php' );


/**//**************************************************
* This has been cloned into bi_controller
****************************************************/
function get_transaction($tid) 
{
	//display_notification( __FILE__ . "::" . __LINE__ );
	//error_reporting(E_ALL);
	$bit = new bi_transactions_model();
	//display_notification( __FILE__ . "::" . __LINE__ );
	return $bit->get_transaction( $tid );

/*
*	$sql = "
*	    SELECT t.*, s.account our_account FROM ".TB_PREF."bi_transactions t
*    	    LEFT JOIN ".TB_PREF."bi_statements as s ON t.smt_id = s.id
*	    WHERE t.id=".db_escape($tid);
*	$result = db_query($sql, "could not get transaction with id $tid");	
*	return db_fetch($result);
*/
}

/**//**************************************************************
* Retreive the counterparty on a transaction
*	Customer or Supplier
*
* @param int The transaction number
* @param int the Transaction Type (JE/BP/SP/...)
* @returns array list of matching counter parties
******************************************************************************/
function get_trans_counterparty( $trans_no, $trans_type )
{
	$arr = array();
	$result = get_gl_trans( $trans_type, $trans_no );
	//	display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $result, true ) );
	while ($myrow = db_fetch($result))
	{
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $myrow, true ) );
		//$counterpartyname = get_subaccount_name($myrow["account"], $myrow["person_id"]);
		//$counterparty_id = $counterpartyname ? sprintf(' %05d', $myrow["person_id"]) : '';
		$arr[] = $myrow;
	}
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $arr, true ) );
	return $arr;
}


/**//*************************************************************************
* Update bi_trans clearing status
*
*	If we had created the transaction, should we void it?
*
* @param int BI transaction index
* @param array list of related transactions
* @param int The transaction number
* @param int the Transaction Type (JE/BP/SP/...)
* @returns none
******************************************************************************/
function reset_transactions($tid, $cids, $trans_no, $trans_type) 
{
	$bit = new bi_transactions_model();
	return $bit->reset_transactions($tid, $cids, $trans_no, $trans_type);
}
/**//*************************************************************************
* Update bi_trans with the related info to FA gl transactions
*
*	Hooks pre_voidDB does similar
*
* @param int BI transaction index
* @param array list of related transactions
* @param int The status to set
* @param int The transaction number
* @param int the Transaction Type (JE/BP/SP/...)
* @param bool matched the transaction
* @param bool created the transaction
* @param string Which Trans Code e.g. QE/SP
* @param string which related entity e.g. 188 is Groceries
* @returns none
******************************************************************************/
function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner, $g_option) 
{
	$bit = new bi_transactions_model();
	return $bit->update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched, $created, $g_partner, $g_option);
}

/**//*************************************************************************
* Update bi_trans with the related info to FA gl transactions
*
* @param int BI transaction index
* @param string account
* @param string account Name
* @returns none
******************************************************************************/
function update_transactions_account($tid, $account, $accountName ) 
{
	$bit = new bi_transactions_model();
	return $bit->update_transactions_account($tid, $account, $accountName );
}

/**//***************************************************************************
*	Select bank info from 0_bank_accounts filtered by input ACCTID from OFX
*
* @param string
* @returns array
******************************************************************************/
function get_bank_account_by_acctid($acctid) 
{
	//display_notification( __FILE__ . "::" . __LINE__ );
	//var_dump( __FILE__ . "::" . __LINE__ );
	$sql = "SELECT a.*
		FROM ".TB_PREF."bank_accounts a 
		WHERE a.ACCTID = ".db_escape($acctid);
	//display_notification( __FILE__ . "::" . __LINE__ . "::" . $sql );
	//var_dump( __FILE__ . "::" . __LINE__ );
	$result = db_query($sql,"could not get bank account");
	return db_fetch($result);
}

/**//***************************************************************************
*	Select bank info from 0_bank_accounts filtered by input account number
*
* @param string
* @returns array
******************************************************************************/
function get_bank_account_by_number($account_number) 
{
	//display_notification( __FILE__ . "::" . __LINE__ );
	$sql = "SELECT a.*
		FROM ".TB_PREF."bank_accounts a 
		WHERE a.bank_account_number = '". trim($account_number) . "'";
		//WHERE a.bank_account_number = ".db_escape($account_number);
	//display_notification( __FILE__ . "::" . __LINE__ . "::" . $sql );
	$result = db_query($sql,"could not get bank account");
	return db_fetch($result);
}

/**//***************************************************
* Take care of adding a customer payment
*
*	sales/customer_payment.php does a whole bunch of things:
*		runs can_process (data checks)
*		makes sure the allocaiton doesn't have a transaction number (new payment)
*		runs write_customer_payment
*		updates the allocation->trans_no with the resulting payment number
*		Writes the allocation
*		Unsets the allocation
*
*	FA: 	write_customer_payment($_SESSION['alloc']->trans_no, $_POST['customer_id'], $_POST['BranchID'],
*       		$_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'],
*       		input_num('amount'), input_num('discount'), $_POST['memo_'], 0, input_num('charge'), input_num('bank_amount', input_num('amount')));
*
*	The function below is identical to the FA write_customer_payment (sales/includes/db/payment_db.inc)
*		except it uses ST_BANKDEPOSIT instead of ST_CUSTPAYMENT.
*
*	Bank Deposit does NOT have an allocation table on the edit screen.
*		Bank Deposits can be allocated on the Allocation screen.
*	Customer Payment DOES have the allocation table on the edit screen.
*
* @param int trans_no
* @param int customer_id
* @param int branch_id
* @param int bank_account
* @param date
* @param string ref
* @param float amount
* @param fload discount
* @param string memo
* @param float exchange rate
* @param float charge
* @param float bank amount
* @returns int payment number (for allocations)
**************************************************************/
function my_write_customer_payment($trans_no, $customer_id, $branch_id, $bank_account, $date_, $ref, $amount, $discount, $memo_, $rate=0, $charge=0, $bank_amount=0, $trans_type=ST_CUSTPAYMENT)
{
	global $Refs;

	//$trans_type_to_use = ST_BANKDEPOSIT;
	//$trans_type_to_use = ST_CUSTPAYMENT;	//Did this come from write_customer_payment?
	$trans_type_to_use = $trans_type;

	begin_transaction();
		$args = func_get_args(); while (count($args) < 12) $args[] = 0;
		$args = (object)array_combine(array('trans_no', 'customer_id', 'branch_id', 'bank_account', 
			'date_', 'ref', 'amount', 'discount', 'memo_','rate','charge', 'bank_amount'), $args);
		hook_db_prewrite($args, $trans_type_to_use);
	
		$company_record = get_company_prefs();
	
		if ($trans_no != 0) {
		display_warning( "Deleting comments, voiding bank transactions and voiding GL trans and cust allocations for TYPE:TRANS_NO - $trans_type_to_use::$trans_no ");
		  delete_comments($trans_type_to_use, $trans_no);
		  void_bank_trans($trans_type_to_use, $trans_no, true);
/**             void bank trans calls voif_gl_trans and void_cust_allocations, among others
                SEE Mantis 3108 for code from function
		  void_gl_trans($trans_type_to_use, $trans_no, true);
		  void_cust_allocations($trans_type_to_use, $trans_no, $date_);
*/
		}
	
		$bank = get_bank_account($bank_account);
	
		if (!$bank_amount) 	// backward compatibility workaround
		{
			if(!$rate)
				$rate = get_exchange_rate_from_to(get_customer_currency($customer_id), $bank['bank_curr_code'], $date_ );
			$bank_amount = $amount/$rate;
		}
	
		// do not use $rate here: global rate stored in exrate table is always used
		$payment_no = write_customer_trans($trans_type_to_use, $trans_no, $customer_id, $branch_id, $date_, $ref, $amount, $discount);
	
		$bank_gl_account = get_bank_gl_account($bank_account);
	
		$total = 0;
	
		/* Bank account entry first */
		$total += add_gl_trans($trans_type_to_use, $payment_no, $date_, $bank_gl_account, 0, 0, '', ($bank_amount - $charge),  $bank['bank_curr_code'], PT_CUSTOMER, $customer_id);
	
		if ($branch_id != ANY_NUMERIC) {
			$branch_data = get_branch_accounts($branch_id);
			$debtors_account = $branch_data["receivables_account"];
			$discount_account = $branch_data["payment_discount_account"];
		} else {
			$debtors_account = $company_record["debtors_act"];
			$discount_account = $company_record["default_prompt_payment_act"];
		}
	
		if (($discount + $amount) != 0)	{
		/* Now Credit Debtors account with receipts + discounts */
		$total += add_gl_trans_customer($trans_type_to_use, $payment_no, $date_,
			$debtors_account, 0, 0, -($discount + $amount), $customer_id,
			"Cannot insert a GL transaction for the debtors account credit");
		}
	
		if ($discount != 0)	{
			/* Now Debit discount account with discounts allowed*/
			$total += add_gl_trans_customer($trans_type_to_use, $payment_no, $date_,
				$discount_account, 0, 0, $discount, $customer_id,
				"Cannot insert a GL transaction for the payment discount debit");
		}
	
		if ($charge != 0)	{
			/* Now Debit bank charge account with charges */
			$charge_act = get_company_pref('bank_charge_act');
			$total += add_gl_trans($trans_type_to_use, $payment_no, $date_,	$charge_act, 0, 0, '', 
				$charge, $bank['bank_curr_code'], PT_CUSTOMER,  $customer_id);
		}
	
	
		/*Post a balance post if $total != 0 due to variance in AR and bank posted values*/
		if ($total != 0)
		{
			$variance_act = get_company_pref('exchange_diff_act');
			add_gl_trans($trans_type_to_use, $payment_no, $date_,	$variance_act, 0, 0, '',
				-$total, null, PT_CUSTOMER,  $customer_id);
		}
	
		/*now enter the bank_trans entry */
		add_bank_trans($trans_type_to_use, $payment_no, $bank_account, $ref,
			$date_, $bank_amount - $charge, PT_CUSTOMER, $customer_id);
	
		add_comments($trans_type_to_use, $payment_no, $date_, $memo_);
	
		//SC: that would be the change!!!
		$Refs->save($trans_type_to_use, $payment_no, $ref);
	
		$args->trans_no = $payment_no;
		hook_db_postwrite($args, $trans_type_to_use);
	commit_transaction();

	return $payment_no;
}

/**//****************************************************
* Add a Quick Entry transaction to cart
*
*	Looks like the original author copied and pasted this
*	from somewhere else, as it checks whether this is a SUPP INVOICE
*	which uses different function calls.  So far I've seen in the 
*	Bank Import module that it uses items_cart sp the checks 
*	are redundant.
*
*	gl_bank uses display_quick_entry from includes/ui/ui_view.php
*
*
*@params items_object cart
*@params int ID - Quick Entry index
*@params float BASE
*@params int TYPE
*@params string description
* @returns float bank_amount
***********************************************************/
function qe_to_cart(&$cart, $id, $base, $type, $descr='') 
{
	$bank_amount = 0;

    	if (!isset($id) || $id == null || $id == "") {
		display_error("No Quick Entries are defined.");
	} 
	else 
	{
		if ($type == QE_DEPOSIT)
		    $base = -$base;
		if ($type != QE_SUPPINV)	// only one quick entry on journal/bank transaction
		    $cart->clear_items();
		$qe = get_quick_entry($id);
	    	//display_notification( "Quick Entry : " . print_r( $qe, true ) );
	    	//display_notification( __LINE__ );
	
		if( $qe['bal_type'] == 1 ) 
		{
			display_notification( __LINE__ );
			if ($qe['base_amount'] == 1.0) // monthly
			{
				$begin	= begin_month($cart->tran_date);
		   		//	display_notification( "Quick Entry is a monthly type" );
			}
			else 
			{
				//display_notification(	__LINE__ );
				if( is_account_balancesheet( $qe['base_desc'] ) ) // total
				{
				  // 	display_notification( __LINE__ );
				   	$begin = "";
				}
				else
				{
		   		//	display_notification( __LINE__ );
				   	$begin = begin_fiscalyear(); // from fiscalyear begin
				}
			}
		   	//display_notification( __LINE__ );
			$base = get_gl_trans_from_to($begin, $cart->tran_date, $qe['base_desc']);
		   	//display_notification( "Base amount: " . $base );
		}
		if( $descr != '') 
		{
			$qe['description'] .= ': '.$descr;
		}
		//display_notification( __LINE__ );
		$result	= get_quick_entry_lines($id);
		//display_notification( __LINE__ . print_r( $result, true ) );
		if( db_num_rows($result) == 0 ) 
		{
			   display_error( _("No Quick Entry lines are defined."));
			   return 0;
		}
		$totrate = 0;
		while( $row = db_fetch($result) ) 
		{
			//display_notification( __LINE__ . print_r( $row, true ) );
			$qe_lines[] = $row;
		   	//display_notification( "Quick Entry ROWS: " . print_r( $qe_lines, true ) );
			switch (strtolower($row['action'])) 
			{
				case "t": // post taxes calculated on base amount
		   			//display_notification( __LINE__ );
				case "t+": // ditto & increase base amount
		   			//display_notification( __LINE__ );
				case "t-": // ditto & reduce base amount
		   			//display_notification( __LINE__ );
				   	if (substr($row['action'],0,1) != 'T') 
					{
			   			//display_notification( __LINE__ );
			   			//display_notification( "row: " . print_r( $row, true ) );
						try
						{
							//$totrate	+= get_tax_type_default_rate($row['dest_id']);
								//This is causing problems.
			   				//display_notification( __LINE__ . ": $totrate" );
						} catch( Exception $e )
						{
			   				//display_notification( __LINE__ );
						}
					}
			  // 		display_notification( __LINE__ );
				break;
			} //switch
		   	//display_notification( __LINE__ . ": Quick Entry ROWS: " . print_r( $qe_lines, true ) );
	
			$first = true;
			$taxbase = 0;
			foreach($qe_lines as $qe_line) 
			{
				if( isset( $qe_line['memo'] ) )
				{
					if( isset( $qe_line['description'] ) )
						$qe_line['description'] .= " " .  $qe_line['memo'];
					else
						$qe_line['description'] = $qe_line['memo'];
				}
				//display_notification( __LINE__ . ": QU_LINE: " . print_r( $qe_line, true ) );
				switch	(strtolower($qe_line['action'])) 
				{
					case	"=": // post current base amount to GL account
						$part	= $base;
						//display_notification(	__LINE__ );
					break;
					case	"a": // post amount to GL account and reduce base
						$part	= $qe_line['amount'];
						//display_notification(	__LINE__ );
					break;
					case	"a+": // post amount to GL account and increase base
						$part	= $qe_line['amount']; $base += $part;
						//display_notification(	__LINE__ );
					break;
					case	"a-": // post amount to GL account and reduce base
						$part	= $qe_line['amount']; $base -= $part;
						//display_notification(	__LINE__ );
					break;
					case	"%":	// store acc*amount% to GL account
						$part	= round2($base * $qe_line['amount'] / 100, user_price_dec());
						//display_notification(	__LINE__ );
					break;
					case	"%+":	// ditto & increase base amount
						$part	= round2($base * $qe_line['amount'] / 100, user_price_dec());
						$base	+= $part;
						//display_notification(	__LINE__ );
					break;
					case	"%-":	// ditto & reduce base amount
						$part	= round2($base * $qe_line['amount'] / 100, user_price_dec());
						$base	-= $part;
						//display_notification(	__LINE__ );
					break;
					case	"t": // post taxes calculated on base amount
					case	"t+": // ditto & increase base amount
					case	"t-": // ditto & reduce base amount
						if	($first) {
							$taxbase	= $base/($totrate+100);
							$first	= false;
						}	//if
			
						display_notification(	__LINE__ );
						if	(substr($qe_line['action'],0,1) != 'T') 
							$part	= $taxbase;
						else
							$part	= $base/100;
						$item_tax	= get_tax_type($qe_line['dest_id']);
						//display_notification(	__LINE__ );
					
						//if	($type == QE_SUPPINV && substr($qe_line['action'],0,1) != 'T')
						if	($type == QE_SUPPINV) 
						{
							$taxgroup	= $cart->tax_group_id;
							$rates	= 0;
							$res	= get_tax_group_rates($cart->tax_group_id);
							while	($row = db_fetch($res))
								$rates	+= $row['rate'];
							if	($rates == 0)
								continue	2;
						}
						display_notification(	__LINE__ );
						$tax	= round2($part * $item_tax['rate'],  user_price_dec());
						if	($tax==0)
						{
							display_notification(	__LINE__ );
							continue	2;
						}
						display_notification(	__LINE__ );
						$gl_code	= ($type == QE_DEPOSIT || ($type == QE_JOURNAL && $base < 0)) ? $item_tax['sales_gl_code'] : $item_tax['purchasing_gl_code'];
						if	(!is_tax_gl_unique($gl_code)) 
						{
					  			display_error("Cannot post to GL account used by more than one tax type.");
							break	2;
						}
						display_notification(	__LINE__ );
						if	($type != QE_SUPPINV)
							$cart->add_gl_item($gl_code,	$qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
						else	
						{
							display_notification(	__LINE__ );
							$acc_name	= get_gl_account_name($gl_code);
							$cart->add_gl_codes_to_trans($gl_code,	$acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
						}
						display_notification(	__LINE__ );
						if	(strpos($qe_line['action'], '+'))
							$base	+= $tax;
						elseif	(strpos($qe_line['action'], '-'))
							$base	-= $tax;
			   				//display_notification( "CART in SWITCH: " . print_r( $cart, true ) );
						continue	2;
					break;
					default:
							//display_notification( __LINE__ . " switch default" );
					break;
				}	//switch???
			   	//display_notification( __LINE__ . " CART before add: " . print_r( $cart, true ) );
//Mantis 3273?
//20250503 Moved foreach } down below to match whats in ui_view
				//display_notification(	__LINE__ );
				if( $type != QE_SUPPINV )
				{
				   		display_notification( __LINE__ . " Not Supplier Invoice. Adding GL items to cart. :" . print_r( $qe_line, true ) );
					//function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $date=null)
					$cart->add_gl_item($qe_line['dest_id'],	$qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description'], $qe['description']);
					//$cart->add_gl_item($qe_line['dest_id'],	$qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
				   		//display_notification( __LINE__ . ":" . print_r( $cart, true ) );
				}
				else	
				{
				   		//display_notification( __LINE__ );
					$acc_name	= get_gl_account_name($qe_line['dest_id']);
					$cart->add_gl_codes_to_trans($qe_line['dest_id'],	$acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
				}	//else
				   	//display_notification( __LINE__ );
			   	//display_notification( "CART after add: " . print_r( $cart, true ) );
//20250503 Moved foreach } from above to match whats in ui_view
			}	//Foreach
		}
    	}
    	return $bank_amount;
}

/**//**********************************************
* Add a Vendor from a Bank Statement
*
* @param class transaction
* @returns int supplier_id
**************************************************/
function add_vendor( $trz )
{
	$v = AddVendor( $trz );
	return $v->getId();
/***
*	require_once( dirname( __FILE__ ) . '/../../ksf_modules_common/class.fa_suppliers.php' );
*	$sup = new fa_suppliers();
*	$sup->init_values();
*	try {
*                if( strcasecmp( $trz['account'], "THE" ) !== 0 )
*		{
*			$sup->set( "supp_ref", $trz['account'], false );
*		}
*                else
*		{
*			$sup->set( "supp_ref", $trz['accountName'], false );
*		}
*		$sup->set( "supp_name", $trz['accountName'], false );
*		$sup->set( "bank_account", $trz['our_account'], false );
*		$sup->set( "address", $trz['address'], false );
*		$sup->set( "supp_address", $trz['address'], false );
*		$sup->set( "notes", "Created by Bank Import module as extended by Kevin Fraser", false );
*		display_notification( __LINE__ );
*	}
*	catch( Exception $e )
*	{
*		display_notification( __LINE__ . ":: Exception: " . print_r( $e, true ) );
*		if( KSF_FIELD_NOT_CLASS_VAR == $e->getCode() )
*		{
*			display_notification( __LINE__ . ":: " . print_r( $sup, true ) );
*		}
*		throw $e;
*	}
*	try {
*		display_notification( __LINE__ . ":: sup data: " . print_r( $sup, true ) );
*		$id = $sup->add_supplier();
*		display_notification( __LINE__ );
*	}
*	catch( Exception $e )
*	{
*		display_notification( __LINE__ . ":: Exception: " . print_r( $e, true ) );
*		throw $e;
*	}
*	return $id;
**/
}
/**//**********************************************
* Add a Customer from a Bank Statement
*
* @param class transaction
* @returns int customer_id (debtor)
**************************************************/
function my_add_customer( $trz )
{
	return -1;
//	//THIS ISN"T CODED YET!!!
//
//		//display_notification( __LINE__ . ":: Trz data: " . print_r( $trz, true ) );
//	require_once( dirname( __FILE__ ) . '/../../ksf_modules_common/class.fa_customers.php' );
//	$sup = new fa_customers();
//		//display_notification( __LINE__ . ":: Trz data: " . print_r( $trz, true ) );
//	$sup->init_values();
//		//display_notification( __LINE__ . ":: Trz data: " . print_r( $trz, true ) );
//	try {
//                if( strcasecmp( $trz['account'], "THE" ) !== 0 )
//		{
//		display_notification( __LINE__ . "::" . $trz['account'] );
//			$sup->set( "supp_ref", $trz['account'], false );
//		}
//                else
//		{
//		display_notification( __LINE__ . "::" . $trz['accountName'] );
//			$sup->set( "supp_ref", $trz['accountName'], false );
//		}
//		//display_notification( __LINE__ . "::" . $trz['account'] );
//		$sup->set( "supp_name", $trz['accountName'], false );
//		//display_notification( __LINE__ . "::" . $trz['accountName'] );
//		$sup->set( "bank_account", $trz['our_account'], false );
//		//display_notification( __LINE__ . "::" . $trz['address );
//		$sup->set( "address", $trz['address'], false );
//		//display_notification( __LINE__ . "::" . $trz['address'] );
//		$sup->set( "supp_address", $trz['address'], false );
//		//display_notification( __LINE__ . "::" . $trz['address );
//		$sup->set( "notes", "Created by Bank Import module as extended by Kevin Fraser", false );
//		display_notification( __LINE__ );
//	}
//	catch( Exception $e )
//	{
//		display_notification( __LINE__ . ":: Exception: " . print_r( $e, true ) );
//		if( KSF_FIELD_NOT_CLASS_VAR == $e->getCode() )
//		{
//			display_notification( __LINE__ . ":: " . print_r( $sup, true ) );
//		}
//		throw $e;
//	}
//	try {
//		display_notification( __LINE__ . ":: sup data: " . print_r( $sup, true ) );
//		$id = $sup->add_supplier();
//		display_notification( __LINE__ );
//	}
//	catch( Exception $e )
//	{
//		display_notification( __LINE__ . ":: Exception: " . print_r( $e, true ) );
//		throw $e;
//	}
//	return $id;
}


/**//**********************************************
* Get the list of existing vendors so we do it once
*
* @param none
* @returns array list of vendors
**************************************************/
function get_vendor_list()
{
	error_reporting(E_ALL);
	//display_notification( __FILE__ . "::" . __LINE__ );
	$vendor_list = array();
	try {
		//display_notification( __FILE__ . "::" . __LINE__ );
/*
		$s = include_once( dirname() . '/../class.fa_suppliers.php' );
		//require_once( '../../ksf_modules_common/class.fa_suppliers.php' );
		//display_notification( __FILE__ . "::" . __LINE__ );
		if( $s )
		{
*/
			//display_notification( __FILE__ . "::" . __LINE__ );
		$ret = include_once( __DIR__ . '/../../ksf_modules_common/class.fa_suppliers.php' );
			//display_notification( __FILE__ . "::" . __LINE__ );
			$sup = new fa_suppliers();
			//display_notification( __FILE__ . "::" . __LINE__ );
			$vendor_list = $sup->supplier_list( true );		//We want inactive vendors because we may have transactions with them to deal with
			//display_notification( __FILE__ . "::" . __LINE__ );
			//display_notification(  __FILE__ . "::" . __LINE__ . "::" . print_r( $vendor_list, true )  );
/*
		}
		else
		{
			display_notification( __FILE__ . "::" . __LINE__ );
		}
*/
	} catch( Throwable $e )
	{
			display_notification( __FILE__ . "::" . __LINE__  . " " . $e->getMessage() );
	}
	//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( debug_backtrace(), true ) );
	return $vendor_list;
}

/**//**********************************************
* Take a long name (e.g. The City of Airdrie) and return a shorter form
*
* THE is a common 1st word in a name.
* Chains often have a #1234 at the end of a name
* City's have "-" to differentiate departments
*
* @param string the name
* @returns string shorter form  
**************************************************/
function shorten_bankAccount_Names( $fullname )
{
/* */
	//display_notification( __FILE__ . "::" . __LINE__ );
	try {
		$ret = include_once( __DIR__ . '/../../ksf_modules_common/class.fa_suppliers.php' );
		if( $ret )
		{
			//display_notification( __FILE__ . "::" . __LINE__ );
			$sup = new fa_suppliers();
			return $sup->shortenName( $fullname, false );
		}
		else
		{
			$tokens = explode( " ", (string) $fullname );
			$shortname = "";
			foreach( $tokens as $token )
			{
			        switch( strtoupper( $token ) )
			        {
			                //case "THE":
			                //      continue;
			                case "AMZN":
			                case "AMAZON":
			                case "AMAZON*":
			                        $shortname .= "Amazon";
			                case "-":
			                case "*":
			                case "#":
			                        break 2;        //Should take us out of the foreach
			                default:
			                        $shortname .= " " . $token;
			                        break;
			        }
			}
			return $shortname;

			display_notification( __FILE__ . "::" . __LINE__ );
			return $fullname;
		}
	} catch( Exception $e )
	{
		display_notification( __FILE__ . "::" . __LINE__ . ":" . $e->getMessage() );
	}
}

/**//***************************************************************
* Find any transactions that alraedy exist that look like this one
*
* @param array Transaction Record to match
* @param array Bank info - the bank account the transaction was imported for
* @returns array GL record(s) that match
********************************************************************/
//function find_matching_existing( $trz, $bank = null )
function find_matching_existing( $trz )
{
                        //display_notification( __FILE__ . "::" . __LINE__ );
        //The transaction is imported into a bank account, with the counterparty being trz['accountName']
        //      Existing transactions will have 2+ line items.  1 should match the bank, one should match the counterparty.
        //      Currently we are matching and scoring each of the line items, rather than matching/scoring the GL itself.

        //Check for matching into the accounts
        // JE# / Date / Account / (Credit/Debit) / Memo in the GL Account (gl/inquiry/gl_account_inquiry.php)

        $inc = include_once( __DIR__ . '/../../ksf_modules_common/class.fa_gl.php' );
        if( $inc )
        {
                $fa_gl = new fa_gl();
                $fa_gl->transaction2me( $trz, 2 );
        //      $fa_gl = new \KSFRASER\FA\fa_gl();
                //Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
                //              E-TRANSFER 010667466304;CUSTOMER NAME;...
                //      function add_days($date, $days) // accepts negative values as well
                try {
                        $new_arr = $fa_gl->find_matching_transactions();
                                //display_notification( __FILE__ . "::" . __LINE__ );
                } catch( Exception $e )
                {
                        display_notification( $e->getMessage() );
                }
                                //display_notification( __FILE__ . "::" . __LINE__ );
        }
        else
        {
                display_notification( __FILE__ . "::" . __LINE__ . ": Require_Once failed." );
                $new_arr = array();
        }
        return $new_arr;
}

