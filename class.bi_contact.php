<?php

use Ksfraser\Contact\DTO\ContactData;

// Required for banking_base parent class
require_once __DIR__ . '/includes/banking.php';

/**
 * @author Kevin Fraser
 * @since 20260320
 */

/************************************************************************************
 * Contact Model and Repository Class
 *
 * Handles CRUD operations for the 0_bi_contact table and provides integration
 * with the ContactData DTO (Ksfraser\Contact\DTO\ContactData) for normalized
 * contact information storage and retrieval.
 *
 * The contact table stores normalized contact/payee information extracted from
 * bank statements, OFX/QIF/CSV files, and other sources. This class manages:
 * - Database persistence of contact records
 * - Conversion between ContactData DTO and database records
 * - Contact deduplication by unique name constraint
 * - Transaction statistics tracking (count, last_ts, total_amount)
 * - CRM linkage (FrontAccounting customer/supplier IDs)
 * 
 *************************************************************************************/

class bi_contact extends banking_base {
	
	// Primary key and timestamps
	var $id = 0;
	var $created_ts = '';
	var $updated_ts = '';
	
	// Core identifiers
	var $name = '';                    // UNIQUE constraint; used for deduplication
	var $display_name = '';            // User-friendly display name
	
	// Contact classification
	var $contact_type = 'unknown';     // One of: vendor|customer|unknown
	var $is_active = 1;                // Boolean flag (1=active, 0=inactive)
	
	// Contact details
	var $email = '';
	var $phone = '';
	var $phone_extension = '';
	var $fax = '';
	var $mobile = '';
	var $website = '';
	
	// Normalized address fields
	var $address_line_1 = '';
	var $address_line_2 = '';
	var $city = '';
	var $state_province = '';
	var $postal_code = '';
	var $country = '';
	var $country_code = '';            // ISO 3166-1 alpha-2 (e.g., 'US', 'CA')
	
	// Business details
	var $company_name = '';
	var $department = '';
	var $contact_person = '';
	var $tax_id = '';                  // VAT ID, EIN, SSN, etc.
	var $registration_number = '';     // Business registration number
	
	// Notes and metadata
	var $notes = '';
	var $tags = '';                    // Comma-separated tags
	
	// CRM linkage
	var $fa_customer_id = '';
	var $fa_supplier_id = '';
	
	// Transaction statistics
	var $transaction_count = 0;
	var $last_transaction_ts = '';
	var $total_amount = 0;
	
	// Database connection (injected or use global)
	public $db = null;
	
	function __construct($db = null) {
		// Only call parent constructor if it exists
		if (@method_exists(parent::class, '__construct')) {
			parent::__construct();
		}
		$this->db = $db;
	}
	
	/**
	 * Set the database connection
	 * @param object $db Database connection object
	 */
	public function setDatabase($db) {
		$this->db = $db;
	}
	
	/**
	 * Get database connection
	 * @return object Database connection
	 */
	public function getDB() {
		if ($this->db === null) {
			global $db;
			$this->db = $db;
		}
		return $this->db;
	}
	
	/**
	 * Create a new contact record in the database
	 * @param ContactData $contactData The contact data to insert
	 * @param bool $check_duplicate If true, check for existing contact by name
	 * @return int|false Contact ID on success, false on failure or if duplicate found
	 */
	public function create(ContactData $contactData, $check_duplicate = true) {
		
		// Check for duplicates by unique name constraint
		if ($check_duplicate && !empty($contactData->name)) {
			$existing = $this->findByName($contactData->name);
			if ($existing) {
				return false; // Duplicate name
			}
		}
		
		$db = $this->getDB();
		
		// Prepare insert data from ContactData
		$data = array(
			'name'                => $contactData->name,
			'display_name'        => $contactData->display_name,
			'contact_type'        => $contactData->contact_type,
			'is_active'           => $contactData->is_active,
			'email'               => $contactData->email,
			'phone'               => $contactData->phone,
			'phone_extension'    => $contactData->phone_extension,
			'fax'                 => $contactData->fax,
			'mobile'              => $contactData->mobile,
			'website'             => $contactData->website,
			'address_line_1'      => $contactData->address_line_1,
			'address_line_2'      => $contactData->address_line_2,
			'city'                => $contactData->city,
			'state_province'      => $contactData->state_province,
			'postal_code'         => $contactData->postal_code,
			'country'             => $contactData->country,
			'country_code'        => $contactData->country_code,
			'company_name'        => $contactData->company_name,
			'department'          => $contactData->department,
			'contact_person'      => $contactData->contact_person,
			'tax_id'              => $contactData->tax_id,
			'registration_number' => $contactData->registration_number,
			'notes'               => $contactData->notes,
			'tags'                => $contactData->tags,
			'fa_customer_id'      => $contactData->fa_customer_id,
			'fa_supplier_id'      => $contactData->fa_supplier_id,
			'transaction_count'   => $contactData->transaction_count,
			'last_transaction_ts' => $contactData->last_transaction_ts,
			'total_amount'        => $contactData->total_amount
		);
		
		// Build INSERT query
		$columns = implode(', `', array_keys($data));
		$values = array_values($data);
		$placeholders = implode(', ', array_fill(0, count($values), '?'));
		
		$sql = "INSERT INTO `0_bi_contact` (`$columns`) VALUES ($placeholders)";
		
		// Execute insert
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		if (!call_user_func_array(array($stmt, 'bind_param'), $this->buildBindParams($values))) {
			return false;
		}
		
		if (!$stmt->execute()) {
			return false;
		}
		
		$insert_id = $stmt->insert_id;
		$stmt->close();
		
		// Load the newly created record into this object
		if ($insert_id) {
			$this->load($insert_id);
		}
		
		return $insert_id;
	}
	
