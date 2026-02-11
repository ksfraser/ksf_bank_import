<?php
	$trzs = array();
	$vendor_list = get_vendor_list();	//array

	error_reporting(E_ALL);

	$sql = " SELECT t.*, s.account our_account, s.currency from " . TB_PREF . "bi_transactions t LEFT JOIN " . TB_PREF . "bi_statements as s ON t.smt_id = s.id";
	$sql .= " WHERE t.valueTimestamp >= '" . date2sql( $_POST['TransAfterDate'] ) . "' AND t.valueTimestamp < '" . date2sql( $_POST['TransToDate'] ) . "'";
	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$sql .= "  AND t.status = '" . $_POST['statusFilter'] . "'";
	}
	$sql .= " ORDER BY t.valueTimestamp ASC";

	try 
	{
		$res = db_query($sql, 'unable to get transactions data'); 
		//The following shows how many rows/columns of results there are but without doint the fetch, just the mysql_results object.
		//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $res, true ) );
	} catch( Error $e )
	{
			display_notification( __FILE__ . "::" . __LINE__ . " " . $e->getMessage() );
	}

class transaction_table
{
	//The table is made up of a series of rows
	protected $transaction_table_rows;	//!<array
	/**//**************************************************
	*
	* @param mysql_res
	* @returns none
	*******************************************************/
	function __construct( $mysql_res )
	{
		$this->transaction_table_rows = array();
		$this->res2arr( $mysql_res );
	}
	/**//************************************************
	* Take the query result and build classes
	*
	* @param mysql_res
	* @returns none builds internal array
	****************************************************/
	function res2arr( $mysql_res )
	{
		while($myrow = db_fetch($res)) 
		{
			$this->transaction_table_rows[] = new transaction_table_row( $myrow );
		}
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		// Use HtmlTable instead of hardcoded HTML
		$table = new \Ksfraser\HTML\Elements\HtmlTable();
		$table->setClass('tablestyle');
		$table->setWidth('100%');
		
		table_header(array("Transaction Details", "Operation/Status"));
		foreach( $this->transaction_table_rows as $row )
		{
			$row->display();	
		}
		
		echo $table->closeTable();
	}
}

class transaction_table_row
{
	/**//**************************************************
	*
	* @param mysql_row db_fetch'd row (array)
	* @returns none
	*******************************************************/
	function __construct( $res_arr )
	{
		 	$this->transactionDC = $res_arr['transactionDC'];
			$this->our_account = $res_arr['our_account'];
			$this->valueTimestamp = $res_arr['valueTimestamp'];
			try {
				$this->bankAccount = shorten_bankAccount_Names( $res_arr['accountName'] );
			}
			catch( Exception $this->e )
			{
				display_notification( __FILE__ . "::" . __LINE__ . ":" . $this->e->getMessage() );
				$this->bankAccount = $res_arr['accountName'];
			}
			$this->bankAccountName = $res_arr['accountName'];
			if( strlen( $res_arr['transactionTitle'] ) < 4 )
			{
				if( strlen( $res_arr['memo'] ) > strlen( $res_arr['transactionTitle'] ) )
				{
					$res_arr['transactionTitle'] .= $res_arr['memo'];
				}
			}
			$this->transactionTitle = $res_arr['transactionTitle'];
			$this->currency = $res_arr['currency'];
			$this->status = $res_arr['status'];
			$this->tid = $res_arr['id'];
			$this->fa_trans_type = $res_arr['fa_trans_type'];
			$this->fa_trans_no = $res_arr['fa_trans_no'];
			if ($res_arr['transactionType'] != 'COM') {
				$this->has_trans = 1;
				$this->amount = $res_arr['transactionAmount'];
			} else if ($res_arr['transactionType'] == 'COM') 
			{
				$this->amount += $res_arr['transactionAmount'];
			}
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
	}
}

/**//**********************
*
*
* I feel like I'm reinventing my own wheel here!!!
* Probably from my VIEW classes.
*
*************************/
class ttr_table
{
	protected $style;
	protected $width;	//!<int percentage
	/**//*************
	*
	* @param int the style definition
	* @param int The percentage width
	function __construct( $style, width )
	{	
		$this->style = $style;
		$this->width = $width
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		// Use standalone HTML instead of FA's start_table() - for independence from FA
		return '<table class="' . strtolower($this->style) . '" width="' . $this->width . '%">';
	}
	
}
class ttr_label_row
{
	protected $data;	//!<string
	function __construct( $data )
	{
		$this->data = $data;
	}
	/**//************************************************
	* Convert our data into an output HTML
	*
	* @param none
	* @return HTML
	*****************************************************/
	function display()
	{
		return label_row( $this->data );
	}
}
	


