<?php
/**
 * Shim file for AllocatableInvoicesTable - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/AllocatableInvoicesTable.php';
class_alias('Ksfraser\FaBankImport\Views\AllocatableInvoicesTable', 'KsfBankImport\Views\AllocatableInvoicesTable');
class_alias('Ksfraser\FaBankImport\Views\AllocatableInvoicesTable', 'AllocatableInvoicesTable');