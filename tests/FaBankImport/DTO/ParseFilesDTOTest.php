<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\DTOs\ParseFilesDTO;

class ParseFilesDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new ParseFilesDTO(['stmt'], 1, 2, 3);
        $this->assertEquals(['stmt'], $dto->statements);
        $this->assertEquals(1, $dto->validCount);
        $this->assertEquals(2, $dto->invalidCount);
        $this->assertEquals(3, $dto->transactionCount);
    }
}
