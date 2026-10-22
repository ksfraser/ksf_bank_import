<?php

declare(strict_types=1);

/**
 * Scenario: exercises the legacy MVC controller
 * \Ksfraser\FaBankImport\Controllers\BankImportController_MVC.
 *
 * That class lives in the same file as the MVC bank import controller but
 * is built on the generic Ksfraser\Application shell: it asks
 * Ksfraser\Application\Container for a transaction view service. The
 * classes of the generic shell that the container needs do not exist in
 * production (only the Application test tree provides them), so this
 * scenario brings its own doubles. They are test doubles, not production
 * code.
 */

namespace Ksfraser\Application\Repositories {
    /**
     * @see \Ksfraser\Application\Container::getTransactionRepository()
     */
    class TransactionRepository
    {
        /** @var string */
        private $table;

        /**
         * @param string $table Table this repository is bound to.
         */
        public function __construct(string $table = '0_bi_transactions')
        {
            $this->table = $table;
        }

        /**
         * @return string
         */
        public function getTable(): string
        {
            return $this->table;
        }
    }
}

namespace Ksfraser\Application\Factories {
    /**
     * The transaction the factory hands out.
     */
    class FakeTransaction
    {
        /** @var string */
        public $type;

        /** @var array */
        public $data;

        /** @var bool */
        public $processed = false;

        /**
         * @param string $type Transaction type discriminator.
         * @param array  $data Transaction payload.
         */
        public function __construct(string $type, array $data)
        {
            $this->type = $type;
            $this->data = $data;
        }

        /**
         * @return void
         */
        public function processTransaction(): void
        {
            $this->processed = true;
        }
    }

    /**
     * @see \Ksfraser\Application\Container::getTransactionTypeFactory()
     */
    class TransactionTypeFactory
    {
        /**
         * @param string $type Transaction type discriminator.
         * @param array  $data Transaction payload.
         * @return FakeTransaction
         */
        public function createTransactionType(string $type, array $data)
        {
            return new FakeTransaction($type, $data);
        }
    }
}

namespace Ksfraser\Application\Views {
    /**
     * @see \Ksfraser\Application\Container::getTransactionViewService()
     */
    class HtmlTransactionView
    {
        /** @var mixed */
        private $transaction;

        /**
         * @param mixed $transaction Transaction instance.
         */
        public function __construct($transaction = null)
        {
            $this->transaction = $transaction;
        }
    }
}

namespace Ksfraser\Application\Services {
    /**
     * @see \Ksfraser\Application\Container::getTransactionViewService()
     */
    class TransactionViewService
    {
        /** @var mixed */
        private $transaction;

        /** @var mixed */
        private $view;

        /**
         * @param mixed $transaction Transaction instance.
         * @param mixed $view        View instance.
         */
        public function __construct($transaction = null, $view = null)
        {
            $this->transaction = $transaction;
            $this->view = $view;
        }

        /**
         * @return string
         */
        public function display(): string
        {
            return '<div class="transaction">' . $this->transaction->type . '</div>';
        }
    }
}

namespace {
    use Ksfraser\FaBankImport\Controllers\BankImportController_MVC;

    // The MVC controller shares its file with the MVC bank import
    // controller, so the autoloader cannot resolve it on its own.
    require_once dirname(__DIR__, 5) . '/src/Ksfraser/FaBankImport/Controllers/BankImportController.php';

    // The configuration of the generic shell lives in a directory whose
    // case does not match its namespace, so it is loaded explicitly too.
    require_once dirname(__DIR__, 5) . '/src/Ksfraser/Application/Config/Config.php';

    if (!function_exists('db_query')) {
        /**
         * @param string $sql  Statement.
         * @param mixed  $error Error message.
         * @return array
         */
        function db_query($sql, $error = null)
        {
            $GLOBALS['__db_query_calls'][] = $sql;

            $row = [
                'id' => 7,
                'transactionDC' => 'C',
                'amount' => 12.5,
            ];

            return $GLOBALS['__db_query_rows'] ? [$row] : [];
        }
    }

    $mode = isset($args[0]) ? (string) $args[0] : 'index';

    $GLOBALS['__db_query_calls'] = [];
    $GLOBALS['__db_query_rows'] = ($mode !== 'not_found');

    $controller = new BankImportController_MVC();

    if ($mode === 'process') {
        $controller->processTransaction(7, 'C');
        echo "MVC_PROCESSED\n";
    } elseif ($mode === 'not_found') {
        try {
            $controller->processTransaction(99, 'C');
            echo "NO_EXCEPTION\n";
        } catch (\InvalidArgumentException $exception) {
            echo 'EXCEPTION=' . $exception->getMessage() . "\n";
        }
    } else {
        ob_start();
        $controller->index();
        $rendered = (string) ob_get_clean();

        echo 'RENDERED=' . trim($rendered) . "\n";
    }

    echo 'DB_QUERY_CALLS=' . json_encode($GLOBALS['__db_query_calls']) . "\n";
    echo "MVC_CONTROLLER_COMPLETED\n";
}
