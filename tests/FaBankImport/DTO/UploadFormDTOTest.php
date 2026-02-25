<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\UploadFormDTO;

class UploadFormDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new UploadFormDTO(['parser1'], 'parser1');
        $this->assertEquals(['parser1'], $dto->parsers);
        $this->assertEquals('parser1', $dto->selectedParser);
    }
}
