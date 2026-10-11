<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `AddCustomerButton` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/AddCustomerButton.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('AddCustomerButton') && class_exists(\Ksfraser\FaBankImport\Views\AddCustomerButton::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\AddCustomerButton::class, 'AddCustomerButton');
}
