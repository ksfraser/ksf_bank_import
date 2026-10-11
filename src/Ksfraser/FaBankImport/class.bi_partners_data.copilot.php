<?php

class BiPartnersData extends GenericFaInterfaceModel
{
    protected $partnerId;       //!< int
    protected $partnerDetailId; //!< int
    protected $partnerType;     //!< int
    protected $data;            //!< string
    protected $updatedTs;       //!< date

    public function __construct()
    {
        parent::__construct(null, null, null, null, null);
        $this->iam = 'bi_partners_data';
        $this->defineTable();
    }

    public function defineTable()
    {
        $ind = 'id';
        $this->fieldsArray[] = [
            'name' => 'updated_ts',
            'label' => 'Last Update',
            'type' => 'timestamp',
            'null' => 'NOT NULL',
            'default' => 'CURRENT_TIMESTAMP',
            'readwrite' => 'readwrite'
        ];

        if (strlen($this->companyPrefix) < 2) {
            $this->companyPrefix = TB_PREF;
        }
        $this->tableDetails['tablename'] = $this->companyPrefix . $this->iam;
        $this->tableDetails['index'][0]['type'] = 'unique';
        $this->tableDetails['index'][0]['columns'] = 'partner_id';
        $this->tableDetails['index'][0]['columns'] = 'partner_detail_id';
        $this->tableDetails['index'][0]['columns'] = 'partner_type';

        $this->fieldsArray[] = [
            'name' => 'partner_id',
            'label' => 'Partner ID',
            'type' => 'int(11)',
            'null' => 'NOT NULL',
            'readwrite' => 'readwrite',
            'comment' => '',
            'default' => ''
        ];
        $this->fieldsArray[] = [
            'name' => 'partner_detail_id',
            'label' => 'Partner Details',
            'type' => 'int(11)',
            'null' => 'NOT NULL',
            'readwrite' => 'readwrite',
            'comment' => '',
            'default' => ''
        ];
        $this->fieldsArray[] = [
            'name' => 'partner_type',
            'label' => 'Partner Type',
            'type' => 'int(11)',
            'null' => 'NOT NULL',
            'readwrite' => 'readwrite',
            'comment' => '',
            'default' => ''
        ];
        $this->fieldsArray[] = [
            'name' => 'data',
            'label' => 'Bank Transaction Data',
            'type' => 'varchar(256)',
            'null' => 'NOT NULL',
            'readwrite' => 'readwrite',
            'comment' => '',
            'default' => ''
        ];
    }

    /**
     * Get the Partner Data
     *
     * @param int $partnerId Foreign key to Supplier/Customer/Bank tables (From bank)
     * @param int $partnerType Indicate which foreign table
     * @param int $partnerDetailId -1 for supplier, BRANCH id for Customer, TO Bank
     * @return array
     */
    public function getPartnerData($partnerId, $partnerType, $partnerDetailId)
    {
        $sql = "
        SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE partner_id=" . db_escape($partnerId) . " AND partner_type=" . db_escape($partnerType);
        if ($partnerType == PT_CUSTOMER || $partnerType == ST_BANKTRANSFER) {
            $sql .= " AND partner_detail_id=" . db_escape($partnerDetailId);
        }
        $result = db_query($sql, "could not get partner data");
        return db_fetch($result);
    }

    /**
     * Insert BANK partner data. Wrapper on set_partner_data
     *
     * @param int $fromBankId From Bank key (Bank table)
     * @param int $partnerType Indicate which foreign table
     * @param int $toBankId TO Bank key (Bank table)
     * @param string $data The data sent from the bank
     * @return void
     */
    public function setBankPartnerData($fromBankId, $partnerType = ST_BANKTRANSFER, $toBankId, $data)
    {
        $this->setPartnerData($fromBankId, $partnerType, $toBankId, $data);
    }

    /**
     * Insert partner data
     *
     * The key on this table is partner_id-partner_detail_id-partner_type
     * Added a check for matches to prevent duplicate entries.
     *
     * @param int $partnerId Foreign key to Supplier/Customer/Bank tables (From bank)
     * @param int $partnerType Indicate which foreign table
     * @param int $partnerDetailId -1 for supplier, BRANCH id for Customer, TO Bank
     * @param string $data The data sent from the bank
     * @return void
     */
    public function setPartnerData($partnerId, $partnerType, $partnerDetailId, $data)
    {
        $arr = $this->getPartnerData($partnerId, $partnerType, $partnerDetailId);
        if (count($arr) > 0) {
            if ($arr['data'] == $data) {
                return;
            } else {
                $match = $this->searchPartnerByBankAccount($partnerType, $data);
                if ($match['partner_id'] == $partnerId) {
                    return;
                }
            }
        }

        $sql = "
        INSERT INTO " . TB_PREF . "bi_partners_data(partner_id, partner_type, partner_detail_id, data) VALUES(" .
            db_escape($partnerId) . "," . db_escape($partnerType) . "," . db_escape($partnerDetailId) . "," . db_escape($data) . ")
        ON DUPLICATE KEY UPDATE
            data=" . db_escape($data);
        db_query($sql, 'Could not update partner');
    }

    /**
     * Search for partner data by needle
     *
     * @param string $needle
     * @return array
     */
    public function searchPartnerDataByNeedle($needle)
    {
        if (empty($needle)) {
            return array();
        }

        $sql = "
        SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE data LIKE '%" . $needle . "%'";
        $result = db_query($sql, "could not get search partner");

        $arr = array();
        while ($row = db_fetch($result)) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * Search for partner by bank account
     *
     * @param int $partnerType
     * @param string $needle
     * @return array
     */
    public function searchPartnerByBankAccount($partnerType, $needle)
    {
        if (empty($needle)) {
            return array();
        }

        $sql = "
        SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE partner_type=" . db_escape($partnerType) . " AND data LIKE '%" . $needle . "%' LIMIT 1";
        $result = db_query($sql, "could not get search partner");
        return db_fetch($result);
    }

    /**
     * Update partner data
     *
     * @param int $partnerId
     * @param int $partnerType
     * @param int $partnerDetailId
     * @param string $data
     * @return void
     */
    public function updatePartnerData($partnerId, $partnerType, $partnerDetailId, $data)
    {
        $accountN = "\n";
        $sql = "
        INSERT INTO " . TB_PREF . "bi_partners_data(partner_id, partner_type, partner_detail_id, data) VALUES(" .
            db_escape($partnerId) . "," . db_escape($partnerType) . "," . db_escape($partnerDetailId) . "," . db_escape($data) . ")
        ON DUPLICATE KEY UPDATE
            data=CONCAT(data, " . db_escape($accountN) . ")";
        db_query($sql, 'Could not update partner');
    }
}
