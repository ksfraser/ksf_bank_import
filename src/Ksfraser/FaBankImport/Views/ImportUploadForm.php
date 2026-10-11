<?php

namespace Ksfraser\FaBankImport\Views;

/*
use Ksfraser\HTML\HTML_LABEL_ROW;
use Ksfraser\HTML\HTML_ROW_LABELDecorator;
require_once( __DIR__ . "/HTML/HTML_ROW_LABELDecorator.php" );
*/


/**//***********************
* Class to return the list of parsers
*
* @todo alter parsers so that they register with this class so we don't have to hardcode the list
*
* @since 20250716
*/
class Parsers
{
	public static function getParsers():array
	{
		//TODO: Refactor to use namespaces and the FaBankImport directory
		global $path_to_root;
		include_once($path_to_root . "/modules/bank_import/includes/parser.php");
		/*
		include_once($path_to_root . "/modules/bank_import/includes/mt940_parser.php");
		include_once($path_to_root . "/modules/bank_import/includes/ro_brd_mt940_parser.php");
		include_once($path_to_root . "/modules/bank_import/includes/ro_bcr_csv_parser.php");
		include_once($path_to_root . "/modules/bank_import/includes/ro_ing_csv_parser.php");
		*/
		include_once($path_to_root . "/modules/bank_import/includes/ro_wmmc_csv_parser.php");
		include_once($path_to_root . "/modules/bank_import/includes/qfx_parser.php");
		
		return array(
		        //'ro_brd_mt940' => array('name' => 'BRD-RO, MT940 format'),
		        //'ro_bcr_csv' => array('name' => 'BCR-RO, CSV format'),
		        //'ro_ing_csv' => array('name' => 'ING-RO, CSV format', 'select' => array('bank_account' => 'Select bank account')),
		        'QFX' => array('name' => 'QFX/OFX/Quickbooks (QBO) format', 'select' => array('bank_account' => 'Select bank account')),
		        'ro_wmmc_csv' => array('name' => 'WMMC, CSV format', 'select' => array('bank_account' => 'Select bank account')),
		        );
	}
	/**//**
	* Take array from getParsers and reformat!
	*
	* @since 20250716
	* @param array
	* @return array
	*/
	public static function getParserArray( $_parsers ):array
	{
    		$parsers = array();
		if( null === $_parsers )
		{
    			$_parsers = self::getParsers();
		}
    		foreach($_parsers as $pid => $pdata) {
        		$parsers[$pid] = $pdata['name'];
    		}
		return $parsers;
	}
}



/**
*class AddVendorButton
*{
*        protected $HTML_LABEL_ROW;
*        function __construct( int $index )
*        {
*                $data = submit( "AddVendor[$index]", _("AddVendor"), false, '', 'default' );
*                $label =  "Add Vendor" ;
*                $this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator(  $data, $label );
*                //label_row("Add Vendor", submit("AddVendor[$this->id]",_("AddVendor"),false, '', 'default'));
*        }
*        function toHTML()
*        {
*                $this->HTML_LABEL_ROW->toHTML();
*        }
*}
**/

/**//********************************************
* Class to display the upload form
*
* @todo refactor to use our HTML display classes
*
* @since 20250716
*
*/
class ImportUploadForm
{
	public static function getImportUploadForm()
	{
    		div_start('doc_tbl');
    		start_table(TABLESTYLE);
    		$th = array(_("Select File(s) and type"), '');
    		table_header($th);

    		$_parsers = Parsers::getParsers();
		$parsers = Parsers::getParserArray( $_parsers );
    		label_row(_("Format:"), array_selector('parser', null, $parsers, array('select_submit' => true)));
    		foreach($_parsers[$_POST['parser']]['select'] as $param => $label) 
		{

        		switch($param) 
			{
            			case 'bank_account':
                			bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
            				break;

        		}
    		}
    		label_row(_("Files"), "<input type='file' name='files[]' multiple />");
    		start_row();
    		label_cell('Upload', "class='label'");
    		submit_cells('upload', _("Upload"));
    		end_row();
    		end_table(1);
    		div_end();
	}
}

?>
