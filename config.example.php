<?php
/**
 * Bank Import Module Configuration
 *
 * This file contains configuration settings for the bank import module.
 * Copy this file to config.php and adjust the settings as needed.
 */

// FrontAccounting Installation Path
// This should point to the root directory of your FrontAccounting installation
// Default assumes the module is installed at FA_ROOT/modules/bank_import/
$config['fa_root'] = '../..';

// Alternative FA paths to try if the default doesn't work
// Add your FA installation paths here
$config['fa_paths'] = [
    '../..',                           // Default: up two levels
    '../../accounting',               // If FA is in accounting/ subdirectory
    '/var/www/html/infra/accounting', // Production path from error logs
    '/opt/frontaccounting',           // Common Linux installation
];

// Database settings (if different from FA)
$config['db_host'] = null;  // null = use FA settings
$config['db_name'] = null;
$config['db_user'] = null;
$config['db_pass'] = null;

// Debug mode
$config['debug'] = true;  // Set to false in production

return $config;