/*************************************************************************************************************/

	//load data
	while($myrow = db_fetch($res)) 
	{
		//display_notification( __FILE__ . "::" . __LINE__ );
		$trz_code = $myrow['transactionCode'];
		if( !isset( $trzs[$trz_code] ) ) 
		{
				$trzs[$trz_code] = array();
		}
		$trzs[$trz_code][] = $myrow;
	}
	
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
	
////////////
		$cids = implode(',', $cids);


		// Use HtmlTableRow and HtmlTableCell instead of hardcoded HTML
		$mainRow = new \Ksfraser\HTML\Elements\HtmlTableRow();
		echo $mainRow->openRow();
		
		$leftCell = new \Ksfraser\HTML\Elements\HtmlTableCell();
		$leftCell->setWidth('50%');
		echo $leftCell->openCell();
		
		// Use HtmlTable instead of hardcoded HTML
		$detailsTable = new \Ksfraser\HTML\Elements\HtmlTable();
		$detailsTable->setClass('tablestyle2');
		$detailsTable->setWidth('100%');
		echo $detailsTable->openTable();
		
		// Use HtmlLabelRow instead of label_row()
		$labelText = new \Ksfraser\HTML\Elements\HtmlString("Trans Date (Event Date):");
		$valueText = new \Ksfraser\HTML\Elements\HtmlString($valueTimestamp . " :: (" . $trz['entryTimestamp'] . ")");
		$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($labelText, $valueText);
		$labelRow->toHtml();
		
		// Replace label_row with HtmlLabelRow for transaction type
		$transTypeLabel = new \Ksfraser\HTML\Elements\HtmlString("Trans Type:");
		switch( $transactionDC )
		{
			case 'C':
				$transTypeContent = new \Ksfraser\HTML\Elements\HtmlString("Credit");
			break;
			case 'D':
				$transTypeContent = new \Ksfraser\HTML\Elements\HtmlString("Debit");
			break;
			case 'B':
				$transTypeContent = new \Ksfraser\HTML\Elements\HtmlString("Bank Transfer");
			break;
		}
		$transTypeRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($transTypeLabel, $transTypeContent);
		$transTypeRow->toHtml();
		
		$bank = fa_get_bank_account_by_number( $our_account );
			//Info from 0_bank_accounts
			//	Account Name
			//	Type
			//	Currency
			//	GL Account
			//	Bank
			//	Number
			//	Address
		//var_dump( $bank );
		/*
			Array ( [account_code] => 1061
				[account_type] => 0
				[bank_account_name] => CIBC Savings account 
				[bank_account_number] => 00449 12-93230 
				[bank_name] => CIBC 
				[bank_address] => 
				[bank_curr_code] => CAD 
				[dflt_curr_act] => 1 
				[id] => 1 
				[bank_charge_act] => 5690 
				[last_reconciled_date] => 0000-00-00 00:00:00 
				[ending_reconcile_balance] => 0 
				[inactive] => 0 )
		*/
		
		// Replace label_row calls with HtmlLabelRow
		$bankAcctLabel = new \Ksfraser\HTML\Elements\HtmlString("Our Bank Account - (Account Name)(Number):");
		$bankAcctContent = new \Ksfraser\HTML\Elements\HtmlString($our_account . ' - ' . $bank['bank_name'] . " (" . $bank['bank_account_name'] . ")(" . $bank['account_code'] . ")");
		$bankAcctRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($bankAcctLabel, $bankAcctContent);
		$bankAcctRow->toHtml();
		
		$otherAcctLabel = new \Ksfraser\HTML\Elements\HtmlString("Other account:");
		$otherAcctContent = new \Ksfraser\HTML\Elements\HtmlString($bankAccount . ' / '. $bankAccountName);
		$otherAcctRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($otherAcctLabel, $otherAcctContent);
		$otherAcctRow->toHtml();
		
		$amountLabel = new \Ksfraser\HTML\Elements\HtmlString("Amount/Charge(s):");
		$amountContent = new \Ksfraser\HTML\Elements\HtmlString($amount.' / '.$charge." (".$currency.")");
		$amountRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($amountLabel, $amountContent);
		$amountRow->toHtml();
		
		$titleLabel = new \Ksfraser\HTML\Elements\HtmlString("Trans Title:");
		$titleContent = new \Ksfraser\HTML\Elements\HtmlString($transactionTitle);
		$titleRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($titleLabel, $titleContent);
		$titleRow->toHtml();
		
		if( ! in_array(  trim($bankAccount), $vendor_list['shortnames'] ) )
		{
	
			//display_notification( __FILE__ . "::" . __LINE__ . "::" . " Looked for: //" . trim($bankAccount) . "// but didn't fint it.  :: " . print_r( $vendor_list['shortnames'], true )  );
			
			// Replace submit button with HtmlSubmit
			$addVendorLabel = new \Ksfraser\HTML\Elements\HtmlString(_("AddVendor"));
			$addVendorButton = new \Ksfraser\HTML\Elements\HtmlSubmit($addVendorLabel);
			$addVendorButton->setName("AddVendor[$tid]");
			$addVendorButton->setClass("default");
			
			$addVendorLabelText = new \Ksfraser\HTML\Elements\HtmlString("Add Vendor");
			$addVendorRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($addVendorLabelText, $addVendorButton);
			$addVendorRow->toHtml();
			
			//label_row("Add Customer", submit("AddCustomer[$tid]",_("AddCustomer"),false, '', 'default'));
			
			// Replace hidden() with HtmlHidden
			$hiddenVendorShort = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_short_$tid", $bankAccount);
			$hiddenVendorShort->toHtml();
			
			$hiddenVendorLong = new \Ksfraser\HTML\Elements\HtmlHidden("vendor_long_$tid", $bankAccountName);
			$hiddenVendorLong->toHtml();
			
			$matched_supplier = null;
		}
		else
		{
			//IS INARRAY
			$matched_vendor = array_search( trim($bankAccount), $vendor_list['shortnames'], true );
			$matched_supplier = $vendor_list[$matched_vendor]['supplier_id'];
			
			// Replace hidden() with HtmlHidden
			$hiddenVendorId = new \Ksfraser\HTML\Elements\HtmlHidden('vendor_id', $matched_vendor);
			$hiddenVendorId->toHtml();
			
			// Replace label_row with HtmlLabelRow
			$matchedVendorLabel = new \Ksfraser\HTML\Elements\HtmlString("Matched Vendor");
			$matchedVendorContent = new \Ksfraser\HTML\Elements\HtmlString(print_r( $matched_vendor, true ) . "::" . print_r( $vendor_list[$matched_vendor]['supplier_id'], true ) . "::" . print_r( $vendor_list[$matched_vendor]['supp_name'], true ));
			$matchedVendorRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($matchedVendorLabel, $matchedVendorContent);
			$matchedVendorRow->toHtml();
			//label_row("Add Vendor", submit("AddVendor[$tid]",_("AddVendor"),false, '', 'default'));
		}
		echo $detailsTable->closeTable(); // Close the tablestyle2 table
		$arr_arr = find_matching_existing( $trz, $bank );

		echo $leftCell->closeCell();
		
		$rightCell = new \Ksfraser\HTML\Elements\HtmlTableCell();
		$rightCell->setWidth('50%');
		$rightCell->setVAlign('top');
		echo $rightCell->openCell();
		
		// Use HtmlTable instead of hardcoded HTML
		$operationsTable = new \Ksfraser\HTML\Elements\HtmlTable();
		$operationsTable->setClass('tablestyle2');
		$operationsTable->setWidth('100%');
		echo $operationsTable->openTable();
		//now display stuff: forms and information

