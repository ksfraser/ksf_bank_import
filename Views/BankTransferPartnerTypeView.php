<?php
// Legacy shim: alias the namespaced implementation so legacy code can
// continue to instantiate `BankTransferPartnerTypeView` without changing includes.
// Ensure namespaced implementation is loaded and provide a legacy alias.
$nsFile = __DIR__ . '/../src/Ksfraser/FaBankImport/Views/BankTransferPartnerTypeView.php';
if (file_exists($nsFile)) {
	require_once $nsFile;
}
if (!class_exists('BankTransferPartnerTypeView') && class_exists(\Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView::class)) {
	class_alias(\Ksfraser\FaBankImport\Views\BankTransferPartnerTypeView::class, 'BankTransferPartnerTypeView');
}