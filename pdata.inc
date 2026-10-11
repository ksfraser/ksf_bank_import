<?php

/**//***********************************************************************
* Get the Partner Data
*
* @param int foreign key to Supplier/Customer/Bank tables  (From bank)
* @param int Indicate which foreign table
* @param int -1 for supplier.  BRANCH id for Customer. TO Bank
* @returns array
******************************************************************************************/
function get_partner_data($partner_id, $partner_type, $partner_detail_id) {
    $sql = "
	SELECT * FROM ".TB_PREF."bi_partners_data
	    WHERE partner_id=".db_escape($partner_id)." AND partner_type=".db_escape($partner_type);
    if ($partner_type == PT_CUSTOMER OR $partner_type == ST_BANKTRANSFER)
	$sql .= " AND partner_detail_id=".db_escape($partner_detail_id);
//    display_notification($sql);
    $result = db_query($sql, "could not get partner data");	
    return db_fetch($result);
}

/**//*********************************************************************************
* Insert BANK partner data.  Wrapper on set_partner_data
*
* @param int From Bank key (Bank table)
* @param int Indicate which foreign table
* @param int TO Bank key  (Bank table)
* @param string the data sent from the bank
* @returns
******************************************************************************************/
function set_bank_partner_data($from_bank_id, $partner_type = ST_BANKTRANSFER, $to_bank_id, $data) 
{

	//display_notification( __FILE__ . "::" . __LINE__ . ":" . $from_bank_id . ":" . $partner_type  . ":" . $to_bank_id . ":" . $data  . "//");
	set_partner_data($from_bank_id, $partner_type, $to_bank_id, $data);
}
/**//*********************************************************************************
* Insert partner data
*
*	The key on this table is partner_id-partner_detail_id-partner_type
*
*	The original version of this function would keep adding the same
*	partner data in, so we have partners that are SHOPPERS\nSHOPPERS\n....
*	Added a check for matches.
*
* @param int foreign key to Supplier/Customer/Bank tables  (From bank)
* @param int Indicate which foreign table
* @param int -1 for supplier.  BRANCH id for Customer. TO Bank
* @param string the data sent from the bank
* @returns
******************************************************************************************/
function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data) 
{
	$arr = get_partner_data( $partner_id, $partner_type, $partner_detail_id );
//	display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $arr, true ) );
	if( count( $arr ) > 0 )
	{
		if( $arr['data'] == $data )
		{
			//no update needed
			return;
		}
		else
		{
			$match = search_partner_by_bank_account($partner_type, $data );
			if( $match['partner_id'] == $partner_id )
			{
				//already there
				return;
			}
			//We need to update the record.
		}
	}

    $sql = "
	INSERT INTO ".TB_PREF."bi_partners_data(partner_id, partner_type, partner_detail_id, data) VALUES(".
	    db_escape($partner_id).",".db_escape($partner_type).",".db_escape($partner_detail_id).",".db_escape($data).")
	ON DUPLICATE KEY UPDATE
	    data=".db_escape($data);
//    display_notification($sql);
    db_query($sql, 'Could not update partner');

}

/**//********************************************************
* Search for partner data by needle
*
* There could possibly be different partners with the same needle
*
* @param string needle
* @returns array
*************************************************************/
function search_partner_data_by_needle( $needle ) {
    if (empty($needle))
	return array();

    $sql = "
	SELECT * FROM ".TB_PREF."bi_partners_data
	    WHERE data LIKE '%".$needle."%'";
//    display_notification($sql);
    $result = db_query($sql, "could not get search partner");	

	$arr = array();
    	while( $row = db_fetch($result) )
	{
		$arr[] = $row;
	}
	return $arr;
}

function search_partner_by_bank_account($partner_type, $needle) {
    if (empty($needle))
	return array();

    $sql = "
	SELECT * FROM ".TB_PREF."bi_partners_data
	    WHERE partner_type=".db_escape($partner_type)." AND data LIKE '%".$needle."%' LIMIT 1";

//    display_notification($sql);
	
    
    $result = db_query($sql, "could not get search partner");	
    return db_fetch($result);
}

//in development
/**//*********************************************************************************
* Update partner data
*
* @param int
* @param int
* @param int
* @param string
* @returns none
******************************************************************************************/
function update_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    //$account_n = "\n" . $account;
    $account_n = "\n";
    $sql = "
	INSERT INTO ".TB_PREF."bi_partners_data(partner_id, partner_type, partner_detail_id, data) VALUES(".
	    db_escape($partner_id).",".db_escape($partner_type).",".db_escape($partner_detail_id).",".db_escape($data).")
	ON DUPLICATE KEY UPDATE
	    data=CONCAT(data, ".db_escape($account_n) . ")";
//    display_notification($sql);
    db_query($sql, 'Could not update partner');

}
