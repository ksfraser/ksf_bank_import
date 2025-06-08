<?php




///WARNING WARNING WARNING WARNING
This file is NOT complete.  Copilot only provided the first X lines...






/****************************************************************************************
 * Table and handling class for staging of imported financial data
 *
 * This table will hold each record that we are importing. That way we can check if
 * we have already seen the record when re-processing the same file, or perhaps one
 * from the same source that overlaps dates so we would have duplicate data.
 *
 * *************************************************************************************/

$path_to_root = "../..";

/*******************************************
 * If you change the list of properties below, ensure that you also modify
 * build_write_properties_array
 * */

// TODO
// Update the queries in the functions to use $this->table_details['tablename'] instead of .TB_PREF."bi_transactions 

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 * This table should not have any views (forms).
 * */

require_once(__DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php');
require_once(__DIR__ . '/../ksf_modules_common/defines.inc.php');

/**************************************************************************************************************
 * A DATA class to handle the storage and retrieval of bank records. STAGE the records before processing into FA.
 *
 * ***** WARNING *** WARNING *** WARNING *****
 * MySQL has a row limit of 4k. Having a bunch of large fields can lead to errors and issues.
 *
 * +---------------------+--------------+------+-----+---------+----------------+
 * | Field               | Type         | Null | Key | Default | Extra          |
 * +---------------------+--------------+------+-----+---------+----------------+
 * | id                  | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | smt_id              | int(11)      | NO   |     | NULL    |                |
 * | valueTimestamp      | date         | YES  |     | NULL    |                |
 * | entryTimestamp      | date         | YES  |     | NULL    |                |
 * | account             | varchar(24)  | YES  |     | NULL    |                |
 * | accountName         | varchar(60)  | YES  |     | NULL    |                |
 * | transactionType     | varchar(3)   | YES  |     | NULL    |                |
 * | transactionCode     | varchar(32)  | YES  |     | NULL    |                |
 * | transactionCodeDesc | varchar(32)  | YES  |     | NULL    |                |
 * | transactionDC       | varchar(2)   | YES  |     | NULL    |                |
 * | transactionAmount   | double       | YES  |     | NULL    |                |
 * | transactionTitle    | varchar(256) | YES  |     | NULL    |                |
 * | status              | int(11)      | YES  |     | 0       |                |
 * | matchinfo           | varchar(256) | YES  |     | NULL    |                |
 * | fa_trans_type       | int(11)      | YES  |     | 0       |                |
 * | fa_trans_no         | int(11)      | YES  |     | 0       |                |
 * | fitid               | varchar(32)  | NO   |     | NULL    |                |
 * | acctid              | varchar(32)  | NO   |     | NULL    |                |
 * | merchant            | varchar(64)  | NO   |     | NULL    |                |
 * | category            | varchar(64)  | NO   |     | NULL    |                |
 * | sic                 | varchar(64)  | NO   |     | NULL    |                |
 * | memo                | varchar(64)  | NO   |     | NULL    |                |
 * | checknumber         | int(11)      | NO   |     | NULL    |                |
 * | matched             | int(1)       | NO   |     | 0       |                |
 * | created             | int(1)       | NO   |     | 0       |                |
 * +---------------------+--------------+------+-----+---------+----------------+
 *
 **************************************************************************************************************/

class bi_transactions_model extends generic_fa_interface_model
{
    var $id_bi_transactions_model; //!< Index of table
    protected $id; //| int(11) | NO | PRI | NULL | auto_increment |
    protected $smt_id; //| int(11) | NO | | NULL | |
    protected $valueTimestamp; //| date | YES | | NULL | |
    protected $entryTimestamp; //| date | YES | | NULL | |
    protected $account; //| varchar(24) | YES | | NULL | |
    protected $accountName; //| varchar(60) | YES | | NULL | |
    protected $transactionType; //| varchar(3) | YES | | NULL | |
    protected $transactionCode; //| varchar(32) | YES | | NULL | |
    protected $transactionCodeDesc; //| varchar(32) | YES | | NULL | |
    protected $transactionDC; //| varchar(2) | YES | | NULL | |
    protected $transactionAmount; //| double | YES | | NULL | |
    protected $transactionTitle; //| varchar(256) | YES | | NULL | |
    protected $status; //| int(11) | YES | | 0 | |
    protected $matchinfo; //| varchar(256) | YES | | NULL | |
    protected $fa_trans_type; //| int(11) | YES | | 0 | |
    protected $fa_trans_no; //| int(11) | YES | | 0 | |
    protected $fitid;
    protected $acctid;
    protected $merchant; //| varchar(64) | NO | | NULL | |
    protected $category; //| varchar(64) | NO | | NULL | |
    protected $sic; //| varchar(64) | NO | | NULL | |
    protected $memo; //| varchar(64) | NO | | NULL | |
    protected $checknumber; //!< int
    protected $matched; //!< bool
    protected $created; //!< bool
    protected $g_partner; //!< varchar Which action (bank/Quick Entry/...
    protected $g_option; //!< varchar Which choice - ATB/Groceries/...
    protected $limit; //!< int SQL Limit

    function __construct()
    {
        parent::__construct(null, null, null, null, null);
        $this->iam = "bi_transactions";
        $this->define_table();
        $this->matched = 0;
        $this->created = 0;
    }

    function define_table()
    {
        $ind = "id";
        $this->fields_array[] = array('name' => $ind, 'type' => 'int(11)', 'auto_increment' => 'yes', 'readwrite' => 'read');
        $this->fields_array[] = array('name' => 'updated_ts', 'type' => 'timestamp', 'null' => 'NOT NULL', 'default' => 'CURRENT_TIMESTAMP', 'readwrite' => 'read');
        if (strlen($this->company_prefix) < 2) {
            $this->company_prefix = TB_PREF;
        }
        $this->table_details['tablename'] = $this->company_prefix . $this->iam;
        $this->table_details['primarykey'] = $ind;
        $this->table_details['orderby'] = 'valueTimestamp, id';
        $this->fields_array[] = array('name' => 'id', 'label' => 'ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'smt_id', 'label' => 'Statement ID', 'type' => 'int(11)', 'null' => 'NOT NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'valueTimestamp', 'label' => 'Value Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'entryTimestamp', 'label' => 'Entry Timestamp', 'type' => 'date', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'account', 'label' => 'Account', 'type' => 'varchar(24)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'accountName', 'label' => 'Account Name', 'type' => 'varchar(60)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionType', 'label' => 'Transaction Type', 'type' => 'varchar(3)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionCode', 'label' => 'Transaction Code', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionCodeDesc', 'label' => 'Transaction Desc', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionDC', 'label' => 'Transaction DC', 'type' => 'varchar(2)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionAmount', 'label' => 'Transaction Amount', 'type' => 'double', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'transactionTitle', 'label' => 'Transaction Title', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'status', 'label' => 'Status', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
        $this->fields_array[] = array('name' => 'matchinfo', 'label' => 'Match Info', 'type' => 'varchar(256)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'fa_trans_type', 'label' => 'FA Transaction Type', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
        $this->fields_array[] = array('name' => 'fa_trans_no', 'label' => 'FA Transaction Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
        $this->fields_array[] = array('name' => 'fitid', 'label' => 'Financial Institute Transaction ID', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'acctid', 'label' => 'Account ID', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'merchant', 'label' => 'Merchant', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'category', 'label' => 'Category', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'sic', 'label' => 'S I Code', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'memo', 'label' => 'Memo', 'type' => 'varchar(64)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'checknumber', 'label' => 'Check Number', 'type' => 'int(11)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'matched', 'label' => 'Matched', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
        $this->fields_array[] = array('name' => 'created', 'label' => 'Created', 'type' => 'int(1)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
        $this->fields_array[] = array('name' => 'g_partner', 'label' => 'Transaction Type (Partner)', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => 'NULL');
        $this->fields_array[] = array('name' => 'g_option', 'label' => 'Transaction Type Detail', 'type' => 'varchar(32)', 'null' => 'NULL', 'readwrite' => 'readwrite', 'comment' => '', 'default' => '0');
    }

    /**
     * Set the field if possible.
     *
     * Tries to set the field in this class as well as in table_interface,
     * assumption being we are going to do something with the field in
     * the database (else why set the model...).
     *
     * @param string $field
     * @param mixed $value
     * @param bool $enforce
     * @return void
     * @throws Exception
     */
    function set($field, $value = null, $enforce = true)
    {
        if ($field === 'limit' && !is_numeric($value)) {
            throw new Exception("Limit must be a number as its for SQL", KSF_INVALID_DATA_TYPE);
        }
        parent::set($field, $value, $enforce);
    }

    /**
     * Insert a transaction record.
     *
     * @return void
     */
    function insert_transaction()
    {
        $this->insert_data(get_object_vars($this));
    }

    /**
     * Generate summary SQL query for transactions.
     *
     * @param string $TransAfterDate
     * @param string $TransToDate
     * @param int $statusFilter
     * @return resource
     * @throws Exception If query execution fails.
     */
    function summary_sql($TransAfterDate, $TransToDate, $statusFilter)
    {
        $sql = $this->build_summary_sql($TransAfterDate, $TransToDate, $statusFilter);
        $res = db_query($sql, 'unable to get transactions data');
        if (!$res) {
            throw new Exception('Query execution failed.');
        }
        return $res;
    }

    /**
     * Build summary SQL query for transactions.
     *
     * @param string $TransAfterDate
     * @param string $TransToDate
     * @param int $statusFilter
     * @return string
     */
    private function build_summary_sql($TransAfterDate, $TransToDate, $statusFilter)
    {
        $sql = "SELECT t.*, s.account our_account, s.currency FROM " . TB_PREF . "bi_transactions t";
        $sql .= " LEFT JOIN " . TB_PREF . "bi_statements AS s ON t.smt_id = s.id";
        $sql .= " WHERE t.valueTimestamp >= " . db_escape(date2sql($TransAfterDate));
        $sql .= " AND t.valueTimestamp < " . db_escape(date2sql($TransToDate));
        if ($statusFilter != 255) {
            $sql .= " AND t.status = " . db_escape($statusFilter);
        }
        $sql .= " ORDER BY t.valueTimestamp ASC";
        return $sql;
    }

    // Other methods...

    /**
     * Toggle from D to C or C to D.
     *
     * Some banks don't send the data correctly. Toggle the direction.
     *
     * @return void
     * @throws Exception If required field transactionDC is not set or has unexpected value.
     */
    function toggleDebitCredit()
    {
        if (!isset($this->transactionDC)) {
            throw new Exception("Required field transactionDC not set!", KSF_FIELD_NOT_SET);
        }
        switch ($this->transactionDC) {
            case 'D':
                $this->set("transactionDC", "C");
                $this->set("transactionCodeDesc", "Credit");
                break;
            case 'C':
                $this->set("transactionDC", "D");
                $this->set("transactionCodeDesc", "Debit");
                break;
            default:
                throw new Exception("Field transactionDC has unexpected value!", KSF_INVALID_DATA_VALUE);
        }
        $sql = "UPDATE " . TB_PREF . "bi_transactions t ";
       
