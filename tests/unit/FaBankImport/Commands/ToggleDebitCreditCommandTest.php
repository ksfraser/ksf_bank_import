<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\ToggleDebitCreditCommand;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeToggleService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class ToggleDebitCreditCommandTest extends TestCase
{
    public function testItIsACommand(): void
    {
        $command = new ToggleDebitCreditCommand([], new FakeToggleService());

        $this->assertInstanceOf(CommandInterface::class, $command);
        $this->assertSame('ToggleDebitCredit', $command->getName());
    }

    public function testItFailsWithoutATransactionToToggle(): void
    {
        $command = new ToggleDebitCreditCommand([], new FakeToggleService());

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No transaction to toggle', $result->getMessage());
    }

    public function testItFailsWhenTheTransactionListIsEmpty(): void
    {
        $command = new ToggleDebitCreditCommand(['ToggleTransaction' => []], new FakeToggleService());

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No transaction to toggle', $result->getMessage());
    }

    public function testItTogglesASingleTransaction(): void
    {
        $service = new FakeToggleService([
            7 => ['old_dc' => 'D', 'new_dc' => 'C'],
        ]);

        $command = new ToggleDebitCreditCommand(['ToggleTransaction' => [7 => 'Toggle']], $service);

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Toggled debit/credit for 1 transaction', $result->getMessage());
        $this->assertSame(
            [['transaction_id' => 7, 'new_dc' => 'C', 'old_dc' => 'D']],
            $result->getData('toggled')
        );
    }

    public function testItUsesThePluralFormForSeveralTransactions(): void
    {
        $service = new FakeToggleService([
            1 => ['old_dc' => 'C', 'new_dc' => 'D'],
            2 => ['old_dc' => 'D', 'new_dc' => 'C'],
        ]);

        $command = new ToggleDebitCreditCommand(
            ['ToggleTransaction' => [1 => 'Toggle', 2 => 'Toggle']],
            $service
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Toggled debit/credit for 2 transactions', $result->getMessage());
        $this->assertCount(2, $result->getData('toggled'));
    }

    public function testAMissingDirectionIsReportedAsNull(): void
    {
        $service = new FakeToggleService([
            7 => [],
        ]);

        $command = new ToggleDebitCreditCommand(['ToggleTransaction' => [7 => 'Toggle']], $service);

        $result = $command->execute();

        $this->assertSame(
            ['transaction_id' => 7, 'new_dc' => null, 'old_dc' => null],
            $result->getData('toggled')[0]
        );
    }

    public function testItFailsWhenEveryToggleFails(): void
    {
        $command = new ToggleDebitCreditCommand(
            ['ToggleTransaction' => [7 => 'Toggle']],
            new FakeToggleService()
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('Failed to toggle 1 transaction(s)', $result->getMessage());
        $this->assertSame(
            [['transaction_id' => 7, 'error' => 'toggle failed for 7']],
            $result->getData('errors')
        );
    }

    public function testItWarnsWhenOnlySomeTogglesFail(): void
    {
        $service = new FakeToggleService([
            1 => ['old_dc' => 'C', 'new_dc' => 'D'],
        ]);

        $command = new ToggleDebitCreditCommand(
            ['ToggleTransaction' => [1 => 'Toggle', 2 => 'Toggle']],
            $service
        );

        $result = $command->execute();

        $this->assertTrue($result->isWarning());
        $this->assertSame('Toggled 1 transaction(s), 1 failed', $result->getMessage());
        $this->assertCount(1, $result->getData('toggled'));
        $this->assertCount(1, $result->getData('errors'));
    }
}
