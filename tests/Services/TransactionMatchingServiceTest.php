<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Services;

use Ksfraser\FaBankImport\Services\TransactionMatchingService;
use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use PHPUnit\Framework\TestCase;

/**
 * TransactionMatchingServiceTest - Test context-aware transaction matching
 *
 * Tests that transactions can be matched to partners with contextual
 * adjustments based on:
 * - Transaction recency (recent patterns more reliable)
 * - Amount ranges (recurring amounts more reliable)
 * - Transaction type consistency (check from supplier is more likely)
 * - Confidence adjustment and clamping
 *
 * @coversDefaultClass \Ksfraser\FaBankImport\Services\TransactionMatchingService
 */
class TransactionMatchingServiceTest extends TestCase
{
    private TransactionMatchingService $service;
    private KeywordMatchingService $matchingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matchingService = $this->createMockMatchingService();
        $this->service = new TransactionMatchingService($this->matchingService);
    }

    /**
     * Test service can be instantiated
     *
     * @test
     * @covers ::__construct
     */
    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TransactionMatchingService::class, $this->service);
    }

    /**
     * Test matchTransaction throws on missing required fields
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionThrowsOnMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have at least one');

        $this->service->matchTransaction([]);
    }

    /**
     * Test matchTransaction returns empty for no matches
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionReturnsEmptyWhenNoMatches(): void
    {
        $transaction = [
            'transactionTitle' => 'Xyz Unknown Corp',
            'memo' => 'Random payment',
            'account' => '',
            'amount' => 100,
        ];

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([]);

        $service = new TransactionMatchingService($matchingService);
        $result = $service->matchTransaction($transaction);

        $this->assertEmpty($result);
    }

    /**
     * Test matchTransaction returns partner match with confidence
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionReturnsPartnerMatchWithConfidence(): void
    {
        $transaction = [
            'transactionTitle' => 'Hydro One',
            'memo' => 'Monthly utility payment',
            'account' => '',
            'amount' => 85.50,
            'date' => date('Y-m-d'),
            'type' => 'CHECK',
        ];

        $match = $this->createMockKeywordMatch(1, 1, 80.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match]);

        $service = new TransactionMatchingService($matchingService);
        $result = $service->matchTransaction($transaction);

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result['partner_id']);
        $this->assertIsFloat($result['confidence']);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(100, $result['confidence']);
    }

    /**
     * Test matchTransaction recency boost
     *
     * Recent transactions should have higher confidence than old ones
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionRecentTransactionGetsBoost(): void
    {
        $match = $this->createMockKeywordMatch(1, 1, 60.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match]);

        $service = new TransactionMatchingService($matchingService);

        // Very recent transaction (today)
        $recentTx = [
            'transactionTitle' => 'Test',
            'date' => date('Y-m-d'),
            'amount' => 100,
        ];

        $recentResult = $service->matchTransaction($recentTx);

        // Old transaction (120 days ago)
        $oldTx = [
            'transactionTitle' => 'Test',
            'date' => date('Y-m-d', time() - (120 * 24 * 60 * 60)),
            'amount' => 100,
        ];

        $oldResult = $service->matchTransaction($oldTx);

        // Recent should have higher confidence than old
        $this->assertGreaterThan(
            $oldResult['confidence'],
            $recentResult['confidence']
        );
    }

    /**
     * Test matchTransaction amount range boost
     *
     * Normal amounts ($25-$1000) should get boost
     * Very small amounts (< $5) should get penalty
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionAmountRangeAffectsConfidence(): void
    {
        $match = $this->createMockKeywordMatch(1, 1, 50.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match]);

        $service = new TransactionMatchingService($matchingService);

        // Normal amount (high confidence)
        $normalTx = ['transactionTitle' => 'Test', 'amount' => 500];
        $normalResult = $service->matchTransaction($normalTx);

        // Very small amount (low confidence)
        $smallTx = ['transactionTitle' => 'Test', 'amount' => 2.50];
        $smallResult = $service->matchTransaction($smallTx);

        $this->assertGreaterThan($smallResult['confidence'], $normalResult['confidence']);
    }

    /**
     * Test matchTransaction type consistency
     *
     * CHECK from supplier should boost confidence
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionTypeConsistencyBoosts(): void
    {
        $match = $this->createMockKeywordMatch(1, 1, 60.0); // supplier

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match]);

        $service = new TransactionMatchingService($matchingService);

        // Supplier + CHECK = boost
        $checkTx = ['transactionTitle' => 'Test', 'type' => 'CHECK'];
        $checkResult = $service->matchTransaction($checkTx);

        // Supplier + DEPOSIT = no boost
        $depositTx = ['transactionTitle' => 'Test', 'type' => 'DEPOSIT'];
        $depositResult = $service->matchTransaction($depositTx);

        $this->assertGreaterThan($depositResult['confidence'], $checkResult['confidence']);
    }

    /**
     * Test getTransactionCandidates returns multiple matches
     *
     * @test
     * @covers ::getTransactionCandidates
     */
    public function testGetTransactionCandidatesReturnsMultiple(): void
    {
        $match1 = $this->createMockKeywordMatch(1, 1, 90.0);
        $match2 = $this->createMockKeywordMatch(2, 1, 60.0);
        $match3 = $this->createMockKeywordMatch(3, 1, 40.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match1, $match2, $match3]);

        $service = new TransactionMatchingService($matchingService);

        $transaction = ['transactionTitle' => 'Test'];
        $candidates = $service->getTransactionCandidates($transaction);

        $this->assertCount(3, $candidates);
        $this->assertEquals(1, $candidates[0]['partner_id']);
        $this->assertEquals(2, $candidates[1]['partner_id']);
        $this->assertEquals(3, $candidates[2]['partner_id']);
    }

    /**
     * Test getTransactionCandidates respects limit
     *
     * @test
     * @covers ::getTransactionCandidates
     */
    public function testGetTransactionCandidatesRespectsLimit(): void
    {
        $matches = [
            $this->createMockKeywordMatch(1, 1, 90.0),
            $this->createMockKeywordMatch(2, 1, 80.0),
            $this->createMockKeywordMatch(3, 1, 70.0),
            $this->createMockKeywordMatch(4, 1, 60.0),
            $this->createMockKeywordMatch(5, 1, 50.0),
        ];

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn($matches);

        $service = new TransactionMatchingService($matchingService);

        $transaction = ['transactionTitle' => 'Test'];
        $candidates = $service->getTransactionCandidates($transaction, null, 3);

        $this->assertCount(3, $candidates);
    }

    /**
     * Test matchTransaction auto_match flag
     *
     * Confidence >= 75 should set auto_match to true
     *
     * @test
     * @covers ::matchTransaction
     */
    public function testMatchTransactionAutoMatchFlag(): void
    {
        // High confidence match
        $highMatch = $this->createMockKeywordMatch(1, 1, 85.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$highMatch]);

        $service = new TransactionMatchingService($matchingService);

        $transaction = ['transactionTitle' => 'Test'];
        $result = $service->matchTransaction($transaction);

        $this->assertTrue($result['auto_match']);
    }

    /**
     * Test confidence clamping (0-100 range)
     *
     * @test
     */
    public function testConfidenceClamping(): void
    {
        // Very low match with adjustment might go negative
        $match = $this->createMockKeywordMatch(1, 1, 2.0);

        $matchingService = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $matchingService->method('search')->willReturn([$match]);

        $service = new TransactionMatchingService($matchingService);

        // Very small amount provides -5 penalty
        $transaction = ['transactionTitle' => 'Test', 'amount' => 1.50];
        $result = $service->matchTransaction($transaction);

        // Confidence should be clamped to >= 0
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(100, $result['confidence']);
    }

    /**
     * Helper: Create mock KeywordMatch
     */
    private function createMockKeywordMatch(
        int $partnerId,
        int $partnerType,
        float $confidence
    ): KeywordMatch {
        $keywords = [new Keyword('test')];

        $confidenceObj = $this->getMockBuilder(MatchConfidence::class)
            ->disableOriginalConstructor()
            ->getMock();
        $confidenceObj->method('getPercentage')->willReturn($confidence);

        $match = $this->getMockBuilder(KeywordMatch::class)
            ->disableOriginalConstructor()
            ->getMock();
        $match->method('getPartnerId')->willReturn($partnerId);
        $match->method('getPartnerType')->willReturn($partnerType);
        $match->method('getPartnerDetailId')->willReturn($partnerId * 10);
        $match->method('getPartnerName')->willReturn("Partner {$partnerId}");
        $match->method('getConfidence')->willReturn($confidenceObj);
        $match->method('getMatchedKeywords')->willReturn($keywords);
        $match->method('getMatchedKeywordCount')->willReturn(1);

        return $match;
    }

    /**
     * Helper: Create mock matching service
     */
    private function createMockMatchingService(): KeywordMatchingService
    {
        $mock = $this->getMockBuilder(KeywordMatchingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->method('search')->willReturn([]);
        return $mock;
    }
}
