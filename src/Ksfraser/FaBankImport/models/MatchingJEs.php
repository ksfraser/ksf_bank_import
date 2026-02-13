<?php

namespace Ksfraser\FaBankImport\models;

/**
 * MatchingJEs - Find matching Journal Entries for imported transactions
 * 
 * This class encapsulates the logic for finding existing journal entries
 * in FrontAccounting that may match an imported bank transaction.
 * 
 * Extracted from bi_lineitem::findMatchingExistingJE() during refactoring.
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */
class MatchingJEs
{
    protected $bi_lineitem;
    protected $matching_trans = array();
    
    /**
     * Constructor
     * 
     * @param object $bi_lineitem The bi_lineitem object containing transaction details
     */
    public function __construct($bi_lineitem)
    {
        $this->bi_lineitem = $bi_lineitem;
        $this->findMatches();
    }
    
    /**
     * Find matching existing journal entries
     * 
     * The transaction is imported into a bank account, with the counterparty being trz['accountName']
     * Existing transactions will have 2+ line items.  1 should match the bank, one should match the counterparty.
     * Currently we are matching and scoring each of the line items, rather than matching/scoring the GL itself.
     * 
     * Check for matching into the accounts
     * JE# / Date / Account / (Credit/Debit) / Memo in the GL Account (gl/inquiry/gl_account_inquiry.php)
     */
    protected function findMatches()
    {
        $new_arr = array();
        /** Namespace *
        *    use Ksfraser\frontaccounting\FaGl;
        *        Will need to adjust he if( $inc )
        **/
        $faGlFile = __DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php';
        $inc = is_file($faGlFile) ? include_once($faGlFile) : false;
        if( $inc && class_exists('fa_gl') )
        {
            /** Namespace *
             *       $fa_gl = new FaGl();
             *       $fa_gl = new \KSFRASER\FA\fa_gl();
            **/
            $fa_gl = new \fa_gl();
            $fa_gl->set( "amount_min", $this->bi_lineitem->amount );
            $fa_gl->set( "amount_max", $this->bi_lineitem->amount );
            $fa_gl->set( "amount", $this->bi_lineitem->amount );
            $fa_gl->set( "transactionDC", $this->bi_lineitem->transactionDC );
            $fa_gl->set( "days_spread", $this->bi_lineitem->days_spread );
            $fa_gl->set( "startdate", $this->bi_lineitem->valueTimestamp );     //Set converts using sql2date
            $fa_gl->set( "enddate", $this->bi_lineitem->entryTimestamp );       //Set converts using sql2date
            $fa_gl->set( "accountName", $this->bi_lineitem->otherBankAccountName );
            $fa_gl->set( "transactionCode", $this->bi_lineitem->transactionCode );
            $fa_gl->set( "memo_", $this->bi_lineitem->memo );
            

            //Customer E-transfers usually get recorded the day after the "payment date" when recurring invoice, or recorded paid on Quick Invoice
            //              E-TRANSFER 010667466304;CUSTOMER NAME;...
            //      function add_days($date, $days) // accepts negative values as well
            try {
                $new_arr = $fa_gl->find_matching_transactions( $this->bi_lineitem->memo );
                        //display_notification( __FILE__ . "::" . __LINE__ );
            } catch( \Exception $e )
            {
                display_notification(  __FILE__ . "::" . __LINE__ . "::" . $e->getMessage() );
            }
                        //display_notification( __FILE__ . "::" . __LINE__ );
        }
        else
        {
            $new_arr = array();
        }
        $this->matching_trans = $new_arr;
    }
    
    /**
     * Get the array of matching transactions
     * 
     * @return array Array of matching transactions with their scores
     */
    public function getMatchArr()
    {
        return $this->matching_trans;
    }
}
