<?php

declare(strict_types=1);

/**
 * Handler double used to drive the transaction processor without a handler
 * that touches FrontAccounting.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Recording handler double.
 */
final class RecordingTransactionHandler implements TransactionHandlerInterface
{
    /** @var string Partner type the handler is registered for. */
    private $partnerType;

    /** @var bool Value returned by canProcess(). */
    private $canProcess;

    /** @var array<int, array> Calls made to process(). */
    private $calls = [];

    /**
     * Constructor.
     *
     * @param string $partnerType Partner type of the handler.
     * @param bool   $canProcess  Value returned by canProcess().
     */
    public function __construct(string $partnerType = 'XX', bool $canProcess = true)
    {
        $this->partnerType = $partnerType;
        $this->canProcess = $canProcess;
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
        $this->calls[] = [
            'transaction' => $transaction,
            'transactionPostData' => $transactionPostData,
            'transactionId' => $transactionId,
            'collectionIds' => $collectionIds,
            'ourAccount' => $ourAccount,
        ];

        return TransactionResult::success($transactionId, 0, 'Recorded');
    }

    /**
     * @inheritDoc
     */
    public function getPartnerType(): string
    {
        return $this->partnerType;
    }

    /**
     * @inheritDoc
     */
    public function canProcess(string $partnerType): bool
    {
        return $this->canProcess;
    }

    /**
     * The calls that were made to process().
     *
     * @return array<int, array>
     */
    public function calls(): array
    {
        return $this->calls;
    }
}
