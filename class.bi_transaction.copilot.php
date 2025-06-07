i<?php

/****************************************************************************************
 * Class for handling the processing of ONE transaction.
 *
 * This class will hold ONE record that we are importing.
 * Extending the _model class so that we can call parent:: calls
 * Also so we inherit the columns.
 *
 * Overriding most of the SQL functions out of the gate.
 ****************************************************************************************/

$path_to_root = "../..";

// TODO
// Update the queries in the functions to use $this->table_details['tablename'] instead of .TB_PREF."bi_transactions 

/*
 *
 * Each import type needs to read in the source document, and process line by line placing a record into this class.
 * This class then needs to insert the record.
 *
 */

require_once(__DIR__ . '/../ksf_modules_common/class.generic_fa_interface.php');
require_once(__DIR__ . '/../ksf_modules_common/defines.inc.php');
require_once('class.bi_transactions.php');

/**
 * Class bi_transaction
 *
 * Handles the processing of a single transaction.
 * Extends the bi_transactions_model to inherit columns and call parent methods.
 */
class bi_transaction extends bi_transactions_model
{
    /**
     * Inherits columns from bi_transactions_model.
     * @var int $partnerId
     * @var string $custBranch
     * @var string $invoiceNo
     */

    protected $partnerId;
    protected $custBranch;
    protected $invoiceNo;

    /**
     * Constructor for bi_transaction class.
     */
    public function __construct()
    {
        parent::__construct(null, null, null, null, null);
        $this->iam = "bi_transaction";
        $this->matched = 0;
        $this->created = 0;
    }

    /**
     * Set the field if possible.
     *
     * @param string $field The field to set.
     * @param mixed $value The value to set.
     * @param bool $enforce Whether to enforce setting the field.
     * @return mixed The result of setting the field.
     * @throws Exception If the field cannot be set.
     */
    public function set(string $field, $value = null, bool $enforce = true)
    {
        return parent::set($field, $value, $enforce);
    }

    /**
     * Extract the variables out of _POST for this id.
     *
     * @param int $id Post ID.
     * @return bool Whether partnerId was set.
     */
    public function extractPost(int $id): bool
    {
        $_cids = isset($_POST['cids'][$id]) ? array_filter(explode(',', $_POST['cids'][$id])) : [];

        $this->setIfExists("partnerId", $_POST["partnerId_$id"] ?? null);
        $this->setIfExists("partnerType", $_POST['partnerType'][$id] ?? null);
        $this->setIfExists("invoiceNo", $_POST["Invoice_$id"] ?? $_POST['Invoice'] ?? null);
        $this->setIfExists("custBranch", $_POST["partnerDetailId_$id"] ?? null);
        $this->setIfExists("fa_trans_type", $_POST["trans_type_$id"] ?? null);
        $this->setIfExists("fa_trans_no", $_POST["trans_no_$id"] ?? null);
        $this->setIfExists("memo", $_POST["memo_$id"] ?? $_POST["title_$id"] ?? null);
        $this->setIfExists("transactionTitle", $_POST["title_$id"] ?? null);

        return isset($this->partnerId);
    }

    /**
     * Set the field if the value exists.
     *
     * @param string $field The field to set.
     * @param mixed $value The value to set.
     */
    private function setIfExists(string $field, $value): void
    {
        if ($value !== null) {
            $this->set($field, $value);
        }
    }

    /**
     * Placeholder for inserting a transaction.
     */
    public function insert_transaction()
    {
        // parent::insert_data(get_object_vars($this));
    }

    /**
     * Update bi_trans clearing status.
     *
     * @param int $tid BI transaction index.
     * @param array $cids List of related transactions.
     * @param int $trans_no The transaction number.
     * @param int $trans_type The transaction type (JE/BP/SP/...).
     */
    public function reset_transactions($tid, $cids, $trans_no, $trans_type)
    {
        // parent::reset_transactions($tid, $cids, $trans_no, $trans_type);
    }

    /**
     * Update bi_trans with the related info to FA gl transactions.
     *
     * @param int $tid BI transaction index.
     * @param array $cids List of related transactions.
     * @param int $status The status to set.
     * @param int $trans_no The transaction number.
     * @param int $trans_type The transaction type (JE/BP/SP/...).
     * @param bool $matched Whether the transaction is matched.
     * @param bool $created Whether the transaction is created.
     * @param string|null $g_partner Transaction type code SP/BT/QE/...
     * @param string $g_option QE or vendor or customer or... int as string.
     */
    public function update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched = 0, $created = 0, $g_partner = null, $g_option = "")
    {
        // parent::update_transactions($tid, $cids, $status, $trans_no, $trans_type, $matched, $created, $g_partner, $g_option);
    }

    /**
     * Update bi_trans with the related info to FA gl transactions.
     *
     * @param int $tid BI transaction index.
     * @param string $account Account.
     * @param string $accountName Account name.
     */
    public function update_transactions_account($tid, $account, $accountName)
    {
        // parent::update_transactions_account($tid, $account, $accountName);
    }

    /**
     * Reset bi_trans data when the related FA gl transaction is voided.
     *
     * @param int|array $type The transaction type (JE/BP/SP/...).
     * @param int $trans_no The transaction number.
     */
    public function db_prevoid($type, $trans_no)
    {
        // parent::db_prevoid($type, $trans_no);
    }

    /**
     * Get transactions details for display.
     *
     * @param int|null $status Status.
     * @return array Transaction rows sorted.
     */
    public function get_transactions($status = null)
    {
        // parent::get_transactions($status);
    }

    /**
     * Get a specific transaction's details.
     *
     * @param int|null $tid Transaction ID.
     * @param bool $bSetInternal Whether to set the internal variables.
     * @return array Transaction row from db.
     */
    public function get_transaction($tid = null, $bSetInternal = false)
    {
        return parent::get_transaction($tid, true);
    }

    /**
     * Get the normal actions for a counterparty.
     *
     * @since 20240729
     * @param string|null $account Account to search for.
     * @return array Transaction rows from db.
     */
    public function get_normal_pairing($account = null)
    {
        // parent::get_normal_pairing($account);
    }

    /**
     * Convert Transaction array to this object.
     *
     * @param array $trz Transaction array.
     * @return int How many fields were copied.
     */
    public function trz2obj($trz)
    {
        return parent::obj2obj($trz);
    }

    /**
     * Hand build the INSERT statement.
     *
     * @return string SQL statement.
     */
    public function hand_insert_sql()
    {
        // parent::hand_insert_sql();
    }

    /**
     * Hand build the UPDATE statement.
     *
     * @return string SQL statement.
     */
    public function hand_update_sql()
    {
        // parent::hand_update_sql();
    }

    /**
     * Determine if this particular transaction already exists in the staging table.
     *
     * @return bool Whether the transaction already exists.
     */
    public function trans_exists()
    {
        // parent::trans_exists();
    }

    /**
     * Update a transaction record.
     *
     * @param array $arr The transaction from bank_import's import.
     * @return bool Success.
     */
    public function update($arr)
    {
        // parent::update();
    }
}
