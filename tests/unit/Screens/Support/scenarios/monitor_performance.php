<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support\scenarios
 * @license    MIT
 */

use Ksfraser\Application\Config\Config;

$config = $GLOBALS['screen_config'];
$root = dirname(__DIR__, 5);

// The generic shell classes are not resolvable by the composer autoloader
// (the directory names do not match the namespace case), so they are loaded
// explicitly.
require_once $root . '/src/Ksfraser/Application/Config/Config.php';
require_once $root . '/src/Ksfraser/Application/Services/MetricsAggregator.php';
require_once $root . '/src/Ksfraser/Application/Services/AlertService.php';

$settings = isset($config['settings']) && is_array($config['settings']) ? $config['settings'] : [];
if (!empty($settings)) {
    $applicationConfig = Config::getInstance();
    foreach ($settings as $key => $value) {
        $applicationConfig->set((string) $key, $value);
    }
}

if (!isset($config['script']) || !is_file($config['script'])) {
    fwrite(STDERR, "The scenario configuration does not name the cron script.\n");
    exit(2);
}

require $config['script'];
