<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\QuickEntryDataProvider;

/**
 * QuickEntryDataProviderTest
 *
 * Tests for QuickEntryDataProvider class.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251020
 */
class QuickEntryDataProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static cache before each test
        QuickEntryDataProvider::resetCache();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        QuickEntryDataProvider::resetCache();
    }

    public function testConstruction(): void
    {
        $provider = new QuickEntryDataProvider();
        $this->assertInstanceOf(QuickEntryDataProvider::class, $provider);
    }

    public function testGetQuickEntriesReturnsArray(): void
    {
        $provider = new QuickEntryDataProvider();
        $entries = $provider->getQuickEntries('QE_DEPOSIT');

        $this->assertIsArray($entries);
    }

    public function testGetQuickEntriesWithMockData(): void
    {
        $provider = new QuickEntryDataProvider();

        $mockDeposits = [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
            ['id' => '2', 'description' => 'Cash Deposit', 'type' => 'QE_DEPOSIT'],
        ];

        $provider->setQuickEntries('QE_DEPOSIT', $mockDeposits);
        $entries = $provider->getQuickEntries('QE_DEPOSIT');

        $this->assertCount(2, $entries);
        $this->assertEquals('Bank Deposit', $entries[0]['description']);
        $this->assertEquals('Cash Deposit', $entries[1]['description']);
    }

    public function testGetQuickEntriesForBothTypes(): void
    {
        $provider = new QuickEntryDataProvider();

        $mockDeposits = [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ];
        $mockPayments = [
            ['id' => '3', 'description' => 'Bank Payment', 'type' => 'QE_PAYMENT'],
        ];

        $provider->setQuickEntries('QE_DEPOSIT', $mockDeposits);
        $provider->setQuickEntries('QE_PAYMENT', $mockPayments);

        $deposits = $provider->getQuickEntries('QE_DEPOSIT');
        $payments = $provider->getQuickEntries('QE_PAYMENT');

        $this->assertCount(1, $deposits);
        $this->assertCount(1, $payments);
        $this->assertEquals('Bank Deposit', $deposits[0]['description']);
        $this->assertEquals('Bank Payment', $payments[0]['description']);
    }

    public function testStaticCachingPreventsDuplicateLoads(): void
    {
        $mockData = [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ];

        $provider1 = new QuickEntryDataProvider();
        $provider1->setQuickEntries('QE_DEPOSIT', $mockData);

        $provider2 = new QuickEntryDataProvider();
        $entries = $provider2->getQuickEntries('QE_DEPOSIT');

        // Should return the same cached data
        $this->assertCount(1, $entries);
        $this->assertEquals('Bank Deposit', $entries[0]['description']);
    }

    public function testResetCacheClearsStaticCache(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        QuickEntryDataProvider::resetCache();

        $entries = $provider->getQuickEntries('QE_DEPOSIT');
        $this->assertCount(0, $entries);
    }

    public function testGenerateSelectHtmlReturnsString(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $html = $provider->generateSelectHtml('quickEntry', 'QE_DEPOSIT', null);

        $this->assertIsString($html);
    }

    public function testGenerateSelectHtmlContainsFieldName(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $html = $provider->generateSelectHtml('quickEntry', 'QE_DEPOSIT', null);

        $this->assertStringContainsString('name="quickEntry"', $html);
    }

    public function testGenerateSelectHtmlContainsDescriptions(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
            ['id' => '2', 'description' => 'Cash Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $html = $provider->generateSelectHtml('quickEntry', 'QE_DEPOSIT', null);

        $this->assertStringContainsString('Bank Deposit', $html);
        $this->assertStringContainsString('Cash Deposit', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('value="2"', $html);
    }

    public function testGenerateSelectHtmlWithSelectedId(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_PAYMENT', [
            ['id' => '1', 'description' => 'Payment 1', 'type' => 'QE_PAYMENT'],
            ['id' => '2', 'description' => 'Payment 2', 'type' => 'QE_PAYMENT'],
        ]);

        $html = $provider->generateSelectHtml('quickEntry', 'QE_PAYMENT', '2');

        $this->assertStringContainsString('selected', $html);
        $this->assertMatchesRegularExpression('/value="2"[^>]*selected/', $html);
    }

    public function testGetQuickEntryDescriptionById(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $description = $provider->getQuickEntryDescriptionById('QE_DEPOSIT', '1');

        $this->assertEquals('Bank Deposit', $description);
    }

    public function testGetQuickEntryDescriptionByIdReturnsNullForUnknown(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $description = $provider->getQuickEntryDescriptionById('QE_DEPOSIT', '999');

        $this->assertNull($description);
    }

    public function testGetQuickEntryCount(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit 1', 'type' => 'QE_DEPOSIT'],
            ['id' => '2', 'description' => 'Deposit 2', 'type' => 'QE_DEPOSIT'],
        ]);

        $count = $provider->getQuickEntryCount('QE_DEPOSIT');

        $this->assertEquals(2, $count);
    }

    public function testGetQuickEntryCountReturnsZeroWhenEmpty(): void
    {
        $provider = new QuickEntryDataProvider();

        $count = $provider->getQuickEntryCount('QE_DEPOSIT');

        $this->assertEquals(0, $count);
    }

    public function testIsLoadedReturnsFalseInitially(): void
    {
        $provider = new QuickEntryDataProvider();

        $this->assertFalse($provider->isLoaded('QE_DEPOSIT'));
        $this->assertFalse($provider->isLoaded('QE_PAYMENT'));
    }

    public function testIsLoadedReturnsTrueAfterLoading(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $this->assertTrue($provider->isLoaded('QE_DEPOSIT'));
        $this->assertFalse($provider->isLoaded('QE_PAYMENT'));
    }

    public function testMultipleInstancesShareCache(): void
    {
        $provider1 = new QuickEntryDataProvider();
        $provider1->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $provider2 = new QuickEntryDataProvider();
        $provider3 = new QuickEntryDataProvider();

        $this->assertTrue($provider2->isLoaded('QE_DEPOSIT'));
        $this->assertTrue($provider3->isLoaded('QE_DEPOSIT'));
        $this->assertCount(1, $provider2->getQuickEntries('QE_DEPOSIT'));
        $this->assertCount(1, $provider3->getQuickEntries('QE_DEPOSIT'));
    }

    public function testGenerateSelectHtmlUsesCache(): void
    {
        $provider1 = new QuickEntryDataProvider();
        $provider1->setQuickEntries('QE_PAYMENT', [
            ['id' => '1', 'description' => 'Payment', 'type' => 'QE_PAYMENT'],
        ]);

        $provider2 = new QuickEntryDataProvider();
        $html = $provider2->generateSelectHtml('entry', 'QE_PAYMENT', null);

        $this->assertStringContainsString('Payment', $html);
    }

    public function testGetQuickEntryByIdReturnsFullRecord(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Bank Deposit', 'type' => 'QE_DEPOSIT', 'base_amount' => '100.00'],
        ]);

        $entry = $provider->getQuickEntryById('QE_DEPOSIT', '1');

        $this->assertIsArray($entry);
        $this->assertEquals('Bank Deposit', $entry['description']);
        $this->assertEquals('QE_DEPOSIT', $entry['type']);
        $this->assertEquals('100.00', $entry['base_amount']);
    }

    public function testGetQuickEntryByIdReturnsNullForUnknown(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $entry = $provider->getQuickEntryById('QE_DEPOSIT', '999');

        $this->assertNull($entry);
    }

    public function testGetQuickEntryByIdForWrongType(): void
    {
        $provider = new QuickEntryDataProvider();
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        // Try to get with QE_PAYMENT when data is QE_DEPOSIT
        $entry = $provider->getQuickEntryById('QE_PAYMENT', '1');

        $this->assertNull($entry);
    }

    public function testIndependentCachingForBothTypes(): void
    {
        $provider = new QuickEntryDataProvider();
        
        $provider->setQuickEntries('QE_DEPOSIT', [
            ['id' => '1', 'description' => 'Deposit', 'type' => 'QE_DEPOSIT'],
        ]);

        $this->assertTrue($provider->isLoaded('QE_DEPOSIT'));
        $this->assertFalse($provider->isLoaded('QE_PAYMENT'));
        
        $provider->setQuickEntries('QE_PAYMENT', [
            ['id' => '2', 'description' => 'Payment', 'type' => 'QE_PAYMENT'],
        ]);

        $this->assertTrue($provider->isLoaded('QE_DEPOSIT'));
        $this->assertTrue($provider->isLoaded('QE_PAYMENT'));
        $this->assertCount(1, $provider->getQuickEntries('QE_DEPOSIT'));
        $this->assertCount(1, $provider->getQuickEntries('QE_PAYMENT'));
    }
}
