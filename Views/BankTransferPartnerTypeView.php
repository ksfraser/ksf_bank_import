<?php
/**
 * Shim file for BankTransferPartnerTypeView - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/BankTransferPartnerTypeView.php';
class_alias('Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView', 'KsfBankImport\Views\BankTransferPartnerTypeView');
class_alias('Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView', 'BankTransferPartnerTypeView');