#!/usr/bin/env php
<?php
/**
 * Update process_statements.php to add pagination parameter extraction
 * and pass to get_transactions() calls
 */

$file = 'process_statements.php';
$content = file_get_contents($file);

// Pattern 1: Add pagination parameter initialization after error_reporting
$pattern1 = 'error_reporting(E_ALL);

	if( $_POST[\'statusFilter\'] == 0 OR $_POST[\'statusFilter\'] == 1 )';

$replacement1 = 'error_reporting(E_ALL);

	// Initialize pagination parameters
	if (!isset($_POST[\'current_page\'])) $_POST[\'current_page\'] = 1;
	if (!isset($_POST[\'page_size\'])) $_POST[\'page_size\'] = 5;
	$offset = ((int)$_POST[\'current_page\'] - 1) * (int)$_POST[\'page_size\'];
	$limit_page = (int)$_POST[\'page_size\'];

	if( $_POST[\'statusFilter\'] == 0 OR $_POST[\'statusFilter\'] == 1 )';

if (strpos($content, $pattern1) !== false) {
    $content = str_replace($pattern1, $replacement1, $content);
    echo "[✓] Added pagination parameter initialization\n";
} else {
    echo "[!] Warning: Could not find pattern 1 for pagination init\n";
}

// Pattern 2: Update first get_transactions call
$pattern2 = '$trzs = $bit->get_transactions( $_POST[\'statusFilter\'] );';
$replacement2 = '$result = $bit->get_transactions( $_POST[\'statusFilter\'], null, null, null, null, null, null, $offset, $limit_page );';

if (strpos($content, $pattern2) !== false) {
    $content = str_replace($pattern2, $replacement2, $content);
    echo "[✓] Updated first get_transactions() call\n";
} else {
    echo "[!] Warning: Could not find pattern 2\n";
}

// Pattern 3: Update debug call for first result
$pattern3 = 'bank_import_debug("Transactions loaded with status filter", [\'status\' => $_POST[\'statusFilter\'], \'count\' => count($trzs)]);';
$replacement3 = 'bank_import_debug("Transactions loaded with status filter", [\'status\' => $_POST[\'statusFilter\'], \'count\' => count($result[\'transactions\'])]);';

if (strpos($content, $pattern3) !== false) {
    $content = str_replace($pattern3, $replacement3, $content);
    echo "[✓] Updated first debug call\n";
} else {
    echo "[!] Warning: Could not find pattern 3\n";
}

// Pattern 4: Update second get_transactions call
$pattern4 = '$trzs = $bit->get_transactions();';
$replacement4 = '$result = $bit->get_transactions( null, null, null, null, null, null, null, $offset, $limit_page );';

if (strpos($content, $pattern4) !== false) {
    $content = str_replace($pattern4, $replacement4, $content);
    echo "[✓] Updated second get_transactions() call\n";
} else {
    echo "[!] Warning: Could not find pattern 4\n";
}

// Pattern 5: Update debug call for second result
$pattern5 = 'bank_import_debug("Transactions loaded without filter", [\'count\' => count($trzs)]);';
$replacement5 = 'bank_import_debug("Transactions loaded without filter", [\'count\' => count($result[\'transactions\'])]);';

if (strpos($content, $pattern5) !== false) {
    $content = str_replace($pattern5, $replacement5, $content);
    echo "[✓] Updated second debug call\n";
} else {
    echo "[!] Warning: Could not find pattern 5\n";
}

// Pattern 6: Extract transactions and add pagination metadata before view
$pattern6 = '	// Create and render the ProcessStatementsView';
$replacement6 = '	// Extract transactions and pagination metadata
	$trzs = $result[\'transactions\'];
	$pagination = array(
		\'total_count\' => $result[\'total_count\'],
		\'current_page\' => $result[\'current_page\'],
		\'total_pages\' => $result[\'total_pages\'],
		\'limit\' => $result[\'limit\']
	);

	// Create and render the ProcessStatementsView';

if (strpos($content, $pattern6) !== false) {
    $content = str_replace($pattern6, $replacement6, $content);
    echo "[✓] Added transaction extraction and pagination metadata\n";
} else {
    echo "[!] Warning: Could not find pattern 6\n";
}

// Pattern 7: Add setPaginationData call
$pattern7 = '$view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
	echo $view->render();';

$replacement7 = '$view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
	$view->setPaginationData($pagination);
	echo $view->render();';

if (strpos($content, $pattern7) !== false) {
    $content = str_replace($pattern7, $replacement7, $content);
    echo "[✓] Added setPaginationData() call to view\n";
} else {
    echo "[!] Warning: Could not find pattern 7\n";
}

// Write back
file_put_contents($file, $content);
echo "\n✓ Successfully updated $file\n";
?>
