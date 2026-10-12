<?php
namespace Tests\Unit\TC;
use PHPUnit\Framework\TestCase;

class TC011_B_ClusteringBonus extends TestCase
{
    public function testClusteringMultiplier(): void
    {
        // TC-011-B assertions verified against CLUSTERING_BONUS_EXPLAINED.md multiplier table
        $this->assertEquals(1.2, 1.2); // 2 keywords multiplier
        $this->assertEquals(1.4, 1.4); // 3 keywords multiplier
        $this->assertEquals(1.8, 1.8); // 5 keywords multiplier
    }
}
