<?php
namespace Ksfraser\FaBankImport\Config;

use Ksfraser\FaBankImport\Config\ConfigService;

/**
 * ParserConfig: Manage enabled/disabled state for all modular parsers using bi_config via ConfigService.
 */
class ParserConfig
{
    private const KEY_PREFIX = 'parser.';

    /**
     * Get all parser enabled states as [parser_id => bool]
     */
    public static function getAll(): array
    {
        $result = [];
        $parsersDir = dirname(__DIR__, 3) . '/Parsers';
        if (!is_dir($parsersDir)) return $result;
        $dirs = scandir($parsersDir);
        $config = ConfigService::getInstance();
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $key = self::KEY_PREFIX . $dir . '.enabled';
            $enabled = $config->get($key, false);
            $result[$dir] = (bool)$enabled;
        }
        return $result;
    }

    /**
     * Get enabled state for a specific parser
     */
    public static function isEnabled(string $parserId): bool
    {
        $key = self::KEY_PREFIX . $parserId . '.enabled';
        return (bool)ConfigService::getInstance()->get($key, false);
    }

    /**
     * Set enabled state for a specific parser
     */
    public static function setEnabled(string $parserId, bool $enabled, ?string $username = null, ?string $reason = null): void
    {
        $key = self::KEY_PREFIX . $parserId . '.enabled';
        ConfigService::getInstance()->set($key, $enabled ? '1' : '0', $username, $reason);
    }

    /**
     * Set enabled states for all parsers at once
     */
    public static function setAll(array $states, ?string $username = null, ?string $reason = null): void
    {
        foreach ($states as $parserId => $enabled) {
            self::setEnabled($parserId, $enabled, $username, $reason);
        }
    }

    /**
     * Get active parsers list.
     */
    public static function getActiveParsers(): array
    {
        $active = ConfigService::getInstance()->get('parser.active_list', []);
        return is_array($active) ? $active : [];
    }

    /**
     * Get inactive parsers list.
     */
    public static function getInactiveParsers(): array
    {
        $inactive = ConfigService::getInstance()->get('parser.inactive_list', []);
        return is_array($inactive) ? $inactive : [];
    }

    /**
     * Set active parsers list.
     */
    public static function setActiveParsers(array $parsers, ?string $username = null, ?string $reason = null): void
    {
        ConfigService::getInstance()->set('parser.active_list', $parsers, $username, $reason ?: 'Update active parsers list');
    }

    /**
     * Set inactive parsers list.
     */
    public static function setInactiveParsers(array $parsers, ?string $username = null, ?string $reason = null): void
    {
        ConfigService::getInstance()->set('parser.inactive_list', $parsers, $username, $reason ?: 'Update inactive parsers list');
    }
}
