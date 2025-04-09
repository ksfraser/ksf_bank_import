<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

require_once( 'includes/includes.inc' );

$vendor_list = get_vendor_list();
var_dump( $vendor_list );
$bankAccount = "WAL-MART";
if( ! in_array(  $bankAccount, $vendor_list ) )
        {
		echo "Not in list";
        }
        else
        {
		echo "In List";
        }

