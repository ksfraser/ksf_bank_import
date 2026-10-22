<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\UnsetTransactionCommand;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeTransactionLookup;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class UnsetTransactionCommandTest extends TestCase
{
    public function testItIsACommand(): void
    {
        $command = new UnsetTransactionCommand([], new FakeTransactionLookup());

        $this->assertInstanceOf(CommandInterface::class, $command);
        $this->assertSame('UnsetTransaction', $command->getName());
    }

    public function testItFailsWithoutTransactionsToUnset(): void
    {
        $command = new UnsetTransactionCommand([], new FakeTransactionLookup());

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No transactions to unset', $result->getMessage());
    }

    public function testItFailsWhenTheTransactionListIsEmpty(): void
    {
        $command = new UnsetTransactionCommand(['UnsetTrans' => []], new FakeTransactionLookup());

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No transactions to unset', $result->getMessage());
    }

    public function testItUnsetsASingleTransaction(): void
    {
        $repository = new FakeTransactionLookup();

        $command = new UnsetTransactionCommand(['UnsetTrans' => [7 => 'Unset']], $repository);

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Disassociated 1 transaction', $result->getMessage());
        $this->assertSame(1, $result->getData('count'));
        $this->assertSame([7], $result->getData('transaction_ids'));
        $this->assertSame([7], $repository->getReset());
    }

    public function testItUnsetsSeveralTransactions(): void
    {
        $repository = new FakeTransactionLookup();

        $command = new UnsetTransactionCommand(
            ['UnsetTrans' => [7 => 'Unset', 8 => 'Unset']],
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Disassociated 2 transactions', $result->getMessage());
        $this->assertSame([7, 8], $result->getData('transaction_ids'));
        $this->assertSame([7, 8], $repository->getReset());
    }

    public function testAFailingResetIsReportedAsAnError(): void
    {
        $repository = new FakeTransactionLookup([
            7 => ['id' => 7, 'fail' => 'reset refused'],
        ]);

        $command = new UnsetTransactionCommand(['UnsetTrans' => [7 => 'Unset']], $repository);

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('Failed to unset transactions: reset refused', $result->getMessage());
        $this->assertSame(0, $result->getData('attempted_count'));
        $this->assertSame(\RuntimeException::class, $result->getData('exception'));
    }

    public function testTheCountOfTheAttemptedResetsIsReported(): void
    {
        $repository = new FakeTransactionLookup([
            8 => ['id' => 8, 'fail' => 'reset refused'],
        ]);

        $command = new UnsetTransactionCommand(
            ['UnsetTrans' => [7 => 'Unset', 8 => 'Unset']],
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame(1, $result->getData('attempted_count'));
    }
}
