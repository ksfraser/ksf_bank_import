<?php

declare(strict_types=1);

/**
 * Loads the classes of the module that the composer autoloader cannot
 * resolve on a case sensitive file system.
 *
 * composer.json maps
 *   "Ksfraser\\FaBankImport\\": "src/Ksfraser/FaBankImport/"
 * so every class below src/Ksfraser/FaBankImport is reachable through the
 * generic prefix - except the ones that live in a directory whose case does
 * not match the namespace, for example
 *   Ksfraser\FaBankImport\Views\HtmlTransactionView
 *     -> src/Ksfraser/FaBankImport/views/HtmlTransactionView.php
 *
 * The container of the module builds a Ksfraser\FaBankImport\Views\
 * HtmlTransactionView, so the file is loaded explicitly here. This is a
 * test harness workaround for a production autoloading defect - the defect
 * itself is not fixed here.
 */

$root = dirname(__DIR__, 4);

require_once $root . '/src/Ksfraser/FaBankImport/views/HtmlTransactionView.php';
