<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

//we need to interpret the file and generate a new statement for each day of transactions

//TODO
//	Have a config whether we should match MEMO field i.e. INTERNET TRASFER/PAY/DEPOSIT/...
//	Have a further config to indicate chunk delimiters.  CIBC uses ";".  Is this standard?

require_once (__DIR__ . '/vendor/autoload.php' );
include_once( 'includes.inc' );

/**//************************************************
* Class to parse a QFX/OFX file
*
******************************************************/
class qfx_parser extends parser {

	protected $bank_from_file;	//!<bool did we get bank info from the imported file
	protected $bankid_from_file;	//!<bool did we get bank ID from the imported file
	/**//**
	 * Convert an array of  CSV lines into assoc array
	 *
	 * Called by parse
	 */
	function _combine_array(&$row, $key, $header) {
  		$row = array_combine($header, $row);
	}

	/**//*********************************************************
	* Import a QFX/OFX
	*
	*	OFX allows multiple bank account transactions within a file.
	*
	*
	* @param string contents from file_get_contents
	*
	**************************************************************/
    function parse($content, $static_data = array(), $debug = true) {
	//echo __FILE__ . "::" . __LINE__ . "::" . " In Parser QFX\n";
			var_dump( __FILE__ . "::" . __LINE__ );
	//var_dump( $content );

	$ofxParser = new OfxParser\Parser();
	$ofx = $ofxParser->loadFromString( $content );
	//$ofx = $ofxParser->loadFromFile('test.qfx');

	//var_dump( $ofx->signOn );
/**
[CREDITCARDMSGSRSV1] => SimpleXMLElement Object
        (
            [CCSTMTTRNRS] => SimpleXMLElement Object
                (
                    [TRNUID] => 20240223062401
                    [STATUS] => SimpleXMLElement Object
                        (
                            [CODE] => 0
                            [SEVERITY] => INFO
                            [MESSAGE] => OK
                        )

                    [CCSTMTRS] => SimpleXMLElement Object
                        (
                            [CURDEF] => CAD
                            [CCACCTFROM] => SimpleXMLElement Object
                                (
                                    [ACCTID] => 4503300016180307
                                    [ACCTTYPE] => CREDITLINE
                                )

                            [BANKTRANLIST] => SimpleXMLElement Object
                                (
                                    [DTSTART] => 20170228120000
                                    [DTEND] => 20240220120000
**/
			//var_dump( __FILE__ . "::" . __LINE__ );
	//var_dump( $institute );
	$institute = $ofx->signOn->institute;
	if( null !== $institute->name )
	{
		$bank = (string) $institute->name;
		$this->bank_from_file = true;
	}
	else
	{
		if( isset( $static_data['account_name'] ) )
		{
			$bank = $static_data['account_name'];
			$this->bank_from_file = false;
		}
		else
		{
			$bank = "Savings";
			$this->bank_from_file = false;
		}
	}
			//var_dump( __FILE__ . "::" . __LINE__ );
	if( null !== $institute->id )
	{
		$bankid = (string) $institute->id;
		$this->bankid_from_file = true;
	}
	else
	{
		$this->bankid_from_file = false;
		if( isset( $static_data['account_code'] ) )
			$bankid = $static_data['account_code'];
		else
			$bankid = '1060';
	}

	//var_dump( __FILE__ . "::" . __LINE__ );
	//var_dump( $ofx );
//bankAccounts is an array.  Can be multiple accounts....
//	Reset rewinds an array and returns a pointer to the first element.
//	next can be used to go to the next element.

		//var_dump( __FILE__ . "::" . __LINE__ );
/***********************************************************************************************************************************/
		//var_dump( __FILE__ . "::" . __LINE__ );
	//$bankAccount = reset($ofx->bankAccounts);
	/*    	public $accountNumber;	//<! string
    		public $accountType; 	//<! string
    		public $balance;	//<! string
    		public $balanceDate;	//<! DateTimeInterface
    		public $routingNumber;	//<! string
    		public $statement;	//!< Statement
    		public $transactionUid;	//<! string
    		public $agencyNumber;	//<! string
	*/
	/*	STATEMENT
    		public $currency;	//<! string
    		public $transactions;	//!< array Transaction[]
    		public $startDate;	//!< DateTimeInterface
    		public $endDate;	//!< DateTimeInterface
	*/
	/*	TRANSACTION
		public $type;		//<! string				CREDIT (Payment) or DEBIT (Charge)
    		public $date;		//!< DateTimeInterface			POSTING		Probably needs massaging
    		public $userInitiatedDate;	//!< DateTimeInterface		TRX		Probably needs massaging
    		public $amount;		//!< float
    		public $uniqueId;	//<! string
    		public $name;		//<! string
    		public $memo;		//<! string
    		public $sic;		//<! string
    		public $checkNumber;	//<! string
	*/

	//keep statements in an array, hashed by statement-id
	//statement id is the statement date: yyyy-mm-dd-<number>-<seq>
	// as each line is processed, adjust statement data and add tranzactions
	$smts = array();
	$accountCount = 0;

		//var_dump( __FILE__ . "::" . __LINE__ );
	reset($ofx->bankAccounts);
		//var_dump( $ofx->bankAccounts );
			//var_dump( __FILE__ . "::" . __LINE__ );
	foreach( $ofx->bankAccounts as $bankAccount )
	{
		if( null == $bankAccount )
			continue;
		$accountCount++;

		//var_dump( __FILE__ . "::" . __LINE__ );
		//var_dump( $bankAccount );
		$accountNumber = (string) $bankAccount->accountNumber;	//CIBC Savings as branch <space> ac-count.  VISA is just 16c XXXXyyyyzzzzaaaa.  PCMC is just the last 4.
		$accountType = (string) $bankAccount->accountType;	//CIBC sets this as CREDITLINE or SAVINGS.   PCMC doesn't set.
		$branchId = (string) $bankAccount->agencyNumber;	//CIBC doesn't include this.  PCMC doesn't set
		$bankId = (string) $bankAccount->routingNumber;		//CIBC (Savings) is 600000100.  Not included with VISA.  Not in PCMC
									//Each of the banks set INTU.BID
									//Manulife sends proper XML with closing tags.
									//Manulife has a BankID of 1  BID 00034
									//ATB sets ORG as ATB Financial, FID 1, BID 12883
		//bankid (vice bankId) and bank set above from either STATIC or file
		//Need to look up BANK to ensure it matches the accountNumber from the file.
		//var_dump( __FILE__ . "::" . __LINE__ );
			//var_dump( __FILE__ . "::" . __LINE__ );
		$gba = get_bank_account_by_acctid($accountNumber);
		//var_dump( __FILE__ . "::" . __LINE__ );
		//var_dump( $gba );
		//var_dump( __FILE__ . "::" . __LINE__ );
			//var_dump( __FILE__ . "::" . __LINE__ );
		if( isset( $gba['bank_account_name'] ) )
		{
			//var_dump( __FILE__ . "::" . __LINE__ );
			$bank = $gba['bank_account_name'];
		}
		else
		{
			//Why didn't it reset?
			//var_dump( __FILE__ . "::" . __LINE__ );
		}
	
		if( isset( $bankAccount->balance ) )
		{
			if( is_float( $bankAccount->balance ) )
			{
				//echo "It's a Float\n";
				$endbalance = $bankAccount->balance;
			}
			else
			{
				//echo "It's NOT a Float\n";
				$endbalance = (string) $bankAccount->balance;
				//$endbalance = '0.00';
			}
		}
		else
		{
				//echo "It's NOT SET\n";
			$endbalance = '0.00';
		}
	
		$balanceDate = $bankAccount->balanceDate->format(DATE_ATOM);		//Probably needs massaging: "2016-03-06 13:34:48.000000"
		// Get the statement start and end dates
		$currency = (string) $bankAccount->statement->currency;
		$startDate = $bankAccount->statement->startDate->format(DATE_ATOM);
		$endDate = $bankAccount->statement->endDate->format(DATE_ATOM);
		if( !empty( $balanceDate ) ) {
			$sid = $balanceDate . "-" . $accountCount;
			//$sid = $balanceDate;
		}
	
	
		//if smtid exists in results, add to this statement else create new statement
		//var_dump( __FILE__ . "::" . __LINE__ );
		//var_dump( $accountNumber );
		//var_dump( $bank );
		if( empty( $smts[$sid] ) ) {
			$smts[$sid] = new statement;
			$smts[$sid]->account = $accountNumber;		//This is an account number string i.e. from the bank. PCMC - 5181....
			$smts[$sid]->acctid = $accountNumber;		//This is an account number string i.e. from the bank. PCMC - 5181....
			$smts[$sid]->intu_bid = $bankid;			
			$smts[$sid]->bank = $bank;			
			//get additional info from static_data
				//Someone might want to extend this if you have multiple cards on the account,
				//and you want to insert each person's spending into a different GL
			$smts[$sid]->currency = $currency;
			$smts[$sid]->timestamp = $sid;
			$smts[$sid]->startBalance = '0';
			$smts[$sid]->endBalance = $endbalance;
			$smts[$sid]->number = '00000';
			$smts[$sid]->sequence = '0';
			$smts[$sid]->statementId = "{$sid}-{$smts[$sid]->number}-{$smts[$sid]->sequence}";
			//echo "debug: adding a statement with sid=$sid\n";
		} else {
			//echo "debug: statement exists for sid=$sid\n";
		}
		//var_dump( $smts );
			//var_dump( __FILE__ . "::" . __LINE__ );
	
		//current transaction
		$trz = null;
	
		// Get the statement transactions for the account
		$transactions = $bankAccount->statement->transactions;
			//CIBC SIC looks to be 0000 w/ NAME "PAYMENT...", uniqueId, amount, type CREDIT
			//No Frills, 5411, "NO FRILLS...", uniqueId, -amount,  DEBIT
			//Win Garden 5812
			//Gas Bar 5542
			//Pub 5812
			//Costco 5300
			//Direct Energy 4900
		//var_dump( $transactions );
		foreach( $transactions as $transaction ) 
		{
			//var_dump( $transaction );
		
			    //echo "----------------------------------------------------\n";
				//var_dump( $transaction );
			//var_dump( __FILE__ . "::" . __LINE__ );
		
			//state machine
			// in transaction && new transaction indicator => close transaction
	/*
			if ( $trz ) 
			{
				$trz->dump();
			//	$smts[$trz->valueTimestamp]->addTransaction($trz);
			}
	*/
			    
			if ($debug) echo "debug: adding new transaction....\n";
			$trz = new transaction;
			//var_dump( __LINE__ );
		
			/*	TRANSACTION
				public $type;		//<! string				CREDIT (Payment) or DEBIT (Charge)
		    		public $date;		//!< DateTimeInterface			POSTING		Probably needs massaging
		    		public $userInitiatedDate;	//!< DateTimeInterface		TRX		Probably needs massaging
		    		public $amount;		//!< float
		    		public $uniqueId;	//<! string
		    		public $name;		//<! string
		    		public $memo;		//<! string
		    		public $sic;		//<! string
		    		public $checkNumber;	//<! string
			*/
	
			//TRF for Transactions
			//COM for fees - ATM fees, commissions, admin fees.
//ATB CC sends everything as a CREDIT and then amount is +/-
//CIBC Visa sends CREDIT (payment) as + and DEBIT (Charge) as -
//SIMPLII sends Promotional Interest as DEP.  
//SIMPLII sends Regular Interest as INT.  
//SIMPLII sends Bank Transfer IN  as DEP.  
//MANULIFE sends Regular Interest as INT.  
			if( "CREDIT" == $transaction->type )
			{
			var_dump( __FILE__ . "::" . __LINE__ );
				//Payment
				if( false !== strpos( $transaction->name, "PAYMENT" ) )
				{
			//var_dump( __LINE__ );
					//PAYMENT, comes from a Bank
			    		$trz->transactionDC = 'B';
					$trz->transactionType = 'TRF';
				}		
				else
				{
			//var_dump( __LINE__ );
					//IF Not PAYMENT - REFUND
			    		$trz->transactionDC = 'C';
		  			$trz->transactionType = 'TRF';
				}
			}
/* Mantis 3178
 *	SIMPLII and MANU sends type as INT rather than CREDIT for interest */
			else if( "DEP" == $transaction->type )
			{
				var_dump( __FILE__ . "::" . __LINE__ );
				//SIMPLII Deposit - CREDIT
			    	$trz->transactionDC = 'C';
				$trz->transactionType = 'TRF';
			}
			else if( "INT" == $transaction->type )
			{
				var_dump( __FILE__ . "::" . __LINE__ );
				//SIMPLII INTEREST - CREDIT
			    	$trz->transactionDC = 'C';
				$trz->transactionType = 'TRF';
			}
/* !3178 */
			else
			{
				//Charge
			    	$trz->transactionDC = 'D';
		  		$trz->transactionType = 'TRF';
			}
				//$amount = floatval(preg_replace('/[^\d\.]/', '', $f[12]));
			$amnt = (string) $transaction->amount;
//ATB CC sends everything as a CREDIT and then amount is +/-
//CIBC Visa sends CREDIT (payment) as + and DEBIT (Charge) as -
//SIMPLII sends CREDIT (payment) as + and DEBIT (Charge) as -
//TODO: Setup the detection so that we can have a table of non conformant banks
//	ATB uses INTU.BID 12883 and FID 1
//	CIBC is INTU.BID 00005
//	SIMPLII is INTU.BID 05060
/*
 *	Mantis 2778
 *	ATB Import
 */
			if( 0 > (float)$amnt AND $transaction->type == "CREDIT" )
			{
			    	$trz->transactionDC = 'D';
			}
/* ! Mantis 2778 */
			$trz->transactionAmount = abs((float)$amnt);
			//$trz->transactionAmount =  (string) $transaction->amount;
		  	$trz->entryTimestamp = $transaction->date->format(DATE_ATOM);		//Posted Date
			if( null !== $transaction->userInitiatedDate )
				$value = $transaction->userInitiatedDate->format(DATE_ATOM);
			else
				$value = $trz->entryTimestamp;
		  	$trz->valueTimestamp = $value;		//Trans Date
			$trz->currency = $currency;
			//$trz->currency = $smts[$sid]->currency;
		
		                //$trz->transactionTitle1 = $f[7];	//Merchant
		                //$trz->transactionTitle2 = $f[8] . ", ";	//City
		                //$trz->transactionTitle3 = $f[9] . "  ";	//State
		                //$trz->transactionTitle4 = $f[10] . " ";	//Country
		                //$trz->transactionTitle5 = $f[11] . "  ";	//Postal Code
		                //$trz->transactionTitle6 = $f[6] . "; ";	//Category
		                //$trz->transactionTitle7 = $f[13] . " ";	//Rewards
		                //$trz->category = $f[6] . "; ";	//Category
		
			$trz->transactionCode = (string) $transaction->uniqueId;		//Reference Number
			$trz->fitid = (string) $transaction->uniqueId;	
			$trz->acctid = $accountNumber;		//This is an account number string i.e. from the bank. PCMC - 5181....
			$trz->intu_bid = $bankid;			
			$trz->reference = (string) $transaction->uniqueId;		//Reference Number
			$trz->transactionCodeDesc = (string) $transaction->type;	//Status
			$trz->checknumber = (string) $transaction->checkNumber;		//Cheque  Number
	/*
			$Merchant = explode( " ", (string) $transaction->name );
	                if( strcasecmp( $Merchant[0], "THE" ) !== 0 )
				$trz->account = $Merchant[0];			//Merchant.These become supplier payments, so we want to match against the merchant
	                else
				$trz->account = (string) $transaction->name;			//Merchant.These become supplier payments, so we want to match against the merchant
	*/
			if( include_once( 'includes.inc' ) )
			{
				//Does shorten_bankAccount_Names check bi_partners_data?
				//Does it parse common strings e.g. e-transfer?
				//	E-TRANSFER 105098257975;DANCE THROUGH LIFE LTD
				$shortname = shorten_bankAccount_Names( (string) $transaction->name );
			}
			else
			{
				//We need a better shortname as this becomes the "account" in the tables.
				$shortname = (string) $transaction->name;
			}
			$trz->account = $shortname;
	
			$trz->accountName1 = (string) $transaction->name;	//Merchant Full name
			//$trz->accountName2 = " " . $bank;
			$trz->transactionTitle1 = (string) $transaction->name;	//Merchant
			$trz->transactionTitle2 = (string) $transaction->sic;
			$trz->sic = (string) $transaction->sic;
			$trz->transactionTitle3 = (string) $transaction->checkNumber;
			$trz->transactionTitle4 = (string) $transaction->memo;
			$trz->accountName = $trz->accountName1;
			//$trz->accountName = $trz->accountName1 + $trz->accountName2;
			$trz->memo = (string) $transaction->memo;
			if( strlen( $trz->account ) < 2 )
			{
				if( strlen( $trz->memo ) > 2 )
				{
					$accs = explode( ";", $trz->memo );
					if( strpos( $accs[0], "E-TRANSFER" ) )
					{
						$trz->account = $trz->accountName = $accs[1];	//Customer
					}
					else
					if( strpos( $accs[0], "INTERNET TRANSFER" ) )
					{
						//account to account transfer.  Maybe visa pay
						$trz->account = $trz->accountName = $accs[1];	//Customer
					}
					else
					if( strpos( $accs[0], "PAY" ) )
					{
						//account to account transfer.  Maybe visa pay
						$trz->account = $trz->accountName = $accs[1];	//Customer
					}
					else
					if( strpos( $accs[0], "PAY" ) )
					{
						//account to account transfer.  Maybe visa pay
						$trz->account = $trz->accountName = $accs[1];	//Customer
					}
					else
					if( strpos( $accs[0], "DEPOSIT" ) )
					{
						if( isset( $accs[3] ) )
						{
							if( strpos( $accs[3], "Electronic Funds Transfer" ) )
							{
								//SQUARE / TD Auto
								$trz->account = $trz->accountName = $accs[2];
							}
						}
						else
						{
							if( strpos( $accs[2], "Electronic Funds Transfer" ) )
							{
								//Allianz
								$trz->account = $trz->accountName = $accs[1];
							}
						}
					}
				}
			}
			$trz->merchant = (string) $transaction->name;	//Merchant
			//var_dump( $trz );
			if ($trz)
			    $smts[$sid]->addTransaction($trz);
			//display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $trz, true ) );
			echo( __FILE__ . "::" . __LINE__ . " " . print_r( $trz, true ) );
	
		}
		//parsing ended, cleanup
	}

	//time to return
	return $smts;
    }

}



