<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\ChargeCalculationException;

/**
 * Service for calculating charges from collection IDs.
 * 
 * Centralizes charge calculation logic and provides consistent
 * error handling for both import workflows and process workflows.
 */
class ChargeCalculator
{
    /**
     * Calculate total charges for a transaction from collection IDs.
     *
     * @param int $transactionId
     * @param string $collectionIdsCsv Comma-separated collection IDs
     * @return float
     * @throws ChargeCalculationException
     */
    public function calculate(int $transactionId, string $collectionIdsCsv): float
    {
        if (empty($collectionIdsCsv)) {
            return 0.0;
        }

        try {
            $collectionIds = array_filter(
                array_map('trim', explode(',', $collectionIdsCsv)),
                static fn($id) => is_numeric($id) && (int)$id > 0
            );

            if (empty($collectionIds)) {
                return 0.0;
            }

            $totalCharge = 0.0;

            foreach ($collectionIds as $collectionId) {
                $charge = $this->getChargeAmount((int)$collectionId, $transactionId);
                $totalCharge += $charge;
            }

            return round($totalCharge, 2);
        } catch (ChargeCalculationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ChargeCalculationException::queryFailed(
                $transactionId,
                $e->getMessage()
            );
        }
    }

    /**
     * Get charge amount for a specific collection ID.
     *
     * @param int $collectionId
     * @param int $transactionId
     * @return float
     * @throws ChargeCalculationException
     */
    private function getChargeAmount(int $collectionId, int $transactionId): float
    {
        try {
            // In actual implementation:
            // $query = "SELECT amount FROM bi_line_items WHERE id = {$collectionId} AND transaction_id = {$transactionId}";
            // $row = db_fetch_assoc(db_query($query));
            // if (!$row) throw ChargeCalculationException::invalidCollectionIds(...);
            // return (float)$row['amount'];
            
            return 0.0;
        } catch (\Throwable $e) {
            throw ChargeCalculationException::queryFailed(
                $transactionId,
                "Failed to fetch charge for collection {$collectionId}: " . $e->getMessage()
            );
        }
    }

    /**
     * Validate charge amount matches expected value.
     *
     * @param float $expectedAmount
     * @param float $calculatedAmount
     * @param int $transactionId
     * @param float $tolerance Acceptable difference (e.g., 0.01)
     * @return void
     * @throws ChargeCalculationException
     */
    public function validateAmount(
        float $expectedAmount,
        float $calculatedAmount,
        int $transactionId,
        float $tolerance = 0.01
    ): void {
        $difference = abs($expectedAmount - $calculatedAmount);

        if ($difference > $tolerance) {
            throw ChargeCalculationException::amountMismatch(
                $expectedAmount,
                $calculatedAmount,
                $transactionId
            );
        }
    }
}
