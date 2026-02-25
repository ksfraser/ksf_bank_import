<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\ImportSummaryDTO;

class ImportSummaryDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new ImportSummaryDTO(['result']);
        $this->assertEquals(['result'], $dto->results);
    }
}
