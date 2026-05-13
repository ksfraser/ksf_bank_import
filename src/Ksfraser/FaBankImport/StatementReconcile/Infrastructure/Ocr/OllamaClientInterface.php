<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;

/**
 * Contract for calling an Ollama language model.
 *
 * Abstracts the HTTP transport so the parser can be unit-tested without
 * a real Ollama server.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Ocr
 * @author  Kevin Fraser
 */
interface OllamaClientInterface
{
    /**
     * Submit a prompt to the specified Ollama model and return the
     * full response payload as an associative array.
     *
     * For /api/generate the caller can expect a 'response' key.
     * For /api/chat the caller can expect a 'message' key.
     *
     * @param string  $model   Model name (e.g. "gemma4", "glm-ocr").
     * @param string  $prompt  User prompt text.
     * @param array   $options Additional Ollama options (temperature, seed, etc.).
     * @return array Decoded JSON from the Ollama response body.
     * @throws StatementOcrException on HTTP / parse failure.
     */
    public function generate(string $model, string $prompt, array $options = []): array;
}
