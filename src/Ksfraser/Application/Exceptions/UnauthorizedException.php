<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
