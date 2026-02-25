<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\ImportHandler;

class ImportHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new ImportHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\ImportSummaryDTO', $dto);
    }
}
