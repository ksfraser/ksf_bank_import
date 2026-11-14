<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application;

use Ksfraser\Application\Models\ClockInterface;

/**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
 */
class TimeFormatter
{
    private ClockInterface $clock;

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function whatTimeIsIt(): string
    {
        return $this->clock->getCurrentTime()->format('Y-m-d\TH:i:sO');
    }

    /**
 * @BABOK FR-LOG-002 | BR-LOG-001 | UAT-LOG-001
     */
    public function YYYYMMDDHMS(): string
    {
        return $this->clock->getCurrentTime()->format('Y-m-d H:i:s');
    }
}
