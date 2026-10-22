<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use PHPUnit\Framework\TestCase;

final class ProcessTransactionCommandTest extends TestCase
{
    public function testItCarriesTheTransactionIdTheTypeAndTheUser(): void
    {
        $command = new ProcessTransactionCommand(42, 'C', 7);

        $this->assertSame(42, $command->getTransactionId());
        $this->assertSame('C', $command->getType());
        $this->assertSame(7, $command->getUserId());
    }

    public function testTheUserIsOptional(): void
    {
        $command = new ProcessTransactionCommand(42, 'D');

        $this->assertSame(42, $command->getTransactionId());
        $this->assertSame('D', $command->getType());
        $this->assertNull($command->getUserId());
    }
}
