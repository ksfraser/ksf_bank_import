<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\ParserException;

/**
 * Contract for parser factory/selection
 *
 * Responsible for:
 * - Detecting appropriate parser for file type
 * - Creating parser instances
 * - Managing parser registry
 */
interface ParserFactoryInterface
{
    /**
     * Get parser for file type
     *
     * @param string $filePath Path to file to parse
     * @return ParserInterface Parser instance
     *
     * @throws ParserException If no suitable parser found
     */
    public function getParser(string $filePath): ParserInterface;

    /**
     * Register a parser
     *
     * @param ParserInterface $parser
     * @return void
     */
    public function registerParser(ParserInterface $parser): void;

    /**
     * Get all registered parsers
     *
     * @return array<int, ParserInterface>
     */
    public function getParsers(): array;
}
