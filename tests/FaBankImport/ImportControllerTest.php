<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\ImportController;

class ImportControllerTest extends TestCase
{
    public function testHandleInitialStep()
    {
        $controller = new ImportController();
        $request = [];
        ob_start();
        $controller->handle($request);
        $output = ob_get_clean();
        $this->assertStringContainsString('Upload', $output);
        $this->assertEquals('PARSE_FILES', $request['import_step']);
    }
}
