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
        $this->assertEquals("1984-04-04T00:00:00+0000", $actual);
        sleep(60);
        $actual = $timeFormatter->whatTimeIsIt();
        $this->assertEquals("1984-04-04T00:00:00+0000", $actual);
    }
}
