<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\Contact\DTO\ContactData;
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;

require_once __DIR__ . '/../../../class.bi_contact.php';

/**
 * ContactService
 * 
 * Wrapper service for the bi_contact model providing high-level contact management operations.
 * Centralizes contact CRUD logic, deduplication, and business rule enforcement.
 *
 * Phase 4 Refactoring: Uses BankAccountMappingRepository for bank account linkages,
 * following SRP and repository pattern for all OFX identifier lookups.
 *
 * @author Kevin Fraser
 * @since 20260322
 */
class ContactService
{
    /**
     * @var object Database connection
     */
    private $db;

    /**
     * @var \bi_contact Contact model instance
     */
    private $contactModel;

    /**
     * @var BankAccountMappingRepository Bank account mapping repository
     */
    private $bankAccountMappingRepository;

    /**
     * Constructor
     *
     * @param object $db Database connection (mysqli)
     * @param BankAccountMappingRepository|null $bankAccountMappingRepository Optional repository instance
     */
    public function __construct($db = null, ?BankAccountMappingRepository $bankAccountMappingRepository = null)
    {
        $this->db = $db;
        $this->contactModel = new \bi_contact($db);
        $this->bankAccountMappingRepository = $bankAccountMappingRepository ?: new BankAccountMappingRepository();
    }

