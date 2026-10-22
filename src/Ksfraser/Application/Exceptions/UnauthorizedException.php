<?php

namespace Ksfraser\Application\Exceptions;

//TODO: Does this come from a framework?

/**
 * @BABOK FR-LOG-006 | BR-LOG-002 | UAT-LOG-005
 */
class UnauthorizedException extends \Exception
{
    /**
 * @BABOK FR-LOG-006 | BR-LOG-002 | UAT-LOG-005
     */
    public function __construct(string $message = "Unauthorized access", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
