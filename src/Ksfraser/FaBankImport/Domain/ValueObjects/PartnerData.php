<?php

namespace Ksfraser\FaBankImport\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing partner data entry
 *
 * Immutable object that encapsulates partner identification and their associated
 * keyword data for pattern matching.
 *
 * @package Ksfraser\FaBankImport\Domain\ValueObjects
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class PartnerData
{
    /**
     * @var int Partner ID (customer_id, supplier_id, etc.)
     */
    private int $partnerId;

    /**
     * @var int Partner type constant (PT_CUSTOMER, PT_SUPPLIER, etc.)
     */
    private int $partnerType;

    /**
     * @var int Partner detail ID (branch_id, dimension_id, etc.)
     */
    private int $partnerDetailId;

    /**
     * @var string The keyword/pattern data for matching
     */
    private string $data;

    /**
     * @var int Number of times this keyword has been matched
     */
    private int $occurrenceCount;

    /**
     * Create a new PartnerData value object
     *
     * @param int    $partnerId       Partner ID (must be positive)
     * @param int    $partnerType     Partner type constant
     * @param int    $partnerDetailId Detail ID (0 for none)
     * @param string $data            Keyword/pattern data (non-empty)
     * @param int    $occurrenceCount Occurrence count (non-negative, default 1)
     *
     * @throws InvalidArgumentException If any parameter is invalid
     */
    public function __construct(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data,
        int $occurrenceCount = 1
    ) {
        $this->validatePartnerId($partnerId);
        $this->validatePartnerType($partnerType);
        $this->validatePartnerDetailId($partnerDetailId);
        $this->validateData($data);
        $this->validateOccurrenceCount($occurrenceCount);

        $this->partnerId = $partnerId;
        $this->partnerType = $partnerType;
        $this->partnerDetailId = $partnerDetailId;
        $this->data = trim($data);
        $this->occurrenceCount = $occurrenceCount;
    }

    /**
     * Get partner ID
     *
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * Get partner type
     *
     * @return int
     */
    public function getPartnerType(): int
    {
        return $this->partnerType;
    }

    /**
     * Get partner detail ID
     *
     * @return int
     */
    public function getPartnerDetailId(): int
    {
        return $this->partnerDetailId;
    }

    /**
     * Get keyword/pattern data
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Get occurrence count
     *
     * @return int
     */
    public function getOccurrenceCount(): int
    {
        return $this->occurrenceCount;
    }

    /**
     * Create a new instance with incremented occurrence count
     *
     * Since this is an immutable value object, this returns a new instance
     * rather than modifying the current one.
     *
     * @param int $increment Amount to increment by (default 1)
     *
     * @return self New instance with updated count
     * @throws InvalidArgumentException If increment is negative
     */
    public function withIncrementedCount(int $increment = 1): self
    {
        if ($increment < 0) {
            throw new InvalidArgumentException('Increment must be non-negative');
        }

        return new self(
            $this->partnerId,
            $this->partnerType,
            $this->partnerDetailId,
            $this->data,
            $this->occurrenceCount + $increment
        );
    }

    /**
     * Check if this partner data equals another
     *
     * @param PartnerData $other
     *
     * @return bool
     */
    public function equals(PartnerData $other): bool
    {
        return $this->partnerId === $other->partnerId
            && $this->partnerType === $other->partnerType
            && $this->partnerDetailId === $other->partnerDetailId
            && $this->data === $other->data;
    }

    /**
     * Get unique key for this partner data entry
     *
     * Used for checking uniqueness constraint
     *
     * @return string
     */
    public function getUniqueKey(): string
    {
        return sprintf(
            '%d_%d_%d_%s',
            $this->partnerId,
            $this->partnerType,
            $this->partnerDetailId,
            $this->data
        );
    }

    /**
     * Convert to array representation
     *
     * Useful for database storage or serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'partner_id' => $this->partnerId,
            'partner_type' => $this->partnerType,
            'partner_detail_id' => $this->partnerDetailId,
            'data' => $this->data,
            'occurrence_count' => $this->occurrenceCount,
        ];
    }

    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     *
     * @return self
     * @throws InvalidArgumentException If required fields are missing or invalid
     */
    public static function fromArray(array $data): self
    {
        $required = ['partner_id', 'partner_type', 'partner_detail_id', 'data'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        return new self(
            (int)$data['partner_id'],
            (int)$data['partner_type'],
            (int)$data['partner_detail_id'],
            (string)$data['data'],
            isset($data['occurrence_count']) ? (int)$data['occurrence_count'] : 1
        );
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'PartnerData[partner_id=%d, type=%d, detail_id=%d, data="%s", count=%d]',
            $this->partnerId,
            $this->partnerType,
            $this->partnerDetailId,
            $this->data,
            $this->occurrenceCount
        );
    }

    /**
     * Validate partner ID
     *
     * @param int $partnerId
     *
     * @throws InvalidArgumentException
     */
    private function validatePartnerId(int $partnerId): void
    {
        if ($partnerId <= 0) {
            throw new InvalidArgumentException(
                "Partner ID must be positive, got: {$partnerId}"
            );
        }
    }

    /**
     * Validate partner type
     *
     * @param int $partnerType
     *
     * @throws InvalidArgumentException
     */
    private function validatePartnerType(int $partnerType): void
    {
        // Partner type can be any integer (PT_CUSTOMER, PT_SUPPLIER, etc.)
        // We don't validate against specific constants to avoid tight coupling
        // The database foreign key will enforce validity
    }

    /**
     * Validate partner detail ID
     *
     * @param int $partnerDetailId
     *
     * @throws InvalidArgumentException
     */
    private function validatePartnerDetailId(int $partnerDetailId): void
    {
        if ($partnerDetailId < 0) {
            throw new InvalidArgumentException(
                "Partner detail ID must be non-negative, got: {$partnerDetailId}"
            );
        }
    }

    /**
     * Validate data string
     *
     * @param string $data
     *
     * @throws InvalidArgumentException
     */
    private function validateData(string $data): void
    {
        $trimmed = trim($data);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Data string cannot be empty');
        }

        if (strlen($trimmed) > 255) {
            throw new InvalidArgumentException(
                sprintf('Data string too long (max 255 chars): %d', strlen($trimmed))
            );
        }
    }

    /**
     * Validate occurrence count
     *
     * @param int $occurrenceCount
     *
     * @throws InvalidArgumentException
     */
    private function validateOccurrenceCount(int $occurrenceCount): void
    {
        if ($occurrenceCount < 0) {
            throw new InvalidArgumentException(
                "Occurrence count must be non-negative, got: {$occurrenceCount}"
            );
        }
    }
}
