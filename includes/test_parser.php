#!/usr/bin/php -f
<?php

//test section - comment in production
require 'banking.php';
require 'parser.php';
//require 'mt940_parser.php';

if( ! function_exists( html_specials_encode ) )
{
	function html_specials_encode($value)
	{
		return $value;
	}
}

$static_data = array();

/* */
require 'qfx_parser.php';
$parser = new qfx_parser;
$content = file_get_contents('test.qfx');
/* */

/* * /
require 'ro_wmmc_csv_parser.php';
$parser = new ro_wmmc_csv_parser;
$content = file_get_contents('test.csv');
/**/

$static_data['account'] = "2992";
$static_data['account_number'] = "2992";
$static_data['currency'] = "CAD";
$static_data['account_code'] = 1061;
$static_data['account_type'] = "CREDIT";
$static_data['account_name'] = "WALMART";
$static_data['bank_charge_act'] = 5690;



//require 'ro_ing_csv_parser.php';
//$parser = new ro_ing_csv_parser;
//$content = file_get_contents('statement_ro_ing_csv.csv');

//require 'ro_bcr_csv_parser.php';
//$parser = new ro_bcr_csv_parser;
//$content = file_get_contents('statement_ro_bcr_csv.csv');

//require 'ro_brd_mt940_parser.php';
//$parser = new ro_brd_mt940_parser;
//$content = file_get_contents('statement_ro_brd_mt940.sta');

   $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }


$statements = $parser->parse($content, $static_data, $debug = true);


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


