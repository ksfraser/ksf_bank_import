<?php
/**
 * OFX Parser Version Configuration
 * 
 * This file allows easy switching between different OFX parser implementations
 * for testing and migration purposes.
 * 
 * @since 2026-01-12
 */

// OFX Parser Version Selection
// Options:
//   'current'   - Use includes/vendor/asgrim (currently in production)
//   'ksf_fork'  - Use lib/ksf_ofxparser (your fork with improvements - HAS BUGS, NEEDS FIXES)
//   'root'      - Use vendor/asgrim (root composer install - not recommended)
if (!defined('OFX_PARSER_VERSION')) {
    define('OFX_PARSER_VERSION', 'current');
}

// Autoloader paths for each version
$ofx_parser_paths = [
    'current'  => __DIR__ . '/../includes/vendor/autoload.php',
    'ksf_fork' => __DIR__ . '/../lib/ksf_ofxparser/vendor/autoload.php',
    'root'     => __DIR__ . '/../vendor/autoload.php'
];

// Version metadata
$ofx_parser_info = [
    'current' => [
        'name' => 'Production (includes/vendor)',
        'path' => 'includes/vendor/asgrim/ofxparser',
        'status' => 'Active',
        'notes' => 'Currently used in production. Has Parser_orig-mod.php with experimental fixes.'
    ],
    'ksf_fork' => [
        'name' => 'KSF Fork (ksf_ofxparser)',
        'path' => 'lib/ksf_ofxparser',
        'status' => 'Testing',
        'notes' => 'Fork with improvements from multiple maintainers. Under testing.'
    ],
    'root' => [
        'name' => 'Root Composer (vendor)',
        'path' => 'vendor/asgrim/ofxparser',
        'status' => 'Unused',
        'notes' => 'Orphaned copy. Not currently used. Can be removed after migration.'
    ]
];

// Validate version selection
if (!isset($ofx_parser_paths[OFX_PARSER_VERSION])) {
    trigger_error(
        "Invalid OFX_PARSER_VERSION: '" . OFX_PARSER_VERSION . "'. " .
        "Valid options: " . implode(', ', array_keys($ofx_parser_paths)),
        E_USER_ERROR
    );
}

// Export selected path
if (!defined('OFX_PARSER_AUTOLOAD')) {
    define('OFX_PARSER_AUTOLOAD', $ofx_parser_paths[OFX_PARSER_VERSION]);
}

// Log version being used (if debugging enabled)
if (defined('DEBUG_OFX_PARSER') && DEBUG_OFX_PARSER) {
    error_log(sprintf(
        '[OFX Parser] Using version: %s (%s)',
        OFX_PARSER_VERSION,
        $ofx_parser_info[OFX_PARSER_VERSION]['name']
    ));
}
