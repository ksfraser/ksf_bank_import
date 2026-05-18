<?php
/**
 * Shim file for PartnerDataProviderInterface - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Views/DataProviders/PartnerDataProviderInterface.php';
class_alias('Ksfraser\FaBankImport\Views\DataProviders\PartnerDataProviderInterface', 'KsfBankImport\Views\DataProviders\PartnerDataProviderInterface');