<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Schema;

trait SchemaDescriptorHelpersTrait
{
    /** @return string */
    public static function table(): string
    {
        $d = static::descriptor();
        return (string) ($d['table'] ?? '');
    }

    /** @return string */
    public static function primaryKey(): string
    {
        $d = static::descriptor();
        return (string) ($d['primaryKey'] ?? '');
    }

    /**
     * @return array<int, string>
     */
    public static function fieldNames(): array
    {
        $d = static::descriptor();
        $fields = isset($d['fields']) && is_array($d['fields']) ? $d['fields'] : array();
        return array_keys($fields);
    }

    /**
     * Return the actual table name including prefix (if any).
     */
    public static function tableName(?string $prefix = null): string
    {
        if ($prefix === null) {
            $prefix = defined('TB_PREF') ? (string) TB_PREF : '';
        }

        return $prefix . static::table();
    }
}
