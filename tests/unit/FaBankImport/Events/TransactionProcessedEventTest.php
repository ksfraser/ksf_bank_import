<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Events;

use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use PHPUnit\Framework\TestCase;

final class TransactionProcessedEventTest extends TestCase
{
    public function testItCarriesTheTransactionIdAndTheType(): void
    {
        $event = new TransactionProcessedEvent(42, 'credit');

        $this->assertSame(42, $event->getTransactionId());
        $this->assertSame('credit', $event->getType());
    }

    public function testItStampsTheEventWithTheCurrentTime(): void
    {
        $before = new \DateTime();
        $event = new TransactionProcessedEvent(1, 'debit');
        $after = new \DateTime();

        $timestamp = $event->getTimestamp();

        $this->assertInstanceOf(\DateTime::class, $timestamp);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $timestamp->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $timestamp->getTimestamp());
    }
}
