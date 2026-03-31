<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\DTOs\DuplicateResolutionDTO;

class DuplicateResolutionDTOTest extends TestCase
{
    public function testProperties()
    {
        $dto = new DuplicateResolutionDTO(['dup']);
        $this->assertEquals(['dup'], $dto->duplicates);
    }
}
