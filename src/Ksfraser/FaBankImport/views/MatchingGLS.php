<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class MatchingGLS implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		$data = new MatchingGLFactory( $bi_lineitem );
		$label = "Matching GLs:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	function getHtml()
	{
		$this->row->getHtml();
	}
	function toHtml()
	{
		$this->row->toHtml();
	}
}

class MatchingGLFactory
{
	function __construct( $bi_lineitem )
	{
	}
}

/**
 if( count( $this->matching_trans ) > 0 )
                {
                        $match_html = "";
                        $matchcount = 1;
                        foreach( $this->matching_trans as $matchgl )
                        {
                                //[type] => 0 [type_no] => 8811 [tran_date] => 2023-01-03 [account] => 2620.frontier [memo_] => 025/2023
                                //      [amount] => 432.41 [person_type_id] => [person_id] => [account_name] => Auto Loan Frontier (Nissan Finance) [reference] => 025/2023 [score] => 111 [is_invoice]
                                if( isset( $matchgl['tran_date'] ) )
                                {
                                        /******************************************************************************************
                                        * In Savings Account, Customer Payment is a DEBIT.
                                        * NISSAN is a DEBIT out of Savings in the IMPORT file.  So amount in example should be -
                                        *
                                        *Customer Payment is a CREDIT from import file.  Amount should match exact the Bank trans.
                                        *
                                        * so if the bank account number matches and adjusted amount matches...
                                        *****************************************************************************************/
                                        $match_html .= "<b>$matchcount</b>: ";
                                        unset( $param );
                                        $param = array();
                                        if( ! @include_once( __DIR__  . "/../ksf_modules_common/defines.inc.php") )
                                        {
                                                $param[] = array( "type_id" => $trans_types_readable[$matchgl['type']] );
                                                $param[] = array( "trans_no" => $matchgl['type_no'] );
                                                $URL = "../../gl/view/gl_trans_view.php";
                                                $text = " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];

                                                $match_html .= $this->makeURLLink( $URL, $param, $text );
                                                //$match_html .= " Transaction " . $trans_types_readable[$matchgl['type']] . ":" . $matchgl['type_no'];
                                        }
                                        else
                                        {
                                                $type = $matchgl['type'];
                                                $type_no = $matchgl['type_no'];
                                                $param[] = array( "type_id" => $type );
                                                $param[] = array( "trans_no" => $type_no );
                                                $URL = "../../gl/view/gl_trans_view.php";
                                                $text = " Transaction " . $matchgl['type'] . ":" . $matchgl['type_no'];

                                                $match_html .= $this->makeURLLink( $URL, $param, $text );
                                                //$match_html .= " Transaction " . $matchgl['type'] . ":" . $matchgl['type_no'];
                                        }
                                        $match_html .= " Score " . $matchgl['score'] . " ";
                                        if( strcasecmp( $this->our_account, $matchgl['account'] ) OR strcasecmp( $this->ourBankDetails['bank_account_name'], $matchgl['account'] ) )
                                        {
                                                $match_html .= "Account <b>" . $matchgl['account'] . "</b> ";
                                        }
                                        else
                                        {
                                                $match_html .= "MATCH BANK:: ";
                                                $match_html .=  print_r( $our_account, true );
                                                $match_html .= "::" . print_r( $this->ourBankDetails['bank_account_name'], true );
                                                $match_html .= " Matching " . print_r( $matchgl, true );
                                                $match_html .= "Account " . $matchgl['account'] . "---";
                                        }
                                        $match_html .= " " . $matchgl['account_name'] . " ";
                                        if( $this->transactionDC == 'D' )
                                        {
                                                $scoreamount = -1 * $this->amount;
                                        }
                                        else
                                        {
                                                $scoreamount = 1 * $this->amount;
                                        }
                                        if( $scoreamount == $matchgl['amount'] )
                                        {
                                                $match_html .= "<b> " . $matchgl['amount'] . "</b> ";
                                        }
                                        else
 					{
                                                $match_html .= $matchgl['amount'];
                                        }
                                        if( isset( $matchgl["person_type_id"] ) )
                                        {
                                                require_once( __DIR__ . '/Views/TransactionCustomerDetails.php' );
                                                $cdet = new TransactionCustomerDetails( $matchgl['type'], $matchgl['type_no'] );
                                                $match_html .= $cdet->getLineitemMatchedCustomerDetails();
                                        }
                                        $match_html .= "<br />";
                                        $matchcount++;
                                } //if isset
                        } //foreach
                        label_row("Matching GLs.  Ensure you double check Accounts and Amounts", $match_html );
                }
                else
                {
                                label_row("Matching GLs", "No Matches found automatically" );
                }

