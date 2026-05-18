<?php
/**
 * Shim file for SupplierPartnerTypeView - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/SupplierPartnerTypeView.php';
class_alias('Ksfraser\FaBankImport\Views\SupplierPartnerTypeView', 'KsfBankImport\Views\SupplierPartnerTypeView');
class_alias('Ksfraser\FaBankImport\Views\SupplierPartnerTypeView', 'SupplierPartnerTypeView');