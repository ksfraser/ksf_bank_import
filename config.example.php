<?php
/**
 * Bank Import Module Configuration
 *
 * This file contains configuration settings for the bank import module.
 * Copy this file to config.php and adjust the settings as needed.
 */

// FrontAccounting Installation Path
// Auto-detect: try common paths relative to this module directory.
// If auto-detect fails, falls back to $config['fa_paths'], then to this value.
// New prod uses 'accounting', old prod uses 'infra/accounting'.
$fa_paths_to_try = ['accounting', '../accounting', '../../accounting', 'infra/accounting', '../infra/accounting'];
$config['fa_root'] = null;
foreach ($fa_paths_to_try as $candidate) {
    $resolved = realpath(__DIR__ . '/' . $candidate);
    if ($resolved && file_exists($resolved . '/includes/session.inc')) {
        $config['fa_root'] = $candidate;
        break;
    }
}
// Fallback to hardcoded if auto-detect fails (override in production)
if ($config['fa_root'] === null) {
    $config['fa_root'] = '../../accounting';
}

// Alternative FA paths to try if the primary fa_root doesn't work.
// These are checked in order if fa_root/includes/session.inc is missing.
$config['fa_paths'] = [
    '../../accounting',
    '../accounting',
    '../../infra/accounting',
    '../infra/accounting',
    '../..',
];

// Database settings (leave null to use FA settings)
$config['db_host'] = null;  // null = use FA settings
$config['db_name'] = null;
$config['db_user'] = null;
$config['db_pass'] = null;

// Debug mode
$config['debug'] = true;  // Set to false in production

return $config;
