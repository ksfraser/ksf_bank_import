<?php
/**
 * Shim file for QuickEntryPartnerTypeView - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/QuickEntryPartnerTypeView.php';
class_alias('Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView', 'KsfBankImport\Views\QuickEntryPartnerTypeView');
class_alias('Ksfraser\FaBankImport\Views\QuickEntryPartnerTypeView', 'QuickEntryPartnerTypeView');