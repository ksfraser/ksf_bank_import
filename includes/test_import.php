#!/usr/bin/php -f
<?php

//test section - comment in production
require 'banking.php';
require 'parser.php';
//require 'mt940_parser.php';


$static_data = array();

require 'qfx_parser.php';
$parser = new qfx_parser;
$content = file_get_contents('test.qfx');
   $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }


global $static_data;
$static_data = array();
$static_data['account'] = "2992";
$static_data['account_number'] = "2992";
$static_data['currency'] = "CAD";
$static_data['account_code'] = 1061;
$static_data['account_type'] = "CREDIT";
$static_data['account_name'] = "WALMART";
$static_data['bank_charge_act'] = 5690;

/** This is in parse_uploaded files below
$statements = $parser->parse($content, $static_data, $debug = true);
**/

/**//**********************************
* This function on the web page displays the statements and controls
*
*************************************/
function import_statements() {

    $statements = unserialize($_SESSION['statements']);
    foreach($statements as $id => $smt) {
        echo "importing statement {$smt->statementId} ...";
        echo importStatement($smt);
        echo "\n";
    }
        echo "\r\n";
}

function importStatement($smt) {
        $message = '';
                echo( __FILE__ . "::" . __LINE__ );
        require_once(  './class.bi_statements.php' );
        //require_once( dirname( __FILE__ ) .  '/class.bi_statements.php' );
                //echo( __FILE__ . "::" . __LINE__ );
        $bis = new bi_statements_model();
                //echo( __FILE__ . "::" . __LINE__ . ":" . print_r( $smt, true ) );
        $bis->set( "bank", $smt->bank );
                //echo( __FILE__ . "::" . __LINE__ );
        $bis->set( "statementId", $smt->statementId );
                //echo( __FILE__ . "::" . __LINE__ );
        $exists = $bis->statement_exists();
                echo( __FILE__ . "::" . __LINE__ );

        $bis->obj2obj( $smt );

        if( ! $exists )
        {
                        echo( __FILE__ . "::" . __LINE__ . "Statement Doesn't Exist.  Inserting" );
                $sql = $bis->hand_insert_sql();
                        //echo( __FILE__ . "::" . __LINE__ . " " . $sql );
                $res = db_query($sql, "could not insert transaction");
                        //echo( __FILE__ . "::" . __LINE__ . " " . $res );
                $smt_id = db_insert_id();
                        //echo( __FILE__ . "::" . __LINE__ . " " . $smt_id );
                $bis->set( "id", $smt_id );
                        echo( __FILE__ . "::" . __LINE__ . "Inserted Statement $smt_id" );
                $message .= "new, imported";
        } else
        {
                        //echo( __FILE__ . "::" . __LINE__ . "Statement Exists.  Updating" );
                $bis->update_statement();
                        echo( __FILE__ . "::" . __LINE__ . "Updated Statement $smt->statementId " );
                $message .= "existing, updated";
        }
        //$smt_id = $bis->get( "statementId" );
        $smt_id = $bis->get( "id" );
/* */
        require_once( '../class.bi_transactions.php' );
        foreach($smt->transactions as $id => $t)
        {
        set_time_limit( 0 );    //Don't time out oin php.  Apache might still kill us...

                //echo( __FILE__ . "::" . __LINE__ );
                try {
                                unset( $bit );
                        $bit = new bi_transactions_model();
                } catch( Exception $e )
                {
                        echo( __FILE__ . "::" . __LINE__ . " " . print_r( $e, true ) );
                }
                //echo( __FILE__ . "::" . __LINE__ );
                $bit->trz2obj( $t );
                //echo( __FILE__ . "::" . __LINE__ . ": " . print_r( $bit, true ) );
                $bit->set( "smt_id", $smt_id );
                //echo( __FILE__ . "::" . __LINE__ );
                $dupe = $bit->trans_exists();
                if( $dupe )
                {
                        //Transaction already in the tables;
                        //echo( __FILE__ . "::" . __LINE__ . ": Duplicate Insert" );
                }
                else
                {
                        //echo( __FILE__ . "::" . __LINE__ );
                        $sql = $bit->hand_insert_sql();
                        //echo( __FILE__ . "::" . __LINE__ . " " . $sql );
                        $res = db_query($sql, "could not insert transaction");
                        //echo( __FILE__ . "::" . __LINE__ );
                        $t_id = db_insert_id();
                        echo( __FILE__ . "::" . __LINE__ . " Inserted $t_id " );
                }
        }       //foreach statement
        $message .= ' ' . count($smt->transactions) . ' transactions';
        $message .= ' ' . count($smt->transactions) . ' transactions';
        return $message;
/* */
}       //import_statement fc

/**//************************************
* In a CLI a web form isn't relevant.
*
*	STUB
*****************************************/
function do_upload_form() {
}

/**//*********************************************
* Test_parser did most of this 
*
*	Removed WEB based code
*
* @param string content.  On WEB version this isn't here
* @param class PARSER
************************************************/
function parse_uploaded_files( $content, $parser ) 
{

global $static_data;

	// initialize parser class
/*
	$parserClass = $_POST['parser'] . '_parser';
	$parser = new $parserClass;
*/

	//prepare static data for parser
/*
	$_parsers = getParsers();
*/
//USES STATIC DATA.  Defined at top...

	$smt_ok = 0;
	$trz_ok = 0;
	$smt_err = 0;
	$trz_err = 0;

/**
	On web version, it grabs file names and parsers from _FILES and _POST
	Here we hard coded at top
**/


	$statements = $parser->parse($content, $static_data, $debug=true ); // false for no debug, true for debug
	var_dump( $statements );

	foreach ($statements as $smt) 
	{
		echo "statement: {$smt->statementId}:";
		if ($smt->validate($debug = false)) 
		{
			$smt_ok ++;
			$trz_cnt = count($smt->transactions);
			$trz_ok += $trz_cnt;
			echo " is valid, $trz_cnt transactions\n";
		} else 
		{
			echo " is invalid!!!!!!!!!\n";
			$smt->validate($debug=true);
			$smt_err ++;
		}
	}

		echo "======================================\n";
		echo "Valid statements   : $smt_ok\n";
		echo "Invalid statements : $smt_err\n";
		echo "Total transactions : $trz_ok\n";

	if ($smt_err == 0) {
		$_SESSION['statements'] = serialize($statements);
	}
}


parse_uploaded_files( $content, $parser ); 

echo "======================================\n";
$smt_ok = $trz_ok = 0;
foreach ($statements as $smt) {
    if ($smt->validate($debug = true)) {
	$smt_ok ++;
	$trz_cnt = count($smt->transactions);
	$trz_ok += $trz_cnt;
	echo "  valid statement found, $trz_cnt transactions\n";
    } else {
	echo "  invalid statement, ignored\n";
	echo "  --------------------------------------------\n";
	$smt->dump();
	echo "  --------------------------------------------\n";
    }
}

echo "======================================\n";
echo "Total statements  : $smt_ok\n";
echo "Total transactions: $trz_ok\n";


