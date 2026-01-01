<?php
/**
 * Bank Import Module Configuration
 *
 * Copy this from config.example.php and adjust settings for your environment.
 */

// FrontAccounting Installation Path
// Based on error logs, your FA installation appears to be at:
$config['fa_root'] = '/var/www/html/infra/accounting';

// Alternative FA paths to try if the default doesn't work
$config['fa_paths'] = [
    '/var/www/html/infra/accounting',  // Production path from error logs
    '../..',                           // Default relative path
    '../../accounting',               // Alternative relative path
];

// Database settings (leave null to use FA settings)
$config['db_host'] = null;
$config['db_name'] = null;
$config['db_user'] = null;
$config['db_pass'] = null;

// Debug mode
$config['debug'] = true;  // Set to false in production

return $config;