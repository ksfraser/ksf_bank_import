<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\SupplierMatcher;
use Ksfraser\FaBankImport\Services\SupplierMatchingConfiguration;
use Ksfraser\FaBankImport\Services\ConfidenceEnhancer;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

class SupplierMatcherTest extends TestCase
{
    private SupplierMatcher $matcher;
    private SupplierMatchingConfiguration $config;
    private ConfidenceEnhancer $confidenceEnhancer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new SupplierMatchingConfiguration();
        $this->confidenceEnhancer = $this->createMock(ConfidenceEnhancer::class);
        $this->matcher = new SupplierMatcher($this->config, $this->confidenceEnhancer);
    }

    public function testMatcherHasConfiguration(): void
    {
        $this->assertSame($this->config, $this->matcher->getConfiguration());
    }

    public function testMatcherHasConfidenceEnhancer(): void
    {
        $this->assertSame($this->confidenceEnhancer, $this->matcher->getConfidenceEnhancer());
    }

    public function testNoSupplierCandidatesReturnsNoMatch(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        $this->assertTrue($result->isNoMatch());
        $this->assertEquals(0, $result->getMatchCount());
    }

    public function testResultHasDecisionProperty(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00, 'memo' => 'Payment'];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        // Decision can be 'auto', 'manual', or 'no_match'
        $this->assertContains($result->getDecision(), ['auto', 'manual', 'no_match']);
    }

    public function testResultHasMatchArray(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $matches = $result->getMatches();
        $this->assertIsArray($matches);
    }

    public function testBestMatchReturnsNullWhenNoMatches(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $best = $result->getBestMatch();
        $this->assertNull($best);
    }

    public function testIsAutoMatchReturnsBooleanWhenNoMatches(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $this->assertFalse($result->isAutoMatch());
    }

    public function testIsManualSelectionReturnsBooleanWhenNoMatches(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $this->assertFalse($result->isManualSelection());
    }

    public function testIsNoMatchReturnsBooleanWhenNoMatches(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $this->assertTrue($result->isNoMatch());
    }

    public function testMatchResultCanConvertToArray(): void
    {
        $transaction = ['account' => '123456789', 'amount' => 1000.00];
        $result = $this->matcher->matchSuppliers($transaction, []);
        
        $array = $result->toArray();
        
        $this->assertArrayHasKey('matches', $array);
        $this->assertArrayHasKey('decision', $array);
        $this->assertArrayHasKey('match_count', $array);
        $this->assertArrayHasKey('best_supplier_id', $array);
    }

    /**
     * Create mock supplier KeywordMatch for testing
     *
     * @param int $supplierId Supplier ID
     * @param string $name Supplier name
     * @return KeywordMatch Mock supplier
     */
    private function createMockSupplier(int $supplierId, string $name): KeywordMatch
    {
        $mock = $this->createMock(KeywordMatch::class);
        $mock->method('getPartnerId')->willReturn($supplierId);
        $mock->method('getPartnerName')->willReturn($name);
        $mock->method('getPartnerType')->willReturn(1);
        return $mock;
    }
}
