<?php

namespace {
    // Mock the global db_query function used by FrontAccounting
    if (!function_exists('db_query')) {
        /**
         * Mock database query function
         * @param string $query
         * @param array $params
         * @return array|bool
         */
        function db_query(string $query, array $params = []) {
            return \Ksfraser\FaBankImport\TestHelpers\FaDbMock::mockQuery($query, $params);
        }
    }
}

namespace Ksfraser\FaBankImport\TestHelpers {
    class FaDbMock {
        private static $queryResults = [];
        private static $queryLog = [];
        private static $shouldFail = false;

        public static function setQueryResult(string $query, $result) {
            self::$queryResults[$query] = $result;
        }

        public static function getQueryLog(): array {
            return self::$queryLog;
        }

        public static function setShouldFail(bool $fail) {
            self::$shouldFail = $fail;
        }

        public static function reset() {
            self::$queryResults = [];
            self::$queryLog = [];
            self::$shouldFail = false;
        }

        /**
         * Mock query execution
         * @param string $query
         * @param array $params
         * @return array|bool
         */
        public static function mockQuery(string $query, array $params = []) {
            // Log the query for test assertions
            self::$queryLog[] = [
                'query' => $query,
                'params' => $params
            ];

            if (self::$shouldFail) {
                return false;
            }

            // Return predefined result if exists
            if (isset(self::$queryResults[$query])) {
                return self::$queryResults[$query];
            }

            // Default return empty array for SELECT queries
            if (stripos($query, 'SELECT') === 0) {
                return [];
            }

            // Default return true for other queries (INSERT, UPDATE, DELETE)
            return true;
        }
    }
}