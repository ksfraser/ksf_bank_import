<?php
//Initial draft of a CLASS to replace the body of this function
//is in Ksfraser\FaBankImport\Views\ImportUploadForm
//TODO migrate to use the class.
function do_upload_form() {
    $parsers = array();
    $_parsers = getParsers();
    foreach($_parsers as $pid => $pdata) {
	$parsers[$pid] = $pdata['name'];
    }


    div_start('doc_tbl');
    start_table(TABLESTYLE);
    $th = array(_("Select File(s) and type"), '');
    table_header($th);


	$selected_parser = isset($_POST['parser']) && isset($_parsers[$_POST['parser']]) ? $_POST['parser'] : (array_key_exists('QFX', $parsers) ? 'QFX' : array_key_first($parsers));
	label_row(_("Format:"), array_selector('parser', $selected_parser, $parsers, array('select_submit' => true)));
	if (isset($_parsers[$selected_parser]['select']) && is_array($_parsers[$selected_parser]['select'])) {
		foreach($_parsers[$selected_parser]['select'] as $param => $label) {

	switch($param) {
	    case 'bank_account':
		// Bank account selection is resolved post-parse (per-statement) using detected account mapping.
		// Keeping this commented out during UAT in case we reverse course.
		// bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
	    break;

	}