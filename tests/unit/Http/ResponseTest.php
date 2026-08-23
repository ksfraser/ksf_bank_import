<?php
/**
 * Unit tests for the local Http\Response value object.
 *
 * Replaces symfony/http-foundation dependency (#44 option 2): the module
 * only needs status + headers + content semantics.
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Http\Response;

class ResponseTest extends TestCase
{
    /** @test */
    public function it_defaults_to_200_empty(): void
    {
        $r = new Response();

        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('', $r->getContent());
        $this->assertSame([], $r->getHeaders());
    }

    /** @test */
    public function it_is_immutable_with_fluent_setters(): void
    {
        $base = new Response();
        $r = $base->withContent('hello')->withStatusCode(201)->withHeader('X-Test', 'yes');

        $this->assertNotSame($base, $r);
        $this->assertSame('', $base->getContent());
        $this->assertSame('hello', $r->getContent());
        $this->assertSame(201, $r->getStatusCode());
        $this->assertSame(['X-Test' => 'yes'], $r->getHeaders());
    }

    /** @test */
    public function it_creates_json_responses(): void
    {
        $r = Response::json(['status' => 'success']);

        $this->assertSame(200, $r->getStatusCode());
        $this->assertJson($r->getContent());
        $this->assertSame(['status' => 'success'], json_decode($r->getContent(), true));
        $this->assertSame('application/json', $r->getHeaders()['Content-Type']);
    }

    /** @test */
    public function it_creates_redirect_responses(): void
    {
        $r = Response::redirect('/target', 303);

        $this->assertSame(303, $r->getStatusCode());
        $this->assertSame('/target', $r->getHeaders()['Location']);
    }

    /** @test */
    public function it_redirects_with_302_by_default(): void
    {
        $r = Response::redirect('/target');

        $this->assertSame(302, $r->getStatusCode());
    }

    /** @test */
    public function it_sends_status_headers_and_content(): void
    {
        $r = (new Response())
            ->withStatusCode(201)
            ->withHeader('X-Test', 'yes')
            ->withContent('payload');

        $this->assertSame(201, $r->getStatusCode());

        ob_start();
        $r->send();
        $output = ob_get_clean();

        $this->assertSame('payload', $output);
    }
}
