<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `TransDate` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/TransDate.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('TransDate') && class_exists(\Ksfraser\FaBankImport\Views\TransDate::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\TransDate::class, 'TransDate');
}