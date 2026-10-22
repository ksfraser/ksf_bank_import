<?php

declare(strict_types=1);

/**
 * Global-scope doubles required by the transaction-processing cluster.
 *
 * Some production code reaches for FrontAccounting globals that cannot be
 * namespaced (items_cart is instantiated as \items_cart, sumCharges() is
 * probed with function_exists() in the global scope, and the ST_/QE_/PT_
 * constants are read as global constants).
 *
 * Every declaration here is guarded, so a real FrontAccounting install - or
 * another test double - always wins.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace {

    use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;

    if (!defined('DEFAULT_DAYS_SPREAD')) {
        /**
         * Number of days either side of a bank date used when matching GL entries.
         */
        define('DEFAULT_DAYS_SPREAD', 3);
    }

    if (!defined('QE_DEPOSIT')) {
        /**
         * Quick entry template type used for deposits.
         */
        define('QE_DEPOSIT', 1);
    }

    if (!defined('QE_PAYMENT')) {
        /**
         * Quick entry template type used for payments.
         */
        define('QE_PAYMENT', 0);
    }

    if (!defined('PT_QUICKENTRY')) {
        /**
         * FrontAccounting person type used for quick entry templates.
         */
        define('PT_QUICKENTRY', 'Q');
    }

    if (!class_exists('items_cart', false)) {
        /**
         * Minimal stand-in for FrontAccounting's items_cart.
         *
         * Only the members used by the bank import handlers are implemented:
         * the transaction type, the GL lines and their total.
         */
        class items_cart
        {
            /** @var int Transaction type of the cart. */
            public $trans_type = 0;

            /** @var int Order (document) number. */
            public $order_id = 0;

            /** @var string Transaction date. */
            public $tran_date = '';

            /** @var string Transaction reference. */
            public $reference = '';

            /** @var float Original amount of the transaction. */
            public $original_amount = 0.0;

            /** @var array<int, array<string, mixed>> GL lines of the cart. */
            public $gl_items = [];

            /**
             * Constructor.
             *
             * @param int $transType Transaction type of the cart.
             */
            public function __construct($transType = 0)
            {
                $this->trans_type = (int) $transType;
            }

            /**
             * Add a GL line to the cart.
             *
             * @param string $account    GL account code.
             * @param int    $dimension1 First dimension id.
             * @param int    $dimension2 Second dimension id.
             * @param float  $amount     Line amount.
             * @param string $memo       Line memo.
             * @param string $comment    Optional line comment.
             *
             * @return void
             */
            public function add_gl_item($account, $dimension1, $dimension2, $amount, $memo, $comment = ''): void
            {
                // A test can make the helper reject the line, the way
                // FrontAccounting does when the account is not usable.
                if (!\Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry::getReturn(
                    'items_cart.add_gl_item',
                    true
                )) {
                    return;
                }

                $this->gl_items[] = [
                    'account' => $account,
                    'dimension1' => $dimension1,
                    'dimension2' => $dimension2,
                    'amount' => (float) $amount,
                    'memo' => $memo,
                    'comment' => $comment,
                ];
            }

            /**
             * Number of GL lines currently held by the cart.
             *
             * @return int
             */
            public function count_gl_items(): int
            {
                return count($this->gl_items);
            }

            /**
             * Sum of every GL line amount in the cart.
             *
             * @return float
             */
            public function gl_items_total(): float
            {
                $total = 0.0;

                foreach ($this->gl_items as $line) {
                    $total += (float) $line['amount'];
                }

                return $total;
            }
        }
    }

    if (!class_exists('TransactionTypeLabel', false)) {
        /**
         * Global stand-in for the legacy TransactionTypeLabel helper.
         *
         * The production Transaction class instantiates \TransactionTypeLabel.
         */
        class TransactionTypeLabel
        {
            /** @var string Human readable label. */
            private $transactionTypeLabel = '';

            /**
             * Constructor.
             *
             * @param string $transactionDC Debit/credit marker.
             */
            public function __construct($transactionDC)
            {
                switch ($transactionDC) {
                    case 'C':
                        $this->transactionTypeLabel = 'Credit';
                        break;
                    case 'B':
                        $this->transactionTypeLabel = 'Bank Transfer';
                        break;
                    case 'D':
                    default:
                        $this->transactionTypeLabel = 'Debit';
                        break;
                }
            }

            /**
             * The label for the transaction direction.
             *
             * @return string
             */
            public function getTransactionTypeLabel()
            {
                return $this->transactionTypeLabel;
            }
        }
    }
}
