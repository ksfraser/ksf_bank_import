<?php
/**
 * Shim file for MatchingGLS - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/MatchingGLS.php';
class_alias('Ksfraser\FaBankImport\Views\MatchingGLS', 'MatchingGLS');
class_alias('Ksfraser\FaBankImport\Views\MatchingGLFactory', 'MatchingGLFactory');