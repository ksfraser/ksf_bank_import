<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Events;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Base class for all domain events
 * 
 * Domain events represent significant things that happen in the business domain.
 * They are immutable records of what happened and are published to event listeners.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
abstract class DomainEvent
{
    /**
     * @var string Unique identifier for this event instance
     */
    public readonly string $eventId;

    /**
     * @var DateTimeImmutable When this event occurred (UTC)
     */
    public readonly DateTimeImmutable $occurredAt;

    /**
     * Initialize event with unique ID and UTC timestamp
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    protected function __construct()
    {
        $this->eventId = uniqid('event_', true);
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * Convert event to array for serialization
     * 
     * @return array
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    abstract public function toArray(): array;

    /**
     * Create event from serialized array
     * 
     * @param array $data
     * @return static
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Get event name/type
     * 
     * @return string
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function getEventType(): string
    {
        return static::class;
    }
}
