<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Review\Interfaces;

use Psr\Log\LoggerInterface;

/**
 * Logger interface alias for type hints
 * 
 * Uses PSR-3 standard LoggerInterface from psr/log package
 * Type hint against this interface instead of LoggerInterface directly
 * 
 * @deprecated Use Psr\Log\LoggerInterface directly
 */
interface ILogger extends LoggerInterface
{
}
