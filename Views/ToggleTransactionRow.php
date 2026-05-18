<?php
/**
 * Shim file for ToggleTransactionRow - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/ToggleTransactionRow.php';
class_alias('Ksfraser\FaBankImport\Views\ToggleTransactionRow', 'ToggleTransactionRow');