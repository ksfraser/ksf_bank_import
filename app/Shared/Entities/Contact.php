<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

/**
 * Contact/payee repository with full CRUD, deduplication by name, and CRM linkage
 * 
 * This is a consolidation of the legacy bi_contact class.
 */
class Contact
{
    public $id;
    public $created_ts;
    public $updated_ts;
    
    // Identification
    public $name;
    public $display_name;
    public $contact_type;
    public $is_active;

    // Contact details
    public $email;
    public $phone;
    public $fax;
    public $mobile;
    public $website;

    // Address
    public $address_line_1;
    public $address_line_2;
    public $city;
    public $state_province;
    public $postal_code;
    public $country;
    public $country_code;

    // Business details
    public $company_name;
    public $department;
    public $contact_person;
    public $tax_id;
    public $registration_number;

    // Metadata
    public $notes;
    public $tags;

    // CRM linkage
    public $fa_customer_id;
    public $fa_supplier_id;

    // Transaction stats
    public $transaction_count;
    public $last_transaction_ts;
    public $total_amount;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the primary contact name for display
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? $this->name ?? "[ID: {$this->id}]";
    }

    /**
     * Check if contact is linked to FA
     */
    public function isLinkedToFA(): bool
    {
        return !empty($this->fa_customer_id) || !empty($this->fa_supplier_id);
    }

    /**
     * Get the FA entity type (customer or supplier)
     */
    public function getFAType(): ?string
    {
        if (!empty($this->fa_customer_id)) return 'customer';
        if (!empty($this->fa_supplier_id)) return 'supplier';
        return null;
    }

    /**
     * Get the FA entity ID
     */
    public function getFAId(): ?int
    {
        return $this->fa_customer_id ?? $this->fa_supplier_id;
    }
}
