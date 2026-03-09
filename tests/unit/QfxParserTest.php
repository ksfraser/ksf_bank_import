<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for qfx_parser.php
 *
 * Tests the QFX/OFX parser functionality:
 * - Parser instantiation
 * - Array combination method
 * - Parse method structure
 * - Error handling
 */
class QfxParserTest extends TestCase
{
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new \qfx_parser();
    }

    /**
     * Test qfx_parser instantiation
     */
    public function testQfxParserInstantiation(): void
    {
        $this->assertInstanceOf(\qfx_parser::class, $this->parser);
        $this->assertInstanceOf(\parser::class, $this->parser);
    }

    /**
     * Test qfx_parser property defaults
     */
    public function testQfxParserPropertyDefaults(): void
    {
        $this->assertFalse($this->parser->bank_from_file);
        $this->assertFalse($this->parser->bankid_from_file);
    }

    /**
     * Test _combine_array method
     */
    public function testCombineArrayMethod(): void
    {
        $header = ['name', 'amount', 'date'];
        $row = ['John Doe', '100.00', '2024-01-01'];

        $this->parser->_combine_array($row, 0, $header);

        $expected = [
            'name' => 'John Doe',
            'amount' => '100.00',
            'date' => '2024-01-01'
        ];

        $this->assertEquals($expected, $row);
    }

    /**
     * Test _combine_array with mismatched lengths
     */
    public function testCombineArrayWithMismatchedLengths(): void
    {
        $header = ['name', 'amount'];
        $row = ['John Doe', '100.00', '2024-01-01']; // Extra element

        $this->expectException(\ArgumentCountError::class);
        $this->parser->_combine_array($row, 0, $header);
    }

    /**
     * Test _combine_array with empty arrays
     */
    public function testCombineArrayWithEmptyArrays(): void
    {
        $header = [];
        $row = [];

        $this->parser->_combine_array($row, 0, $header);

        $this->assertEquals([], $row);
    }

    /**
     * Test parse method with empty content
     */
    public function testParseMethodWithEmptyContent(): void
    {
        $result = $this->parser->parse('', [], false);

        // Should return empty or handle gracefully
        $this->assertIsArray($result);
    }

    /**
     * Test parse method with invalid content
     */
    public function testParseMethodWithInvalidContent(): void
    {
        $invalidContent = 'This is not a QFX file';

        $result = $this->parser->parse($invalidContent, [], false);

        // Should handle invalid content gracefully
        $this->assertIsArray($result);
    }

    /**
     * Test parse method debug output
     */
    public function testParseMethodDebugOutput(): void
    {
        $content = 'Minimal QFX content';

        ob_start();
        $result = $this->parser->parse($content, [], true);
        $debugOutput = ob_get_clean();

        // Debug mode should produce output
        $this->assertIsString($debugOutput);
        $this->assertIsArray($result);
    }

    /**
     * Test parse method with static data
     */
    public function testParseMethodWithStaticData(): void
    {
        $content = 'QFX content';
        $staticData = [
            'bank_account' => '12345',
            'parser_config' => 'test'
        ];

        $result = $this->parser->parse($content, $staticData, false);

        $this->assertIsArray($result);
    }

    /**
     * Test parser inheritance
     */
    public function testParserInheritance(): void
    {
        $this->assertInstanceOf(\parser::class, $this->parser);

        // Test that parent methods exist
        $this->assertTrue(method_exists($this->parser, 'parse'));
        $this->assertTrue(method_exists($this->parser, '_combine_array'));
    }

    /**
     * Test bank_from_file property access
     */
    public function testBankFromFileProperty(): void
    {
        // Test default value
        $this->assertFalse($this->parser->bank_from_file);

        // Test setting value
        $this->parser->bank_from_file = true;
        $this->assertTrue($this->parser->bank_from_file);
    }

    /**
     * Test bankid_from_file property access
     */
    public function testBankIdFromFileProperty(): void
    {
        // Test default value
        $this->assertFalse($this->parser->bankid_from_file);

        // Test setting value
        $this->parser->bankid_from_file = true;
        $this->assertTrue($this->parser->bankid_from_file);
    }

    /**
     * Test parse method handles OFX format
     */
    public function testParseHandlesOfxFormat(): void
    {
        // Basic OFX structure test
        $ofxContent = '<?xml version="1.0"?>
<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20240101</DTPOSTED>
            <TRNAMT>-100.00</TRNAMT>
            <MEMO>Test transaction</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>';

        $result = $this->parser->parse($ofxContent, [], false);

        $this->assertIsArray($result);
        // The parser should handle XML content without crashing
    }

    /**
     * Test parse method handles QFX format
     */
    public function testParseHandlesQfxFormat(): void
    {
        // Basic QFX structure test (similar to OFX but with different tags)
        $qfxContent = '<OFX>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20240101</DTPOSTED>
            <TRNAMT>-50.00</TRNAMT>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>';

        $result = $this->parser->parse($qfxContent, [], false);

        $this->assertIsArray($result);
    }

    /**
     * Test parse method with malformed XML
     */
    public function testParseMethodWithMalformedXml(): void
    {
        $malformedContent = '<OFX><BANKMSGSRSV1><unclosed>';

        $result = $this->parser->parse($malformedContent, [], false);

        // Should handle malformed XML gracefully
        $this->assertIsArray($result);
    }

    /**
     * Test multiple calls to parse method
     */
    public function testMultipleParseCalls(): void
    {
        $content1 = 'Content 1';
        $content2 = 'Content 2';

        $result1 = $this->parser->parse($content1, [], false);
        $result2 = $this->parser->parse($content2, [], false);

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }
}