    /**
     * Get FA bank account mapping by OFX identifiers
     * 
     * Phase 4 Refactoring: Delegates to BankAccountMappingRepository
     * for all OFX identifier lookups.
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
     */
    public function getBankAccountMappingByOFXIdentifiers(?string $bankid, ?string $acctid, ?string $intu_bid)
    {
        try {
            return $this->bankAccountMappingRepository->findByOFXIdentifiers($bankid, $acctid, $intu_bid);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get FA bank account ID for contact by OFX identifiers
     * 
     * Convenience method combining contact operations with bank account lookups.
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return int|null The FA bank account ID or null if not found
     */
    public function getFABankAccountIdByOFXIdentifiers(?string $bankid, ?string $acctid, ?string $intu_bid): ?int
    {
        try {
            $mapping = $this->bankAccountMappingRepository->findByOFXIdentifiers($bankid, $acctid, $intu_bid);
            return $mapping ? $mapping->bank_account_id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get or create contact with deduplication checking
     *
     * @param ContactData $contactData Contact data to get or create
     * @param bool $checkDuplicate Whether to check for duplicate names (default: true)
     * @return \bi_contact|false Contact object or false if creation failed
     */
    public function getOrCreateContact(ContactData $contactData, $checkDuplicate = true)
    {
        if (!$contactData || !$contactData->name) {
            return false;
        }

        // Check if contact exists by name (UNIQUE constraint)
        $existing = $this->findByName($contactData->name);
        if ($existing) {
            return $existing;
        }

        // Create new contact
        return $this->createContact($contactData);
    }

    /**
     * Create a new contact from ContactData DTO
     *
     * @param ContactData $contactData Contact data to create
     * @return \bi_contact|false Created contact object or false if creation failed
     */
    public function createContact(ContactData $contactData)
    {
        if (!$contactData || !$this->db) {
            return false;
        }

        try {
            $contact = new \bi_contact($this->db);
            $result = $contact->create($contactData, true);

            // create() returns contact ID or false
            if ($result) {
                $contact->id = $result;
                return $contact;
            }
            return false;
        } catch (\Exception $e) {
            error_log('ContactService::createContact Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load contact by ID
     *
     * @param int $contactId Contact ID
     * @return \bi_contact|false Contact object or false if not found
     */
    public function getContactById($contactId)
    {
        if (!$contactId || !is_numeric($contactId)) {
            return false;
        }

        $contact = new \bi_contact($this->db);
        return $contact->load($contactId);
    }

    /**
     * Find contact by name (UNIQUE constraint lookup)
     *
     * @param string $name Contact name
     * @return \bi_contact|false Contact object or false if not found
     */
    public function findByName($name)
    {
        if (!$name || !$this->db) {
            return false;
        }

        $contact = new \bi_contact($this->db);
        return $contact->findByName($name);
    }

    /**
     * Find contacts by email address (may return multiple)
     *
     * @param string $email Email address
     * @return array Array of contact objects or empty array if not found
     */
    public function findByEmail($email)
    {
        if (!$email || !$this->db) {
            return [];
        }

        $contact = new \bi_contact($this->db);
        $result = $contact->findByEmail($email);

        return is_array($result) ? $result : [];
    }

    /**
     * Find contact linked to FA customer
     *
     * @param string $faCustomerId FA customer ID
     * @return \bi_contact|false Contact object or false if not found
     */
    public function findByFACustomerId($faCustomerId)
    {
        if (!$faCustomerId || !$this->db) {
            return false;
        }

        $contact = new \bi_contact($this->db);
        return $contact->findByFACustomerId($faCustomerId);
    }

    /**
     * Find contact linked to FA supplier
     *
     * @param string $faSupplerId FA supplier ID
     * @return \bi_contact|false Contact object or false if not found
     */
    public function findByFASupplierId($faSupplierId)
    {
        if (!$faSupplierId || !$this->db) {
            return false;
        }

        $contact = new \bi_contact($this->db);
        return $contact->findByFASupplierId($faSupplierId);
    }

    /**
     * Get all active contacts with optional limit
     *
     * @param int $limit Maximum number of contacts to return (default: 100)
     * @return array Array of contact objects or empty array if none found
     */
    public function getAllActiveContacts($limit = 100)
    {
        if (!$this->db || !is_numeric($limit) || $limit < 1) {
            return [];
        }

        $contact = new \bi_contact($this->db);
        $result = $contact->getAllActive($limit);

        return is_array($result) ? $result : [];
    }

    /**
     * Update existing contact
     *
     * @param \bi_contact $contact Contact object to update
     * @return bool True if update successful, false otherwise
     */
    public function updateContact(\bi_contact $contact)
    {
        if (!$contact || !$contact->id || !$this->db) {
            return false;
        }

        try {
            $contact->setDatabase($this->db);
            $dto = $contact->toContactData();
            return $contact->update($dto);
        } catch (\Exception $e) {
            error_log('ContactService::updateContact Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete contact by ID
     *
     * @param int $contactId Contact ID to delete
     * @return bool True if deletion successful, false otherwise
     */
    public function deleteContact($contactId)
    {
        if (!$contactId || !is_numeric($contactId) || !$this->db) {
            return false;
        }

        $contact = new \bi_contact($this->db);
        return $contact->delete($contactId);
    }

    /**
     * Link contact to FA customer
     *
     * @param \bi_contact $contact Contact object
     * @param string $faCustomerId FA customer ID
     * @return bool True if link successful, false otherwise
     */
    public function linkToFACustomer(\bi_contact $contact, $faCustomerId)
    {
        if (!$contact || !$faCustomerId) {
            return false;
        }

        $contact->fa_customer_id = $faCustomerId;
        return $this->updateContact($contact);
    }

    /**
     * Link contact to FA supplier
     *
     * @param \bi_contact $contact Contact object
     * @param string $faSupplierId FA supplier ID
     * @return bool True if link successful, false otherwise
     */
    public function linkToFASupplier(\bi_contact $contact, $faSupplierId)
    {
        if (!$contact || !$faSupplierId) {
            return false;
        }

        $contact->fa_supplier_id = $faSupplierId;
        return $this->updateContact($contact);
    }

    /**
     * Record transaction against contact
     *
     * @param int $contactId Contact ID
     * @param float $amount Transaction amount
     * @param string $timestamp Transaction timestamp (default: now)
     * @return bool True if recorded successful, false otherwise
     */
    public function recordTransaction($contactId, $amount, $timestamp = null)
    {
        if (!$contactId || !is_numeric($contactId) || !is_numeric($amount)) {
            return false;
        }

        $contact = $this->getContactById($contactId);
        if (!$contact) {
            return false;
        }

        // Update transaction statistics
        $contact->transaction_count++;
        $contact->total_amount += $amount;
        $contact->last_transaction_ts = $timestamp ?? date('Y-m-d H:i:s');

        return $this->updateContact($contact);
    }

    /**
     * Merge duplicate contacts (update references to target contact)
     * Note: Requires transaction link table to exist for full merge
     *
     * @param int $sourceContactId Contact ID to remove (source - to be deleted)
     * @param int $targetContactId Contact ID to keep (target)
     * @return bool True if merge successful, false otherwise
     */
    public function mergeContacts($sourceContactId, $targetContactId)
    {
        if (!$sourceContactId || !$targetContactId || $sourceContactId == $targetContactId) {
            return false;
        }

        if (!$this->db) {
            return false;
        }

        try {
            // Source contact to be merged (delete)
            $source = $this->getContactById($sourceContactId);
            if (!$source) {
                return false;
            }

            // Target contact to keep
            $target = $this->getContactById($targetContactId);
            if (!$target) {
                return false;
            }

            // Merge transaction statistics
            $target->transaction_count += $source->transaction_count;
            $target->total_amount += $source->total_amount;

            // Update target with merged data
            $this->updateContact($target);

            // Update all transactions pointing to source to point to target
            // This assumes 0_bi_transactions table has contact_id FK
            $this->updateTransactionContactReferences(
                $sourceContactId,
                $targetContactId
            );

            // Delete source contact
            return $this->deleteContact($sourceContactId);
        } catch (\Exception $e) {
            error_log('ContactService::mergeContacts Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update transaction contact references during merge
     *
     * @param int $oldContactId Old contact ID
     * @param int $newContactId New contact ID
     * @return bool True if update successful
     */
    private function updateTransactionContactReferences($oldContactId, $newContactId)
    {
        if (!$this->db) {
            return false;
        }

        try {
            $sql = "UPDATE 0_bi_transactions 
                    SET contact_id = ? 
                    WHERE contact_id = ?";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('ii', $newContactId, $oldContactId);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log('ContactService::updateTransactionContactReferences Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get duplicate contacts by name similarity
     *
     * @param string $name Contact name to search for similar names
     * @param int $limit Maximum number of results (default: 10)
     * @return array Array of potential duplicate contacts
     */
    public function findPotentialDuplicates($name, $limit = 10)
    {
        if (!$name || !$this->db || !is_numeric($limit)) {
            return [];
        }

        try {
            // Simple LIKE search for similar names
            $searchTerm = '%' . $name . '%';
            $sql = "SELECT * FROM 0_bi_contact 
                    WHERE name LIKE ? 
                    AND is_active = 1 
                    ORDER BY name 
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param('si', $searchTerm, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $contacts = [];
            while ($row = $result->fetch_assoc()) {
                $contact = new \bi_contact($this->db);
                $contact->fromArray($row);
                $contacts[] = $contact;
            }

            return $contacts;
        } catch (\Exception $e) {
            error_log('ContactService::findPotentialDuplicates Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Convert contact to DTO for API/external use
     *
     * @param \bi_contact $contact Contact object
     * @return ContactData|null DTO or null if conversion failed
     */
    public function convertToDTO(\bi_contact $contact)
    {
        if (!$contact) {
            return null;
        }

        try {
            return $contact->toContactData();
        } catch (\Exception $e) {
            error_log('ContactService::convertToDTO Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert DTO to contact model
     *
     * @param ContactData $dto Contact DTO
     * @return \bi_contact|null Contact model or null if conversion failed
     */
    public function convertFromDTO(ContactData $dto)
    {
        if (!$dto) {
            return null;
        }

        try {
            return \bi_contact::fromContactData($dto, $this->db);
        } catch (\Exception $e) {
            error_log('ContactService::convertFromDTO Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set the database connection for this service
     *
     * @param object $db Database connection
     * @return self Fluent interface
     */
    public function setDatabase($db)
    {
        $this->db = $db;
        $this->contactModel->setDatabase($db);
        return $this;
    }

    /**
     * Get the database connection
     *
     * @return object Database connection
     */
    public function getDatabase()
    {
        return $this->db;
    }
}
