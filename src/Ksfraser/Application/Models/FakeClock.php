<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Models;

class FakeClock implements ClockInterface
{
    private \DateTimeInterface $fixedTime;

    public function __construct(?\DateTimeInterface $fixedTime = null)
    {
        $this->fixedTime = $fixedTime ?? new \DateTimeImmutable('1984-04-04 00:00:00');
    }

    public function getCurrentTime(): \DateTimeInterface
    {
        return $this->fixedTime;
    }
}
