<?php

namespace Ksfraser\Application;

use Ksfraser\Application\Models\ClockInterface;

class TimeFormatter
{
    private ClockInterface $clock;

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    public function whatTimeIsIt(): string
    {
        return $this->clock->getCurrentTime()->format('Y-m-d\TH:i:sO');
    }

    public function YYYYMMDDHMS(): string
    {
        return $this->clock->getCurrentTime()->format('Y-m-d H:i:s');
    }
}
