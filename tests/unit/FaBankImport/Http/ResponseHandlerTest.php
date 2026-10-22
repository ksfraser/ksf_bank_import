<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Http;

use Ksfraser\FaBankImport\Http\Response;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use PHPUnit\Framework\TestCase;

final class ResponseHandlerTest extends TestCase
{
    public function testANewHandlerCarriesAnEmptyResponse(): void
    {
        $handler = new ResponseHandler();

        $this->assertInstanceOf(Response::class, $handler->getResponse());
        $this->assertSame('', $handler->getResponse()->getContent());
        $this->assertSame(200, $handler->getResponse()->getStatusCode());
    }

    public function testSetHeaderReturnsTheHandlerAndStoresTheHeader(): void
    {
        $handler = new ResponseHandler();

        $this->assertSame($handler, $handler->setHeader('X-Test', 'yes'));
        $this->assertSame(['X-Test' => 'yes'], $handler->getResponse()->getHeaders());
    }

    public function testSetStatusCodeReturnsTheHandlerAndStoresTheStatus(): void
    {
        $handler = new ResponseHandler();

        $this->assertSame($handler, $handler->setStatusCode(404));
        $this->assertSame(404, $handler->getResponse()->getStatusCode());
    }

    public function testSetContentReturnsTheHandlerAndStoresTheBody(): void
    {
        $handler = new ResponseHandler();

        $this->assertSame($handler, $handler->setContent('the body'));
        $this->assertSame('the body', $handler->getResponse()->getContent());
    }

    public function testJsonEchoesTheEncodedPayload(): void
    {
        $handler = new ResponseHandler();

        ob_start();
        $handler->json(['ok' => true]);
        $output = (string) ob_get_clean();

        $this->assertSame('{"ok":true}', $output);
    }

    public function testRedirectEchoesTheMetaRefreshFallback(): void
    {
        $handler = new ResponseHandler();

        ob_start();
        $handler->redirect('/target.php');
        $output = (string) ob_get_clean();

        $this->assertSame(
            "<meta http-equiv=\"refresh\" content=\"0;url='/target.php'\" />",
            $output
        );
    }

    public function testRedirectDoesNotChangeTheStatusCodeOfTheResponse(): void
    {
        $handler = new ResponseHandler();

        ob_start();
        $handler->redirect('/target.php', 301);
        ob_end_clean();

        $this->assertSame(200, $handler->getResponse()->getStatusCode());
        $this->assertSame([], $handler->getResponse()->getHeaders());
    }

    public function testSendWritesTheContentOfTheResponse(): void
    {
        $handler = new ResponseHandler();
        $handler->setContent('the body');

        ob_start();
        $handler->send();
        $output = (string) ob_get_clean();

        $this->assertSame('the body', $output);
    }
}
