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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
final class DuplicateDecisionMade extends DomainEvent
{
    /** @var int */
    public $transactionId;
    /** @var string */
    public $previousStatus;
    /** @var string */
    public $newStatus;
    /** @var string */
    public $decidedBy;
    /** @var DateTimeImmutable */
    public $decidedAt;
    /** @var ?string */
    public $reason = null;

    /**
     * @param int $transactionId The duplicate transaction ID
     * @param string $previousStatus The status before decision (usually PENDING)
     * @param string $newStatus The status after decision (APPROVED, REJECTED, INVESTIGATE)
     * @param string $decidedBy Who made the decision
     * @param DateTimeImmutable $decidedAt When the decision was made (UTC)
     * @param string|null $reason Optional reason for the decision
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function __construct(
        int $transactionId,
        string $previousStatus,
        string $newStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        ?string $reason = null
    ) {
        parent::__construct();
        $this->transactionId = $transactionId;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $decidedAt;
        $this->reason = $reason;
    }

    /**
     * Convert event to array for serialization
     * 
     * @return array
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
