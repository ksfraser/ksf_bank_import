<?php

declare(strict_types=1);

/**
 * Stand-ins for classes that the generic MVC shell under
 * src/Ksfraser/Application refers to but which do not exist in that
 * namespace.
 *
 * The container (src/Ksfraser/Application/Container.php) imports
 *   Ksfraser\Application\Repositories\TransactionRepository
 *   Ksfraser\Application\Factories\TransactionTypeFactory
 *   Ksfraser\Application\Services\TransactionService
 *   Ksfraser\Application\Services\TransactionViewService
 *   Ksfraser\Application\Views\HtmlTransactionView
 *   Ksfraser\Application\Commands\ProcessTransactionCommand
 *   Ksfraser\Application\Handlers\ProcessTransactionCommandHandler
 *   Ksfraser\Application\Controllers\BankImportController
 *   Ksfraser\Application\Middleware\TransactionValidationMiddleware
 *   Ksfraser\Application\Events\TransactionProcessedEvent
 *
 * The implementations that DO exist live in the Ksfraser\FaBankImport
 * namespace (src/Ksfraser/FaBankImport/...) and are not drop-in
 * replacements (different constructors, different namespaces), so they
 * cannot simply be aliased.
 *
 * These stubs exist purely so the generic shell can be exercised. They are
 * test doubles, not production code.
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
     * @see \Ksfraser\Application\Container::getTransactionTypeFactory()
     */
    class TransactionTypeFactory
    {
        /**
         * @param string $type Transaction type discriminator.
         * @param array  $data Transaction payload.
         * @return \stdClass
         */
        public function createTransactionType(string $type, array $data)
        {
            $transaction = new \stdClass();
            $transaction->type = $type;
            $transaction->data = $data;

            return $transaction;
        }
    }
}

namespace Ksfraser\Application\Services {
    /**
     * @see \Ksfraser\Application\Container::getTransactionService()
     */
    class TransactionService
    {
        /** @var mixed */
        private $repository;

        /** @var mixed */
        private $factory;

        /**
         * @param mixed $repository Transaction repository.
         * @param mixed $factory    Transaction type factory.
         */
        public function __construct($repository = null, $factory = null)
        {
            $this->repository = $repository;
            $this->factory = $factory;
        }
    }

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

namespace Ksfraser\Application\Commands {
    /**
     * @see \Ksfraser\Application\Container::getCommandBus()
     */
    class ProcessTransactionCommand
    {
        /** @var mixed */
        private $transactionId;

        /**
         * @param mixed $transactionId Identifier of the transaction.
         */
        public function __construct($transactionId = null)
        {
            $this->transactionId = $transactionId;
        }
    }
}

namespace Ksfraser\Application\Handlers {
    /**
     * @see \Ksfraser\Application\Container::getProcessTransactionCommandHandler()
     */
    class ProcessTransactionCommandHandler
    {
        /** @var mixed */
        private $transactionService;

        /**
         * @param mixed $transactionService Transaction service.
         */
        public function __construct($transactionService = null)
        {
            $this->transactionService = $transactionService;
        }

        /**
         * @param mixed $command Command being handled.
         * @return string
         */
        public function handle($command)
        {
            return 'handled:' . (is_object($command) ? get_class($command) : gettype($command));
        }
    }
}

namespace Ksfraser\Application\Events {
    /**
     * @see \Ksfraser\Application\Bootstrap::setupEventListeners()
     */
    class TransactionProcessedEvent
    {
        /** @var int */
        private $transactionId;

        /** @var string */
        private $type;

        /** @var \DateTime */
        private $timestamp;

        /**
         * @param int    $transactionId Identifier of the transaction.
         * @param string $type          Transaction type.
         */
        public function __construct(int $transactionId, string $type)
        {
            $this->transactionId = $transactionId;
            $this->type = $type;
            $this->timestamp = new \DateTime();
        }

        /**
         * @return int
         */
        public function getTransactionId(): int
        {
            return $this->transactionId;
        }

        /**
         * @return string
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * @return \DateTime
         */
        public function getTimestamp(): \DateTime
        {
            return $this->timestamp;
        }
    }
}

namespace Ksfraser\Application\Controllers {
    /**
     * @see \Ksfraser\Application\Container::getBankImportController()
     */
    class BankImportController
    {
        /**
         * When set, handle() throws. Used to exercise the error path of
         * \Ksfraser\Application\Application::run().
         *
         * @var bool
         */
        public static $shouldThrow = false;

        /**
         * @param string $action Action name.
         * @return string
         */
        public function handle(string $action): string
        {
            if (self::$shouldThrow) {
                throw new \RuntimeException('Bank import controller failure for action ' . $action);
            }

            return 'handled:' . $action;
        }
    }
}

namespace Ksfraser\Application\Middleware {
    /**
     * @see \Ksfraser\Application\Application::setupMiddleware()
     */
    class TransactionValidationMiddleware implements \Ksfraser\Application\Middleware\MiddlewareInterface
    {
        /**
         * @param \Ksfraser\Application\Http\RequestHandler $request The request.
         * @param callable                                  $next    Next middleware.
         * @return mixed
         */
        public function process(\Ksfraser\Application\Http\RequestHandler $request, callable $next)
        {
            return $next($request);
        }
    }
}
