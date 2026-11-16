<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionPartnerMatcher;
use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;

class EtransferMemoConsistencyMatcherTest extends TestCase
{
    public function testEtransferMemoPrefersCustomerWhenCustomerConsistencyIsStronger(): void
    {
        $engine = $this->createMock(ScoringRuleEngine::class);
        $config = new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 10;
            }
        };

        $matcher = new TransactionPartnerMatcher($engine, $config);

        $engine->method('calculateAdjustment')
            ->willReturnCallback(function (array $transaction, $candidate): float {
                $id = $candidate->getPartnerId();
                if ($id === 1) {
                    return 50.0; // supplier single hit
                }
                if ($id === 201) {
                    return 45.0; // customer 1
                }
                if ($id === 202) {
                    return 44.0; // customer 2
                }
                return 0.0;
            });

        $transaction = [
            'memo' => 'E-TRANSFER 010667466304;XYZ;Payment',
            'amount' => 250.00,
            'account' => '123',
            'partner_account' => '999',
        ];

        $suppliers = [
            ['partner_id' => 1, 'name' => 'XYZ SUPPLIER', 'account' => '999'],
        ];
        $customers = [
            ['partner_id' => 201, 'name' => 'XYZ CUSTOMER', 'account' => '999'],
            ['partner_id' => 202, 'name' => 'XYZ CUSTOMER ALT', 'account' => '999'],
        ];

        $results = $matcher->matchTransaction($transaction, $suppliers, $customers, []);

        $this->assertNotNull($results['best_match']);
        $this->assertEquals('customer', $results['best_match']->getPartnerType());
        $this->assertContains($results['best_match']->getPartnerId(), [201, 202]);
    }

    public function testNonEtransferMemoKeepsRawBestMatchSelection(): void
    {
        $engine = $this->createMock(ScoringRuleEngine::class);
        $config = new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 10;
            }
        };

        $matcher = new TransactionPartnerMatcher($engine, $config);

        $engine->method('calculateAdjustment')
            ->willReturnCallback(function (array $transaction, $candidate): float {
                $id = $candidate->getPartnerId();
                if ($id === 1) {
                    return 60.0; // supplier stays best for non e-transfer
                }
                if ($id === 201) {
                    return 45.0;
                }
                if ($id === 202) {
                    return 44.0;
                }
                return 0.0;
            });

        $transaction = [
            'memo' => 'WIRE PAYMENT XYZ',
            'amount' => 250.00,
            'account' => '123',
            'partner_account' => '999',
        ];

        $suppliers = [
            ['partner_id' => 1, 'name' => 'XYZ SUPPLIER', 'account' => '999'],
        ];
        $customers = [
            ['partner_id' => 201, 'name' => 'XYZ CUSTOMER', 'account' => '999'],
            ['partner_id' => 202, 'name' => 'XYZ CUSTOMER ALT', 'account' => '999'],
        ];

        $results = $matcher->matchTransaction($transaction, $suppliers, $customers, []);

        $this->assertNotNull($results['best_match']);
        $this->assertEquals('supplier', $results['best_match']->getPartnerType());
        $this->assertEquals(1, $results['best_match']->getPartnerId());
    }

    public function testEtransferMemoHonorsHistoricalTypeHint(): void
    {
        $engine = $this->createMock(ScoringRuleEngine::class);
        $config = new class {
            public function getMinimumConfidenceThreshold(): int
            {
                return 10;
            }
        };

        $matcher = new TransactionPartnerMatcher($engine, $config);

        $engine->method('calculateAdjustment')
            ->willReturnCallback(function (array $transaction, $candidate): float {
                $id = $candidate->getPartnerId();
                if ($id === 1) {
                    return 70.0; // supplier stronger raw
                }
                if ($id === 201) {
                    return 55.0; // customer weaker raw
                }
                return 0.0;
            });

        $transaction = [
            'memo' => 'E-TRANSFER 010667466304;XYZ;Payment',
            'memo_consistency_hint' => 'customer',
            'amount' => 250.00,
            'account' => '123',
            'partner_account' => '999',
        ];

        $suppliers = [
            ['partner_id' => 1, 'name' => 'XYZ SUPPLIER', 'account' => '999'],
        ];
        $customers = [
            ['partner_id' => 201, 'name' => 'XYZ CUSTOMER', 'account' => '999'],
        ];

        $results = $matcher->matchTransaction($transaction, $suppliers, $customers, []);

        $this->assertNotNull($results['best_match']);
        $this->assertEquals('customer', $results['best_match']->getPartnerType());
        $this->assertEquals(201, $results['best_match']->getPartnerId());
    }
}
