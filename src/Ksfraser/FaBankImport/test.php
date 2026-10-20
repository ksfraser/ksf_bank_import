<?php

require_once( 'includes/includes.inc' );
/**
 * @BABOK RTM: NFR-016 / BR-001; file: test.php; coverage: docs/TestCases/ (file rollup)
 */

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

