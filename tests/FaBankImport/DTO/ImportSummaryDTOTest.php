<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\DTOs\ImportSummaryDTO;

class ImportSummaryDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new ImportSummaryDTO(['result']);
        $this->assertEquals(['result'], $dto->results);
    }
}
