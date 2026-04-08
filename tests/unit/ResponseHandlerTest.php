<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ResponseHandlerTest extends TestCase
{
    private $responseHandler;

    protected function setUp(): void
    {
        $this->responseHandler = new ResponseHandler();
    }

    public function testSetHeader()
    {
        $this->responseHandler->setHeader('Content-Type', 'text/html');
        $response = $this->responseHandler->getResponse();
        $this->assertEquals('text/html', $response->headers->get('Content-Type'));
    }

    public function testSetStatusCode()
    {
        $this->responseHandler->setStatusCode(404);
        $response = $this->responseHandler->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSetContent()
    {
        $content = '<html><body>Test content</body></html>';
        $this->responseHandler->setContent($content);
        $response = $this->responseHandler->getResponse();
        $this->assertEquals($content, $response->getContent());
    }

    public function testMethodChaining()
    {
        $result = $this->responseHandler
            ->setHeader('Content-Type', 'text/html')
            ->setStatusCode(200)
            ->setContent('test');
        
        $this->assertInstanceOf(ResponseHandler::class, $result);
    }

    public function testJsonResponse()
    {
        $data = ['status' => 'success', 'message' => 'Test message'];
        
        ob_start();
        $this->responseHandler->json($data);
        $output = ob_get_clean();
        
        $this->assertJson($output);
        $this->assertEquals($data, json_decode($output, true));
    }

    public function testRedirectResponse()
    {
        $url = '/test-redirect';
        
        $this->expectOutputRegex('/<meta http-equiv="refresh" content="0;url=\'\\/test-redirect\'" \\/>/');
        $this->responseHandler->redirect($url);
    }

    public function testGetResponse()
    {
        $response = $this->responseHandler->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}