/*************************************************************************************************************/
		if ($status == 1) 
		{
			// the transaction is settled, we can display full details
			$statusLabel = new \Ksfraser\HTML\Elements\HtmlString("Status:");
			$statusContent = new \Ksfraser\HTML\Elements\HtmlString("<b>Transaction is settled!</b>");
			$statusRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($statusLabel, $statusContent);
			$statusRow->toHtml();
			
			switch ($trz['fa_trans_type']) 
			{
				case ST_SUPPAYMENT:
					$opLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
					$opContent = new \Ksfraser\HTML\Elements\HtmlString("Payment");
					$opRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($opLabel, $opContent);
					$opRow->toHtml();
					
					// get supplier info
					$supplierLabel = new \Ksfraser\HTML\Elements\HtmlString("Supplier:");
					$supplierContent = new \Ksfraser\HTML\Elements\HtmlString($minfo['supplierName']);
					$supplierRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($supplierLabel, $supplierContent);
					$supplierRow->toHtml();
					
					$bankLabel = new \Ksfraser\HTML\Elements\HtmlString("From bank account:");
					$bankContent = new \Ksfraser\HTML\Elements\HtmlString($minfo['coyBankAccountName']);
					$bankRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($bankLabel, $bankContent);
					$bankRow->toHtml();
				break;
				case ST_BANKDEPOSIT:
					$opLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
					$opContent = new \Ksfraser\HTML\Elements\HtmlString("Deposit");
					$opRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($opLabel, $opContent);
					$opRow->toHtml();
					
					//get customer info from transaction details
					$fa_trans = get_customer_trans($fa_trz_no, $fa_trz_type);
					$custLabel = new \Ksfraser\HTML\Elements\HtmlString("Customer/Branch:");
					$custContent = new \Ksfraser\HTML\Elements\HtmlString(get_customer_name($fa_trans['debtor_no']) . " / " . get_branch_name($fa_trans['branch_code']));
					$custRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($custLabel, $custContent);
					$custRow->toHtml();
				break;
				case 0:
					$opLabel = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
					$opContent = new \Ksfraser\HTML\Elements\HtmlString("Manual settlement");
					$opRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($opLabel, $opContent);
					$opRow->toHtml();
				break;
				default:
					$otherLabel = new \Ksfraser\HTML\Elements\HtmlString("Status:");
					$otherContent = new \Ksfraser\HTML\Elements\HtmlString("other transaction type; no info yet " . print_r( $trz, true ));
					$otherRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($otherLabel, $otherContent);
					$otherRow->toHtml();
					$pid = $trz['id'];
				break;
			}
			
			// Replace submit button with HtmlSubmit
			$unsetLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Unset Transaction $fa_trans_no"));
			$unsetButton = new \Ksfraser\HTML\Elements\HtmlSubmit($unsetLabel);
			$unsetButton->setName("UnsetTrans[$pid]");
			$unsetButton->setClass("default");
			
			$unsetLabelText = new \Ksfraser\HTML\Elements\HtmlString("Unset Transaction Association");
			$unsetRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($unsetLabelText, $unsetButton);
			$unsetRow->toHtml();
	/*************************************************************************************************************/
		} else {	
			//transaction NOT settled
			// this is a new transaction, but not matched by routine so just display some forms
			if( $transactionDC == 'C' )
				$oplabel = "Depost";
			else
				$oplabel = "Payment";
	
			if( !isset( $_POST['partnerType'][$tid] ) ) 
			{
				switch( $transactionDC )
				{
					case 'C':
							$_POST['partnerType'][$tid] = 'CU';
						$oplabel = "Depost";
					break;
					case 'D':
							$_POST['partnerType'][$tid] = 'SP';
						$oplabel = "Payment";
					break;
					case 'B':
							$_POST['partnerType'][$tid] = 'BT';
						$oplabel = "Bank Transfer";
					break;
				}
			}
	/**/
			if( count( $arr_arr ) > 0 )
			{
				//Rewards (points) might have a split so only 1 count
				//	Walmart
				//	PC Points
				//	Gift Cards (Restaurants, Amazon)
				//but everyone else should have 2
				
				if( count( $arr_arr ) < 3 )
				{
				
					//We matched some JEs
					if( 50 <= $arr_arr[0]['score'] )
					{
						//var_dump( __LINE__ );
						//It was an excellent match
						if( $arr_arr[0]['is_invoice'] )
						{
							//This TRZ is a supplier payment
							//that matches an invoice exactly.
								$_POST['partnerType'][$tid] = 'SP';
						}
						else
								$_POST['partnerType'][$tid] = 'ZZ';
						$oplabel = "MATCH";
						
						// Replace hidden() with HtmlHidden
						$hiddenTransType = new \Ksfraser\HTML\Elements\HtmlHidden("trans_type_$tid", $arr_arr[0]['type']);
						$hiddenTransType->toHtml();
						
						$hiddenTransNo = new \Ksfraser\HTML\Elements\HtmlHidden("trans_no_$tid", $arr_arr[0]['type_no']);
						$hiddenTransNo->toHtml();
						
					}
					else
						var_dump( __LINE__ );
				}
				else
				{
					//If there are 3+ then we need to sort by score and take the highest scored item!
				}
			}
	/**/
			// Replace label_row with HtmlLabelRow
			$opLabelText = new \Ksfraser\HTML\Elements\HtmlString("Operation:");
			$opContent = new \Ksfraser\HTML\Elements\HtmlString($oplabel);
			$opRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($opLabelText, $opContent);
			$opRow->toHtml();
			
			//label_row("Operation:", (($transactionDC=='C') ? "Deposit" : "Payment"), "width='25%' class='label'");
			
			// Use HtmlSelect directly for array_selector (SOLID: data already in array)
			$partnerSelect = new \Ksfraser\HTML\Elements\HtmlSelect("partnerType[$tid]");
			$partnerSelect->addOptionsFromArray($optypes, $_POST['partnerType'][$tid]);
			$partnerSelect->setAttribute('onchange', 'this.form.submit()'); // select_submit option
			
			$partnerLabelText = new \Ksfraser\HTML\Elements\HtmlString("Partner:");
			$partnerContent = new \Ksfraser\HTML\Elements\HtmlString($partnerSelect->getHtml());
			$partnerRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($partnerLabelText, $partnerContent);
			$partnerRow->toHtml();
	
	/*************************************************************************************************************/
		//3rd cell
			if ( !isset( $_POST["partnerId_$tid"] ) ) 
			{
				$_POST["partnerId_$tid"] = '';
			}
	
			switch($_POST['partnerType'][$tid]) {
			//supplier payment
			case 'SP':
				//propose supplier
				if (empty($_POST["partnerId_$tid"])) {
				$match = search_partner_by_bank_account(PT_SUPPLIER, $bankAccount);
				if (!empty($match)) {
					$_POST["partnerId_$tid"] = $match['partner_id'];
				}
				}
				//			 supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
	
				// Use SupplierDataProvider with HtmlSelect (SOLID: separated data from presentation)
				$supplierProvider = new \Ksfraser\SupplierDataProvider();
				$supplierSelectHtml = $supplierProvider->generateSelectHtml("partnerId_$tid", $matched_supplier);
				
				$paymentToLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Payment To:"));
				$supplierContent = new \Ksfraser\HTML\Elements\HtmlString($supplierSelectHtml);
				$paymentToRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($paymentToLabel, $supplierContent);
				$paymentToRow->toHtml();
				break;
			//customer deposit
			case 'CU':
				//propose customer
				if (empty($_POST["partnerId_$tid"])) {
					$match = search_partner_by_bank_account(PT_CUSTOMER, $bankAccount);
					if (!empty($match)) {
					$_POST["partnerId_$tid"] = $match['partner_id'];
					$_POST["partnerDetailId_$tid"] = $match['partner_detail_id'];
					}
				}
				
				// Use CustomerDataProvider with HtmlSelect (SOLID: separated data from presentation)
				$customerProvider = new \Ksfraser\CustomerDataProvider();
				$customerSelectHtml = $customerProvider->generateCustomerSelectHtml("partnerId_$tid", $_POST["partnerId_$tid"] ?? null);
				
				// Check if customer has branches and add branch selector
				if (!empty($_POST["partnerId_$tid"]) && count($customerProvider->getBranches($_POST["partnerId_$tid"])) > 0) {
					$branchSelectHtml = $customerProvider->generateBranchSelectHtml($_POST["partnerId_$tid"], "partnerDetailId_$tid", $_POST["partnerDetailId_$tid"] ?? null);
					$cust_text = $customerSelectHtml . $branchSelectHtml;
				} else {
					// Replace hidden() with HtmlHidden
					$hiddenPartnerDetailId = new \Ksfraser\HTML\Elements\HtmlHidden("partnerDetailId_$tid", ANY_NUMERIC);
					ob_start();
					$hiddenPartnerDetailId->toHtml();
					$hiddenHtml = ob_get_clean();
					$cust_text = $customerSelectHtml . $hiddenHtml;
					$_POST["partnerDetailId_$tid"] = ANY_NUMERIC;
				}
				
				// Display customer/branch selector
				$customerLabel = new \Ksfraser\HTML\Elements\HtmlString(_("From Customer/Branch:"));
				$customerContent = new \Ksfraser\HTML\Elements\HtmlString($cust_text);
				$customerRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($customerLabel, $customerContent);
				$customerRow->toHtml();
				//label_row("debug", "customerid_tid=".$_POST["partnerId_$tid"]." branchid[tid]=".$_POST["partnerDetailId_$tid"]);
	
			break;
			// Bank Transfer
			case 'BT':	//partnerType
				if (empty($_POST["partnerId_$tid"])) 
				{
					$match = search_partner_by_bank_account(ST_BANKTRANSFER, $bankAccount);
						if (!empty($match)) 
					{
						$_POST["partnerId_$tid"] = $match['partner_id'];
						$_POST["partnerDetailId_$tid"] = $match['partner_detail_id'];
						}
					else
					{
						$_POST["partnerId_$tid"] = ANY_NUMERIC;
					}
				}
			//function bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
				 //bank_accounts_list_row( _("From:") , 'bank_account', null, true);
				
				// Use BankAccountDataProvider with HtmlSelect (SOLID: separated data from presentation)
				$bankAccountProvider = new \Ksfraser\BankAccountDataProvider();
				$bankAccountSelectHtml = $bankAccountProvider->generateSelectHtml("partnerId_$tid", $_POST["partnerId_$tid"] ?? null);
				
				$transferFromLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Transfer From:"));
				$bankAccountsContent = new \Ksfraser\HTML\Elements\HtmlString($bankAccountSelectHtml);
				$transferFromRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($transferFromLabel, $bankAccountsContent);
				$transferFromRow->toHtml();
				 		//bank_accounts_list_row( _("From:") , 'bank_account', null, false)
			break;
			// quick entry
			case 'QE':	//partnerType
				//label_row("Option:", "<b>Process via quick entry</b>");
				
				// Use QuickEntryDataProvider with HtmlSelect (SOLID: separated data from presentation)
				$qeType = ($transactionDC == 'C') ? QE_DEPOSIT : QE_PAYMENT;
				$qeProvider = new \Ksfraser\QuickEntryDataProvider();
				$qeSelectHtml = $qeProvider->generateSelectHtml($qeType, "partnerId_$tid", get_post("partnerId_$tid"));
				
				// Get description for selected quick entry
				$selectedId = get_post("partnerId_$tid");
				$qe_text = $qeSelectHtml;
				if ($selectedId) {
					$qe = $qeProvider->getQuickEntryById($qeType, $selectedId);
					if ($qe) {
						$qe_text .= " " . $qe['base_desc'];
					}
				}
			
				$quickEntryLabel = new \Ksfraser\HTML\Elements\HtmlString("Quick Entry:");
				$quickEntryContent = new \Ksfraser\HTML\Elements\HtmlString($qe_text);
				$quickEntryRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($quickEntryLabel, $quickEntryContent);
				$quickEntryRow->toHtml();
			break;
			case 'MA':
				// Replace hidden() with HtmlHidden
				$hiddenPartnerId = new \Ksfraser\HTML\Elements\HtmlHidden("partnerId_$tid", 'manual');
				$hiddenPartnerId->toHtml();
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
				
				// Use HtmlSelect directly for array_selector (SOLID: data already in array)
				$existingTypeSelect = new \Ksfraser\HTML\Elements\HtmlSelect($name);
				$existingTypeSelect->addOptionsFromArray($opts_arr, '0');
				
				$existingTypeLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Existing Entry Type:"));
				$existingTypeContent = new \Ksfraser\HTML\Elements\HtmlString($existingTypeSelect->getHtml());
				$existingTypeRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($existingTypeLabel, $existingTypeContent);
				$existingTypeRow->toHtml();
				
				//function text_input($name, $value=null, $size='', $max='', $title='', $params='')
				// Note: text_input returns HTML, wrap in HtmlString for now
				$existingEntryLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Existing Entry:"));
				$existingEntryInput = text_input( "Existing_Entry", 0, 6, '', _("Existing Entry:") );
				$existingEntryContent = new \Ksfraser\HTML\Elements\HtmlString($existingEntryInput);
				$existingEntryRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($existingEntryLabel, $existingEntryContent);
				$existingEntryRow->toHtml();
			break;
			case 'ZZ':	//partnerType
				//Matched an existing item
				// Replace hidden() calls with HtmlHidden
				$hiddenPartnerIdZZ = new \Ksfraser\HTML\Elements\HtmlHidden("partnerId_$tid", $arr_arr[0]['type']);
				$hiddenPartnerIdZZ->toHtml();
				
				$hiddenPartnerDetailIdZZ = new \Ksfraser\HTML\Elements\HtmlHidden("partnerDetailId_$tid", $arr_arr[0]['type_no']);
				$hiddenPartnerDetailIdZZ->toHtml();
				
				$hiddenTransTypeZZ = new \Ksfraser\HTML\Elements\HtmlHidden("trans_type_$tid", $arr_arr[0]['type']);
				$hiddenTransTypeZZ->toHtml();
				
				$hiddenTransNoZZ = new \Ksfraser\HTML\Elements\HtmlHidden("trans_no_$tid", $arr_arr[0]['type_no']);
				$hiddenTransNoZZ->toHtml();
				
				$hiddenMemoZZ = new \Ksfraser\HTML\Elements\HtmlHidden("memo_$tid", $trz['memo']);
				$hiddenMemoZZ->toHtml();
				
				$hiddenTitleZZ = new \Ksfraser\HTML\Elements\HtmlHidden("title_$tid", $trz['transactionTitle']);
				$hiddenTitleZZ->toHtml();
			break;
			}
			
			// Replace submit button with HtmlSubmit
			$processLabel = new \Ksfraser\HTML\Elements\HtmlString(_("Process"));
			$processButton = new \Ksfraser\HTML\Elements\HtmlSubmit($processLabel);
			$processButton->setName("ProcessTransaction[$tid]");
			$processButton->setClass("default");
			
			$emptyLabel = new \Ksfraser\HTML\Elements\HtmlString("");
			$processRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($emptyLabel, $processButton);
			$processRow->toHtml();
	
			//other common info
			hidden("cids[$tid]",$cids);
	
			if( count( $arr_arr ) > 0 )
			{
				$match_html = "";
				$matchcount = 1;
				foreach( $arr_arr as $matchgl )
				{
					//[type] => 0 [type_no] => 8811 [tran_date] => 2023-01-03 [account] => 2620.frontier [memo_] => 025/2023 [amount] => 432.41 [person_type_id] => [person_id] => [account_name] => Auto Loan Frontier (Nissan Finance) [reference] => 025/2023 [score] => 111 [is_invoice]
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
						$match_html .= " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
						$match_html .= " Score " . $matchgl['score'] . " ";
						if( strcasecmp( $our_account, $matchgl['account'] ) OR strcasecmp( $bank['bank_account_name'], $matchgl['account'] ) )
						{
							$match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
						}
						else
						{
							/* */
							$match_html .= "MATCH BANK:: ";
							$match_html .=  print_r( $our_account, true );
								//$match_html .= "::" . print_r( $bank, true );
							$match_html .= "::" . print_r( $bank['bank_account_name'], true );
							$match_html .= " Matching " . print_r( $matchgl, true );
							//$match_html .= " Matching " . print_r( $matchgl['account'], true );
							$match_html .= "Account " . $matchgl['account'] . "---";
							/* */
						}
						$match_html .= " " . $matchgl['account_name'] . " ";
						if( $transactionDC == 'D' )
						{
							$scoreamount = -1 * $amount;
						}
						else
						{
							$scoreamount = 1 * $amount;
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
							$cdet = get_customer_details_from_trans( 	$matchgl['type'], 
													$matchgl['type_no']
								);
	
							//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $cdet, true ) );
							$match_html .= " //Person " . $cdet['name'] . "/" . $cdet["br_name"];
							//$match_html .= " //Person " . print_r( $cdet, true ) . "/" . $matchgl["person_id"];
							//$match_html .= " //Person " . $matchgl['person_type_id'] . "/" . $matchgl["person_id"];
						}
	
						$match_html .= "<br />";
						$matchcount++;
					} //if isset
				} //foreach
				
				// Replace label_row with HtmlLabelRow
				$matchingGLsLabel = new \Ksfraser\HTML\Elements\HtmlString("Matching GLs");
				$matchingGLsContent = new \Ksfraser\HTML\Elements\HtmlString($match_html);
				$matchingGLsRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($matchingGLsLabel, $matchingGLsContent);
				$matchingGLsRow->toHtml();
				//label_row("Matching GLs", print_r( $arr_arr, true ) );
			}
			else
			{
					label_row("Matching GLs", "No Matches found automatically" );
			}
		}
		echo $operationsTable->closeTable(); // Close tablestyle2 table
		echo $rightCell->closeCell();
		echo $mainRow->closeRow();
		}
		// Main table closing handled by transaction_table class display() method
	}
	
	
	div_end();
	end_form();

end_page(@$_GET['popup'], false, false);
?>
