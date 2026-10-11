<?php
/**
 * Shim file for ModuleMenuView - loads namespaced version and creates alias
 * 
 * @since 20251019
 */
require_once __DIR__ . '/../src/Ksfraser/FaBankImport/Views/module_menu_view.php';
class_alias('Ksfraser\FaBankImport\Views\ModuleMenuView', 'Views\ModuleMenuView');