<?php

namespace Ksfraser\Application\Models;

interface ClockInterface
{
    public function getCurrentTime(): \DateTimeInterface;
}
