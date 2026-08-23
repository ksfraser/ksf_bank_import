<?php
/**
 * Unit tests for the local Http\Request value object.
 *
 * Replaces the symfony/http-foundation dependency (#44 option 2).
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Http\Request;

class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /** @test */
    public function it_captures_globals(): void
    {
        $_GET = ['page' => '2'];
        $_POST = ['transaction' => 'go'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $r = Request::createFromGlobals();

        $this->assertSame(['page' => '2'], $r->getQueryParams());
        $this->assertSame(['transaction' => 'go'], $r->getPostParams());
        $this->assertTrue($r->isMethod('POST'));
        $this->assertFalse($r->isMethod('GET'));
    }

    /** @test */
    public function it_defaults_to_get_with_empty_params(): void
    {
        $r = Request::createFromGlobals();

        $this->assertSame([], $r->getQueryParams());
        $this->assertSame([], $r->getPostParams());
        $this->assertTrue($r->isMethod('GET'));
    }

    /** @test */
    public function it_has_and_gets_post_values(): void
    {
        $_POST = ['transaction' => 'process'];
        $r = Request::createFromGlobals();

        $this->assertTrue($r->hasPost('transaction'));
        $this->assertSame('process', $r->getPost('transaction'));
        $this->assertFalse($r->hasPost('missing'));
        $this->assertNull($r->getPost('missing'));
    }
}
