<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\AddVendorCommand;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeTransactionLookup;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeVendorService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class AddVendorCommandTest extends TestCase
{
    public function testItIsACommand(): void
    {
        $command = new AddVendorCommand([], new FakeVendorService(), new FakeTransactionLookup());

        $this->assertInstanceOf(CommandInterface::class, $command);
        $this->assertSame('AddVendor', $command->getName());
    }

    public function testItFailsWithoutVendorData(): void
    {
        $command = new AddVendorCommand([], new FakeVendorService(), new FakeTransactionLookup());

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No vendor data provided', $result->getMessage());
    }

    public function testItFailsWhenTheVendorListIsEmpty(): void
    {
        $command = new AddVendorCommand(
            ['AddVendor' => []],
            new FakeVendorService(),
            new FakeTransactionLookup()
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No vendor data provided', $result->getMessage());
    }

    public function testItCreatesOneVendor(): void
    {
        $repository = new FakeTransactionLookup([
            123 => ['id' => 123, 'counterpartyName' => 'Acme Ltd'],
        ]);

        $command = new AddVendorCommand(
            ['AddVendor' => [123 => 'Add']],
            new FakeVendorService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Created 1 vendor', $result->getMessage());
        $this->assertSame(
            [['vendor_id' => 200, 'name' => 'Acme Ltd']],
            $result->getData('created')
        );
    }

    public function testItUsesThePluralFormForSeveralVendors(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'counterpartyName' => 'One'],
            2 => ['id' => 2, 'counterpartyName' => 'Two'],
        ]);

        $command = new AddVendorCommand(
            ['AddVendor' => [1 => 'Add', 2 => 'Add']],
            new FakeVendorService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Created 2 vendors', $result->getMessage());
        $this->assertCount(2, $result->getData('created'));
    }

    public function testTheCounterpartyNameFallsBackToUnknown(): void
    {
        $repository = new FakeTransactionLookup([
            5 => ['id' => 5],
        ]);

        $command = new AddVendorCommand(
            ['AddVendor' => [5 => 'Add']],
            new FakeVendorService(),
            $repository
        );

        $result = $command->execute();

        $this->assertSame('Unknown', $result->getData('created')[0]['name']);
    }

    public function testItFailsWhenEveryVendorFails(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'fail' => 'no partner found'],
        ]);

        $command = new AddVendorCommand(
            ['AddVendor' => [1 => 'Add']],
            new FakeVendorService(),
            $repository
        );

        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('Failed to create 1 vendor(s)', $result->getMessage());
        $this->assertSame(
            [['transaction_id' => 1, 'error' => 'no partner found']],
            $result->getData('errors')
        );
    }

    public function testItWarnsWhenOnlySomeVendorsFail(): void
    {
        $repository = new FakeTransactionLookup([
            1 => ['id' => 1, 'counterpartyName' => 'One'],
            2 => ['id' => 2, 'fail' => 'no partner found'],
        ]);

        $command = new AddVendorCommand(
            ['AddVendor' => [1 => 'Add', 2 => 'Add']],
            new FakeVendorService(),
            $repository
        );

        $result = $command->execute();

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isWarning());
        $this->assertSame('Created 1 vendor(s), 1 failed', $result->getMessage());
        $this->assertCount(1, $result->getData('created'));
        $this->assertCount(1, $result->getData('errors'));
    }
}
