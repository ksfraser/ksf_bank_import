<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTO\DuplicateResolutionDTO;

class DuplicateResolutionDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new DuplicateResolutionDTO(['dup']);
        $this->assertEquals(['dup'], $dto->duplicates);
    }
}
