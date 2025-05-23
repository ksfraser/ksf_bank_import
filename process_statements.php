<?php

require_once __DIR__ . '/vendor/autoload.php';

use Ksfraser\FaBankImport\Application;

session_start();

$app = new Application();
$app->run();
