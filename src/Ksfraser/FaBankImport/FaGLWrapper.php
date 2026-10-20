<?php

namespace Ksfraser\FaBankImport;

use Exception;

/**//**************************************************************
 * @BABOK CLASS ROLLUP: FaGLWrapper — 2 methods; BR-002 (2); FR-008 (2); FR-018 (2)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: FaGLWrapper.php; coverage: docs/TestCases/ (file rollup)
* fa_gl is a DAO so we shouldn't be putting business logic into it
* This wraps it for that logic
*
******************************************************************/
class FaGLWrapper
{
	protected $fa_gl;
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( $transaction )
	{
	        $inc = include_once( __DIR__ . '../../../../../ksf_modules_common/class.fa_gl.php' );
	        if( $inc )
	        {
		/** Namespace *
	         *       $fa_gl = new FaGl();
	         *       $fa_gl = new \KSFRASER\FA\fa_gl();
		**/
	                $fa_gl = new \fa_gl();
 			$fa_gl->set( "amount_min", $transaction->amount );
                	$fa_gl->set( "amount_max", $transaction->amount );
                	$fa_gl->set( "amount", $transaction->amount );
                	$fa_gl->set( "transactionDC", $transaction->transactionDC );
                	$fa_gl->set( "days_spread", $transaction->days_spread );
                	$fa_gl->set( "startdate", $transaction->valueTimestamp );     //Set converts using sql2date
                	$fa_gl->set( "enddate", $transaction->entryTimestamp );       //Set converts using sql2date
                	$fa_gl->set( "accountName", $transaction->otherBankAccountName );
                	$fa_gl->set( "transactionCode", $transaction->transactionCode );
                	$fa_gl->set( "memo_", $transaction->memo );
		                //Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
		                //              E-TRANSFER 010667466304;CUSTOMER NAME;...
		                //      function add_days($date, $days) // accepts negative values as well
			$this->fa_gl = $fa_gl;
	        }
		else
		{
			throw new Exception( "Couldn't open " . __DIR__ . "/../ksf_modules_common/class.fa_gl.php" );
		}
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: retrieveMatchingTransactions */
	function retrieveMatchingTransactions()
	{
		return $this->fa_gl->find_matching_transactions( $this->fa_gl->memo );
	}
}


