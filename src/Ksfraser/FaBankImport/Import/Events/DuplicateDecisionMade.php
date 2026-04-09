<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Events;

use DateTimeImmutable;
use DateTimeZone;

/**
 * DuplicateDecisionMade Domain Event
 * 
 * This event is published when a reviewer makes a decision (approve/reject/investigate)
 * on a detected duplicate transaction. Listeners (Story 4 posting service, logging system)
 * react to this event to take appropriate downstream actions.
 */
final class DuplicateDecisionMade extends DomainEvent
{
    /**
     * @param int $transactionId The duplicate transaction ID
     * @param string $previousStatus The status before decision (usually PENDING)
     * @param string $newStatus The status after decision (APPROVED, REJECTED, INVESTIGATE)
     * @param string $decidedBy Who made the decision
     * @param DateTimeImmutable $decidedAt When the decision was made (UTC)
     * @param string|null $reason Optional reason for the decision
     */
    public function __construct(
        public readonly int $transactionId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly string $decidedBy,
        public readonly DateTimeImmutable $decidedAt,
        public readonly ?string $reason = null,
    ) {
        parent::__construct();
    }

    /**
     * Convert event to array for serialization
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->getEventType(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'transaction_id' => $this->transactionId,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'decided_by' => $this->decidedBy,
            'decided_at' => $this->decidedAt->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
        ];
    }

    /**
     * Create event from serialized array
     * 
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new self(
            transactionId: (int)$data['transaction_id'],
            previousStatus: $data['previous_status'],
            newStatus: $data['new_status'],
            decidedBy: $data['decided_by'],
            decidedAt: new DateTimeImmutable($data['decided_at'], new DateTimeZone('UTC')),
            reason: $data['reason'] ?? null,
        );
    }
}
