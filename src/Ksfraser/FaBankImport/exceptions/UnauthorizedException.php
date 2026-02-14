<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :UnauthorizedException [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for UnauthorizedException.
 */
namespace Ksfraser\FaBankImport\Exceptions;

class UnauthorizedException extends \Exception
{
    public function __construct(string $message = "Unauthorized access", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}