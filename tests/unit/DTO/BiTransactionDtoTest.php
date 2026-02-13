<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\DTO\BiTransactionDto;
use PHPUnit\Framework\TestCase;

final class BiTransactionDtoTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::resetInstance();
        parent::tearDown();
    }

    public function testFromArrayNormalizesTitleAndKeepsOnlySchemaFields(): void
    {
        $dto = BiTransactionDto::fromArray([
            'id' => 7,
            'title' => 'Coffee purchase',
            'transactionDC' => 'C',
            'status' => 0,
            'non_schema_key' => 'drop-me',
        ]);

        $data = $dto->toArray();

        $this->assertSame(7, $dto->getId());
        $this->assertSame('Coffee purchase', $dto->getTitle());
        $this->assertSame('C', $dto->getTransactionDC());
        $this->assertArrayNotHasKey('non_schema_key', $data);
        $this->assertSame('Coffee purchase', $data['transactionTitle']);
    }

    public function testGettersReturnDefaultsWhenDataMissing(): void
    {
        $dto = BiTransactionDto::fromArray([]);

        $this->assertSame(0, $dto->getId());
        $this->assertSame('', $dto->getTitle());
        $this->assertSame('D', $dto->getTransactionDC());
    }

    public function testGetTransactionDCUsesConfiguredDefault(): void
    {
        $config = Config::getInstance();
        $config->set('transaction.default_dc', 'C');

        $dto = BiTransactionDto::fromArray([]);

        $this->assertSame('C', $dto->getTransactionDC());
    }

    public function testGetTransactionDCFallsBackToDForInvalidConfiguredDefault(): void
    {
        $config = Config::getInstance();
        $config->set('transaction.default_dc', 'BT');

        $dto = BiTransactionDto::fromArray([]);

        $this->assertSame('D', $dto->getTransactionDC());
    }
}
