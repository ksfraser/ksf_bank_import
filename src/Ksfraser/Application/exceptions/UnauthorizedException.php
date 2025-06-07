<?php

namespace Ksfraser\Application\Exceptions;

//TODO: Does this come from a framework?

class UnauthorizedException extends \Exception
{
    public function __construct(string $message = "Unauthorized access", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
