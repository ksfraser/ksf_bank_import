<?php
/**
 * Bank Import Settings Page
 * 
 * Provides UI for configuring bank import behavior including:
 * - Transaction reference number logging
 * - GL account for reference logging
 * 
 * @package Bank Import
 * @author Kevin Fraser
 * @version 1.0.0
 * @license MIT
 */

$path_to_root = "../..";

require_once($path_to_root . "/includes/session.inc");
require_once($path_to_root . "/includes/ui.inc");
require_once(__DIR__ . "/../../src/Configuration/BankImportConfig.php");

use KsfBankImport\Configuration\BankImportConfig;

page(_($help_context = "Bank Import Settings"), false, false, "", null);

// ===================================================================
// FORM SUBMISSION HANDLER
// ===================================================================

if (isset($_POST['save_settings'])) {
    begin_transaction();
    
    try {
        // Transaction Reference Logging
        $trans_ref_enabled = isset($_POST['trans_ref_logging_enabled']) ? 1 : 0;
        BankImportConfig::setTransRefLoggingEnabled($trans_ref_enabled);
        
        // GL Account for Trans Ref Logging
        $trans_ref_account = get_post('trans_ref_account', '0000');
        if (empty($trans_ref_account)) {
            $trans_ref_account = '0000';
        }
        BankImportConfig::setTransRefAccount($trans_ref_account);
        
        commit_transaction();
        display_notification(_("Settings have been updated."));
    } catch (Exception $e) {
        cancel_transaction();
        display_error(_("Failed to save settings: ") . $e->getMessage());
    }
} elseif (isset($_POST['reset_settings'])) {
    begin_transaction();
    
    try {
        BankImportConfig::resetToDefaults();
        commit_transaction();
        display_notification(_("Settings have been reset to defaults."));
    } catch (Exception $e) {
        cancel_transaction();
        display_error(_("Failed to reset settings: ") . $e->getMessage());
    }
}

// ===================================================================
// RENDER SETTINGS FORM
// ===================================================================

start_form();

start_outer_table(TABLESTYLE2);

// Section: Transaction Reference Logging
table_section_title(_("Transaction Reference Logging"));

// Enable/Disable Trans Ref Logging
$trans_ref_enabled = BankImportConfig::getTransRefLoggingEnabled();
check_row(
    _("Enable Transaction Reference Logging"),
    'trans_ref_logging_enabled',
    $trans_ref_enabled,
    false,
    _("When enabled, transaction reference numbers will be logged to a GL account")
);

// GL Account for Trans Ref Logging
$trans_ref_account = BankImportConfig::getTransRefAccount();
gl_all_accounts_list_row(
    _("GL Account for Reference Logging"),
    'trans_ref_account',
    null,
    false,
    false,
    $trans_ref_account,
    true
);

label_row(
    _("Current Account"),
    $trans_ref_account,
    '',
    '',
    0,
    _("This is the GL account where transaction references will be logged")
);

end_outer_table(1);

// Action Buttons
start_table(TABLESTYLE2);
echo "<tr>";
echo "<td style='text-align: center;'>";
submit_center('save_settings', _("Save Settings"), true, _("Save configuration settings"), 'default');
echo "&nbsp;";
submit_center('reset_settings', _("Reset to Defaults"), true, _("Reset all settings to default values"), false);
echo "</td>";
echo "</tr>";
end_table(1);

end_form();

// ===================================================================
// DISPLAY CURRENT SETTINGS (Read-Only)
// ===================================================================

echo "<br>";
start_outer_table(TABLESTYLE);

table_section_title(_("Current Configuration"));

$all_settings = BankImportConfig::getAllSettings();

start_table(TABLESTYLE_ALT);
$th = array(
    _("Setting"),
    _("Value"),
    _("Description")
);
table_header($th);

foreach ($all_settings as $key => $value) {
    start_row();
    
    label_cell($key);
    
    // Format value
    $formatted_value = $value;
    if (is_bool($value)) {
        $formatted_value = $value ? _("Yes") : _("No");
    } elseif ($value === null) {
        $formatted_value = _("(not set)");
    }
    label_cell($formatted_value);
    
    // Description
    $description = '';
    switch ($key) {
        case 'trans_ref_logging_enabled':
            $description = _("Whether transaction reference logging is active");
            break;
        case 'trans_ref_account':
            $description = _("GL account for logging transaction references");
            break;
        default:
            $description = '';
    }
    label_cell($description);
    
    end_row();
}

end_table(1);
end_outer_table(1);

// ===================================================================
// HELP TEXT
// ===================================================================

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #2196F3;'>";
echo "<h3>" . _("Help") . "</h3>";
echo "<p><strong>" . _("Transaction Reference Logging") . ":</strong> ";
echo _("When enabled, the system logs transaction reference numbers to the specified GL account. ");
echo _("This provides an audit trail for reference number assignments.");
echo "</p>";
echo "<p><strong>" . _("GL Account") . ":</strong> ";
echo _("Select the general ledger account where reference logging entries should be recorded. ");
echo _("Default is '0000'. The account should be set up for this specific purpose.");
echo "</p>";
echo "</div>";

end_page();
?>
