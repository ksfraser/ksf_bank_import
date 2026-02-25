<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\MappingConfirmationHandler;

class MappingConfirmationHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new MappingConfirmationHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\MappingConfirmationDTO', $dto);
    }
}
