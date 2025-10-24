<?php

declare(strict_types=1);

namespace Ksfraser;

/**
 * PartnerFormData
 *
 * Encapsulates access to partner form data from $_POST superglobal.
 * Provides type-safe getters and setters for partner-related form fields.
 *
 * Single Responsibility: Manage partner form data access and persistence.
 *
 * This class eliminates direct $_POST manipulation in Views and follows
 * the Tell Don't Ask principle by providing a clean API for form data.
 *
 * @package    Ksfraser
 * @author     Kevin Fraser / ChatGPT
 * @since      2025-01-07
 * @version    1.0.0
 *
 * @example
 * ```php
 * // Initialize for a specific line item
 * $formData = new PartnerFormData(123);
 *
 * // Set values
 * $formData->setPartnerId(456);
 * $formData->setPartnerDetailId(789);
 *
 * // Get values
 * $partnerId = $formData->getPartnerId();      // Returns 456
 * $detailId = $formData->getPartnerDetailId();  // Returns 789
 *
 * // Check existence
 * if ($formData->hasPartnerId()) {
 *     // Process...
 * }
 * ```
 */
class PartnerFormData
{
    /**
     * @var int The line item ID
     */
    private int $lineItemId;

    /**
     * @var FormFieldNameGenerator Field name generator
     */
    private FormFieldNameGenerator $fieldGenerator;

    /**
     * Constructor
     *
     * @param int                         $lineItemId     The line item ID
     * @param FormFieldNameGenerator|null $fieldGenerator Optional field name generator
     *
     * @since 2025-01-07
     */
    public function __construct(
        int $lineItemId,
        ?FormFieldNameGenerator $fieldGenerator = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->fieldGenerator = $fieldGenerator ?? new FormFieldNameGenerator();
    }

    /**
     * Get partner ID from POST data
     *
     * @return int|null The partner ID or null if not set
     *
     * @since 2025-01-07
     */
    public function getPartnerId(): ?int
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        
        if (!isset($_POST[$fieldName])) {
            return null;
        }
        
        $value = $_POST[$fieldName];
        
        // Handle empty string or ANY_NUMERIC constant
        if ($value === '' || $value === ANY_NUMERIC) {
            return null;
        }
        
        return (int)$value;
    }

    /**
     * Set partner ID in POST data
     *
     * @param int|null $partnerId The partner ID to set
     *
     * @return self For method chaining
     *
     * @since 2025-01-07
     */
    public function setPartnerId(?int $partnerId): self
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        
        if ($partnerId === null) {
            $_POST[$fieldName] = ANY_NUMERIC;
        } else {
            $_POST[$fieldName] = $partnerId;
        }
        
        return $this;
    }

    /**
     * Check if partner ID exists in POST data
     *
     * @return bool True if partner ID is set and not empty
     *
     * @since 2025-01-07
     */
    public function hasPartnerId(): bool
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        
        return isset($_POST[$fieldName]) && $_POST[$fieldName] !== '' && $_POST[$fieldName] !== ANY_NUMERIC;
    }

    /**
     * Get partner detail ID from POST data
     *
     * @return int|null The partner detail ID or null if not set
     *
     * @since 2025-01-07
     */
    public function getPartnerDetailId(): ?int
    {
        $fieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
        
        if (!isset($_POST[$fieldName])) {
            return null;
        }
        
        $value = $_POST[$fieldName];
        
        // Handle empty string or ANY_NUMERIC constant
        if ($value === '' || $value === ANY_NUMERIC) {
            return null;
        }
        
        return (int)$value;
    }

    /**
     * Set partner detail ID in POST data
     *
     * @param int|null $partnerDetailId The partner detail ID to set
     *
     * @return self For method chaining
     *
     * @since 2025-01-07
     */
    public function setPartnerDetailId(?int $partnerDetailId): self
    {
        $fieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
        
        if ($partnerDetailId === null) {
            $_POST[$fieldName] = ANY_NUMERIC;
        } else {
            $_POST[$fieldName] = $partnerDetailId;
        }
        
        return $this;
    }

    /**
     * Check if partner detail ID exists in POST data
     *
     * @return bool True if partner detail ID is set and not empty
     *
     * @since 2025-01-07
     */
    public function hasPartnerDetailId(): bool
    {
        $fieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
        
        return isset($_POST[$fieldName]) && $_POST[$fieldName] !== '' && $_POST[$fieldName] !== ANY_NUMERIC;
    }

    /**
     * Get raw partner ID value from POST (including ANY_NUMERIC)
     *
     * This is useful for passing to FA functions that expect the raw value.
     *
     * @return mixed The raw partner ID value from POST
     *
     * @since 2025-01-07
     */
    public function getRawPartnerId()
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        
        return $_POST[$fieldName] ?? '';
    }

    /**
     * Get raw partner detail ID value from POST (including ANY_NUMERIC)
     *
     * This is useful for passing to FA functions that expect the raw value.
     *
     * @return mixed The raw partner detail ID value from POST
     *
     * @since 2025-01-07
     */
    public function getRawPartnerDetailId()
    {
        $fieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
        
        return $_POST[$fieldName] ?? '';
    }

    /**
     * Clear partner ID from POST data
     *
     * @return self For method chaining
     *
     * @since 2025-01-07
     */
    public function clearPartnerId(): self
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        
        unset($_POST[$fieldName]);
        
        return $this;
    }

    /**
     * Clear partner detail ID from POST data
     *
     * @return self For method chaining
     *
     * @since 2025-01-07
     */
    public function clearPartnerDetailId(): self
    {
        $fieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
        
        unset($_POST[$fieldName]);
        
        return $this;
    }

    /**
     * Get the line item ID
     *
     * @return int The line item ID
     *
     * @since 2025-01-07
     */
    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }

    /**
     * Get the field name generator
     *
     * @return FormFieldNameGenerator The field name generator
     *
     * @since 2025-01-07
     */
    public function getFieldGenerator(): FormFieldNameGenerator
    {
        return $this->fieldGenerator;
    }
}
