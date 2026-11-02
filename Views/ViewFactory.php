<?php
/**
 * Shim file for ViewFactory - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/ViewFactory.php';
class_alias('Ksfraser\FaBankImport\Views\ViewFactory', 'KsfBankImport\Views\ViewFactory');
class_alias('Ksfraser\FaBankImport\Views\ViewFactory', 'ViewFactory');