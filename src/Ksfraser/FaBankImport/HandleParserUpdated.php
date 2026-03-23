<?php

namespace Ksfraser\FaBankImport;
class HandleParserUpdated
{
    /**
    * This class handles the logic for when a parser is updated in the upload form.
    * It checks if the '_parser_update' POST parameter is set and, if so, activates the 'doc_tbl' section of the page using AJAX.
    * This allows the upload form to refresh and show any changes related to the selected parser without a full page reload.
    */
static public function parserUpdated()
    {
        if (function_exists('get_post') && get_post('_parser_update')) {
	        if (isset($Ajax)) {
		        $Ajax->activate('doc_tbl');
	        }
        }
    }
}