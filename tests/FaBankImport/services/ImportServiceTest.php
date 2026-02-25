<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ImportService;

class ImportServiceTest extends TestCase
{
    public function testImportStatementsReturnsArray()
    {
        $service = new ImportService();
        $result = $service->importStatements([]);
        $this->assertIsArray($result);
    }
}
