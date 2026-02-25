<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\FileParserService;

class FileParserServiceTest extends TestCase
{
    public function testParseFilesReturnsArray()
    {
        $service = new FileParserService();
        $result = $service->parseFiles([], '');
        $this->assertIsArray($result);
    }
}
