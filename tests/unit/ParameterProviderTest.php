<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\Superglobals\PostParameterProvider;
use Ksfraser\Superglobals\GetParameterProvider;

class ParameterProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalPost = $_POST;
        $this->originalGet = $_GET;
    }

    protected function tearDown(): void
    {
        // Restore original superglobals
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;
    }

    public function testPostParameterProviderGetsExistingValue()
    {
        $_POST['test_key'] = 'test_value';
        $provider = new PostParameterProvider();
        $this->assertEquals('test_value', $provider->get('test_key'));
    }

    public function testPostParameterProviderReturnsNullForNonExistingValue()
    {
        $provider = new PostParameterProvider();
        $this->assertNull($provider->get('non_existing_key'));
    }

    public function testGetParameterProviderGetsExistingValue()
    {
        $_GET['test_key'] = 'test_value';
        $provider = new GetParameterProvider();
        $this->assertEquals('test_value', $provider->get('test_key'));
    }

    public function testGetParameterProviderReturnsNullForNonExistingValue()
    {
        $provider = new GetParameterProvider();
        $this->assertNull($provider->get('non_existing_key'));
    }
}