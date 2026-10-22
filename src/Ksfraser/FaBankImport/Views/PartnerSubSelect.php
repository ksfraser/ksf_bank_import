<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**//******************
* This subselect depends on Transaction Type.
* TODO: Refactor so that the transaction provides this!!
*
*/
/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class PartnerSubSelect implements HtmlElementInterface
{
	protected $row;
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$id = $bi_lineitem->id;
		$data =  hidden("title_$id", $bi_lineitem->transactionTitle );
		$data .= hidden("memo_$id", $bi_lineitem->memo );
		$label = "Partner Sub Select";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}

/**
                switch( $_POST['partnerType'][$this->id] )
                {
                        case 'SP':
                                $this->displaySupplierPartnerType();
                                break;
                        case 'CU':
                                $this->displayCustomerPartnerType();
                                break;
                        case 'BT':      //partnerType
                                $this->displayBankTransferPartnerType();
                                break;
                        // quick entry
                        case 'QE':      //partnerType
                                $this->displayQuickEntryPartnerType();
                                break;
                        case 'MA':
                                $this->displayMatchedPartnerType();
                                break;
                        case 'ZZ':      //partnerType
                                //Matched an existing item
                                if( isset( $this->matching_trans[0] ) )
                                {
                                        //if( isset( $this->matching_trans[0] ) )
                                        //{
                                                hidden("partnerId_$this->id", $this->matching_trans[0]['type'] );
                                        //}
                                        //if( isset( $this->matching_trans[0]['type_no'] ) )
                                        //{
                                                hidden("partnerDetailId_$this->id", $this->matching_trans[0]['type_no'] );
                                                hidden("trans_no_$this->id", $this->matching_trans[0]['type_no'] );
                                        //}
                                        //if( isset( $this->matching_trans[0]['type'] ) )
                                        //{
                                                hidden("trans_type_$this->id", $this->matching_trans[0]['type'] );
                                        //}
                                        //if( isset( $this->memo ) )
                                        //{
                                                hidden("memo_$this->id", $this->memo );
                                        //}
                                        //if( isset( $this->transactionTitle ) )
                                        //{
                                                hidden("title_$this->id", $this->transactionTitle );
                                        //}
                                }
                                break;
                }

                        // text_input( "Invoice_$this->id", 0, 6, '', _("Invoice to Allocate Payment:") ) );
                label_row(
                        (_("Comment:")),
                        text_input( "comment_$this->id", $this->memo, strlen($this->memo), '', _("Comment:") )
                );
                label_row("", submit("ProcessTransaction[$this->id]",_("Process"),false, '', 'default'));
*/
