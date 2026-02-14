<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :bank_import_controller [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for bank_import_controller.
 */
require_once( '../ksf_modules_common/class.origin.php' );
require_once( 'class.bi_transaction.php' );
//require_once( 'class.bi_transactions.php' );

class bank_import_controller extends origin
{
	protected $trz;	//!< transaction
	protected $cTransactions;	//!<object	bi_transactions
	protected $our_account;
	protected $reference;
	protected $partnerId;	
	protected $custBranch;
	protected $invoiceNo;
	protected $partnerType;
	protected $charges;
	protected $tid;
	protected $cCart;

	function __construct()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		$this->cTransactions = new bi_transaction();
		//$this->cTransactions = new bi_transactions_model();
		//display_notification( __FILE__ . "::" . __LINE__ );
		/*****	
		*	The way our code currently works
		*	Only one of the following should actually
		*	do anything.  However, just in case we change
		*	how our code works, not wrapping the _POST checks here...
		*/
		$action = "";
		if( isset( $_POST['UnsetTrans'] ) )
		{
			$action = "unsetTrans";
		} else
		if( isset( $_POST['AddCustomer'] ) )
		{
			$action = "AddCustomer";
		} else
		if( isset( $_POST['AddVendor'] ) )
		{
			$action = "AddVendor";
		} else
		if( isset( $_POST['ProcessTransaction'] ) )
		{
			$action = "ProcessTransaction";
		} else
		if( isset( $_POST['ToggleTransaction'] ) )
		{
			$action = "ToggleTransaction";
		} else
		{
		}
		if( strlen( $action ) > 0 )
		{
			//display_notification( __FILE__ . "::" . __LINE__ . " Code in place to call $action()" );
			//$this->$action();
		}

		//$this->unsetTrans();
		//$this->addCustomer();
		//$this->addVendor();
		//$this->processTransactions();
	}

	/**
	 * Display transaction links through shared SRP displayer.
	 * Auto-derived links are intentionally disabled here; callers must pass explicit URLs.
	 *
	 * @param array<string,mixed> $linkData
	 */
	private function displayTransactionLinks(array $linkData, ?int $transType = null, array $context = []): void
	{
		if (empty($linkData)) {
			return;
		}

		if (class_exists('\\Ksfraser\\FA\\Notifications\\TransactionLinkNotificationDisplayer')) {
			$linkDisplayer = new \Ksfraser\FA\Notifications\TransactionLinkNotificationDisplayer(null, false);
			$baseContext = [
				'context' => 'bank_import_controller',
			];
			$fullContext = array_merge($baseContext, $context);
			$linkDisplayer->displayFromResultData(
				$linkData,
				$transType,
				\Ksfraser\FA\Notifications\TransactionLinkNotificationDisplayer::MODE_NOTIFICATION,
				$fullContext
			);
			return;
		}

		if (function_exists('display_notification')) {
			foreach ($linkData as $label => $url) {
				if (!is_string($url) || trim($url) === '') {
					continue;
				}
				display_notification("<a target=_blank href='" . $url . "'>" . ucfirst(str_replace('_', ' ', (string)$label)) . "</a>");
			}
		}
	}

	private function displayMatchedSettlementWithLink(int $transType, int $transNo): void
	{
		$message = 'Transaction was MATCH settled ' . $transType . '::' . $transNo;
		$context = [
			'context' => 'matched_settlement',
		];

		if (class_exists('\\Ksfraser\\FA\\Notifications\\MatchedSettlementNotificationBuilder')) {
			$payload = \Ksfraser\FA\Notifications\MatchedSettlementNotificationBuilder::build($transType, $transNo);
			$message = (string)$payload['message'];
			$context = is_array($payload['context'] ?? null) ? $payload['context'] : $context;
		}

		display_notification($message);
		$this->displayGlTransViewLink($transType, $transNo, 'View Entry', $context);
	}

	private function displayGlTransViewLink(int $transType, int $transNo, string $label = 'View Entry', array $context = []): void
	{
		if (function_exists('display_notification') && class_exists('\\Ksfraser\\FA\\Notifications\\GlTransViewLinkHtmlBuilder')) {
			display_notification(\Ksfraser\FA\Notifications\GlTransViewLinkHtmlBuilder::build($transType, $transNo, $label));
			return;
		}

		$this->displayTransactionLinks([
			'view_gl_link' => $this->buildGlTransViewUrl($transType, $transNo),
		], $transType, $context);
	}

	private function buildGlTransViewUrl(int $transType, int $transNo): string
	{
		return \Ksfraser\FA\Notifications\TransactionLinkUrlBuilder::glTransView($transType, $transNo);
	}

	private function buildFaAbsoluteUrl(string $appRelativePath): string
	{
		$normalizedPath = ltrim($appRelativePath, '/');

		$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
		if (!is_string($host) || $host === '') {
			return '../../' . $normalizedPath;
		}

		$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
		$scheme = $isHttps ? 'https' : 'http';

		$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
		$scriptDir = is_string($scriptName) ? str_replace('\\', '/', dirname($scriptName)) : '';
		$scriptDir = rtrim($scriptDir, '/');

		$appBase = preg_replace('#/modules(?:/.*)?$#', '', $scriptDir);
		if (!is_string($appBase)) {
			$appBase = '';
		}
		$appBase = rtrim($appBase, '/');

		return $scheme . '://' . $host . $appBase . '/' . $normalizedPath;
	}

	private function buildAttachmentDocumentUrl(int $transType, int $transNo): string
	{
		$attachmentPath = 'admin/attachments.php?filterType=' . $transType . '&trans_no=' . $transNo;
		if (class_exists('\\Ksfraser\\FA\\Notifications\\AttachmentLinkUrlBuilder')) {
			$attachmentPath = \Ksfraser\FA\Notifications\AttachmentLinkUrlBuilder::appRelativePath($transType, $transNo);
		}

		return $this->buildFaAbsoluteUrl($attachmentPath);
	}

	private function buildSupplierAllocateUrl(int $transType, int $transNo, int $supplierId): string
	{
		return '../../' . \Ksfraser\FA\Notifications\SupplierAllocateLinkUrlBuilder::appRelativePath($transType, $transNo, $supplierId);
	}
	/**//***********************************************
	*
	* @param string field name to set
	* @param string value to set
	* @param bool should we only set internal fields
	* @returns none currently
	****************************************************/
	function set( $field, $value = null, $enforce = true )
	{
		switch( $field )
		{
			case $tid:
				parent::set( $field, $value, $enforce );
				$this->extractPost();	
				$this->getTransaction($this->tid);
			break;
			default:
				parent::set( $field, $value, $enforce );
			break;
		}
	}
	/**//***********************************************
	*
	* @returns bool was there an error extracting from POST
	*****************************************************/
	function extractPost()
	{
		$bi_t = new bi_transaction();
//20241208
		$bPartnerIdSet = $bi_t->set( "extractPost",  $this->tid );	//Get details from _POST for this transaction
		//check params
		if( ! $bPartnerIdSet )
		{
			$Ajax->activate('doc_tbl');
			display_error('missing partnerId');
			return true;
		}
		else
		{
			$this->set( "partnerId",  $bi_t->get( "partnerId" ) );
			$this->set( "custBranch",  $bi_t->get( "custBranch" ) );
			$this->set( "invoiceNo",  $bi_t->get( "invoiceNo" ) );
			$this->set( "partnerType",  $bi_t->get( "partnerType" ) );
			return false;
		}
	}
	
	/**//***********************************************
	* Get transaction details.  Set ->set( "trz
	*
	* @param int transaction ID from within _POST
	* @returns array trasaction, sets internal
	****************************************************/
	function getTransaction( $id )
	{
		//display_notification( __FILE__ . "::" . __LINE__ );

		//require_once( __DIR__ . '/../class.bi_transactions.php' );
		//original - $bit = new bi_transactions_model(); return $bit->get_transaction( $tid );
		$this->trz = $this->cTransactions->get_transaction( $id, true );
			//->trz is array
			//->cTransactions has values from this ID
		//display_notification( print_r( $this->trz, true )  );

		//From processCustomer but should apply everywhere!
		if( strlen( $this->trz['transactionTitle'] ) < 4 )
		{
			if( strlen( $this->trz['memo'] ) > 0 )
			{
				$this->trz['transactionTitle'] .= " : " . $this->trz['memo'];
			}
		}
		//for backward compatibility
		return $this->trz;
	}
	/**//***********************************************
	* Unset any transactions needing it
	*
	****************************************************/	
	function unsetTrans()
	{
		if( isset( $_POST['UnsetTrans'] ) )
		{
			//display_notification( "Disassociate " . print_r( $_POST['UnsetTrans'], true ) );
			foreach( $_POST['UnsetTrans'] as $key => $value )
			{
				//display_notification( "Key/Value " . print_r( $key, true ) . ":" . print_r( $value, true ) );
				//value is "Unset Transaction"
				$cids = array();	//Need to figure out if there are related IDs being passed in too in the _POST
				$this->cTransactions->reset_transactions($key, $cids, 0, 0 );
				display_notification( "Disassociated $unset from $id"  );
			}
		}
	}
	/**//***********************************************
	* Toggle Debit/Credit for a transaction
	*
	****************************************************/
	function toggleDebitCredit()
	{
		display_notification( __FILE__ . "::" . __LINE__ );
		if( isset( $_POST['ToggleTransaction'] ) ) 
		{
			display_notification( __FILE__ . "::" . __LINE__ );
			foreach( $_POST['ToggleTransaction'] as $key => $value )
			{
				//Array ( [43958] => ToggleTransaction )
			 	//display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $_POST['ToggleTransaction'], true )  );
			 	//display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $key, true )  );
			 	//display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $value, true )  );
				
				//we can't use cTransactions - bi_transaction - because it is an overriding stub
				display_notification( __FILE__ . "::" . __LINE__ );
				$cTransactions = new bi_transactions_model();
				display_notification( __FILE__ . "::" . __LINE__ );
				$cTransactions->get_transaction( $key, true );	//retrieve the transaction
			 	display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $cTransactions, true )  );
				try {
					$cTransactions->toggleDebitCredit();	//changes internal variables only
			 		display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $cTransactions, true )  );
					//$sql =  $cTransactions->hand_update_sql();
					//db_query( $sql, "Couldn't toggle C/D for transaction" );
				} catch (Exception $e )
				{
			 		display_notification( __FILE__ . "::" . __LINE__ . "::" .  print_r( $e, true )  );
				}
				
			}
		}
		else
		{
			display_notification( __FILE__ . "::" . __LINE__ );
		}
		display_notification( __FILE__ . "::" . __LINE__ . " Exit" );
	}
	/**//***********************************************
	* Create any new customer from a transaction
	*
	****************************************************/	
	function addCustomer()
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		if( isset( $_POST['AddCustomer'] ) ) 
		{
		    	//display_notification( __FILE__ . "::" . __LINE__ );
			foreach( $_POST['AddCustomer'] as $key => $value )
			{
				//display_notification( __FILE__ . "::" . __LINE__ );
			 	//display_notification( print_r( $_POST['AddCustomer'], true )  );
			 	//display_notification( print_r( $key . "::" . $_POST["vendor_short_$key"]  . "::" . $_POST["vendor_long_$key"], true )  );
			 	$trz = $this->getTransaction($key);	//originally get_transaction($key)
					//also sets this->trz
				// display_notification( __FILE__ . "::" . __LINE__ );
				$custid = my_add_customer( $trz );
				if( $custid > 0 )
				{
					      //display_notification( __FILE__ . "::" . __LINE__ );
					display_notification( "Created Customer ID $custid"  );
				} else
				{
					//display_notification( __FILE__ . "::" . __LINE__ );
					display_warning( "Failedto create a Customer"  );
				}
			}
		}
		//display_notification( __FILE__ . "::" . __LINE__ );

	}
	/**//***********************************************
	* Create a new vendor from a transaction
	*
	****************************************************/	
	function addVendor()
	{
		if (isset($_POST['AddVendor'])) {
			foreach( $_POST['AddVendor'] as $key => $value )
			{
				 //display_notification( print_r( $_POST['AddVendor'], true )  );
				 //display_notification( print_r( $key . "::" . $_POST["vendor_short_$key"]  . "::" . $_POST["vendor_long_$key"], true )  );
				$trz = $this->getTransaction($key);	//originally get_transaction($key)
					//also sets this->trz
				$vendid = add_vendor( $trz );
				if( $vendid > 0 )
				{
					display_notification( "Created Supplier ID $vendid"  );
				} else
				{
					display_warning( "Failed to create Supplier"  );
				}
			}
		}

	}
	/**//*********************************************************
	* Sum up charges for the transaction
	*
	* @param int Transaction ID from _POST
	* @returns float sum of Charges
	***************************************************************/
	function sumCharges( $tid )
	{
//20241208 Do we need to keep tid for back compatability, or can we use ->tid?
		 //display_notification( __FILE__ . "::" . __LINE__ );
		//get charges
		$chgs = array();
		$charge = 0;
		//display_notification("tid=$this->tid, cids=`".$_POST['cids'][$tid]."`");
		$_cids = array_filter(explode(',', $_POST['cids'][$tid]));
		//display_notification("cids_array=".print_r($_cids,true));

		foreach($_cids as $cid) {
			$this->getTransaction( $cid );
			//now sum up
			$charge += $this->trz['transactionAmount'];
		}
		//display_notification("amount=$this->trz['transactionAmount'], charge=$charge");
		return $charge;
	}
	/**//**********************************************************
	* Get next New Ref
	*
	***************************************************************/
	function getNewRef( $transType )
	{
//20241208 Do we need to keep transType for back compatability, or can we use ->transType?
//	This should be inherited since it isn't this class specific
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		global $Refs;
		do {
			$reference = $Refs->get_next($transType);
		} while(!is_new_reference($reference, $transType));
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		$this->reference = $reference;
		return $reference;
	}
