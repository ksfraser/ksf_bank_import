<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Exceptions;

//TODO: Does this come from a framework?

class UnauthorizedException extends \Exception
{
    public function __construct(string $message = "Unauthorized access", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
