<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\MappingConfirmationDTO;

class MappingConfirmationDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new MappingConfirmationDTO(['pending']);
        $this->assertEquals(['pending'], $dto->pendingMappings);
    }
}