/**//*************************************************************************
* Update bi_trans with the related info to FA gl transactions
*
*       Hooks pre_voidDB does similar
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
function update_transactions($tid, $cids, $status, $trans_no, $transType, $matched = 0, $created = 0, $g_partner, $g_option)
{
        $bit = new bi_transactions_model();
        return $bit->update_transactions($tid, $cids, $status, $trans_no, $transType, $matched, $created, $g_partner, $g_option);
}
/**//*********************************************************************************
* Update partner data
*
* @param int
* @returns none
******************************************************************************************/
function update_partner_data( $partner_detail_id  = ANY_NUMERIC) 
{
	require_once( 'includes/pdata.inc' );
	update_partner_data( $this->partnerId, $this->partnerType, $partner_detail_id, $this->trz['account']);
}

	/**//**********************************************************
	* Create the CART class as needed by some routines
	*
	***************************************************************/	
	function generateCart()
	{
		$this->cCart = new items_cart($this->transType);
		$this->cCart->order_id = 0;
		$this->cCart->original_amount = $this->trz['transactionAmount'] + $charge;
		$reference = $this->getNewRef( $this->transType );
		$this->cCart->tran_date = sql2date($this->trz['valueTimestamp']);
		/** date of cart should be date of transaction
		*if (!is_date_in_fiscalyear($this->cCart->tran_date))
		*{
		*	$this->cCart->tran_date = end_fiscalyear();
		*}
		*/
	}

	/**//**********************************************************
	* Process Supplier Transaction
	*
	**************************************************************/
	function processSupplierTransaction()
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		if( !isset( $this->trz ) )
		{
				display_error( "->trz not set" );
			throw new Exception( "->trz not set", KSF_VAR_NOT_SET );
		}
		if( !isset( $this->partnerId ) )
		{
				display_error( "->partnerId not set" );
			throw new Exception( "->partnerId not set", KSF_VAR_NOT_SET );
		}
		if( !isset( $this->our_account ) )
		{
				display_error( "->our_account not set" );
			throw new Exception( "->our_account not set", KSF_VAR_NOT_SET );
		}
		if( !isset( $this->charge ) )
		{
				display_error( "->charge not set" );
			throw new Exception( "->charge not set", KSF_VAR_NOT_SET );
		}
		if( !isset( $this->tid ) )
		{
				display_error( "->tid not set" );
			throw new Exception( "->tid not set", KSF_VAR_NOT_SET );
		}
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		$trans_no = 0;  //NEW.  A number would be an update - leads to voiding of a bunch of stuff and then redo-ing.
		$this->partnerType = PT_SUPPLIER;
		if( $this->trz['transactionDC'] == 'D' )
		{
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
			//Normal SUPPLIER PAYMENT
			$this->transType = ST_SUPPAYMENT;
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
			$reference = $this->getNewRef( $this->transType );
			if( !isset( $reference ) )
			{
				display_error( "Didn't acquire new Reference" );
				throw new Exception( "Didn't acquire new Reference", KSF_VAR_NOT_SET );
			}
		//display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		
			//write_supp_payment calls hooks
			//	voids existing payments if trans_no != 0
			//	write_supp_trans
			//	add_gl_trans_supplier
			//	add_gl_trans (multiple times)(bank charge, discount, etc)
			//	add_comments
			//purchasing/includes/db/supp_payment_db.inc
					// write_supp_payment($trans_no, $supplier_id, $bank_account, $date_, $ref, $supp_amount, $supp_discount, $memo_, $bank_charge=0, $bank_amount=0)
			$payment_id = write_supp_payment( $trans_no, $this->partnerId, $this->our_account['id'], sql2date($this->trz['valueTimestamp']), $reference, 
								user_numeric($this->trz['transactionAmount']), 0, $this->trz['transactionTitle'], user_numeric($this->charge), 0);
			display_notification("payment_id = $payment_id");
			/***/
			//update trans with payment_id details
			if ($payment_id) 
			{
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
				$counterparty_arr = get_trans_counterparty( $payment_id, $this->transType );
				//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				/***/
				$this->update_transactions($this->tid, $_cids, $status=1, $payment_id, $this->transType, false, true,  "SP", $this->partnerId );
				$this->update_partner_data( null );	//Suppliers don't have branches
				display_notification('Supplier Payment Processed:' . $payment_id );
				//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
				//display_notification("<a target=_blank href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
				$this->displayTransactionLinks([
					'view_payment_link' => $this->buildGlTransViewUrl((int)$this->transType, (int)$payment_id),
					'allocate_link' => $this->buildSupplierAllocateUrl((int)$this->transType, (int)$payment_id, (int)$this->partnerId),
				], (int)$this->transType);
			}
		}
		else if( $this->trz['transactionDC'] == 'C' )
		{
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
			//FA Native creates this as a Supplier Credit Note -> BANK DEPOSIT
			//http://fhsws002.ksfraser.com/infra/accounting/gl/view/gl_deposit_view.php?trans_no=4
			//vs
			//http://fhsws002.ksfraser.com/infra/purchasing/view/view_supp_payment.php?trans_no=183
			//Needs to be a BANK DEPOSIT in order for the payment to be recognized for allocation.
			// gl/gl_bank.php?NewDeposit=Yes
		
			    //SUPPLIER REFUND
			$this->transType = ST_BANKDEPOSIT;
			$payment_id = 0;

			$this->trz['transactionAmount'] = $this->trz['transactionAmount'] * -1;	//should we be ABS() the RHS in case a bank sends as negative
			$this->cCart = new items_cart($this->transType);
			$this->cCart->order_id = $trans_no;			//Could we associate against an invoice to credit things??

			//Whe NEW_DOC_DATE?  Shouldn't this be the transaction date?
			//$this->cCart->tran_date = new_doc_date();		
			//if (!is_date_in_fiscalyear($this->cCart->tran_date))	
			//{
			//	$this->cCart->tran_date = end_fiscalyear();
			//}
			$this->cCart->tran_date = sql2date($this->trz['valueTimestamp']); 
			$this->cCart->reference = $reference = $this->getNewRef( $this->transType );
			//display_notification("Reference = $reference");

			while (count($args) < 10) $args[] = 0;
			$args = (object)array_combine( array( 'trans_no', 'supplier_id', 'bank_account', 'date_', 'ref', 'bank_amount', 'supp_amount', 'supp_discount', 'memo_', 'bank_charge'), $args);

		/* */
			//$supplier_accounts = get_supplier_accounts($this->partnerId);
			$supplier_accounts = get_supplier($this->partnerId);  //Does this give us the dimensions?
			$this->cCart->add_gl_item(
				$supplier_accounts["payable_account"],
				$supplier_accounts["dimension_id"],
				$supplier_accounts["dimension2_id"],
				user_numeric($this->trz['transactionAmount']),
				$this->trz['transactionTitle']
			);

			if ( $this->cCart->count_gl_items() < 1) 
			{
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
				display_error(_("You must enter at least one payment line."));
			}
			if ( $this->cCart->gl_items_total() == 0.0) 
			{
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
				display_error(_("The total bank amount cannot be 0."));
			}
	
			//write_bank_trans returns an array with element 0 = trans_type, and 1 = trans_no
			//write_bank_transactions calls pre and post hooks.
			//	write_customer_trans IF PT_CUSTOMER (not this case here)
			//	write_supplier_trans IF PT_SUPPLIER
			//	add_bank_trans
			//	add_gl_trans
			//	add_exchange_variation
			//	add_gl_tax_details
			//	add_audit_trail
			//	add_comments
			//	../../gl/includes/db/gl_db_banking.inc
			$payment_id = write_bank_transaction( $this->cCart->trans_type, $this->cCart->order_id, $our_account['id'], $this->cCart, sql2date( $this->trz['valueTimestamp'] ), $this->partnerType, $this->partnerId,
								ANY_NUMERIC, $this->cCart->reference, $this->trz['transactionTitle'], true, number_format2(abs( $this->cCart->gl_items_total() ) ));

			//update trans with payment_id details
			if ($payment_id)
			{
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
				/***/
				$counterparty_arr = get_trans_counterparty( $payment_id, $this->transType );
				display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				/***/
				$this->update_transactions($this->tid, $_cids, $status=1, $payment_id[1], $this->transType, false, true,  "SP", $this->partnerId );
				$this->update_partner_data( null );
				display_notification('Supplier Refund Processed:' . print_r( $payment_id, true ) );
				//While we COULD attach to a Supplier Payment, we don't see them in the P/L drill downs.  More valuable to attach to the related Supplier Invoice
				//display_notification("<a target=_blank href='http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType=" . ST_PAYMENT . "&trans_no=" . $payment_id . "'>Attach Document</a>" );
				$this->displayTransactionLinks([
					'view_gl_link' => $this->buildGlTransViewUrl((int)$this->transType, (int)$payment_id[1]),
				], (int)$this->transType);
			}
		display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
		    }
		else 
		{
			display_notification( __FILE__ . "::" . __LINE__ . "::" . __METHOD__);
			display_warning( __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . " transactionDC not D nor C: " . $this->trz['transactionDC'] );
		}
	}
	/**//********
	*
	*************/
	function processCustomerPayment()
	{
		display_notification( __FILE__ . "::" . __LINE__ . "Index passed in (processTransaction from post): " . $this->tid );
		display_notification( __FILE__ . "::" . __LINE__ . "Invoice for this Index: " . $_POST['Invoice_$this->tid'] );
		//20240211 Works.  Not sure why BANKDEPOSIT vice CUSTPAYMENT in original module.
		//$this->transType = ST_BANKDEPOSIT;
		$this->transType = ST_CUSTPAYMENT;
	
		//insert customer payment into database
		$reference = $this->getNewRef( $this->transType );
						//20240304 The BRANCH doesn't seem to get selected though.
		/** Mantis 3018
			We are trying to allocate Customer Payments against a specific invoice
				Should we be setting trans_no?   It is currently NULL.
				partnerId is being set right before the opening of this switch statement
		
		/var/www/html/infra/accounting/modules/bank_import/process_statements.php::376::
			Array (
				[vendor_short_33038] => WENDY'S MCKNIGHT
				[vendor_long_33038] => WENDY'S MCKNIGHT
				[partnerType] => Array (
					[33038] => SP
					[33053] => CU
					[35723] => BT
				[partnerId_33038] => 308
				[cids] => Array (
					[33038] =>
					[33043] =>
					[33053] =>
					[32838] => )
				[vendor_id] => 29
				[Invoice] => 0
		------------
				[vendor_short_35643] =>
				[vendor_long_35643] =>
				[partnerId_35643] => 108
				[partnerDetailId_35643] => 128
				[ProcessTransaction] => Array (
					[35643] => Process )
				[_focus] => TransAfterDate
		*/
		
		//WARNING WARNING WARNING
		//If trans_no is set, the function tries to void/delete that trans number as if it's an update!!!
		$deposit_id = my_write_customer_payment(
				$trans_no = 0, $this->partnerId, $this->custBranch, $this->our_account['id'],
				sql2date($this->trz['valueTimestamp']), $reference, user_numeric($this->trz['transactionAmount']),
				0, $this->trz['transactionTitle'], 0, user_numeric($this->charge), 0, $this->transType);
		display_notification( __FILE__ . "::" . __LINE__ . "::" . "Deposit ID: " . $deposit_id );
		//update trans with payment_id details
		if ($deposit_id) {
			display_notification("Invoice Number and Deposit Number: $this->invoiceNo :: $deposit_id ");
			if( $this->invoiceNo )
			{
				$counterparty_arr = get_trans_counterparty( $deposit_id, $this->transType );
				display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
				$fcp = new fa_customer_payment( $this->partnerId );
				$fcp->set( "trans_date", $valueTimestamp );
				$fcp->set( "trans_type", $this->transType );
				$fcp->set( "payment_id", $deposit_id );
				$fcp->write_allocation();
			}
			update_transactions($this->tid, $_cids, $status=1, $deposit_id, $this->transType, false, true,  "CU", $this->partnerId);
			//We want to update fa_trans_type, fa_trans_no, account/accountName, status, matchinfo, matched/created, g_partner
			update_partner_data($this->partnerId, $this->transType, $this->custBranch, $this->trz['memo']);
			if( $this->transType !== PT_CUSTOMER )
				update_partner_data($this->partnerId, PT_CUSTOMER, $this->custBranch, $this->trz['memo']);
			display_notification('Customer Payment/Deposit processed');
			$this->displayGlTransViewLink((int)$this->transType, (int)$deposit_id);
		}
	}
	/**//**
	* search for bank account info
	*
	* @returns bool whether the info was in error
	*************/
	function retrieveOurAccount()
	{
		//check bank account
		$this->our_account = fa_get_bank_account_by_number($this->trz['our_account']);
		if (empty($this->our_account))
		{
			$Ajax->activate('doc_tbl');
			display_error('the bank account <b>'.$this->trz['our_account'].'</b> is not defined in Bank Accounts');
			return true;
		}
		return false;
	}
	function ProcessTransaction()
	{
		return $this->processTransactions();
	}
	/**//**********************************************************
	* Process transactions
	*
	**************************************************************/
	function processTransactions()
	{
		if ( ! isset( $_POST['ProcessTransaction'] ) ) {
			return;
		}
		//display_notification( __LINE__ . "::" .  print_r( $_POST, true ));

		//20240208 EACH is depreciated.  Should rewrite with foreach
		list($this->tid, $v) = each($_POST['ProcessTransaction']);      //K is index.  V is "process/..."
		if(isset($this->tid) && isset($v) && isset($_POST['partnerType'][$this->tid]))
		{
			$error = 0;
			$bError = $this->extractPost();
				//time to gather data about transaction
				$this->getTransaction($this->tid);
				$bError = $this->retrieveOurAccount();
			if ( ! $bError ) 
			{
				$this->charge = $this->sumCharges( $this->tid );
				switch(true)
				{
						/*************************************************************************************************************/
						//TODO:
						//      See if there is a Purchase Order with the right total.  If so, convert to Invoice
						//      See if there is an invoice with the right date and total.  If so Allocate the Payment.
						//      If there isn't, then create a Purchase Order.
						//	      I want to write a "recurring order" similar to on the Sales side.
						//		      i.e. Walmart is almost always groceries.
						//		       No Frills R is Pharmacy
						//		       Nissan is always truck
						//      Auto match Vendor (from CC data) to Suppliers.
						//	      Auto Create a supplier if doesn't exist
						//		      We get name, address, etc from CC statements.
					case ( $partnerType == 'SP' ):
						$this->processSupplierTransaction();
					break;
			/*************************************************************************************************************/
						//TODO:
						//      Match customers to records
						//	      i.e. E-Transfer from XXYYY (CIBC statements)
					case ($_POST['partnerType'][$this->tid] == 'CU' && $this->trz['transactionDC'] == 'C'):
						$this->processCustomer();
					break;
			/*************************************************************************************************************/
					case ($_POST['partnerType'][$this->tid] == 'QE'):
						$this->transType = ($this->trz['transactionDC'] == 'D')?ST_BANKPAYMENT:ST_BANKDEPOSIT;
						$this->generateCart();
							//this loads the QE into cart!!!
						try {
		
							//function qe_to_cart(&$cart, $id, $base, $type, $descr='')
							$qe_memo = "A:" . $this->our_account['bank_account_name'] . ":" . $this->trz['account_name'] . " M:" . $this->trz['account'] . ":" . $this->trz['transactionTitle'] . ": " . $this->trz['transactionCode'];
							$rval = qe_to_cart($cart, $this->partnerId, $this->trz['transactionAmount'], ($this->trz['transactionDC']=='C') ? QE_DEPOSIT : QE_PAYMENT, $qe_memo );
						} catch( Exception $e )
						{
							display_notification('RVAL Exception' . print_r( $e, true ) );
						}
						// function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $memo='', $act_descr=null, $person_id=null, $date=null)
			//TODO:
			//      Config which account to log these in
			//      Conig whether to log these.
						$this->cCart->add_gl_item( '0000', 0, 0, 0.01, 'TransRef::'.$this->trz['transactionCode'], "Trans Ref");
						$this->cCart->add_gl_item( '0000', 0, 0, -0.01, 'TransRef::'.$this->trz['transactionCode'], "Trans Ref");
						$total = $this->cCart->gl_items_total();
						if ($total != 0)
						{
							//need to add the charge to the cart
							$this->cCart->add_gl_item(get_company_pref('bank_charge_act'), 0, 0, $this->charge, 'Charge/'.$this->trz['transactionTitle']);
							//process the transaction
							begin_transaction();
		
							$trans = write_bank_transaction(
								$this->cCart->trans_type, $this->cCart->order_id, $this->our_account['id'],
								$this->cCart, sql2date($this->trz['valueTimestamp']),
									PT_QUICKENTRY, $this->partnerId, 0,
									$this->cCart->reference, $qe_memo, true, null);
									//$this->cCart->reference, $this->trz['transactionTitle'], true, null);
		
							$counterparty_arr = get_trans_counterparty( $trans[1], $this->transType );
							//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
						/***
							Array (	 [0] =>
									Array (	 [counter] => 171128
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
									[1] => Array (  ... )
									[2] => Array ( ...  )
									[3] => Array ( ...  )
							)
								//partnerID in this case is the QE index.
							set_bank_partner_data( $this->our_account['id'], $this->transType, $this->partnerId, $this->trz['transactionTitle'] );
						***/
							update_transactions($this->tid, $_cids, $status=1, $trans[1], $this->transType, false, true, "QE", $this->partnerId );
							commit_transaction();
							//Don't want this preventing the commit!
							set_bank_partner_data( $this->our_account['id'], $this->transType, $this->partnerId, $this->trz['transactionTitle'] );
							//ST_BANKPAYMENT or ST_BANKDEPOSIT
		
							//Let User attach a document
							$this->displayTransactionLinks(['attach_document_link' => $this->buildAttachmentDocumentUrl((int)$this->transType, (int)$trans[1])], (int)$this->transType);
							//Let the user view the created transaction
							$this->displayGlTransViewLink((int)$this->transType, (int)$trans[1]);
		
		
							}
						else
						{
							display_notification('CART4B ' . print_r( $this->cCart, true ) );
							display_notification("QE not loaded: rval=$rval, k=$this->tid, total=$total");
							//display_notification("debug: <pre>".print_r($_POST, true).'</pre>');
							}
					break;
			/*************************************************************************************************************/
					case ($_POST['partnerType'][$this->tid] == 'BT'):
						$inc = require_once( '../ksf_modules_common/class.fa_bank_transfer.php' );
						if( $inc )
						{
							$bttrf = new fa_bank_transfer();
							try
							{
								$bttrf->set( "trans_type", ST_BANKTRANSFER );
								if( $this->trz['transactionDC'] == 'C' OR $this->trz['transactionDC'] == 'B' )
								{
									//display_notification( __LINE__ . " :: " . print_r( $this->our_account, true )  );
									$bttrf->set( "ToBankAccount", $this->our_account['id'] );
									$pid = 'partnerId_' . $this->tid;
									//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
									$bttrf->set( "FromBankAccount", $_POST[$pid] );
								}
								else
								if( $this->trz['transactionDC'] == 'D' )
								{
									//On a Debit, the bank accounts are reversed.
									//display_notification( __LINE__ . " :: " . print_r( $this->our_account, true )  );
									$bttrf->set( "FromBankAccount", $this->our_account['id'] );
									$pid = 'partnerId_' . $this->tid;
									//display_notification( __LINE__ . " :: " . print_r( $_POST[$pid], true )  );
									$bttrf->set( "ToBankAccount", $_POST[$pid] );
								}
								$bttrf->set( "amount", $this->trz['transactionAmount'] );
								$bttrf->set( "trans_date", $this->trz['valueTimestamp'] );
								$bttrf->set( "memo_", $this->trz['transactionTitle'] . "::" . $this->trz['transactionCode'] . "::" . $this->trz['memo'] );
								$bttrf->set( "target_amount", $this->trz['transactionAmount'] );
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
							$this->transType = $bttrf->get( "trans_type" );
							update_transactions( $this->tid, $_cids, $status=1, $trans_no, $this->transType, false, true,  "BT", $this->partnerId );
							//update_transactions( $this->tid, $_cids, $status=1, $bttrf->get( "trans_no" ), $bttrf->get( "trans_type" ), false, true );
		
							set_bank_partner_data( $bttrf->get( "FromBankAccount" ), $bttrf->get( "trans_type" ), $bttrf->get( "ToBankAccount" ), $this->trz['memo'] );   //Short Form
										//memo/transactionTitle holds the reference number, which would be unique :(
							commit_transaction();
							$this->displayGlTransViewLink((int)$this->transType, (int)$trans_no);
						}
						else
						{
								//display_notification( __LINE__  );
						}
					break;
			/*************************************************************************************************************/
					case ($_POST['partnerType'][$this->tid] == 'MA'):
						$counterparty_arr = get_trans_counterparty( $_POST['Existing_Entry'], $_POST['Existing_Type'] );
							display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
						update_transactions($this->tid, $_cids, $status=1, $_POST['Existing_Entry'], $_POST['Existing_Type'], true, false, null, "" );
						$this->displayGlTransViewLink((int)$_POST['Existing_Type'], (int)$_POST['Existing_Entry']);
						set_partner_data( $counterparty_arr['person_type'], $_POST['Existing_Type'], $counterparty_arr['person_type_id'], $this->trz['memo'] );       //Short Form
						display_notification("Transaction was manually settled " . print_r( $_POST['Existing_Type'], true ) . ":" . print_r( $_POST['Existing_Entry'], true ) );
					break;
			/*************************************************************************************************************/
						//TODO:
						//      *When the Match score is too low, switching to MATCH still gets overwritten the next ajax load
						//      *Test what happens if there are 3+ matches
						//	      right now it doesn't auto match, because we don't have a way to select the trans type/number
						//	      Sort by scoring.  Go with highest?
						//20240214 Matching Works.  As long as score is high enough, can "process".
					case ($_POST['partnerType'][$this->tid] == 'ZZ'):
						//display_notification("Entry Matched against an existing Entry (LE/Cp/SP/...)");
						//display_notification(__FILE__ . "::" . __LINE__ . ":" . " Trans Type and No: ".print_r( $_POST["trans_type_$this->tid"], true) . ":" . print_r( $_POST["trans_no_$this->tid"], true ) );
							$counterparty_arr = get_trans_counterparty( $_POST["trans_no_$this->tid"], $_POST["trans_type_$this->tid"] );
						//display_notification( __FILE__ . "::" . __LINE__ . print_r( $counterparty_arr, true ) );
						if( isset( $_POST["memo_$this->tid"] ) AND strlen ($_POST["memo_$this->tid"]) > 0 )
						{
							$memo = $_POST["memo_$this->tid"];
						}
						else
						if( isset( $_POST["title_$this->tid"] ) AND strlen ($_POST["title_$this->tid"]) > 0 )
						{
							$memo = $_POST["title_$this->tid"];
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
							update_transactions( $this->tid, $_cids, $status=1, $_POST["trans_no_$this->tid"], $_POST["trans_type_$this->tid"], true, false,  "ZZ", $this->partnerId );
							//display_notification(__FILE__ . "::" . __LINE__  );
							$this->displayMatchedSettlementWithLink(
								(int)$_POST["trans_type_$this->tid"],
								(int)$_POST["trans_no_$this->tid"]
							);
						set_partner_data( $person_type, $_POST["trans_type_$this->tid"], $person_type_id, $memo );
							//display_notification(__FILE__ . "::" . __LINE__  );
					break;
					} // end of switch
					$Ajax->activate('doc_tbl');
				} //end of if !error
		
			} // end of is set....
	}
}
