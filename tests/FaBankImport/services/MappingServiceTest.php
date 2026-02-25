<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\MappingService;

class MappingServiceTest extends TestCase
{
    public function testGetPendingMappingsReturnsArray()
    {
        $service = new MappingService();
        $result = $service->getPendingMappings([], []);
        $this->assertIsArray($result);
    }
}
