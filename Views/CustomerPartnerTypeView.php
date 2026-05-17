<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `CustomerPartnerTypeView` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/CustomerPartnerTypeView.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('CustomerPartnerTypeView') && class_exists(\Ksfraser\FaBankImport\Views\CustomerPartnerTypeView::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\CustomerPartnerTypeView::class, 'CustomerPartnerTypeView');
}