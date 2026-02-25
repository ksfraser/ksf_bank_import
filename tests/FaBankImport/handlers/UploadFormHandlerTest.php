<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\UploadFormHandler;

class UploadFormHandlerTest extends TestCase
{
    public function testHandleReturnsDTO()
    {
        $handler = new UploadFormHandler();
        $dto = $handler->handle([]);
        $this->assertInstanceOf('Ksfraser\\FaBankImport\\DTO\\UploadFormDTO', $dto);
    }
}
