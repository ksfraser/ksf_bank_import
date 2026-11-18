<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review\Interfaces;

use Ksfraser\FaBankImport\Import\Events\DomainEvent;

/**
 * Interface for event publishing
 * 
 * Allows the service to publish domain events to registered listeners.
 * This enables decoupling from specific event dispatcher implementations.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
interface IEventPublisher
{
    /**
     * Publish a domain event to all registered listeners
     * 
     * @param DomainEvent $event The event to publish
     * 
     * @return void
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function publish(DomainEvent $event): void;
}
