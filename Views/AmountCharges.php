<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `AmountCharges` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/AmountCharges.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('AmountCharges') && class_exists(\Ksfraser\FaBankImport\Views\AmountCharges::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\AmountCharges::class, 'AmountCharges');
}