<?php

include_once($path_to_root . "/modules/bank_import/includes/parser.php");
/*
include_once($path_to_root . "/modules/bank_import/includes/mt940_parser.php");
include_once($path_to_root . "/modules/bank_import/includes/ro_brd_mt940_parser.php");
include_once($path_to_root . "/modules/bank_import/includes/ro_bcr_csv_parser.php");
include_once($path_to_root . "/modules/bank_import/includes/ro_ing_csv_parser.php");
*/
include_once($path_to_root . "/modules/bank_import/includes/ro_wmmc_csv_parser.php");
include_once($path_to_root . "/modules/bank_import/includes/qfx_parser.php");

function getParsers() {
    return array(
	/*
	'ro_brd_mt940' => array('name' => 'BRD-RO, MT940 format'),
	'ro_bcr_csv' => array('name' => 'BCR-RO, CSV format'),
	'ro_ing_csv' => array('name' => 'ING-RO, CSV format', 'select' => array('bank_account' => 'Select bank account')),
	*/
	'QFX' => array(
	    'name' => 'QFX/OFX/Quickbooks (QBO) format',
	    'select' => array('bank_account' => 'Select bank account'),
	    // QFX/OFX statements carry their own account id and type, so the
	    // picker is only a hint and the user may leave it on "(none)".
	    'optional' => array('bank_account'),
	),
	'ro_wmmc_csv' => array('name' => 'WMMC, CSV format', 'select' => array('bank_account' => 'Select bank account')),
	);
}

/**
 * Whether a selectable parser field has to be filled before parsing.
 *
 * A field that the parser does not declare for selection is never required.
 * A declared field is required unless the parser lists it under 'optional'.
 *
 * @param string $parserId Parser key from getParsers().
 * @param string $param    Select field name, e.g. 'bank_account'.
 * @return bool
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function parser_field_is_required($parserId, $param)
{
    $parsers = getParsers();
    if (!isset($parsers[$parserId]['select'][$param])) {
        return false;
    }
    return empty($parsers[$parserId]['optional'])
	|| !in_array($param, $parsers[$parserId]['optional'], true);
}

?>
