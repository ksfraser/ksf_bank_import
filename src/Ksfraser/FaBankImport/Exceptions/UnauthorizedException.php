<?php

namespace Ksfraser\FaBankImport\Exceptions;

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exceptions/UnauthorizedException.php; coverage: docs/TestCases/ (file rollup)
 */
class UnauthorizedException extends \Exception
{
    /** @BABOK FR-050 / ARCH-003 — method: __construct */
    public function __construct(string $message = "Unauthorized access", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}