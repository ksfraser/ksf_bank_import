<?php
/**
 * Shim file for HTML_ROW - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/HTML_ROW.php';
class_alias('Ksfraser\FaBankImport\Views\HTML_ROW', 'HTML_ROW');