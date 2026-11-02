#!/usr/bin/env python3
"""
Apply pagination updates to process_statements.php manually
Based on exact line numbers and patterns found
"""

file_path = 'process_statements.php'

# Read the current content
with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

print("=== APPLYING PAGINATION UPDATES ===\n")
print("Original line count: {}".format(len(lines)))

# STEP 1: Add pagination initialization after error_reporting (after line 500)
print("\n1. Adding pagination initialization...")
error_report_idx = None
for i, line in enumerate(lines):
    if 'error_reporting(E_ALL);' in line:
        error_report_idx = i
        break

if error_report_idx is not None:
    # Insert after the error_reporting line
    pagination_init = """
\t// Initialize pagination parameters
\tif (!isset($_POST['current_page'])) $_POST['current_page'] = 1;
\tif (!isset($_POST['page_size'])) $_POST['page_size'] = 5;
\t$offset = ((int)$_POST['current_page'] - 1) * (int)$_POST['page_size'];
\t$limit_page = (int)$_POST['page_size'];

"""
    lines.insert(error_report_idx + 1, pagination_init)
    print("   [OK] Added pagination initialization after line {}".format(error_report_idx + 1))
else:
    print("   [ERROR] Could not find error_reporting line")

# STEP 2: Update first get_transactions call
print("\n2. Updating first get_transactions call...")
for i, line in enumerate(lines):
    if '$trzs = $bit->get_transactions( $_POST[\'statusFilter\'] );' in line:
        lines[i] = '\t$result = $bit->get_transactions( $_POST[\'statusFilter\'], null, null, null, null, null, null, $offset, $limit_page );\n'
        print("   [OK] Updated first get_transactions at line {}".format(i + 1))
        break

# STEP 3: Update first debug call
print("\n3. Updating first debug call...")
for i, line in enumerate(lines):
    if 'bank_import_debug("Transactions loaded with status filter", [\'status\' => $_POST[\'statusFilter\'], \'count\' => count($trzs)' in line:
        lines[i] = '\tbank_import_debug("Transactions loaded with status filter", [\'status\' => $_POST[\'statusFilter\'], \'count\' => count($result[\'transactions\'])' + line.split('$trzs)')[1]
        print("   [OK] Updated first debug call at line {}".format(i + 1))
        break

# STEP 4: Update second get_transactions call
print("\n4. Updating second get_transactions call...")
for i, line in enumerate(lines):
    if '$trzs = $bit->get_transactions();' in line and i > error_report_idx:  # Make sure it's after error_reporting
        lines[i] = '\t$result = $bit->get_transactions( null, null, null, null, null, null, null, $offset, $limit_page );\n'
        print("   [OK] Updated second get_transactions at line {}".format(i + 1))
        break

# STEP 5: Update second debug call
print("\n5. Updating second debug call...")
for i, line in enumerate(lines):
    if 'bank_import_debug("Transactions loaded without filter", [\'count\' => count($trzs)' in line:
        lines[i] = '\tbank_import_debug("Transactions loaded without filter", [\'count\' => count($result[\'transactions\'])' + line.split('$trzs)')[1]
        print("   [OK] Updated second debug call at line {}".format(i + 1))
        break

# STEP 6: Add transaction extraction before view creation
print("\n6. Adding transaction extraction...")
for i, line in enumerate(lines):
    if '// Create and render the ProcessStatementsView' in line or 'Create and render the ProcessStatementsView' in line:
        extraction_code = """\t// Extract transactions and pagination metadata
\t$trzs = $result['transactions'];
\t$pagination = array(
\t\t'total_count' => $result['total_count'],
\t\t'current_page' => $result['current_page'],
\t\t'total_pages' => $result['total_pages'],
\t\t'limit' => $result['limit']
\t);

"""
        lines.insert(i, extraction_code)
        print("   [OK] Added transaction extraction before view creation".format(i + 1))
        break

# STEP 7: Add setPaginationData call
print("\n7. Adding setPaginationData call...")
for i, line in enumerate(lines):
    if '$view = new \\Ksfraser\\FaBankImport\\Views\\ProcessStatementsView($trzs, $optypes, $vendor_list);' in line:
        # Find the next line with echo $view->render()
        if i + 1 < len(lines) and 'echo $view->render()' in lines[i + 1]:
            lines.insert(i + 1, '\t$view->setPaginationData($pagination);\n')
            print("   [OK] Added setPaginationData call after view creation at line {}".format(i + 2))
        break

# Write back the file
with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(lines)

print("\n\n=== SUCCESS ===")
print("Updated {} with pagination support".format(file_path))
print("New line count: {}".format(len(lines)))

# Verify the changes
with open(file_path, 'r', encoding='utf-8') as f:
    updated_content = f.read()

markers = ['current_page', 'page_size', 'setPaginationData', '$offset', 'limit_page']
print("\nVerification:")
for marker in markers:
    found = marker in updated_content
    print("  {} {}".format("YES" if found else " NO", marker))
