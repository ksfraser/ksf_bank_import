<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Http;

use Ksfraser\FaBankImport\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /**
     * @var array
     */
    private $serverBackup = [];

    /**
     * @var array
     */
    private $getBackup = [];

    /**
     * @var array
     */
    private $postBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
    }

    public function testANewRequestIsAGetRequestWithoutParameters(): void
    {
        $request = new Request();

        $this->assertSame([], $request->getQueryParams());
        $this->assertSame([], $request->getPostParams());
        $this->assertTrue($request->isMethod('GET'));
        $this->assertFalse($request->isMethod('POST'));
    }

    public function testItKeepsTheQueryAndPostParameters(): void
    {
        $request = new Request(['page' => 2], ['action' => 'save']);

        $this->assertSame(['page' => 2], $request->getQueryParams());
        $this->assertSame(['action' => 'save'], $request->getPostParams());
    }

    public function testTheMethodIsUpperCased(): void
    {
        $request = new Request([], [], 'post');

        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod('post'));
    }

    public function testHasPostTellsWhetherAPostParameterExists(): void
    {
        $request = new Request([], ['transaction' => ['id' => 1]]);

        $this->assertTrue($request->hasPost('transaction'));
        $this->assertFalse($request->hasPost('missing'));
    }

    public function testGetPostReturnsTheValueOrNull(): void
    {
        $request = new Request([], ['transaction' => ['id' => 1]]);

        $this->assertSame(['id' => 1], $request->getPost('transaction'));
        $this->assertNull($request->getPost('missing'));
    }

    public function testCreateFromGlobalsReadsTheSuperglobals(): void
    {
        $_GET = ['q' => 'search'];
        $_POST = ['p' => 'value'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Request::createFromGlobals();

        $this->assertSame(['q' => 'search'], $request->getQueryParams());
        $this->assertSame(['p' => 'value'], $request->getPostParams());
        $this->assertTrue($request->isMethod('POST'));
    }

    public function testCreateFromGlobalsFallsBackToGetWhenNoMethodIsGiven(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        $request = Request::createFromGlobals();

        $this->assertTrue($request->isMethod('GET'));
    }
}
