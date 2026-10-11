<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `AddNoButton` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/AddNoButton.php';
if (file_exists($nsFile)) {
    require_once $nsFile;
}
if (!class_exists('AddNoButton') && class_exists(\Ksfraser\FaBankImport\Views\AddNoButton::class)) {
    class_alias(\Ksfraser\FaBankImport\Views\AddNoButton::class, 'AddNoButton');
}