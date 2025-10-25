<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for HtmlOB (HTML Output Buffer) class
 * 
 * Verifies that HtmlOB can capture echoed output and wrap it as HtmlElementInterface
 */
class HtmlOBTest extends TestCase
{
	protected function setUp(): void
	{
		require_once __DIR__ . '/../../src/Ksfraser/HTML/Elements/HtmlOB.php';
	}
	
	/**
	 * Test static capture method with simple echo
	 */
	public function testCaptureSimpleEcho()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			echo '<p>Hello World</p>';
		});
		
		$this->assertEquals('<p>Hello World</p>', $html->getHtml());
	}
	
	/**
	 * Test static capture method with multiple echoes
	 */
	public function testCaptureMultipleEchoes()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			echo '<div>';
			echo '<span>Line 1</span>';
			echo '<span>Line 2</span>';
			echo '</div>';
		});
		
		$expected = '<div><span>Line 1</span><span>Line 2</span></div>';
		$this->assertEquals($expected, $html->getHtml());
	}
	
	/**
	 * Test that HtmlOB extends HtmlRawString (no HTML escaping)
	 */
	public function testDoesNotEscapeHtml()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			echo '<script>alert("test");</script>';
		});
		
		// Should NOT be escaped
		$this->assertEquals('<script>alert("test");</script>', $html->getHtml());
		$this->assertStringNotContainsString('&lt;', $html->getHtml());
		$this->assertStringNotContainsString('&gt;', $html->getHtml());
	}
	
	/**
	 * Test manual start/end usage
	 */
	public function testManualStartEnd()
	{
		$ob = new \Ksfraser\HTML\Elements\HtmlOB();
		$ob->start();
		echo '<p>Manual capture</p>';
		$result = $ob->end();
		
		$this->assertEquals('<p>Manual capture</p>', $result);
		$this->assertEquals('<p>Manual capture</p>', $ob->getHtml());
	}
	
	/**
	 * Test that toHtml echoes the content
	 */
	public function testToHtmlEchoes()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			echo '<p>Test</p>';
		});
		
		ob_start();
		$html->toHtml();
		$output = ob_get_clean();
		
		$this->assertEquals('<p>Test</p>', $output);
	}
	
	/**
	 * Test capturing output from object method
	 */
	public function testCaptureObjectMethod()
	{
		$obj = new class {
			public function displaySomething() {
				echo '<table>';
				echo '<tr><td>Cell 1</td><td>Cell 2</td></tr>';
				echo '</table>';
			}
		};
		
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() use ($obj) {
			$obj->displaySomething();
		});
		
		$expected = '<table><tr><td>Cell 1</td><td>Cell 2</td></tr></table>';
		$this->assertEquals($expected, $html->getHtml());
	}
	
	/**
	 * Test empty capture
	 */
	public function testEmptyCapture()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			// No output
		});
		
		$this->assertEquals('', $html->getHtml());
	}
	
	/**
	 * Test that it implements HtmlElementInterface
	 */
	public function testImplementsHtmlElementInterface()
	{
		$html = \Ksfraser\HTML\Elements\HtmlOB::capture(function() {
			echo '<p>Test</p>';
		});
		
		$this->assertInstanceOf(\Ksfraser\HTML\HtmlElementInterface::class, $html);
	}
	
	/**
	 * Test constructor with callable
	 */
	public function testConstructorWithCallable()
	{
		$html = new \Ksfraser\HTML\Elements\HtmlOB(function() {
			echo '<p>Constructor callback</p>';
		});
		
		$this->assertEquals('<p>Constructor callback</p>', $html->getHtml());
	}
	
	/**
	 * Test constructor with pre-captured string
	 */
	public function testConstructorWithString()
	{
		$html = new \Ksfraser\HTML\Elements\HtmlOB('<p>Pre-captured</p>');
		
		$this->assertEquals('<p>Pre-captured</p>', $html->getHtml());
	}
	
	/**
	 * Test constructor with null (for manual start/end)
	 */
	public function testConstructorWithNull()
	{
		$html = new \Ksfraser\HTML\Elements\HtmlOB();
		
		$this->assertEquals('', $html->getHtml());
		
		$html->start();
		echo '<p>After start</p>';
		$html->end();
		
		$this->assertEquals('<p>After start</p>', $html->getHtml());
	}
	
	/**
	 * Test that static capture is equivalent to constructor with callable
	 */
	public function testCaptureEquivalentToConstructor()
	{
		$callback = function() {
			echo '<p>Same output</p>';
		};
		
		$html1 = \Ksfraser\HTML\Elements\HtmlOB::capture($callback);
		$html2 = new \Ksfraser\HTML\Elements\HtmlOB($callback);
		
		$this->assertEquals($html1->getHtml(), $html2->getHtml());
		$this->assertEquals('<p>Same output</p>', $html1->getHtml());
	}
}
