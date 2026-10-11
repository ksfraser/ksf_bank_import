<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Infrastructure\Ocr;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr\OllamaClient
 */
class OllamaClientTest extends TestCase
{
    private function makeResponse(string $body, int $status = 200): Response
    {
        return new Response($status, [], $body);
    }

    private function makeClient(string $responseBody, int $status = 200): OllamaClient
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('request')->willReturn($this->makeResponse($responseBody, $status));

        return new OllamaClient($http, 'http://ollama.internal:11434');
    }

    public function testGenerateReturnsDecodedArray(): void
    {
        $payload = json_encode(['model' => 'gemma4', 'response' => '{"key":"val"}', 'done' => true]);
        $client  = $this->makeClient($payload);

        $result = $client->generate('gemma4', 'hello');
        $this->assertIsArray($result);
        $this->assertSame('gemma4', $result['model']);
    }

    public function testApiKeyAddsBearerHeader(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('/api/generate'),
                $this->callback(static function (array $opts): bool {
                    return isset($opts['headers']['Authorization'])
                        && strpos($opts['headers']['Authorization'], 'Bearer ') === 0;
                })
            )
            ->willReturn($this->makeResponse(json_encode(['response' => '{}'])));

        $client = new OllamaClient($http, 'http://ollama.internal:11434', 'my-secret-key');
        $client->generate('gemma4', 'test');
    }

    public function testRejectsEmptyBaseUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OllamaClient($this->createMock(ClientInterface::class), '');
    }

    public function testThrowsOnHttpError(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->method('request')
            ->willThrowException(new RequestException('Connection refused', new Request('POST', '/')));

        $client = new OllamaClient($http, 'http://ollama.internal:11434');

        $this->expectException(StatementOcrException::class);
        $client->generate('gemma4', 'prompt');
    }

    public function testThrowsOnNon200Status(): void
    {
        $client = $this->makeClient('Internal Server Error', 500);

        $this->expectException(StatementOcrException::class);
        $client->generate('gemma4', 'prompt');
    }

    public function testThrowsOnEmptyBody(): void
    {
        $client = $this->makeClient('');

        $this->expectException(StatementOcrException::class);
        $client->generate('gemma4', 'prompt');
    }

    public function testThrowsOnInvalidJson(): void
    {
        $client = $this->makeClient('not-json-at-all');

        $this->expectException(StatementOcrException::class);
        $client->generate('gemma4', 'prompt');
    }

    public function testRejectsEmptyModel(): void
    {
        $client = $this->makeClient('{}');

        $this->expectException(\InvalidArgumentException::class);
        $client->generate('', 'prompt');
    }

    public function testRejectsEmptyPrompt(): void
    {
        $client = $this->makeClient('{}');

        $this->expectException(\InvalidArgumentException::class);
        $client->generate('gemma4', '');
    }

    public function testBaseUrlTrailingSlashNormalised(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with('POST', 'http://ollama.internal:11434/api/generate', $this->anything())
            ->willReturn($this->makeResponse(json_encode(['response' => '{}'])));

        // Pass URL with trailing slash – should be stripped.
        $client = new OllamaClient($http, 'http://ollama.internal:11434/');
        $client->generate('gemma4', 'hello');
    }

    public function testHeaderInjectionInApiKeySanitised(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(static function (array $opts): bool {
                    $auth = $opts['headers']['Authorization'] ?? '';
                    // Must not contain newlines.
                    return strpos($auth, "\r") === false && strpos($auth, "\n") === false;
                })
            )
            ->willReturn($this->makeResponse(json_encode(['response' => '{}'])));

        $client = new OllamaClient($http, 'http://ollama.internal:11434', "evil-key\r\nX-Injected: header");
        $client->generate('gemma4', 'prompt');
    }
}
