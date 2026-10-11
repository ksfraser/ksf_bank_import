<?php

namespace Ksfraser\Application\Models;

class RealClock implements ClockInterface
{
    public function getCurrentTime(): \DateTimeInterface
    {
        return new \DateTimeImmutable();
    }
}