	/**
	 * Read a contact record by ID
	 * @param int $id Contact ID
	 * @return bool True if found and loaded, false otherwise
	 */
	public function load($id) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `id` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		$types = 'i';
		$stmt->bind_param($types, $id);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		
		if (!$row) {
			return false;
		}
		
		// Populate this object from the database row
		$this->fromArray($row);
		return true;
	}
	
	/**
	 * Update an existing contact record
	 * @param ContactData $contactData Updated contact data
	 * @return bool True on success
	 */
	public function update(ContactData $contactData) {
		if (empty($this->id)) {
			return false; // No ID to update
		}
		
		$db = $this->getDB();
		
		// Prepare update data
		$data = array(
			'name'                => $contactData->name,
			'display_name'        => $contactData->display_name,
			'contact_type'        => $contactData->contact_type,
			'is_active'           => $contactData->is_active,
			'email'               => $contactData->email,
			'phone'               => $contactData->phone,
			'phone_extension'    => $contactData->phone_extension,
			'fax'                 => $contactData->fax,
			'mobile'              => $contactData->mobile,
			'website'             => $contactData->website,
			'address_line_1'      => $contactData->address_line_1,
			'address_line_2'      => $contactData->address_line_2,
			'city'                => $contactData->city,
			'state_province'      => $contactData->state_province,
			'postal_code'         => $contactData->postal_code,
			'country'             => $contactData->country,
			'country_code'        => $contactData->country_code,
			'company_name'        => $contactData->company_name,
			'department'          => $contactData->department,
			'contact_person'      => $contactData->contact_person,
			'tax_id'              => $contactData->tax_id,
			'registration_number' => $contactData->registration_number,
			'notes'               => $contactData->notes,
			'tags'                => $contactData->tags,
			'fa_customer_id'      => $contactData->fa_customer_id,
			'fa_supplier_id'      => $contactData->fa_supplier_id,
			'transaction_count'   => $contactData->transaction_count,
			'last_transaction_ts' => $contactData->last_transaction_ts,
			'total_amount'        => $contactData->total_amount
		);
		
		// Build UPDATE query
		$set_clauses = array();
		$values = array();
		foreach ($data as $key => $value) {
			$set_clauses[] = "`$key` = ?";
			$values[] = $value;
		}
		$values[] = $this->id; // Add ID for WHERE clause
		
		$sql = "UPDATE `0_bi_contact` SET " . implode(', ', $set_clauses) . " WHERE `id` = ?";
		
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		if (!call_user_func_array(array($stmt, 'bind_param'), $this->buildBindParams($values))) {
			return false;
		}
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$stmt->close();
		
		// Update this object
		$this->fromArray($data);
		
		return true;
	}
	
	/**
	 * Delete a contact record
	 * @param int $id Contact ID
	 * @return bool True on success
	 */
	public function delete($id = null) {
		$delete_id = ($id !== null) ? $id : $this->id;
		
		if (empty($delete_id)) {
			return false;
		}
		
		$db = $this->getDB();
		
		$sql = "DELETE FROM `0_bi_contact` WHERE `id` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		$types = 'i';
		$stmt->bind_param($types, $delete_id);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$stmt->close();
		return true;
	}
	
	/**
	 * Find contact by unique name
	 * @param string $name Contact name
	 * @return bi_contact|false Loaded contact object or false if not found
	 */
	public function findByName($name) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `name` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		$types = 's';
		$stmt->bind_param($types, $name);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		
		if (!$row) {
			return false;
		}
		
		// Create new instance and load the data
		$contact = new bi_contact($db);
		$contact->fromArray($row);
		return $contact;
	}
	
	/**
	 * Find contacts by email
	 * @param string $email Email address
	 * @return array Array of bi_contact objects
	 */
	public function findByEmail($email) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `email` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return array();
		}
		
		$types = 's';
		$stmt->bind_param($types, $email);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return array();
		}
		
		$result = $stmt->get_result();
		$contacts = array();
		
		while ($row = $result->fetch_assoc()) {
			$contact = new bi_contact($db);
			$contact->fromArray($row);
			$contacts[] = $contact;
		}
		
		$stmt->close();
		return $contacts;
	}
	
	/**
	 * Find contacts by FA customer ID
	 * @param string $fa_id FrontAccounting customer ID
	 * @return bi_contact|false Loaded contact or false
	 */
	public function findByFACustomerId($fa_id) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `fa_customer_id` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		$types = 's';
		$stmt->bind_param($types, $fa_id);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		
		if (!$row) {
			return false;
		}
		
		$contact = new bi_contact($db);
		$contact->fromArray($row);
		return $contact;
	}
	
	/**
	 * Find contacts by FA supplier ID
	 * @param string $fa_id FrontAccounting supplier ID
	 * @return bi_contact|false Loaded contact or false
	 */
	public function findByFASupplierId($fa_id) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `fa_supplier_id` = ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		
		$types = 's';
		$stmt->bind_param($types, $fa_id);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		
		if (!$row) {
			return false;
		}
		
		$contact = new bi_contact($db);
		$contact->fromArray($row);
		return $contact;
	}
	
	/**
	 * Get all active contacts
	 * @param int $limit Max results
	 * @return array Array of bi_contact objects
	 */
	public function getAllActive($limit = 1000) {
		$db = $this->getDB();
		
		$sql = "SELECT * FROM `0_bi_contact` WHERE `is_active` = 1 LIMIT ?";
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return array();
		}
		
		$types = 'i';
		$stmt->bind_param($types, $limit);
		
		if (!$stmt->execute()) {
			$stmt->close();
			return array();
		}
		
		$result = $stmt->get_result();
		$contacts = array();
		
		while ($row = $result->fetch_assoc()) {
			$contact = new bi_contact($db);
			$contact->fromArray($row);
			$contacts[] = $contact;
		}
		
		$stmt->close();
		return $contacts;
	}
	
	/**
	 * Convert this bi_contact to a ContactData DTO
	 * @return ContactData DTO object
	 */
	public function toContactData() {
		$dto = new ContactData();
		$dto->id = $this->id;
		$dto->name = $this->name;
		$dto->display_name = $this->display_name;
		$dto->contact_type = $this->contact_type;
		$dto->is_active = $this->is_active;
		$dto->email = $this->email;
		$dto->phone = $this->phone;
		$dto->phone_extension = $this->phone_extension;
		$dto->fax = $this->fax;
		$dto->mobile = $this->mobile;
		$dto->website = $this->website;
		$dto->address_line_1 = $this->address_line_1;
		$dto->address_line_2 = $this->address_line_2;
		$dto->city = $this->city;
		$dto->state_province = $this->state_province;
		$dto->postal_code = $this->postal_code;
		$dto->country = $this->country;
		$dto->country_code = $this->country_code;
		$dto->company_name = $this->company_name;
		$dto->department = $this->department;
		$dto->contact_person = $this->contact_person;
		$dto->tax_id = $this->tax_id;
		$dto->registration_number = $this->registration_number;
		$dto->notes = $this->notes;
		$dto->tags = $this->tags;
		$dto->fa_customer_id = $this->fa_customer_id;
		$dto->fa_supplier_id = $this->fa_supplier_id;
		$dto->transaction_count = $this->transaction_count;
		$dto->last_transaction_ts = $this->last_transaction_ts;
		$dto->total_amount = $this->total_amount;
		$dto->created_ts = $this->created_ts;
		$dto->updated_ts = $this->updated_ts;
		return $dto;
	}
	
	/**
	 * Create bi_contact from ContactData DTO
	 * @param ContactData $dto The DTO to convert
	 * @return bi_contact New instance populated from DTO
	 */
	public static function fromContactData(ContactData $dto, $db = null) {
		$contact = new bi_contact($db);
		$contact->id = $dto->id;
		$contact->name = $dto->name;
		$contact->display_name = $dto->display_name;
		$contact->contact_type = $dto->contact_type;
		$contact->is_active = $dto->is_active;
		$contact->email = $dto->email;
		$contact->phone = $dto->phone;
		$contact->phone_extension = $dto->phone_extension;
		$contact->fax = $dto->fax;
		$contact->mobile = $dto->mobile;
		$contact->website = $dto->website;
		$contact->address_line_1 = $dto->address_line_1;
		$contact->address_line_2 = $dto->address_line_2;
		$contact->city = $dto->city;
		$contact->state_province = $dto->state_province;
		$contact->postal_code = $dto->postal_code;
		$contact->country = $dto->country;
		$contact->country_code = $dto->country_code;
		$contact->company_name = $dto->company_name;
		$contact->department = $dto->department;
		$contact->contact_person = $dto->contact_person;
		$contact->tax_id = $dto->tax_id;
		$contact->registration_number = $dto->registration_number;
		$contact->notes = $dto->notes;
		$contact->tags = $dto->tags;
		$contact->fa_customer_id = $dto->fa_customer_id;
		$contact->fa_supplier_id = $dto->fa_supplier_id;
		$contact->transaction_count = $dto->transaction_count;
		$contact->last_transaction_ts = $dto->last_transaction_ts;
		$contact->total_amount = $dto->total_amount;
		$contact->created_ts = $dto->created_ts;
		$contact->updated_ts = $dto->updated_ts;
		return $contact;
	}
	
	/**
	 * Populate object properties from array (typically from database)
	 * @param array $data Key-value pairs matching property names
	 */
	public function fromArray($data) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
	
	/**
	 * Export object properties to array
	 * @param bool $include_timestamps Include created_ts and updated_ts
	 * @return array All properties as key-value pairs
	 */
	public function toArray($include_timestamps = true) {
		$result = array(
			'id' => $this->id,
			'name' => $this->name,
			'display_name' => $this->display_name,
			'contact_type' => $this->contact_type,
			'is_active' => $this->is_active,
			'email' => $this->email,
			'phone' => $this->phone,
			'phone_extension' => $this->phone_extension,
			'fax' => $this->fax,
			'mobile' => $this->mobile,
			'website' => $this->website,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city' => $this->city,
			'state_province' => $this->state_province,
			'postal_code' => $this->postal_code,
			'country' => $this->country,
			'country_code' => $this->country_code,
			'company_name' => $this->company_name,
			'department' => $this->department,
			'contact_person' => $this->contact_person,
			'tax_id' => $this->tax_id,
			'registration_number' => $this->registration_number,
			'notes' => $this->notes,
			'tags' => $this->tags,
			'fa_customer_id' => $this->fa_customer_id,
			'fa_supplier_id' => $this->fa_supplier_id,
			'transaction_count' => $this->transaction_count,
			'last_transaction_ts' => $this->last_transaction_ts,
			'total_amount' => $this->total_amount
		);
		
		if ($include_timestamps) {
			$result['created_ts'] = $this->created_ts;
			$result['updated_ts'] = $this->updated_ts;
		}
		
		return $result;
	}
	
	/**
	 * Build bind_param compatible parameter array
	 * Helper for mysqli prepared statements
	 * @param array $values Values to bind
	 * @return array Array with types string as first element
	 */
	private function buildBindParams($values) {
		$types = '';
		foreach ($values as $value) {
			if (is_int($value)) {
				$types .= 'i';
			} elseif (is_float($value)) {
				$types .= 'd';
			} else {
				$types .= 's';
			}
		}
		return array_merge(array($types), $values);
	}
	
	/**
	 * Debug dump of contact data
	 */
	public function dump() {
		$output = "========== bi_contact Dump ==========\n";
		$output .= "ID: {$this->id}\n";
		$output .= "Name: {$this->name}\n";
		$output .= "Display: {$this->display_name}\n";
		$output .= "Type: {$this->contact_type}\n";
		$output .= "Email: {$this->email}\n";
		$output .= "Phone: {$this->phone}\n";
		$output .= "Address: {$this->address_line_1}, {$this->city}, {$this->state_province} {$this->postal_code}\n";
		$output .= "Company: {$this->company_name}\n";
		$output .= "FA Customer: {$this->fa_customer_id}\n";
		$output .= "FA Supplier: {$this->fa_supplier_id}\n";
		$output .= "Transactions: {$this->transaction_count} (Total: {$this->total_amount})\n";
		$output .= "Created: {$this->created_ts}\n";
		$output .= "Updated: {$this->updated_ts}\n";
		$output .= "=====================================\n";
		return $output;
	}
}

?>
