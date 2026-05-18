<?php
/**
 * Shim file for SubmitButton - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/SubmitButton.php';
class_alias('Ksfraser\FaBankImport\Views\SubmitButton', 'SubmitButton');