<?php
/** @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
