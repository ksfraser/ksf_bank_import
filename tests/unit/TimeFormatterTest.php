<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\Application\TimeFormatter;
use Ksfraser\Application\Models\FakeClock;
use Ksfraser\Application\Models\RealClock;

class TimeFormatterTest extends TestCase
{
    public function testWhatTimeIsIt()
    {
        $timeFormatter = new TimeFormatter( new RealClock() );
        $actual = $timeFormatter->whatTimeIsIt();
        // Should return a valid ISO 8601 date string
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{4}$/', $actual);
    }
}
