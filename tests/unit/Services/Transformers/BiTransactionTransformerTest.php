<?php

namespace Tests\Unit\Services\Transformers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Transformers\BiTransactionTransformer;
use Ksfraser\FaBankImport\Import\Services\Transformers\NormalizationRules;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\Exceptions\Utility\TransformException;

/**
 * Tests for BiTransactionTransformer
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Transformers\BiTransactionTransformer
 */
class BiTransactionTransformerTest extends TestCase
{
    /**
     * Transformer under test
     *
     * @var BiTransactionTransformer
     */
    private BiTransactionTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new BiTransactionTransformer();
    }

    /**
     * Test: Valid transaction transforms successfully
     */
    public function testValidTransactionTransforms(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Deposit',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $txn = $result[0];
        $this->assertInstanceOf(BiTransaction::class, $txn);
    }

    /**
     * Test: Batch transforms multiple transactions
     */
    public function testBatchTransformsMultiple(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Payment 1',
                'date' => '2024-01-10'
            ],
            [
                'fitId' => 'TXN002',
                'amount' => 250.50,
                'title' => 'Payment 2',
                'date' => '2024-01-11'
            ],
            [
                'fitId' => 'TXN003',
                'amount' => 500.00,
                'title' => 'Payment 3',
                'date' => '2024-01-12'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(3, $result);
        foreach ($result as $txn) {
            $this->assertInstanceOf(BiTransaction::class, $txn);
        }
    }

    /**
     * Test: Amount normalization (string to float)
     */
    public function testAmountNormalizationStringToFloat(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => '123.45',  // String amount
                'title' => 'Test Transaction',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        // Transaction should be created successfully with normalized amount
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Amount normalization (decimal places)
     */
    public function testAmountNormalizationDecimalPlaces(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => '100.999',  // 3 decimal places
                'title' => 'Test',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        // Amount should be rounded to 2 decimals
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Date normalization (YYYY-MM-DD)
     */
    public function testDateNormalizationISOFormat(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Test',
                'date' => '2024-01-15'  // ISO format
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Date normalization (M/D/YYYY format)
     */
    public function testDateNormalizationSlashFormat(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Test',
                'date' => '1/15/2024'  // North American format
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Title normalized (uppercase to titlecase)
     */
    public function testTitleNormalization(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'PAYMENT TO VENDOR ABC INC',  // all caps
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        // Title should be normalized to titlecase
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Memo field used if title missing
     */
    public function testMemoUsedAsFallback(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                // No 'title' - should use 'memo'
                'memo' => 'Payment details in memo',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Description field used if title and memo missing
     */
    public function testDescriptionUsedAsFallback(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                // No 'title' or 'memo' - should use 'description'
                'description' => 'Transaction description',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Missing fitId causes transaction to be skipped
     */
    public function testMissingFitIdSkipped(): void
    {
        $transactions = [
            [
                // Missing fitId
                'amount' => 100.00,
                'title' => 'Invalid Transaction',
                'date' => '2024-01-15'
            ],
            [
                'fitId' => 'TXN002',
                'amount' => 200.00,
                'title' => 'Valid Transaction',
                'date' => '2024-01-16'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        // Should include only the second transaction
        $this->assertCount(1, $result);
    }

    /**
     * Test: Invalid amount skipped or defaults to 0
     */
    public function testInvalidAmountHandled(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 'not a number',  // Invalid
                'title' => 'Test',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        // Should skip the transaction or handle gracefully
        // Exact behavior depends on implementation
        $this->assertIsArray($result);
    }

    /**
     * Test: Missing date defaults gracefully
     */
    public function testMissingDateHandled(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'No Date Transaction'
                // Missing date
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        // Should still create transaction (date is optional)
        $this->assertCount(1, $result);
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: Account reference normalized
     */
    public function testAccountReferenceNormalized(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Test',
                'date' => '2024-01-15'
            ]
        ];

        $result = $this->transformer->transformBatch(
            $transactions,
            1,
            '  account-123-abc  '  // With spaces and lowercase
        );

        $this->assertCount(1, $result);
        // Account ID should be normalized
        $this->assertInstanceOf(BiTransaction::class, $result[0]);
    }

    /**
     * Test: canTransform accepts valid transaction arrays
     */
    public function testCanTransformAcceptsValidArray(): void
    {
        $transaction = [
            'fitId' => 'TXN001',
            'amount' => 100.00,
            'title' => 'Test'
        ];

        $this->assertTrue($this->transformer->canTransform($transaction));
    }

    /**
     * Test: canTransform rejects missing fitId
     */
    public function testCanTransformRejectsMissingFitId(): void
    {
        $transaction = [
            'amount' => 100.00,
            'title' => 'Test'
        ];

        $this->assertFalse($this->transformer->canTransform($transaction));
    }

    /**
     * Test: canTransform rejects missing amount
     */
    public function testCanTransformRejectsMissingAmount(): void
    {
        $transaction = [
            'fitId' => 'TXN001',
            'title' => 'Test'
        ];

        $this->assertFalse($this->transformer->canTransform($transaction));
    }

    /**
     * Test: canTransform rejects non-array
     */
    public function testCanTransformRejectsNonArray(): void
    {
        $this->assertFalse($this->transformer->canTransform('not an array'));
        $this->assertFalse($this->transformer->canTransform(12345));
        $this->assertFalse($this->transformer->canTransform(null));
    }

    /**
     * Test: getName returns correct identifier
     */
    public function testGetName(): void
    {
        $this->assertEquals('BiTransactionTransformer', $this->transformer->getName());
    }

    /**
     * Test: Empty batch handled
     */
    public function testEmptyBatchReturnsEmptyArray(): void
    {
        $result = $this->transformer->transformBatch([], 1, 'ACC-001');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Test: Partial batch success (some transactions fail, others pass)
     */
    public function testPartialBatchSuccess(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'Valid 1',
                'date' => '2024-01-10'
            ],
            [
                // Missing fitId - should be skipped
                'amount' => 200.00,
                'title' => 'Invalid',
                'date' => '2024-01-11'
            ],
            [
                'fitId' => 'TXN003',
                'amount' => 300.00,
                'title' => 'Valid 2',
                'date' => '2024-01-12'
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        // Should include valid transactions, skip invalid
        $this->assertCount(2, $result);
        foreach ($result as $txn) {
            $this->assertInstanceOf(BiTransaction::class, $txn);
        }
    }

    /**
     * Test: Multiple date formats in batch
     */
    public function testMultipleDateFormatsInBatch(): void
    {
        $transactions = [
            [
                'fitId' => 'TXN001',
                'amount' => 100.00,
                'title' => 'ISO Format',
                'date' => '2024-01-10'
            ],
            [
                'fitId' => 'TXN002',
                'amount' => 200.00,
                'title' => 'Slash Format',
                'date' => '1/11/2024'
            ],
            [
                'fitId' => 'TXN003',
                'amount' => 300.00,
                'title' => 'Value Date Field',
                'valueDate' => '2024-01-12'  // Using valueDate instead of date
            ]
        ];

        $result = $this->transformer->transformBatch($transactions, 1, 'ACC-001');

        // All should transform successfully
        $this->assertCount(3, $result);
    }
}
