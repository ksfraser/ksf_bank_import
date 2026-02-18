
<?php

namespace Ksfraser\FaBankImport\Tests;

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\Schema\BankImportModuleSchemaService;

class BankImportModuleSchemaServiceTest extends TestCase
{
    public function testEnsureAllRunsWithoutError()
    {
        $service = new BankImportModuleSchemaService();
        // This test will simply ensure the method runs without throwing an exception.
        // You may want to mock dependencies or check DB state if needed.
        $this->expectNotToPerformAssertions();
        $service->ensureAll();
    }
}
