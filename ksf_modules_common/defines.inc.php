<?php
// Minimal test/dev shim for the external ksf_modules_common dependency.
// In production, this file lives outside the module repo.

declare(strict_types=1);

// Define constants that are expected by the application
if (!defined('KSF_FIELD_NOT_SET')) {
    define('KSF_FIELD_NOT_SET', 1001);
}

if (!defined('TABLESTYLE2')) {
    define('TABLESTYLE2', 'tablestyle2');
}
