<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\ParseFilesHandler;

class ParseFilesHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new ParseFilesHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\ParseFilesDTO', $dto);
    }
}
