<?php


// Use local includes or stubs for dev/testing
@include_once(__DIR__ . '/parser.php');
// @include_once(__DIR__ . '/mt940_parser.php');
// @include_once(__DIR__ . '/ro_brd_mt940_parser.php');
// @include_once(__DIR__ . '/ro_bcr_csv_parser.php');
// @include_once(__DIR__ . '/ro_ing_csv_parser.php');
@include_once(__DIR__ . '/ro_wmmc_csv_parser.php');
@include_once(__DIR__ . '/qfx_parser.php');

function getParsers() {
    return array(
	/*
	'ro_brd_mt940' => array('name' => 'BRD-RO, MT940 format'),
	'ro_bcr_csv' => array('name' => 'BCR-RO, CSV format'),
	'ro_ing_csv' => array('name' => 'ING-RO, CSV format', 'select' => array('bank_account' => 'Select bank account')),
	*/
	'QFX' => array('name' => 'QFX/OFX/Quickbooks (QBO) format', 'select' => array('bank_account' => 'Select bank account')),
	'ro_wmmc_csv' => array('name' => 'WMMC, CSV format', 'select' => array('bank_account' => 'Select bank account')),
	);
}

?>
