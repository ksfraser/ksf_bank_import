<?php

namespace Ksfraser\FaBankImport\Exceptions;

/**
 * InvalidBiTransactionException
 * 
 * Thrown when a BiTransaction entity fails validation.
 * Indicates invariant violation at domain level.
 * 
 * @package Ksfraser\FaBankImport\Exceptions
 */
class InvalidBiTransactionException extends \RuntimeException
{
}
