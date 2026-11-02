<?php
/**
 * Shim file for CustomerPartnerTypeView - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/CustomerPartnerTypeView.php';
class_alias('Ksfraser\FaBankImport\Views\CustomerPartnerTypeView', 'KsfBankImport\Views\CustomerPartnerTypeView');
class_alias('Ksfraser\FaBankImport\Views\CustomerPartnerTypeView', 'CustomerPartnerTypeView');