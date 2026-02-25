<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\AccountResolutionDTO;

class AccountResolutionDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new AccountResolutionDTO(['acc'], ['fa'], ['map']);
        $this->assertEquals(['acc'], $dto->detectedAccounts);
        $this->assertEquals(['fa'], $dto->faAccounts);
        $this->assertEquals(['map'], $dto->mappings);
    }
}
