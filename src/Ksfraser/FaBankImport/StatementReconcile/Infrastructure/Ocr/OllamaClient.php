<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;

/**
 * Guzzle-backed Ollama REST client.
 *
 * Targets Ollama's /api/generate endpoint with stream=false so the
 * response arrives in a single JSON object.
 *
 * Configuration:
 *   OLLAMA_BASE_URL   – e.g. "http://ollama.internal:11434"
 *   OLLAMA_API_KEY    – optional Bearer token (empty = no auth header)
 *   OLLAMA_TIMEOUT_MS – request timeout in ms (default 30 000)
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr
 * @author  Kevin Fraser
 */
final class OllamaClient implements OllamaClientInterface
{
    /** Ollama generate endpoint path. */
    private const GENERATE_PATH = '/api/generate';

    /** @var ClientInterface */
    private $http;

    /** @var string Base URL including scheme + host + port. */
    private $baseUrl;

    /** @var string API key, empty string means no auth. */
    private $apiKey;

    /** @var int Timeout in seconds (converted from ms). */
    private $timeoutSeconds;

    /**
     * @param ClientInterface $http           Injected Guzzle client (pre-configured base_uri optional).
     * @param string          $baseUrl        Full base URL e.g. "http://ollama.internal:11434".
     * @param string          $apiKey         Optional Bearer token.
     * @param int             $timeoutMs      Request timeout in milliseconds.
     */
    public function __construct(
        ClientInterface $http,
        string $baseUrl,
        string $apiKey = '',
        int $timeoutMs = 30000
    ) {
        if (trim($baseUrl) === '') {
            throw new \InvalidArgumentException('OllamaClient: baseUrl cannot be empty');
        }

        $this->http           = $http;
        $this->baseUrl        = rtrim($baseUrl, '/');
        $this->apiKey         = $apiKey;
        $this->timeoutSeconds = (int) ceil($timeoutMs / 1000);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $model, string $prompt, array $options = []): array
    {
        if (trim($model) === '') {
            throw new \InvalidArgumentException('OllamaClient::generate model cannot be empty');
        }
        if (trim($prompt) === '') {
            throw new \InvalidArgumentException('OllamaClient::generate prompt cannot be empty');
        }

        $body = array_merge(
            [
                'model'  => $model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ],
            $options
        );

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        if ($this->apiKey !== '') {
            // Sanitise: ensure no newlines in the key (header injection guard).
            $safeKey = str_replace(["\r", "\n"], '', $this->apiKey);
            $headers['Authorization'] = 'Bearer ' . $safeKey;
        }

        try {
            $response = $this->http->request(
                'POST',
                $this->baseUrl . self::GENERATE_PATH,
                [
                    'headers' => $headers,
                    'body'    => json_encode($body),
                    'timeout' => $this->timeoutSeconds,
                ]
            );
        } catch (GuzzleException $e) {
            throw StatementOcrException::forReason(
                'Ollama HTTP request failed: ' . $e->getMessage()
            );
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw StatementOcrException::forReason(
                "Ollama returned HTTP {$statusCode}"
            );
        }

        $raw = (string) $response->getBody();
        if (trim($raw) === '') {
            throw StatementOcrException::forReason('Ollama returned an empty response body');
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw StatementOcrException::forReason(
                'Ollama response is not valid JSON: ' . json_last_error_msg()
            );
        }

        return (array) $decoded;
    }
}
