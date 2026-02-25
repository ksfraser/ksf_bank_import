<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\DuplicateDetectionService;

class DuplicateDetectionServiceTest extends TestCase
{
    public function testFindDuplicatesReturnsArray()
    {
        $service = new DuplicateDetectionService();
        $result = $service->findDuplicates([]);
        $this->assertIsArray($result);
    }
}
