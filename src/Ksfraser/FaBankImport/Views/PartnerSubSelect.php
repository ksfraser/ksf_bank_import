<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**//******************
 * @BABOK CLASS ROLLUP: PartnerSubSelect — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/PartnerSubSelect.php; coverage: docs/TestCases/ (file rollup)
* This subselect depends on Transaction Type.
* TODO: Refactor so that the transaction provides this!!
*
*/
class PartnerSubSelect implements HtmlElementInterface
{
	protected $row;
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
	function __construct( $bi_lineitem )
	{
		$id = $bi_lineitem->id;
		$data =  hidden("title_$id", $bi_lineitem->transactionTitle );
		$data .= hidden("memo_$id", $bi_lineitem->memo );
		$label = "Partner Sub Select";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml */
	function getHtml(): string
	{
				return $this->row->getHtml();
		}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml */
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
