<?php

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
