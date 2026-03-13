<?php

namespace Ksfraser\FaBankImport\Request;

use Ksfraser\FaBankImport\Services\ParserRegistry;
use Ksfraser\Superglobals\ParameterProvider;

/**
 * SRP class for selecting a parser based on request parameters.
 */
class ParserSelector
{
    private $parameterProvider;
    private $parserRegistry;

    public function __construct(ParameterProvider $parameterProvider, ParserRegistry $parserRegistry)
    {
        $this->parameterProvider = $parameterProvider;
        $this->parserRegistry = $parserRegistry;
    }

    /**
     * Get the selected parser ID.
     */
    public function getSelectedParser(): ?string
    {
        $requested = $this->parameterProvider->get('parser');
        if ($requested && $this->parserRegistry->getAvailableParsers()[$requested] ?? false) {
            return $requested;
        }

        // Default to QFX if available
        $available = $this->parserRegistry->getParsersArray();
        return array_key_exists('QFX', $available) ? 'QFX' : array_key_first($available);
    }
}