<?php

namespace Ksfraser\FaBankImport\Entity;

/**
 * Partner Entity - Immutable value object representing a partner
 * 
 * Encapsulates partner data with immutability enforced through readonly properties.
 * No setters - state is established at construction.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class PartnerEntity
{
    /**
     * Unique identifier for this partner
     */
    private readonly int $id;
    
    /**
     * Partner display name
     */
    private readonly string $name;
    
    /**
     * Type of partner (supplier, customer, bank transfer, quick entry)
     */
    private readonly PartnerType $type;
    
    /**
     * How many times this partner has been matched successfully
     * Used for occurrence-based scoring and learning
     */
    private readonly int $occurrenceCount;
    
    /**
     * Timestamp of last successful match
     * Used for recency decay calculation in scoring
     */
    private readonly ?\DateTime $lastMatchedTs;
    
    /**
     * Construct a new partner entity
     * 
     * @param int $id Unique identifier (0 for new, >0 for existing)
     * @param string $name Display name of the partner
     * @param PartnerType $type Classification of partner
     * @param int $occurrenceCount How many times successfully matched (default 0)
     * @param ?\DateTime $lastMatchedTs When last matched (default null)
     */
    public function __construct(
        int $id,
        string $name,
        PartnerType $type,
        int $occurrenceCount = 0,
        ?\DateTime $lastMatchedTs = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->occurrenceCount = $occurrenceCount;
        $this->lastMatchedTs = $lastMatchedTs;
    }
    
    /**
     * Get unique identifier
     */
    public function id(): int
    {
        return $this->id;
    }
    
    /**
     * Get partner name
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Get partner type
     */
    public function type(): PartnerType
    {
        return $this->type;
    }
    
    /**
     * Get occurrence count (learning metric)
     */
    public function occurrenceCount(): int
    {
        return $this->occurrenceCount;
    }
    
    /**
     * Get last matched timestamp (recency metric)
     */
    public function lastMatchedTs(): ?\DateTime
    {
        return $this->lastMatchedTs;
    }
    
    /**
     * Prevent dynamic property assignment (enforce immutability)
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \Error(
            "Cannot set property {$name} on immutable " . self::class
        );
    }
}
