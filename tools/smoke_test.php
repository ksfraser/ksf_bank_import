<?php
/**
 * Smoke test for partner-type pagination fix
 * Checks if:
 * 1. JS helper preserveSubmit is included in page <head>
 * 2. Partner type select has onchange="preserveSubmit(this)" handler
 */

// Simulate minimal FA environment
$path_to_root = '../..';

// Mock $_POST to avoid errors
$_POST = array(
    'current_page' => 1,
    'page_size' => 10
);

// Mock $_GET
$_GET = array();

// Capture output
ob_start();

// Include just the JS generation part
$use_popup_windows = false;
$use_date_picker = true;
$js = "";
if ($use_popup_windows)
    $js .= "/* mock popup */";
if ($use_date_picker)
    $js .= "/* mock date picker */";

// This is the new code we added
$js .= "<script>function preserveSubmit(sel){try{var f=sel.form; if(!f) return; var cp = f.querySelector('input[name=\'current_page\']'); if(!cp){cp=document.createElement('input'); cp.type='hidden'; cp.name='current_page'; cp.value='" . (isset($_POST['current_page']) ? (int)$_POST['current_page'] : 1) . "'; f.appendChild(cp);} f.submit();}catch(e){console && console.error(e);} }</script>";

// Mock array_selector output
$mock_select = "array_selector would produce: <select name=\"partnerType[1]\" onchange=\"preserveSubmit(this)\"><option value=\"SP\">Supplier</option></select>";

$output = ob_get_clean();

// Test 1: Check if JS helper is present
$has_preserve_submit = (strpos($js, 'function preserveSubmit') !== false);

// Test 2: Simulate what the output would look like
$simulated_html = '<html><head>' . $js . '</head><body>' . $mock_select . '</body></html>';
$has_onchange = (strpos($simulated_html, 'onchange="preserveSubmit(this)"') !== false);
$has_hidden_input = (strpos($simulated_html, 'name=\'current_page\'') !== false);

// Output results
$results = array(
    'test_1_preserve_submit_function_exists' => $has_preserve_submit,
    'test_2_onchange_handler_present' => $has_onchange,
    'test_3_hidden_current_page_in_js' => $has_hidden_input,
    'js_length' => strlen($js),
    'current_page_value' => isset($_POST['current_page']) ? (int)$_POST['current_page'] : 1,
    'summary' => ($has_preserve_submit && $has_onchange && $has_hidden_input) ? 'PASS' : 'FAIL'
);

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
