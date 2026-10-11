<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Events\VendorNotAddedEvent;
use Ksfraser\Application\TimeFormatter;
use Ksfraser\Application\Models\RealClock;
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Events/VendorAddedEvent.php';

/**
* Trap errors
*/
function MyErrorHandler( $errno, $errstr )
{
	return true;
}

class VendorNotAddedEventTest extends TestCase
{
	private $element;
	private $id;
	private $timestamp;
	private $timeFormatter;
	private $errors;

	protected function setUp(): void
	{
		 $this->errors = array();
        	set_error_handler(array($this, "errorHandler"));
		$this->id = 1;
		$this->timeFormatter = new TimeFormatter( new RealClock() );
		$this->timestamp = $this->timeFormatter->YYYYMMDDHMS();
		$this->element = new VendorNotAddedEvent( $this->id );
		$this->assertEquals( $this->timestamp, $this->element->getTimestamp() );
	}
	public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) 
	{
        	$this->errors[] = compact("errno", "errstr", "errfile", "errline", "errcontext");
    	}

	public function testConstructor():void
	{
	    foreach ($this->errors as $error) {
		$this->assertEquals( $error["errstr"], "Failed to create Supplier" );
            	$this->assertEquals( $error["errno"], E_USER_WARNING ); 
            }
	}
	public function testGetVendorId()
	{
		$this->assertEquals( $this->id, $this->element->getVendorId() );
	}
	public function testGetTimestamp()
	{
		$this->assertEquals( $this->timestamp, $this->element->getTimestamp() );
	}
}




