#!/usr/bin/env python3
"""
Update process_statements.php to add pagination support
"""

# Read the file
with open('process_statements.php', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Find and replace the section
old_code = '''	error_reporting(E_ALL);

	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$trzs = $bit->get_transactions( $_POST['statusFilter'] );
		bank_import_debug("Transactions loaded with status filter", ['status' => $_POST['statusFilter'], 'count' => count($trzs)]);
	}
	else
	{
		$trzs = $bit->get_transactions();
		bank_import_debug("Transactions loaded without filter", ['count' => count($trzs)]);
	}

	// Create and render the ProcessStatementsView
	require_once('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php');
	$view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
	echo $view->render();'''

new_code = '''	error_reporting(E_ALL);

	// Initialize pagination parameters
	if (!isset($_POST['current_page'])) $_POST['current_page'] = 1;
	if (!isset($_POST['page_size'])) $_POST['page_size'] = 5;
	$offset = ((int)$_POST['current_page'] - 1) * (int)$_POST['page_size'];
	$limit_page = (int)$_POST['page_size'];

	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$result = $bit->get_transactions( $_POST['statusFilter'], null, null, null, null, null, null, $offset, $limit_page );
		bank_import_debug("Transactions loaded with status filter", ['status' => $_POST['statusFilter'], 'count' => count($result['transactions'])]);
	}
	else
	{
		$result = $bit->get_transactions( null, null, null, null, null, null, null, $offset, $limit_page );
		bank_import_debug("Transactions loaded without filter", ['count' => count($result['transactions'])]);
	}

	// Extract transactions and pagination metadata
	$trzs = $result['transactions'];
	$pagination = array(
		'total_count' => $result['total_count'],
		'current_page' => $result['current_page'],
		'total_pages' => $result['total_pages'],
		'limit' => $result['limit']
	);

	// Create and render the ProcessStatementsView
	require_once('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php');
	$view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
	$view->setPaginationData($pagination);
	echo $view->render();'''

# Replace
if old_code in content:
    content = content.replace(old_code, new_code)
    print("✓ Successfully updated process_statements.php")
else:
    print("✗ Could not find the code block to replace")
    print(f"Looking for:\n{old_code[:200]}...")

# Write back
with open('process_statements.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done!")
