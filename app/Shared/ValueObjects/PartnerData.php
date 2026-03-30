<?php
namespace Ksfraser\FaBankImport\Shared\ValueObjects;

/**
 * Immutable value object representing a single partner keyword entry
 * Used in partner matching logic
 */
class PartnerData
{
    private $partnerId;
    private $partnerType;
    private $partnerDetailId;
    private $data;
    private $occurrenceCount;

    private const PT_CUSTOMER = 1;
    private const PT_SUPPLIER = 2;

    public function __construct(
        int $partnerId,
        int $partnerType,
        string $data,
        int $partnerDetailId = 0,
        int $occurrenceCount = 1
    ) {
        if (!in_array($partnerType, [self::PT_CUSTOMER, self::PT_SUPPLIER])) {
            throw new \InvalidArgumentException("Invalid partner type: {$partnerType}");
        }

        $this->partnerId = $partnerId;
        $this->partnerType = $partnerType;
        $this->partnerDetailId = $partnerDetailId;
        $this->data = trim($data);
        $this->occurrenceCount = max(1, $occurrenceCount);
    }

    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    public function getPartnerType(): int
    {
        return $this->partnerType;
    }

    public function getPartnerDetailId(): int
    {
        return $this->partnerDetailId;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getOccurrenceCount(): int
    {
        return $this->occurrenceCount;
    }

    public function isCustomer(): bool
    {
        return $this->partnerType === self::PT_CUSTOMER;
    }

    public function isSupplier(): bool
    {
        return $this->partnerType === self::PT_SUPPLIER;
    }

    public function hasBranchDetail(): bool
    {
        return $this->partnerDetailId > 0;
    }

    /**
     * Create a copy with incremented occurrence count
     */
    public function withIncrementedOccurrence(): self
    {
        return new self(
            $this->partnerId,
            $this->partnerType,
            $this->data,
            $this->partnerDetailId,
            $this->occurrenceCount + 1
        );
    }

    /**
     * Get a string representation
     */
    public function __toString(): string
    {
        $type = $this->isCustomer() ? 'Customer' : 'Supplier';
        return "{$type}:{$this->partnerId}:{$this->data}";
    }
}
