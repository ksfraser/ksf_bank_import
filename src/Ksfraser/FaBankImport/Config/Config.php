<?php

namespace Ksfraser\FaBankImport\Config;

/**
 * @BABOK CLASS ROLLUP: Config — 4 methods; FR-002 (4); FR-051 (4)
 * @BABOK RTM: FR-002 / FR-051 / ARCH-001; file: Config/Config.php; coverage: docs/TestCases/ (file rollup)
 */
class Config
{
    private $settings;
    private static $instance = null;

    /** @BABOK FR-002 / FR-051 / ARCH-001 — method: __construct */
    private function __construct()
    {
        $this->settings = [
            'db' => [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'name' => getenv('DB_NAME') ?: 'fa_bank_import',
                'user' => getenv('DB_USER') ?: 'root',
                'pass' => getenv('DB_PASS') ?: ''
            ],
            'logging' => [
                'enabled' => true,
                'path' => __DIR__ . '/../../logs'
            ],
            'transaction' => [
                'allowed_types' => ['C', 'D', 'B'],
                'max_amount' => 1000000.00
            ],
            'upload' => [
                'check_duplicates' => false,  // Check for duplicate file uploads
                'duplicate_window_days' => 90,  // How many days back to check for duplicates
                'duplicate_action' => 'warn'  // Action on duplicate: 'allow', 'warn' (soft deny), 'block' (hard deny)
            ]
        ];
    }

    /** @BABOK FR-002 / FR-051 / ARCH-001 — method: getInstance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @BABOK FR-002 / FR-051 / ARCH-001 — method: get */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = $this->settings;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /** @BABOK FR-002 / FR-051 / ARCH-001 — method: set */
    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $settings = &$this->settings;

        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (!isset($settings[$part]) || !is_array($settings[$part])) {
                $settings[$part] = [];
            }
            $settings = &$settings[$part];
        }

        $settings[array_shift($parts)] = $value;
    }
}