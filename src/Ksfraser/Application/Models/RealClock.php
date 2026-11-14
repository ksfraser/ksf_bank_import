<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Models;

/**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 */
class RealClock implements ClockInterface
{
    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function getCurrentTime(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }
}
