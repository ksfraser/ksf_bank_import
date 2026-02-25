<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\AccountResolutionHandler;

class AccountResolutionHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new AccountResolutionHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\AccountResolutionDTO', $dto);
    }
}
