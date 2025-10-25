<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test HtmlEmail and HtmlA convenience classes
 */
class HtmlEmailAndATest extends TestCase
{
	protected function setUp(): void
	{
		// Load all dependencies in order
		require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlElementInterface.php';
		require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlElement.php';
		require_once __DIR__ . '/../../src/Ksfraser/HTML/Elements/HtmlString.php';
		require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlAttribute.php';
		require_once __DIR__ . '/../../src/Ksfraser/HTML/Elements/HtmlLink.php';
		require_once __DIR__ . '/../../src/Ksfraser/HTML/Elements/HtmlEmail.php';
		require_once __DIR__ . '/../../Views/HTML/HtmlA.php';
	}
	
	public function testHtmlEmailCreatesMailtoLink()
	{
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "test@example.com", new \Ksfraser\HTML\HTMLAtomic\HtmlString( "Email Me" ) );
		$html = $email->getHtml();
		
		$this->assertStringContainsString( 'href="mailto:test@example.com"', $html );
		$this->assertStringContainsString( 'Email Me', $html );
		$this->assertStringContainsString( '<a', $html );
		$this->assertStringContainsString( '</a>', $html );
	}
	
	public function testHtmlEmailWithStringContent()
	{
		// Accepts plain string - should wrap in HtmlString automatically
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "test@example.com", "Contact Us" );
		$html = $email->getHtml();
		
		$this->assertStringContainsString( 'href="mailto:test@example.com"', $html );
		$this->assertStringContainsString( 'Contact Us', $html );
	}
	
	public function testHtmlEmailWithNullContentUsesEmailAddress()
	{
		// Null content should use email address as link text
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "info@example.com" );
		$html = $email->getHtml();
		
		$this->assertStringContainsString( 'href="mailto:info@example.com"', $html );
		$this->assertStringContainsString( '>info@example.com<', $html );
	}
	
	public function testHtmlEmailValidatesEmailAddress()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Invalid email address" );
		
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "not-an-email", "Click" );
	}
	
	public function testHtmlEmailCanSkipValidation()
	{
		// Should not throw even with invalid format
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "custom-format", "Click", false );
		$html = $email->getHtml();
		
		$this->assertStringContainsString( 'href="mailto:custom-format"', $html );
	}
	
	public function testHtmlEmailRejectsInvalidContentType()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Invalid link content type" );
		
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "test@example.com", 123 ); // Invalid: integer
	}
	
	public function testHtmlACreatesStandardLink()
	{
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "https://example.com", new \Ksfraser\HTML\HTMLAtomic\HtmlString( "Visit Site" ) );
		$html = $link->getHtml();
		
		$this->assertStringContainsString( 'href="https://example.com"', $html );
		$this->assertStringContainsString( 'Visit Site', $html );
		$this->assertStringContainsString( '<a', $html );
		$this->assertStringContainsString( '</a>', $html );
	}
	
	public function testHtmlAWithStringContent()
	{
		// Accepts plain string - should wrap automatically
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "https://example.com", "Click Here" );
		$html = $link->getHtml();
		
		$this->assertStringContainsString( 'href="https://example.com"', $html );
		$this->assertStringContainsString( 'Click Here', $html );
	}
	
	public function testHtmlAWithNullContentUsesUrl()
	{
		// Null content should use URL as link text
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "https://example.com" );
		$html = $link->getHtml();
		
		$this->assertStringContainsString( 'href="https://example.com"', $html );
		$this->assertStringContainsString( '>https://example.com<', $html );
	}
	
	public function testHtmlAWithRawHtmlContent()
	{
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "/page", new \Ksfraser\HTML\HTMLAtomic\HtmlRawString( "<strong>Bold</strong> Link" ) );
		$html = $link->getHtml();
		
		$this->assertStringContainsString( 'href="/page"', $html );
		$this->assertStringContainsString( '<strong>Bold</strong> Link', $html );
	}
	
	public function testHtmlARejectsInvalidContentType()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Invalid link content type" );
		
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "https://example.com", ['invalid'] ); // Invalid: array
	}
	
	public function testHtmlAInheritsParamMethods()
	{
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "/search", new \Ksfraser\HTML\HTMLAtomic\HtmlString( "Search" ) );
		$link->addParam( "q", "test query" );
		$link->addParam( "page", "2" );
		
		$html = $link->getHtml();
		
		// Should use http_build_query which URL-encodes spaces
		$this->assertStringContainsString( 'href="/search?', $html );
		$this->assertStringContainsString( 'q=test+query', $html );
		$this->assertStringContainsString( 'page=2', $html );
	}
	
	public function testHtmlEmailInheritsParamMethods()
	{
		// Email links can have query params too (subject, body, cc, etc.)
		$email = new \Ksfraser\HTML\HTMLAtomic\HtmlEmail( "support@example.com", new \Ksfraser\HTML\HTMLAtomic\HtmlString( "Contact Support" ) );
		$email->addParam( "subject", "Help Request" );
		$email->addParam( "body", "I need assistance" );
		
		$html = $email->getHtml();
		
		$this->assertStringContainsString( 'href="mailto:support@example.com?', $html );
		$this->assertStringContainsString( 'subject=Help+Request', $html );
		$this->assertStringContainsString( 'body=I+need+assistance', $html );
	}
	
	public function testHtmlACanSetTarget()
	{
		$link = new \Ksfraser\HTML\HTMLAtomic\HtmlA( "https://external.com", new \Ksfraser\HTML\HTMLAtomic\HtmlString( "External" ) );
		$link->setTarget( "_blank" );
		
		$html = $link->getHtml();
		
		$this->assertStringContainsString( 'target="_blank"', $html );
	}
}
