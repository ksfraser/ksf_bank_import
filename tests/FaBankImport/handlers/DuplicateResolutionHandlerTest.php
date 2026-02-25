<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\DuplicateResolutionHandler;

class DuplicateResolutionHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new DuplicateResolutionHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\DuplicateResolutionDTO', $dto);
    }
}
