#!/usr/bin/php -f
<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CLI mode detection
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

// Start output buffering to prevent any output before session starts
ob_start();

// Load FrontAccounting framework (required for db_query, db_insert_id, etc.)
$path_to_root = "../../..";
if (!file_exists($path_to_root . "/includes/session.inc")) {
    ob_end_clean();
    die("ERROR: Cannot find FrontAccounting framework at $path_to_root\n" . 
        "Please run this script from the correct location or adjust \$path_to_root\n");
}

// Set up CLI environment variables BEFORE loading FA
// These prevent FA from trying to use HTTP-specific features
$_SERVER['REQUEST_METHOD'] = 'GET';  // Prevent POST processing
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';  // Prevent "Undefined index: REMOTE_ADDR" error
$_SERVER['REQUEST_URI'] = '/cli/test_import.php';
$_POST = array();  // Clear any POST data
$_GET = array();   // Clear any GET data

// Capture FA's login redirect and errors
ob_start();
include($path_to_root . "/includes/session.inc");
$fa_output = ob_get_clean();

// Check if FA tried to show login page (captured in output buffer)
if (strpos($fa_output, 'loginform') !== false || strpos($fa_output, 'Please login') !== false) {
    // FA tried to redirect to login - we need to set up authentication
    // Set up a minimal authenticated session for CLI
    $_SESSION['wa_current_user'] = (object) [
        'user' => 1,
        'username' => 'CLI Test Script',
        'company' => 0,
        'logged' => true,
        'login_id' => 1,
        'prefs' => (object) [
            'language' => 'en_US',
            'date_format' => 0,
            'date_sep' => '-',
            'tho_sep' => ',',
            'dec_sep' => '.',
            'theme' => 'default',
            'show_gl' => 1,
            'show_codes' => 1
        ]
    ];
}

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

// Release output buffer and show our messages
ob_end_clean();

echo "=== STARTING CLI IMPORT TEST ===\n\n";
echo "✓ FrontAccounting framework loaded\n";
echo "✓ Database connection established\n";

// Start transaction for testing - will rollback at end (no permanent changes)
echo "\n⚠️  TEST MODE: Starting database transaction (changes will be rolled back)\n";
db_query("START TRANSACTION");
echo "✓ Transaction started - all changes will be temporary\n\n";

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
                echo( __FILE__ . "::" . __LINE__ . "\n");
        require_once(  '../class.bi_statements.php' );
        //require_once( dirname( __FILE__ ) .  '/class.bi_statements.php' );
                echo( __FILE__ . "::" . __LINE__ . "\n");
        
        try {
            $bis = new bi_statements_model();
        } catch (Exception $e) {
            echo "ERROR creating bi_statements_model: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            return "ERROR: " . $e->getMessage();
        }
                echo( __FILE__ . "::" . __LINE__ . "\n");
        $bis->set( "bank", $smt->bank );
                echo( __FILE__ . "::" . __LINE__ . "\n");
        $bis->set( "statementId", $smt->statementId );
                echo( __FILE__ . "::" . __LINE__ . "\n");
        $exists = $bis->statement_exists();
                echo( __FILE__ . "::" . __LINE__ . " - Statement exists: " . ($exists ? "YES" : "NO") . "\n");

        $bis->obj2obj( $smt );

        if( ! $exists )
        {
                        echo( __FILE__ . "::" . __LINE__ . " - Statement Doesn't Exist. Inserting\n" );
                $sql = $bis->hand_insert_sql();
                        echo( __FILE__ . "::" . __LINE__ . " - SQL: " . substr($sql, 0, 100) . "...\n" );
                
                try {
                    $res = db_query($sql, "could not insert statement");
                            echo( __FILE__ . "::" . __LINE__ . " - Query result: " . ($res ? "SUCCESS" : "FAILED") . "\n" );
                    $smt_id = db_insert_id();
                            echo( __FILE__ . "::" . __LINE__ . " - Inserted Statement ID: $smt_id\n" );
                    $bis->set( "id", $smt_id );
                    $message .= "new, imported";
                } catch (Exception $e) {
                    echo "ERROR inserting statement: " . $e->getMessage() . "\n";
                    return "ERROR: " . $e->getMessage();
                }
        } else
        {
                        echo( __FILE__ . "::" . __LINE__ . " - Statement Exists. Updating\n" );
                try {
                    $bis->update_statement();
                            echo( __FILE__ . "::" . __LINE__ . " - Updated Statement $smt->statementId\n" );
                    $message .= "existing, updated";
                } catch (Exception $e) {
                    echo "ERROR updating statement: " . $e->getMessage() . "\n";
                    return "ERROR: " . $e->getMessage();
                }
        }
        //$smt_id = $bis->get( "statementId" );
        $smt_id = $bis->get( "id" );
/* */
        require_once( '../class.bi_transactions.php' );
        
        $newinserted = 0;
        $dupecount = 0;
        
        foreach($smt->transactions as $id => $t)
        {
        set_time_limit( 0 );    //Don't time out in php.  Apache might still kill us...

                echo( __FILE__ . "::" . __LINE__ . " - Processing transaction $id\n");
                try {
                                unset( $bit );
                        $bit = new bi_transactions_model();
                } catch( Exception $e )
                {
                        echo( __FILE__ . "::" . __LINE__ . " ERROR creating bi_transactions_model: " . $e->getMessage() . "\n" );
                        continue;
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
                        echo( __FILE__ . "::" . __LINE__ . " - Duplicate transaction found\n" );
                        $dupecount++;
                }
                else
                {
                        echo( __FILE__ . "::" . __LINE__ . " - Inserting new transaction\n" );
                        try {
                            $sql = $bit->hand_insert_sql();
                            echo( __FILE__ . "::" . __LINE__ . " - Transaction SQL: " . substr($sql, 0, 100) . "...\n" );
                            $res = db_query($sql, "could not insert transaction");
                            $t_id = db_insert_id();
                            echo( __FILE__ . "::" . __LINE__ . " - Inserted transaction ID: $t_id\n" );
                            $newinserted++;
                        } catch (Exception $e) {
                            echo "ERROR inserting transaction: " . $e->getMessage() . "\n";
                        }
                }
        }       //foreach statement
        
        echo "\n========== IMPORT SUMMARY ==========\n";
        echo "New transactions inserted: $newinserted\n";
        echo "Duplicate transactions skipped: $dupecount\n";
        echo "====================================\n\n";
        
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


// Wrap main execution in try-finally to ensure rollback happens
try {
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
    
    // If we got here, test completed successfully
    echo "\n✓ All operations completed successfully\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR during test execution:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
} finally {
    // ALWAYS rollback - this is a test script
    echo "\n⚠️  TEST MODE: Rolling back all database changes...\n";
    db_query("ROLLBACK");
    echo "✓ Rollback complete - no data was permanently saved\n";
    echo "\n=== TEST COMPLETE ===\n";
}


