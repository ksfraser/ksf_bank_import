<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

final class CustomerInvoiceAllocator
{
	/**
	 * Resolve invoice allocations for a customer payment.
	 *
	 * Priority:
	 * 1. Exact amount match on the most recent invoice dated on/before payment date.
	 * 2. Exact subset sum of recent invoices, preferring fewer invoices and newer dates.
	 *
	 * @param array<int,array<string,mixed>> $invoices
	 * @return array<int,array{invoice_no:int,amount:float}>
	 */
	public function resolveAllocations(array $invoices, float $paymentAmount, string $paymentDate = '', int $maxCombinationSize = 6): array
	{
		$target = $this->toCents($paymentAmount);
		if ($target <= 0) {
			return array();
		}

		$normalized = $this->normalizeInvoices($invoices, $paymentDate);
		if (empty($normalized)) {
			return array();
		}

		foreach ($normalized as $invoice) {
			if ($invoice['cents'] === $target) {
				return array(
					array(
						'invoice_no' => $invoice['invoice_no'],
						'amount' => $this->fromCents($invoice['cents']),
					),
				);
			}
		}

		$maxCombinationSize = max(2, $maxCombinationSize);
		$maxCombinationSize = min($maxCombinationSize, count($normalized));
		for ($size = 2; $size <= $maxCombinationSize; $size++) {
			$combo = $this->findExactCombination($normalized, $target, $size);
			if (!empty($combo)) {
				return $combo;
			}
		}

		return array();
	}

	/**
	 * @param array<int,array<string,mixed>> $invoices
	 * @return array<int,array{invoice_no:int,tran_date:string,cents:int}>
	 */
	private function normalizeInvoices(array $invoices, string $paymentDate = ''): array
	{
		$normalized = array();
		foreach ($invoices as $invoice) {
			$invoiceNo = (int)($invoice['invoice_no'] ?? 0);
			$tranDate = (string)($invoice['tran_date'] ?? '');
			$amount = isset($invoice['amount']) ? (float)$invoice['amount'] : (float)($invoice['outstanding'] ?? 0);

			if ($invoiceNo <= 0 || $tranDate === '' || $amount <= 0) {
				continue;
			}

			if ($paymentDate !== '' && strcmp($tranDate, $paymentDate) > 0) {
				continue;
			}

			$normalized[] = array(
				'invoice_no' => $invoiceNo,
				'tran_date' => $tranDate,
				'cents' => $this->toCents($amount),
			);
		}

		usort($normalized, function (array $left, array $right): int {
			if ($left['tran_date'] === $right['tran_date']) {
				return $right['invoice_no'] <=> $left['invoice_no'];
			}

			return strcmp($right['tran_date'], $left['tran_date']);
		});

		return $normalized;
	}

	/**
	 * @param array<int,array{invoice_no:int,tran_date:string,cents:int}> $invoices
	 * @return array<int,array{invoice_no:int,amount:float}>
	 */
	private function findExactCombination(array $invoices, int $targetCents, int $size): array
	{
		$result = $this->searchCombination($invoices, $targetCents, $size, 0, array());
		if ($result === null) {
			return array();
		}

		return array_map(function (array $invoice): array {
			return array(
				'invoice_no' => $invoice['invoice_no'],
				'amount' => $this->fromCents($invoice['cents']),
			);
		}, $result);
	}

	/**
	 * @param array<int,array{invoice_no:int,tran_date:string,cents:int}> $invoices
	 * @param array<int,array{invoice_no:int,tran_date:string,cents:int}> $selected
	 * @return array<int,array{invoice_no:int,tran_date:string,cents:int}>|null
	 */
	private function searchCombination(array $invoices, int $remainingCents, int $size, int $startIndex, array $selected)
	{
		if ($remainingCents === 0 && $size === 0) {
			return $selected;
		}

		if ($remainingCents < 0 || $size === 0) {
			return null;
		}

		for ($index = $startIndex; $index < count($invoices); $index++) {
			$invoice = $invoices[$index];
			if ($invoice['cents'] > $remainingCents) {
				continue;
			}

			$selected[] = $invoice;
			$found = $this->searchCombination($invoices, $remainingCents - $invoice['cents'], $size - 1, $index + 1, $selected);
			if ($found !== null) {
				return $found;
			}
			array_pop($selected);
		}

		return null;
	}

	private function toCents(float $amount): int
	{
		return (int) round($amount * 100, 0);
	}

	private function fromCents(int $cents): float
	{
		return round($cents / 100, 2);
	}
}