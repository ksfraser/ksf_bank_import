<?php

declare(strict_types=1);

/**
 * Test doubles for the collaborators of the Ksfraser\FaBankImport shell.
 *
 * Everything in this file is a test double; none of it is production code.
 * The doubles exist so the shell can be driven without a database, without
 * FrontAccounting and without a real HTTP request.
 */

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
    use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;
    use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
    use Ksfraser\FaBankImport\Services\TransactionService;

    /**
     * In memory configuration repository.
     */
    final class FakeConfigRepository implements ConfigRepositoryInterface
    {
        /** @var array */
        private $values = [];

        /** @var array */
        private $history = [];

        /**
         * @param array $values Initial key => value pairs.
         */
        public function __construct(array $values = [])
        {
            $this->values = $values;
        }

        public function get(string $key, $default = null)
        {
            return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
        }

        public function set(string $key, $value, ?string $username = null, ?string $reason = null): bool
        {
            $this->values[$key] = $value;
            $this->history[] = [
                'key' => $key,
                'value' => $value,
                'username' => $username,
                'reason' => $reason,
            ];

            return true;
        }

        public function getByCategory(string $category): array
        {
            $prefix = $category . '.';
            $result = [];
            foreach ($this->values as $key => $value) {
                if (strpos($key, $prefix) === 0) {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        public function getAll(): array
        {
            return $this->values;
        }

        public function has(string $key): bool
        {
            return array_key_exists($key, $this->values);
        }

        public function delete(string $key): bool
        {
            unset($this->values[$key]);

            return true;
        }

        public function getHistory(?string $key = null, int $limit = 50): array
        {
            if ($key === null) {
                return array_slice($this->history, 0, $limit);
            }

            $filtered = array_values(array_filter($this->history, static function ($entry) use ($key) {
                return $entry['key'] === $key;
            }));

            return array_slice($filtered, 0, $limit);
        }
    }

    /**
     * Transaction repository that answers from a fixed array.
     */
    final class FakeTransactionRepository implements TransactionRepositoryInterface
    {
        /** @var array */
        private $rows;

        /**
         * @param array $rows Rows the repository answers with.
         */
        public function __construct(array $rows = [])
        {
            $this->rows = $rows;
        }

        public function findAll(): array
        {
            return $this->rows;
        }

        public function findById(int $id): ?array
        {
            foreach ($this->rows as $row) {
                if (isset($row['id']) && (int) $row['id'] === $id) {
                    return $row;
                }
            }

            return null;
        }

        public function findByFilters(array $filters): array
        {
            $rows = $this->rows;
            foreach ($filters as $column => $value) {
                $rows = array_values(array_filter($rows, static function ($row) use ($column, $value) {
                    return isset($row[$column]) && $row[$column] === $value;
                }));
            }

            return $rows;
        }

        public function update(
            array $transactionIds,
            int $status,
            int $faTransNo,
            int $faTransType,
            bool $matched = false,
            bool $created = false,
            ?string $partnerType = null,
            string $partnerOption = ''
        ): int {
            return count($transactionIds);
        }

        public function reset(array $transactionIds, int $faTransNo, int $faTransType): int
        {
            return count($transactionIds);
        }

        public function prevoid(int $faTransNo, int $faTransType): int
        {
            return 0;
        }

        public function findNormalPairing(?string $account = null): array
        {
            return [];
        }
    }

    /**
     * Transaction service that never touches the database.
     */
    final class FakeTransactionService extends TransactionService
    {
        /** @var array */
        private $pending;

        /** @var bool */
        private $processResult;

        /**
         * @param array $pending       Rows returned for pending transactions.
         * @param bool  $processResult Value returned by processTransaction().
         */
        public function __construct(array $pending = [], bool $processResult = true)
        {
            parent::__construct(new FakeTransactionRepository($pending), new TransactionTypeFactory());

            $this->pending = $pending;
            $this->processResult = $processResult;
        }

        public function getPendingTransactions(): array
        {
            return $this->pending;
        }

        public function processTransaction(int $id, string $type): bool
        {
            return $this->processResult;
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    /**
     * Customer service double.
     */
    final class FakeCustomerService
    {
        /** @var array */
        private $rows;

        /** @var int */
        private $nextId;

        /**
         * @param array $rows   Transaction rows the repository knows.
         * @param int   $nextId Id handed out for a created customer.
         */
        public function __construct(array $rows = [], int $nextId = 100)
        {
            $this->rows = $rows;
            $this->nextId = $nextId;
        }

        /**
         * @param array $transaction Transaction the customer is created from.
         * @return int
         */
        public function createFromTransaction($transaction): int
        {
            if (isset($transaction['fail'])) {
                throw new \RuntimeException((string) $transaction['fail']);
            }

            return $this->nextId++;
        }
    }

    /**
     * Vendor service double.
     */
    final class FakeVendorService
    {
        /** @var int */
        private $nextId;

        /**
         * @param int $nextId Id handed out for a created vendor.
         */
        public function __construct(int $nextId = 200)
        {
            $this->nextId = $nextId;
        }

        /**
         * @param array $transaction Transaction the vendor is created from.
         * @return int
         */
        public function createFromTransaction($transaction): int
        {
            if (isset($transaction['fail'])) {
                throw new \RuntimeException((string) $transaction['fail']);
            }

            return $this->nextId++;
        }
    }

    /**
     * Transaction lookup double for the commands.
     */
    final class FakeTransactionLookup
    {
        /** @var array */
        private $rows;

        /** @var array */
        private $reset = [];

        /**
         * @param array $rows Transaction rows the lookup knows.
         */
        public function __construct(array $rows = [])
        {
            $this->rows = $rows;
        }

        /**
         * @param int $id Transaction id.
         * @return array|null
         */
        public function findById(int $id): ?array
        {
            foreach ($this->rows as $row) {
                if (isset($row['id']) && (int) $row['id'] === $id) {
                    return $row;
                }
            }

            return null;
        }

        /**
         * @param int $id Transaction id.
         * @return void
         */
        public function reset(int $id): void
        {
            $this->reset[] = $id;

            if (isset($this->rows[$id]['fail'])) {
                throw new \RuntimeException((string) $this->rows[$id]['fail']);
            }
        }

        /**
         * @return array
         */
        public function getReset(): array
        {
            return $this->reset;
        }
    }

    /**
     * Transaction service double that can toggle the debit/credit direction.
     */
    final class FakeToggleService
    {
        /** @var array */
        private $results;

        /**
         * @param array $results Per transaction id result.
         */
        public function __construct(array $results = [])
        {
            $this->results = $results;
        }

        /**
         * @param int $id Transaction id.
         * @return array
         */
        public function toggleDebitCredit(int $id): array
        {
            if (isset($this->results[$id])) {
                return $this->results[$id];
            }

            throw new \RuntimeException('toggle failed for ' . $id);
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    use Ksfraser\FaBankImport\Contracts\CommandInterface;
    use Ksfraser\FaBankImport\Results\TransactionResult;

    /**
     * Command double.
     */
    class RecordingCommand implements CommandInterface
    {
        /** @var string */
        private $name;

        /** @var int */
        public $executions = 0;

        /** @var \Throwable|null */
        private $failure;

        /**
         * @param string $name Command name.
         * @param \Throwable|null $failure Exception the command throws.
         */
        public function __construct(string $name = 'Recording', ?\Throwable $failure = null)
        {
            $this->name = $name;
            $this->failure = $failure;
        }

        public function execute(): TransactionResult
        {
            $this->executions++;

            if ($this->failure !== null) {
                throw $this->failure;
            }

            return TransactionResult::success(0, 0, 'executed ' . $this->name);
        }

        public function getName(): string
        {
            return $this->name;
        }
    }

    /**
     * Container double that hands out prepared command instances.
     */
    final class FakeCommandContainer
    {
        /** @var array */
        private $instances = [];

        /** @var \Throwable|null */
        private $failure;

        /**
         * Registers the instance that is handed out for a command class.
         *
         * @param string $class    Command class.
         * @param object $instance Command instance.
         * @return void
         */
        public function bind(string $class, object $instance): void
        {
            $this->instances[$class] = $instance;
        }

        /**
         * Makes the next call to make() fail.
         *
         * @param \Throwable $failure Exception to throw.
         * @return void
         */
        public function failWith(\Throwable $failure): void
        {
            $this->failure = $failure;
        }

        /**
         * @param string $class      Command class.
         * @param array  $parameters Constructor overrides.
         * @return object
         */
        public function make(string $class, array $parameters = []): object
        {
            if ($this->failure !== null) {
                throw $this->failure;
            }

            if (!isset($this->instances[$class])) {
                throw new \RuntimeException('No command bound for ' . $class);
            }

            return $this->instances[$class];
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    use Ksfraser\FaBankImport\Http\RequestHandler;

    /**
     * Request handler that also answers getQuery() queries.
     *
     * The production request handler of the module has no getQuery() method
     * although the admin controller calls it. The double adds the missing
     * accessor so that the controller can be exercised.
     */
    class FakeRequestHandler extends RequestHandler
    {
        /** @var array */
        public static $query = [];

        /**
         * @param string $key     Query parameter name.
         * @param mixed  $default Value returned when the parameter is absent.
         * @return mixed
         */
        public function getQuery(string $key, $default = null)
        {
            return array_key_exists($key, self::$query) ? self::$query[$key] : $default;
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    /**
     * Command bus double.
     */
    final class FakeCommandBus
    {
        /** @var array */
        private $dispatched = [];

        /** @var \Throwable|null */
        private $failure;

        /**
         * @param \Throwable|null $failure Exception the bus throws.
         */
        public function __construct(?\Throwable $failure = null)
        {
            $this->failure = $failure;
        }

        /**
         * @param object $command Command being dispatched.
         * @return string
         */
        public function dispatch($command): string
        {
            $this->dispatched[] = $command;

            if ($this->failure !== null) {
                throw $this->failure;
            }

            return 'dispatched';
        }

        /**
         * @return array
         */
        public function getDispatched(): array
        {
            return $this->dispatched;
        }
    }

    /**
     * Container double for the MVC bank import controller.
     */
    final class FakeContainer
    {
        /** @var FakeTransactionService */
        private $transactionService;

        /** @var FakeCommandBus */
        private $commandBus;

        /**
         * @param FakeTransactionService|null $transactionService Transaction service.
         * @param FakeCommandBus|null          $commandBus         Command bus.
         */
        public function __construct(?FakeTransactionService $transactionService = null, ?FakeCommandBus $commandBus = null)
        {
            $this->transactionService = $transactionService ?? new FakeTransactionService();
            $this->commandBus = $commandBus ?? new FakeCommandBus();
        }

        /**
         * @return FakeTransactionService
         */
        public function getTransactionService(): FakeTransactionService
        {
            return $this->transactionService;
        }

        /**
         * @return FakeCommandBus
         */
        public function getCommandBus(): FakeCommandBus
        {
            return $this->commandBus;
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support {

    use Ksfraser\FaBankImport\Controllers\AdminController;
    use Ksfraser\FaBankImport\Controllers\BankImportController;

    /**
     * Bank import controller that records the action it was asked to run.
     */
    class RecordingBankImportController extends BankImportController
    {
        /** @var string|null */
        public $handledAction;

        /** @var \Throwable|null */
        public $failure;

        /**
         * @param object|null $container Container to use.
         */
        public function __construct($container = null)
        {
            parent::__construct($container);
        }

        public function index(): void
        {
            $this->handledAction = 'index';

            if ($this->failure !== null) {
                throw $this->failure;
            }
        }

        public function process(): void
        {
            $this->handledAction = 'process';

            if ($this->failure !== null) {
                throw $this->failure;
            }
        }
    }

    /**
     * Admin controller that accepts an injected request handler.
     */
    class TestableAdminController extends AdminController
    {
        /**
         * Replaces the request handler of the controller.
         *
         * @param object $request Request handler.
         * @return void
         */
        public function useRequest($request): void
        {
            $this->request = $request;
        }
    }
}
