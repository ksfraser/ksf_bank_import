<?php
function do_upload_form($error = '') {
	require_once __DIR__ . '/src/Ksfraser/FaBankImport/Views/UploadFormView.php';
	$_parsers = getParsers();
	$parsers = array();
	foreach($_parsers as $pid => $pdata) {
		$parsers[$pid] = $pdata['name'];
	}
	$selected_parser = isset($_POST['parser']) && isset($_parsers[$_POST['parser']]) ? $_POST['parser'] : (array_key_exists('QFX', $parsers) ? 'QFX' : array_key_first($parsers));
	\Ksfraser\FaBankImport\Views\UploadFormView::render($parsers, $selected_parser, $error);
}