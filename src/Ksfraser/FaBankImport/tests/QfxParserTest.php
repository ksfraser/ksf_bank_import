<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :QfxParserTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for QfxParserTest.
 */
use PHPUnit\Framework\TestCase;

class QfxParserTest extends TestCase
{
    protected $parser;

    protected function setUp(): void
    {
        $this->parser = new qfx_parser();
    }

    public function testCombineArray()
    {
        $row = ['value1', 'value2', 'value3'];
        $header = ['key1', 'key2', 'key3'];
        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];

        $this->parser->_combine_array($row, null, $header);

        $this->assertSame($expected, $row);
    }

    public function testParse()
    {
        $content = file_get_contents(__DIR__ . '/sample.qfx');
        $static_data = [
            'account_name' => 'Test Bank',
            'account_code' => '123456'
        ];

        $result = $this->parser->parse($content, $static_data, false);

        // Add assertions to verify the result
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
