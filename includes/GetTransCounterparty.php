<?php

use Ksfraser\FaBankImport\Handlers\AddVendor;


require_once( __DIR__ . '/../class.bi_transactions.php' );

class GetTransCounterparty
{
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
}
