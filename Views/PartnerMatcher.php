<?php
/**
 * Shim file for PartnerMatcher - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Services/PartnerMatcher.php';
class_alias('Ksfraser\FaBankImport\Services\PartnerMatcher', 'PartnerMatcher');