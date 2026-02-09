<?php


require_once( __DIR__ . '/../class.bi_transactions.php' );

class GetTransaction
{

	/*
	*	$sql = "
	*	    SELECT t.*, s.account our_account FROM ".TB_PREF."bi_transactions t
	*    	    LEFT JOIN ".TB_PREF."bi_statements as s ON t.smt_id = s.id
	*	    WHERE t.id=".db_escape($tid);
	*	$result = db_query($sql, "could not get transaction with id $tid");	
	*	return db_fetch($result);
	*/

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
	}
}

