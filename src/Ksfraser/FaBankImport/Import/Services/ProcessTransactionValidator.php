<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use Ksfraser\FaBankImport\Import\Results\ValidationResult;

/**
 * Service for validating POST data in transaction processing workflows.
 * 
 * Centralizes validation of $_POST parameters, ensuring required fields
 * are present, guarded against injection, and properly typed.
 */
class ProcessTransactionValidator
{
    /**
     * Validate POST data for transaction processing action.
     *
     * @param array $post $_POST array
     * @param int $transactionId
     * @return ValidationResult
     */
    public function validatePostData(array $post, int $transactionId): ValidationResult
    {
        $result = ValidationResult::valid();
        
        // Check for ProcessTransaction action
        if (empty($post['ProcessTransaction'])) {
            return $result->addError('ProcessTransaction action not found in POST data');
        }
        
        // Validate partner type exists
        if (empty($post['partnerType'])) {
            result->addFieldError('partnerType', 'Partner type is required');
        } else {
            $result->recordRuleCheck('partnerType_provided', true);
        }
        
        // Validate partner ID exists (guarded access)
        $partnerId = $post['partnerId'] ?? null;
        if ($partnerId === null) {
            $result->addFieldError('partnerId', 'Partner ID is required');
        } else {
            $result->recordRuleCheck('partnerId_provided', true);
        }
        
        // Validate collection IDs (guarded access with default)
        $collectionIds = $post['cids'][$transactionId] ?? '';
        if (empty($collectionIds)) {
            $result->recordRuleCheck('collectionIds_provided', false);
            $result->addWarning('No collection IDs provided for transaction');
        } else {
            $result->recordRuleCheck('collectionIds_provided', true);
        }
        
        return $result;
    }

    /**
     * Validate collection IDs format.
     *
     * @param string $collectionIdsCsv Comma-separated collection IDs
     * @return ValidationResult
     */
    public function validateCollectionIds(string $collectionIdsCsv): ValidationResult
    {
        $result = ValidationResult::valid();
        
        if (empty($collectionIdsCsv)) {
            return $result;
        }
        
        $ids = array_filter(array_map('trim', explode(',', $collectionIdsCsv)));
        
        foreach ($ids as $id) {
            if (!is_numeric($id) || (int)$id <= 0) {
                $result->addFieldError('collectionIds', "Invalid collection ID: {$id}");
            }
        }
        
        $result->recordRuleCheck('collectionIds_valid_format', $result->isValid());
        
        return $result;
    }

    /**
     * Validate partner type value.
     *
     * @param string $partnerType
     * @param array $supportedTypes
     * @return ValidationResult
     */
    public function validatePartnerType(string $partnerType, array $supportedTypes = []): ValidationResult
    {
        $result = ValidationResult::valid();
        
        if (empty($partnerType)) {
            return $result->addFieldError('partnerType', 'Partner type cannot be empty');
        }
        
        // Default supported types if not provided
        if (empty($supportedTypes)) {
            $supportedTypes = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ', 'DE', 'SU', 'VE', 'EM', 'BR'];
        }
        
        if (!in_array($partnerType, $supportedTypes, true)) {
            $result->addFieldError('partnerType', "Unsupported partner type: {$partnerType}");
        }
        
        $result->recordRuleCheck('partnerType_valid', $result->isValid());
        
        return $result;
    }
}
