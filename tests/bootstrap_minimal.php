<?php
// Minimal bootstrap for running isolated unit tests that only need autoloading.
require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('FA_ROOT')) {
    define('FA_ROOT', dirname(__DIR__));
}
