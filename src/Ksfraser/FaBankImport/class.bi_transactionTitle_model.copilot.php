<?php

/**
 * Table and handling class for staging of imported financial data
 *
 * This table will hold each record that we are importing. That way we can check if
 * we have already seen the record when re-processing the same file, or perhaps one
 * from the same source that overlaps dates so we would have duplicate data.
 */

require_once('../ksf_modules_commone/class.generic_fa_interface.php');
require_once('../ksf_modules_commone/defines.inc.php');

/**
 * A DATA class to handle the storage and retrieval of bank records. STAGE the records before processing into FA.
 *
 * ***** WARNING *** WARNING *** WARNING *****
 * MySQL has a row limit of 4k. Having a bunch of large fields can lead to errors and issues.
 */
class bi_transactionTitle_model extends generic_fa_interface_model 
{
    public const STOCK_ID_LENGTH = 256; // Example constant, adjust as needed

    protected $id_bi_transactionTitle_model; //!< Index of table
    protected $transaction_id; // Dream Payments order_num, WooCommerce, OFX TRNUID, MT940, Paypal
    protected $bi_transactionTitle; // all the titles concatenated
    protected $bi_transactionTitle1; // MT940
    protected $bi_transactionTitle2; // MT940
    protected $bi_transactionTitle3; // MT940
    protected $bi_transactionTitle4; // MT940
    protected $bi_transactionTitle5; // MT940
    protected $bi_transactionTitle6; // MT940
    protected $bi_transactionTitle7; // MT940
    protected $bi_transactionTitle8; // MT940
    protected $bi_transactionTitle9; // MT940
    protected $staging_id; // The ID of the staging table record that is the master for this record

    public function __construct()
    {
        // Initialize class properties or perform setup here
    }

    public function define_table(): void
    {
        $ind = "id_" . $this->iam;
        $this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read');
        $this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read');
        $this->table_details['tablename'] = $this->company_prefix . $this->iam;
        $this->table_details['primarykey'] = $ind;
        $this->table_details['orderby'] = 'transaction_date, transaction_id';
        $this->table_details['index'][0]['type'] = 'unique';
        $this->table_details['index'][0]['columns'] = "transaction_id";
        $this->table_details['index'][0]['keyname'] = "transaction_id";

        $this->fields_array[] = array('name' => 'transaction_id', 'label' => 'transaction_id', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'WooCommerce, OFX TRNUID, etc.');
        $this->fields_array[] = array('name' => 'transactionTitle', 'label' => 'BI Transaction Title', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title');
        $this->fields_array[] = array('name' => 'transactionTitle1', 'label' => 'BI Transaction Title 1', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 1');
        $this->fields_array[] = array('name' => 'transactionTitle2', 'label' => 'BI Transaction Title 2', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 2');
        $this->fields_array[] = array('name' => 'transactionTitle3', 'label' => 'BI Transaction Title 3', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 3');
        $this->fields_array[] = array('name' => 'transactionTitle4', 'label' => 'BI Transaction Title 4', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 4');
        $this->fields_array[] = array('name' => 'transactionTitle5', 'label' => 'BI Transaction Title 5', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 5');
        $this->fields_array[] = array('name' => 'transactionTitle6', 'label' => 'BI Transaction Title 6', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 6');
        $this->fields_array[] = array('name' => 'transactionTitle7', 'label' => 'BI Transaction Title 7', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 7');
        $this->fields_array[] = array('name' => 'transactionTitle8', 'label' => 'BI Transaction Title 8', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 8');
        $this->fields_array[] = array('name' => 'transactionTitle9', 'label' => 'BI Transaction Title 9', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'BI Transaction Title 9');
        $this->fields_array[] = array('name' => 'staging_id', 'label' => 'Master Record in Staging Table', 'type' => 'varchar(' . self::STOCK_ID_LENGTH . ')', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => 'Master Record in Staging Table');
    }

    public function insert_transaction(): void
    {
        $this->insert_data(get_object_vars($this));
    }
}
