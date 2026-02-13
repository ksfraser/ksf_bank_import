<?php

namespace Ksfraser\FaBankImport\Config;

class Config
{
    private $settings;
    private static $instance = null;

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
                'default_dc' => getenv('BANK_IMPORT_DEFAULT_TRANSACTION_DC') ?: 'D',
                'max_amount' => 1000000.00
            ]
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(?self $instance): void
    {
        self::$instance = $instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

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