<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `TransTitle` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/TransTitle.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('TransTitle') && class_exists(\Ksfraser\FaBankImport\Views\TransTitle::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\TransTitle::class, 'TransTitle');
}