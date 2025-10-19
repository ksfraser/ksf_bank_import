-- Mantis #2708: Configuration Management System
-- Store module configuration in database instead of PHP files
-- Allows UI-based configuration changes in production

-- Configuration table for bank import module
CREATE TABLE IF NOT EXISTS `0_bi_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(20) NOT NULL DEFAULT 'string',
  `description` text,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration values
INSERT INTO `0_bi_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_system`) VALUES
-- Upload configuration
('upload.check_duplicates', '0', 'boolean', 'Enable duplicate file detection', 'upload', 0),
('upload.duplicate_window_days', '90', 'integer', 'How many days back to check for duplicates', 'upload', 0),
('upload.duplicate_action', 'warn', 'string', 'Action on duplicate: allow, warn, block', 'upload', 0),
('upload.max_file_size', '10485760', 'integer', 'Maximum upload file size in bytes (10MB default)', 'upload', 0),
('upload.allowed_extensions', 'qfx,ofx,csv,mt940,sta', 'string', 'Comma-separated list of allowed file extensions', 'upload', 0),

-- Storage configuration
('storage.retention_days', '730', 'integer', 'How many days to retain uploaded files (2 years default)', 'storage', 0),
('storage.compression_enabled', '0', 'boolean', 'Enable file compression for storage', 'storage', 0),

-- Logging configuration
('logging.enabled', '1', 'boolean', 'Enable import logging', 'logging', 0),
('logging.level', 'info', 'string', 'Log level: debug, info, warning, error', 'logging', 0),
('logging.retention_days', '30', 'integer', 'How many days to retain logs', 'logging', 0),

-- Performance configuration
('performance.batch_size', '100', 'integer', 'Number of transactions to process per batch', 'performance', 0),
('performance.memory_limit', '256M', 'string', 'PHP memory limit for imports', 'performance', 0),

-- Security configuration (system only)
('security.require_permission', 'SA_BANKFILEVIEW', 'string', 'Required permission for file management', 'security', 1),
('security.htaccess_enabled', '1', 'boolean', 'Protect upload directory with .htaccess', 'security', 1);

-- Configuration history table (audit trail)
CREATE TABLE IF NOT EXISTS `0_bi_config_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `old_value` text,
  `new_value` text,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` varchar(60) NOT NULL,
  `change_reason` text,
  PRIMARY KEY (`id`),
  KEY `config_key` (`config_key`),
  KEY `changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
