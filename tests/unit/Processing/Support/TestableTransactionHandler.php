<?php

declare(strict_types=1);

/**
 * Concrete transaction handler used to exercise AbstractTransactionHandler.
 *
 * The abstract base class keeps its template methods protected, so the double
 * exposes them to the test through thin public wrappers.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;
use Ksfraser\PartnerTypes\PartnerTypeInterface;

/**
 * Testable concrete handler.
 */
final class TestableTransactionHandler extends AbstractTransactionHandler
{
    /** @var PartnerTypeInterface Partner type returned to the base class. */
    private $partnerType;

    /**
     * Constructor.
     *
     * @param PartnerTypeInterface         $partnerType      Partner type of the handler.
     * @param ReferenceNumberService|null  $referenceService Optional reference service.
     */
    public function __construct(
        PartnerTypeInterface $partnerType,
        ?ReferenceNumberService $referenceService = null
    ) {
        $this->partnerType = $partnerType;

        parent::__construct($referenceService);
    }

    /**
     * @inheritDoc
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return $this->partnerType;
    }

    /**
     * @inheritDoc
     */
    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult {
        return $this->createSuccessResult($transactionId, 0, 'Processed');
    }

    /**
     * Expose the injected reference service.
     *
     * @return ReferenceNumberService
     */
    public function referenceService(): ReferenceNumberService
    {
        return $this->referenceService;
    }

    /**
     * Expose the protected transaction validation.
     *
     * @param array $transaction     Transaction data.
     * @param array $requiredFields Optional list of required fields.
     *
     * @return void
     */
    public function callValidateTransaction(array $transaction, array $requiredFields = []): void
    {
        $this->validateTransaction($transaction, $requiredFields);
    }

    /**
     * Expose the protected partner id extraction.
     *
     * @param array $transactionPostData Transaction specific POST data.
     *
     * @return int
     */
    public function callExtractPartnerId(array $transactionPostData): int
    {
        return $this->extractPartnerId($transactionPostData);
    }

    /**
     * Expose the protected charge calculation.
     *
     * @param int $transactionId Transaction id.
     *
     * @return float
     */
    public function callCalculateCharge(int $transactionId): float
    {
        return $this->calculateCharge($transactionId);
    }

    /**
     * Expose the protected error result factory.
     *
     * @param string $message Error message.
     * @param array  $data    Additional data.
     *
     * @return TransactionResult
     */
    public function callCreateErrorResult(string $message, array $data = []): TransactionResult
    {
        return $this->createErrorResult($message, $data);
    }

    /**
     * Expose the protected success result factory.
     *
     * @param int    $transNo   Transaction number.
     * @param int    $transType Transaction type.
     * @param string $message   Success message.
     * @param array  $data      Additional data.
     *
     * @return TransactionResult
     */
    public function callCreateSuccessResult(
        int $transNo,
        int $transType,
        string $message,
        array $data = []
    ): TransactionResult {
        return $this->createSuccessResult($transNo, $transType, $message, $data);
    }

    /**
     * Expose the protected warning result factory.
     *
     * @param string $message   Warning message.
     * @param int    $transNo   Transaction number.
     * @param int    $transType Transaction type.
     * @param array  $data      Additional data.
     *
     * @return TransactionResult
     */
    public function callCreateWarningResult(
        string $message,
        int $transNo = 0,
        int $transType = 0,
        array $data = []
    ): TransactionResult {
        return $this->createWarningResult($message, $transNo, $transType, $data);
    }
}
