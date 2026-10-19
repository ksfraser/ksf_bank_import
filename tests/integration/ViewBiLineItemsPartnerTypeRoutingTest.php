<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Partner type routing of the line item rendering.
 *
 * This test used to be a "main branch" comparison: it only ran when the
 * current branch was 'main' and it asserted hard coded values against a
 * switch statement that lived in the test itself, so it could never fail
 * whatever the production code did. It skipped itself on every other branch.
 *
 * The main branch and the refactored branch are now the same code, so the
 * comparison has no meaning. What is worth pinning is the ROUTING itself:
 * which partner type renders which form. That is asserted here against the
 * real strategy (PartnerTypeDisplayStrategy, the class bi_lineitem::
 * displayPartnerType() delegates to), so a change in the routing - or a
 * regression in it - fails the test.
 */
class ViewBiLineItemsPartnerTypeRoutingTest extends TestCase
{
    /** @var array Data handed to the strategy, mirrors bi_lineitem's own payload */
    private $testData;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../views/PartnerTypeDisplayStrategy.php';
        require_once __DIR__ . '/../../views/ViewFactory.php';

        $this->testData = [
            'id' => 123,
            'otherBankAccount' => 'TEST-ACCOUNT',
            'valueTimestamp' => '2025-01-15',
            'transactionDC' => 'D',
            'partnerId' => 456,
            'partnerDetailId' => 789,
            'memo' => 'Test memo',
            'transactionTitle' => 'Test transaction',
            'matching_trans' => [
                [
                    'type' => 1,
                    'type_no' => 999,
                    'tran_date' => '2025-01-15',
                    'amount' => 100.00,
                ],
            ],
        ];
    }

    /**
     * Render a partner type and return the HTML it produced.
     *
     * @param string $partnerType
     *
     * @return string
     */
    private function render(string $partnerType): string
    {
        $strategy = new PartnerTypeDisplayStrategy($this->testData);

        ob_start();
        try {
            $strategy->display($partnerType);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }

    /**
     * SP renders the supplier ("Payment To") form.
     */
    public function testPartnerTypeRoutingSP(): void
    {
        $output = $this->render('SP');

        $this->assertStringContainsString('Payment To:', $output, 'SP must render the supplier form');
        $this->assertStringContainsString('name="partnerId_123"', $output, 'SP must select the partner');
        $this->assertStringContainsString('Test Supplier 1', $output, 'SP must list the suppliers');
    }

    /**
     * CU renders the customer ("From Customer/Branch") form.
     */
    public function testPartnerTypeRoutingCU(): void
    {
        $output = $this->render('CU');

        $this->assertStringContainsString('From Customer/Branch:', $output, 'CU must render the customer form');
        $this->assertStringContainsString('name="partnerId_123"', $output, 'CU must select the partner');
        $this->assertStringContainsString('Test Customer 1', $output, 'CU must list the customers');
    }

    /**
     * BT renders the bank transfer form.
     */
    public function testPartnerTypeRoutingBT(): void
    {
        $output = $this->render('BT');

        $this->assertStringContainsString('Transfer from', $output, 'BT must render the bank transfer form');
        $this->assertStringContainsString('name="partnerId_123"', $output, 'BT must select the partner');
    }

    /**
     * QE renders the quick entry form.
     */
    public function testPartnerTypeRoutingQE(): void
    {
        $output = $this->render('QE');

        $this->assertStringContainsString('Quick Entry:', $output, 'QE must render the quick entry form');
        $this->assertStringContainsString('name="partnerId_123"', $output, 'QE must select the quick entry');
    }

    /**
     * MA renders the manual settlement form.
     */
    public function testPartnerTypeRoutingMA(): void
    {
        $output = $this->render('MA');

        $this->assertStringContainsString('Existing Entry Type:', $output, 'MA must render the manual settlement form');
        $this->assertStringContainsString('name="Existing_Type"', $output, 'MA must offer the entry types');
    }

    /**
     * ZZ (matched to an existing item) sets the hidden fields of the match.
     */
    public function testPartnerTypeRoutingZZWithMatch(): void
    {
        $output = $this->render('ZZ');

        $this->assertStringContainsString('name="partnerId_123" value="1"', $output, 'ZZ must carry the partner id');
        $this->assertStringContainsString('name="partnerDetailId_123" value="999"', $output, 'ZZ must carry the partner detail id');
        $this->assertStringContainsString('name="trans_no_123" value="999"', $output, 'ZZ must carry the transaction number');
        $this->assertStringContainsString('name="trans_type_123" value="1"', $output, 'ZZ must carry the transaction type');
    }

    /**
     * An unknown partner type is rejected instead of rendering something.
     */
    public function testPartnerTypeRoutingUnknown(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown partner type');

        $this->render('UNKNOWN');
    }

    /**
     * All the partner types the module supports are handled.
     */
    public function testAllPartnerTypesHandled(): void
    {
        $testCases = [
            'SP' => 'Payment To:',
            'CU' => 'From Customer/Branch:',
            'BT' => 'Transfer from',
            'QE' => 'Quick Entry:',
            'MA' => 'Existing Entry Type:',
        ];

        foreach ($testCases as $type => $expected) {
            $output = $this->render($type);

            $this->assertStringContainsString(
                $expected,
                $output,
                "Partner type {$type} must render its own form"
            );
        }
    }
}
