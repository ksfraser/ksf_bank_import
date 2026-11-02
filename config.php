<?php
/**
 * Bank Import Module Configuration
 *
 * Copy this from config.example.php and adjust settings for your environment.
 */

// FrontAccounting Installation Path
// Auto-detect: try common paths relative to this module directory.
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

// Database settings (leave null to use FA settings)
$config['db_host'] = null;
$config['db_name'] = null;
$config['db_user'] = null;
$config['db_pass'] = null;

// Debug mode
$config['debug'] = true;  // Set to false in production

return $config;