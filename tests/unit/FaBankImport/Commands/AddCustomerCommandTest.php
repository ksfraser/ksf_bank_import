<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\AddCustomerCommand;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeCustomerService;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeTransactionLookup;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class AddCustomerCommandTest extends TestCase
{
    public function testItIsACommand(): void
    {
        $command = new AddCustomerCommand([], new FakeCustomerService(), new FakeTransactionLookup());

        $this->assertInstanceOf(CommandInterface::class, $command);
        $this->assertSame('AddCustomer', $command->getName());
    }

    public function testItFailsWithoutCustomerData(): void
    {
        $command = new AddCustomerCommand([], new FakeCustomerService(), new FakeTransactionLookup());

        $result = $command->execute();

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isError());
        $this->assertSame('No customer data provided', $result->getMessage());
    }

    public function testItFailsWhenTheCustomerListIsEmpty(): void
    {
        $command = new AddCustomerCommand(
            ['AddCustomer' => []],
            new FakeCustomerService(),
            new FakeTransactionLookup()
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No customer data provided', $result->getMessage());
    }

    public function testItCreatesOneCustomer(): void
    {
        $repository = new FakeTransactionLookup([
            123 => ['id' => 123, 'counterpartyName' => 'John Doe'],
        ]);

        $command = new AddCustomerCommand(
            ['AddCustomer' => [123 => 'Add']],
            new FakeCustomerService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Created 1 customer', $result->getMessage());
        $this->assertSame(
            [['customer_id' => 100, 'name' => 'John Doe']],
            $result->getData('created')
        );
    }

    public function testItUsesThePluralFormForSeveralCustomers(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'counterpartyName' => 'One'],
            2 => ['id' => 2, 'counterpartyName' => 'Two'],
        ]);

        $command = new AddCustomerCommand(
            ['AddCustomer' => [1 => 'Add', 2 => 'Add']],
            new FakeCustomerService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Created 2 customers', $result->getMessage());
        $this->assertCount(2, $result->getData('created'));
    }

    public function testTheCounterpartyNameFallsBackToUnknown(): void
    {
        $repository = new FakeTransactionLookup([
            5 => ['id' => 5],
        ]);

        $command = new AddCustomerCommand(
            ['AddCustomer' => [5 => 'Add']],
            new FakeCustomerService(),
            $repository
        );

        $result = $command->execute();

        $this->assertSame('Unknown', $result->getData('created')[0]['name']);
    }

    public function testItFailsWhenEveryCustomerFails(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'fail' => 'no partner found'],
        ]);

        $command = new AddCustomerCommand(
            ['AddCustomer' => [1 => 'Add']],
            new FakeCustomerService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('Failed to create 1 customer(s)', $result->getMessage());
        $this->assertSame(
            [['transaction_id' => 1, 'error' => 'no partner found']],
            $result->getData('errors')
        );
    }

    public function testItWarnsWhenOnlySomeCustomersFail(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'counterpartyName' => 'One'],
            2 => ['id' => 2, 'fail' => 'no partner found'],
        ]);

        $command = new AddCustomerCommand(
            ['AddCustomer' => [1 => 'Add', 2 => 'Add']],
            new FakeCustomerService(),
            $repository
        );

        $result = $command->execute();

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isWarning());
        $this->assertSame('Created 1 customer(s), 1 failed', $result->getMessage());
        $this->assertCount(1, $result->getData('created'));
        $this->assertCount(1, $result->getData('errors'));
    }
}
