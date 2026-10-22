<?php

declare(strict_types=1);

/**
 * Test double for \Ksfraser\Application\Http\RequestHandler.
 *
 * The production RequestHandler has no isPost() and no getQuery() method,
 * yet \Ksfraser\Application\Application::run() calls $request->isPost() and
 * \Ksfraser\Application\Controllers\AdminController calls
 * $this->request->getQuery(). Both therefore fatal in production.
 *
 * This double is ONLY loaded inside the isolated child processes (see
 * isolated_runner.php) so those two classes can be exercised. The real
 * RequestHandler is covered by its own unit test in the parent process.
 *
 * @see \Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess
 */

namespace Ksfraser\Application\Http {

    /**
     * Fake request handler.
     */
    class RequestHandler
    {
        /**
         * Value returned by isPost().
         *
         * @var bool
         */
        public static $isPost = false;

        /**
         * Values returned by getQuery().
         *
         * @var array
         */
        public static $query = [];

        /**
         * Value returned by getTransactionCommand().
         *
         * @var array|null
         */
        public static $transaction = null;

        /** @var array */
        private $params = [];

        /**
         * @param array $params Request parameters.
         */
        public function __construct(array $params = [])
        {
            $this->params = $params;
        }

        /**
         * @return bool
         */
        public function isPost(): bool
        {
            return self::$isPost;
        }

        /**
         * @param string $key     Query parameter name.
         * @param mixed  $default Value returned when the parameter is absent.
         * @return mixed
         */
        public function getQuery(string $key, $default = null)
        {
            return array_key_exists($key, self::$query) ? self::$query[$key] : $default;
        }

        /**
         * @return array|null
         */
        public function getTransactionCommand(): ?array
        {
            return self::$transaction;
        }

        /**
         * @return array
         */
        public function getParams(): array
        {
            return $this->params;
        }
    }
}
