<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Seed;

/**
 * Default seed rows for bi_config.
 *
 * Kept separate from schema/install orchestration so seed content can evolve
 * independently from table DDL and installer wiring.
 */
final class BiConfigDefaultSeed
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function rows(): array
    {
        return array(
            array('config_key' => 'upload.check_duplicates', 'config_value' => '0', 'config_type' => 'boolean', 'description' => 'Enable duplicate file detection', 'category' => 'upload', 'is_system' => 0),
            array('config_key' => 'upload.duplicate_window_days', 'config_value' => '90', 'config_type' => 'integer', 'description' => 'How many days back to check for duplicates', 'category' => 'upload', 'is_system' => 0),
            array('config_key' => 'upload.duplicate_action', 'config_value' => 'warn', 'config_type' => 'string', 'description' => 'Action on duplicate: allow, warn, block', 'category' => 'upload', 'is_system' => 0),
            array('config_key' => 'upload.max_file_size', 'config_value' => '10485760', 'config_type' => 'integer', 'description' => 'Maximum upload file size in bytes (10MB default)', 'category' => 'upload', 'is_system' => 0),
            array('config_key' => 'upload.allowed_extensions', 'config_value' => 'qfx,ofx,csv,mt940,sta', 'config_type' => 'string', 'description' => 'Comma-separated list of allowed file extensions', 'category' => 'upload', 'is_system' => 0),
            array('config_key' => 'storage.retention_days', 'config_value' => '730', 'config_type' => 'integer', 'description' => 'How many days to retain uploaded files (2 years default)', 'category' => 'storage', 'is_system' => 0),
            array('config_key' => 'storage.compression_enabled', 'config_value' => '0', 'config_type' => 'boolean', 'description' => 'Enable file compression for storage', 'category' => 'storage', 'is_system' => 0),
            array('config_key' => 'logging.enabled', 'config_value' => '1', 'config_type' => 'boolean', 'description' => 'Enable import logging', 'category' => 'logging', 'is_system' => 0),
            array('config_key' => 'logging.level', 'config_value' => 'info', 'config_type' => 'string', 'description' => 'Log level: debug, info, warning, error', 'category' => 'logging', 'is_system' => 0),
            array('config_key' => 'logging.retention_days', 'config_value' => '30', 'config_type' => 'integer', 'description' => 'How many days to retain logs', 'category' => 'logging', 'is_system' => 0),
            array('config_key' => 'performance.batch_size', 'config_value' => '100', 'config_type' => 'integer', 'description' => 'Number of transactions to process per batch', 'category' => 'performance', 'is_system' => 0),
            array('config_key' => 'performance.memory_limit', 'config_value' => '256M', 'config_type' => 'string', 'description' => 'PHP memory limit for imports', 'category' => 'performance', 'is_system' => 0),
            array('config_key' => 'security.require_permission', 'config_value' => 'SA_BANKFILEVIEW', 'config_type' => 'string', 'description' => 'Required permission for file management', 'category' => 'security', 'is_system' => 1),
            array('config_key' => 'security.htaccess_enabled', 'config_value' => '1', 'config_type' => 'boolean', 'description' => 'Protect upload directory with .htaccess', 'category' => 'security', 'is_system' => 1),
        );
    }
}
