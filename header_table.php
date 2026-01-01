<?php

require_once( '../ksf_modules_common/class.origin.php' );

/**//******************************************************************************
* File to generate the HEADER table on the process_statements
*
*	The process_statements has become very large and unwieldly.
*
*	This is to take code out of that file.
*
*	Also, this code could be used for other modules that need to filter on dates.
*
******************************************************************************************/
class ksf_modules_table_filter_by_date extends origin
{
	protected $cell1;	//!<array "label", "var_name", "type" (callback), "options"
	protected $cell2;	//!<array "label", "var_name", "type" (callback), "options"
	protected $cell3;	//!<array "label", "var_name", "type" (callback), "options"
	protected $cell4;	//!<array "label", "var_name", "type" (callback), "options"
	protected $cell5;	//!<array "label", "var_name", "type" (callback), "options"
	protected $cell6;	//!<array "label", "var_name", "type" (callback), "options"

	/**//*******************************************************************************
	*
	***********************************************************************************/
	function __construct()
	{
		parent::__construct();
	}
	/**//********************************************************
	* Display the table
	*
	*************************************************************/
	function display( $tablestype = TABLESTYLE_NOBORDER )
	{
 		// this is filter table
        	start_table( $tablestyle );
        	start_row();
        	if (!isset($_POST['statusFilter']))
        	        $_POST['statusFilter'] = 0;
        	if (!isset($_POST['TransAfterDate']))
        	        $_POST['TransAfterDate'] = begin_month(Today());

        	if (!isset($_POST['TransToDate']))
        	        $_POST['TransToDate'] = end_month(Today());

		if( isset( $this->cell1 ) )
		{
			$this->cell1['type']( _( "$this->cell1['label']:" ), $this->cell1['var_name'], $this->cell1['options'] );
		}
		if( isset( $this->cell2 ) )
		{
			$this->cell2['type']( _( "$this->cell2['label']:" ), $this->cell2['var_name'], $this->cell2['options'] );
		}
		if( isset( $this->cell3 ) )
		{
			$this->cell3['type']( _( "$this->cell3['label']:" ), $this->cell3['var_name'], $this->cell3['options'] );
		}
		if( isset( $this->cell4 ) )
		{
			$this->cell4['type']( _( "$this->cell4['label']:" ), $this->cell4['var_name'], $this->cell4['options'] );
		}
		if( isset( $this->cell5 ) )
		{
			$this->cell5['type']( _( "$this->cell5['label']:" ), $this->cell5['var_name'], $this->cell5['options'] );
		}
		if( isset( $this->cell6 ) )
		{
			$this->cell6['type']( _( "$this->cell6['label']:" ), $this->cell6['var_name'], $this->cell6['options'] );
		}
	        end_row();
	        end_table();
	}
	function bank_import_header( $tablestype = TABLESTYLE_NOBORDER )
	{
 		// this is filter table
        	start_table( $tablestyle );
        	start_row();
        	if (!isset($_POST['statusFilter']))
        	        $_POST['statusFilter'] = 0;
        	if (!isset($_POST['TransAfterDate']))
        	        $_POST['TransAfterDate'] = begin_month(Today());

        	if (!isset($_POST['TransToDate']))
        	        $_POST['TransToDate'] = end_month(Today());
        	date_cells(_("From:"), 'TransAfterDate', '', null, -30);
        	date_cells(_("To:"), 'TransToDate', '', null, 1);
        	label_cells(_("Status:"), array_selector('statusFilter', $_POST['statusFilter'], array(0 => 'Unsettled', 1 => 'Settled', 255 => 'All')));
/**Mantis 3188
* Filter by Bank account
* /
		require_once( '../ksf_modules_common/class.fa_bank_transfer.php' );
		$ba_model = new fa_bank_accounts_MODEL();
		$ba_view = new fa_bank_accounts_VIEW( $ba_model );
		$ba_view->set( "b_showNoneAll", true );
/*
		$ba_view->bank_accounts_list_row( _("Filter by Bank Account") , 'bank_account_filter', null, false);
*/
/** ! 3188 */
	        submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
	        end_row();
	        end_table();
	}
}

