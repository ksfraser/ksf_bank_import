<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * PartnerException - Base exception for partner-related errors
 * 
 * All partner subsystem errors inherit from this base exception,
 * enabling catch-all handling while still allowing specific exception types.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerException extends \Exception
{
}
