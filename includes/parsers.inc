<?php


// Use local includes or stubs for dev/testing
@include_once(__DIR__ . '/parser.php');
// @include_once(__DIR__ . '/mt940_parser.php');
// @include_once(__DIR__ . '/ro_brd_mt940_parser.php');
// @include_once(__DIR__ . '/ro_bcr_csv_parser.php');
// @include_once(__DIR__ . '/ro_ing_csv_parser.php');
@include_once(__DIR__ . '/ro_wmmc_csv_parser.php');
@include_once(__DIR__ . '/qfx_parser.php');
@include_once(__DIR__ . '/../vendor/ksfraser/qifparser/qif_parser.php');

function getParsers() {
	require_once __DIR__ . '/../src/Ksfraser/FaBankImport/config/ParserConfig.php';
	$parsers = array();
	$parsersDir = dirname(__DIR__) . '/Parsers';
	if (!is_dir($parsersDir)) return $parsers;
	$dirs = scandir($parsersDir);
	$enabledStates = \Ksfraser\FaBankImport\Config\ParserConfig::getAll();
	foreach ($dirs as $dir) {
		if ($dir === '.' || $dir === '..') continue;
		$parserJson = $parsersDir . '/' . $dir . '/parser.json';
		if (is_file($parserJson)) {
			$config = json_decode(file_get_contents($parserJson), true);
			if (!empty($enabledStates[$dir])) {
				$pid = $dir;
				$parsers[$pid] = array(
					'name' => $config['name'],
					'description' => $config['description'],
					'namespace' => $config['namespace'],
					'class' => $config['class'],
					'filetype' => $config['filetype'],
					'select' => array('bank_account' => 'Select bank account'),
				);
			}
		}
	}
	// Always include QFX parser (legacy, not modularized yet)
	$parsers['QFX'] = array('name' => 'QFX/OFX/Quickbooks (QBO) format', 'select' => array('bank_account' => 'Select bank account'));
	// Always include QIF parser (legacy, not modularized yet)
	$parsers['QIF'] = array('name' => 'QIF (Quicken Interchange Format)', 'select' => array('bank_account' => 'Select bank account'));
	return $parsers;
}

?>
