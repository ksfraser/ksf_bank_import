<?php

namespace Ksfraser\Application\Config;

class Config
{
    private $settings;
    private static $instance = null;

//TODO:  Figure out how to add an array of params and merge them together!
//	Because this is a static instance, may not be able to add to it!
    private function __construct()
    {
        $this->settings = [
            'db' => [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'name' => getenv('DB_NAME') ?: 'test_db',
                'user' => getenv('DB_USER') ?: 'test_user',
                'pass' => getenv('DB_PASS') ?: 'test_pass'
            ],
            'logging' => [
                'enabled' => true,
                'path' => __DIR__ . '/../../../../logs'
            ],
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
