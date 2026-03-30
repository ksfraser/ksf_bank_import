<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Staging table for counterparty/payee information from various payment sources
 * (OFX, PayPal, Intuit, MT940)
 * 
 * This is a consolidation of the legacy bi_counterparty_model class.
 */
class Counterparty
{
    // Payment card data
    public $card_type;
    public $card_number;
    public $receipt_sent;
    public $receipt_email;
    public $receipt_mobile_number;

    // OFX identifiers
    public $bank_id;
    public $bank_name;
    public $account_id;
    public $FID; // OFX FID
    public $org; // OFX org

    // Transaction details
    public $memo;
    public $name;
    public $currency;

    // Vendor classification
    public $vendor_SIC;
    public $accountName;

    // PayPal/payment details
    public $from_email;
    public $to_email;
    public $shipping_address;
    public $ship_addr_status;
    public $address1;
    public $address2;
    public $city;
    public $state;
    public $zip;
    public $country;
    public $phone;

    // Additional metadata
    public $subject;
    public $country_code;

    // Classification
    public $counterpartyType;
    public $counterpartyId;
    public $inserted_fa; // bool, has been added to FA

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the counterparty identifier/name
     */
    public function getIdentifier(): string
    {
        return $this->name ?? $this->accountName ?? "[ID: {$this->counterpartyId}]";
    }

    /**
     * Check if counterparty has been inserted into FA
     */
    public function isInsertedInFA(): bool
    {
        return (bool)$this->inserted_fa;
    }
}
