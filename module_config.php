<?php
/**
 * Bank Import Module Configuration Management UI
 * 
 * Mantis #2708 Enhancement: Database-backed configuration with UI
 * Allows administrators to modify module settings without editing PHP files
 * 
 * @author Kevin Fraser
 * @since 20241215
 */

$page_security = 'SA_SETUPCOMPANY';  // Admin only
$path_to_root = "../..";

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");

// Load configuration service
require_once __DIR__ . '/src/Ksfraser/FaBankImport/Config/ConfigService.php';
require_once __DIR__ . '/src/Ksfraser/FaBankImport/Repository/ConfigRepositoryInterface.php';
require_once __DIR__ . '/src/Ksfraser/FaBankImport/Repository/DatabaseConfigRepository.php';

use Ksfraser\FaBankImport\Config\ConfigService;

page(_($help_context = "Bank Import Configuration"));

// Include module menu
include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

// Initialize configuration service
$configService = ConfigService::getInstance();

// Handle form submission
if (isset($_POST['save_config'])) {
    $username = $_SESSION['wa_current_user']->username;
    $errors = [];
    $saved_count = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'config_') === 0) {
            $config_key = substr($key, 7);  // Remove 'config_' prefix
            
            try {
                // Get reason if provided
                $reason_key = 'reason_' . str_replace('.', '_', $config_key);
                $reason = isset($_POST[$reason_key]) ? $_POST[$reason_key] : 'Updated via UI';
                
                if ($configService->set($config_key, $value, $username, $reason)) {
                    $saved_count++;
                }
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    if ($saved_count > 0) {
        display_notification("Successfully updated $saved_count configuration setting(s).");
    }
    
    foreach ($errors as $error) {
        display_error($error);
    }
}

// Handle reset to defaults
if (isset($_POST['reset_defaults'])) {
    display_warning("Reset to defaults functionality - coming soon");
}

// Get all configurations grouped by category
$allConfigs = $configService->getAll();

start_form(false, false, $_SERVER['PHP_SELF']);

// Configuration tabs by category
display_heading("Bank Import Module Configuration");

echo "<div style='margin: 20px 0;'>";
echo "<p><strong>Note:</strong> System configurations (marked with 🔒) cannot be modified through this interface.</p>";
echo "</div>";

// Upload Configuration
start_table(TABLESTYLE, "width='90%'");
$th = array("Upload Configuration");
table_header($th);

if (isset($allConfigs['upload'])) {
    foreach ($allConfigs['upload'] as $key => $config) {
        $field_name = 'config_' . $key;
        $label = ucwords(str_replace(['upload.', '_'], ['', ' '], $key));
        
        if ($config['is_system']) {
            $label .= " 🔒";
        }
        
        start_row();
        label_cell("<strong>$label</strong><br><small>{$config['description']}</small>", "class='label' style='width:40%'");
        
        if ($config['is_system']) {
            // Display only, not editable
            label_cell($config['value'], "colspan=2");
        } else {
            // Render appropriate input based on type
            echo "<td style='width:30%'>";
            
            switch ($config['type']) {
                case 'boolean':
                    check_cells(null, $field_name, $config['value']);
                    break;
                    
                case 'integer':
                    text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number'");
                    break;
                    
                case 'string':
                    // Check if it's a select (action types)
                    if ($key === 'upload.duplicate_action') {
                        $options = ['allow' => 'Allow (Silent Skip)', 
                                   'warn' => 'Warn (Ask User)', 
                                   'block' => 'Block (Hard Deny)'];
                        echo array_selector($field_name, $config['value'], $options);
                    } else {
                        text_cells(null, $field_name, $config['value'], 40, 100);
                    }
                    break;
            }
            
            echo "</td>";
            
            // Reason for change
            echo "<td style='width:30%'>";
            $reason_name = 'reason_' . str_replace('.', '_', $key);
            text_cells_ex("Reason:", $reason_name, '', 30, 100, null, null, null, "placeholder='Optional: Why are you changing this?'");
            echo "</td>";
        }
        
        end_row();
    }
}

end_table(1);

// Storage Configuration
start_table(TABLESTYLE, "width='90%'");
$th = array("Storage Configuration");
table_header($th);

if (isset($allConfigs['storage'])) {
    foreach ($allConfigs['storage'] as $key => $config) {
        $field_name = 'config_' . $key;
        $label = ucwords(str_replace(['storage.', '_'], ['', ' '], $key));
        
        start_row();
        label_cell("<strong>$label</strong><br><small>{$config['description']}</small>", "class='label' style='width:40%'");
        
        echo "<td style='width:30%'>";
        
        switch ($config['type']) {
            case 'boolean':
                check_cells(null, $field_name, $config['value']);
                break;
            case 'integer':
                text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number'");
                break;
            default:
                text_cells(null, $field_name, $config['value'], 40, 100);
        }
        
        echo "</td>";
        
        echo "<td style='width:30%'>";
        $reason_name = 'reason_' . str_replace('.', '_', $key);
        text_cells_ex("Reason:", $reason_name, '', 30, 100, null, null, null, "placeholder='Optional'");
        echo "</td>";
        
        end_row();
    }
}

end_table(1);

// Logging Configuration
start_table(TABLESTYLE, "width='90%'");
$th = array("Logging Configuration");
table_header($th);

if (isset($allConfigs['logging'])) {
    foreach ($allConfigs['logging'] as $key => $config) {
        $field_name = 'config_' . $key;
        $label = ucwords(str_replace(['logging.', '_'], ['', ' '], $key));
        
        start_row();
        label_cell("<strong>$label</strong><br><small>{$config['description']}</small>", "class='label' style='width:40%'");
        
        echo "<td style='width:30%'>";
        
        switch ($config['type']) {
            case 'boolean':
                check_cells(null, $field_name, $config['value']);
                break;
            case 'integer':
                text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number'");
                break;
            default:
                if ($key === 'logging.level') {
                    $levels = ['debug' => 'Debug', 'info' => 'Info', 'warning' => 'Warning', 'error' => 'Error'];
                    echo array_selector($field_name, $config['value'], $levels);
                } else {
                    text_cells(null, $field_name, $config['value'], 40, 100);
                }
        }
        
        echo "</td>";
        
        echo "<td style='width:30%'>";
        $reason_name = 'reason_' . str_replace('.', '_', $key);
        text_cells_ex("Reason:", $reason_name, '', 30, 100, null, null, null, "placeholder='Optional'");
        echo "</td>";
        
        end_row();
    }
}

end_table(1);

// Performance Configuration
start_table(TABLESTYLE, "width='90%'");
$th = array("Performance Configuration");
table_header($th);

if (isset($allConfigs['performance'])) {
    foreach ($allConfigs['performance'] as $key => $config) {
        $field_name = 'config_' . $key;
        $label = ucwords(str_replace(['performance.', '_'], ['', ' '], $key));
        
        start_row();
        label_cell("<strong>$label</strong><br><small>{$config['description']}</small>", "class='label' style='width:40%'");
        
        echo "<td style='width:30%'>";
        
        switch ($config['type']) {
            case 'integer':
                text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number'");
                break;
            default:
                text_cells(null, $field_name, $config['value'], 20, 50);
        }
        
        echo "</td>";
        
        echo "<td style='width:30%'>";
        $reason_name = 'reason_' . str_replace('.', '_', $key);
        text_cells_ex("Reason:", $reason_name, '', 30, 100, null, null, null, "placeholder='Optional'");
        echo "</td>";
        
        end_row();
    }
}

end_table(1);

// Pattern Matching Configuration
start_table(TABLESTYLE, "width='90%'");
$th = array("Pattern Matching Configuration");
table_header($th);

if (isset($allConfigs['pattern_matching'])) {
    foreach ($allConfigs['pattern_matching'] as $key => $config) {
        $field_name = 'config_' . $key;
        $label = ucwords(str_replace(['pattern_matching.', '_'], ['', ' '], $key));
        
        start_row();
        label_cell("<strong>$label</strong><br><small>{$config['description']}</small>", "class='label' style='width:40%'");
        
        echo "<td style='width:30%'>";
        
        switch ($config['type']) {
            case 'float':
                text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number' step='0.1' min='0' max='1'");
                break;
            case 'integer':
                text_cells(null, $field_name, $config['value'], 10, 10, null, null, null, "type='number'");
                break;
            default:
                text_cells(null, $field_name, $config['value'], 20, 50);
        }
        
        echo "</td>";
        
        echo "<td style='width:30%'>";
        $reason_name = 'reason_' . str_replace('.', '_', $key);
        text_cells_ex("Reason:", $reason_name, '', 30, 100, null, null, null, "placeholder='Optional'");
        echo "</td>";
        
        end_row();
    }
}

end_table(1);

// Security Configuration (Read-only)
start_table(TABLESTYLE, "width='90%'");
$th = array("Security Configuration (System Only)");
table_header($th);

if (isset($allConfigs['security'])) {
    foreach ($allConfigs['security'] as $key => $config) {
        $label = ucwords(str_replace(['security.', '_'], ['', ' '], $key));
        
        label_row("<strong>$label 🔒</strong><br><small>{$config['description']}</small>", 
                 $config['type'] === 'boolean' ? ($config['value'] ? 'Yes' : 'No') : $config['value'],
                 "class='label' style='width:40%'");
    }
}

end_table(1);

// Action buttons
div_start('controls');
echo "<div style='margin: 20px 0;'>";
submit_center_first('save_config', _("Save Configuration"), '', 'default', false);
submit_center_last('cancel', _("Cancel"), '', 'cancel', false);
echo "</div>";
div_end();

end_form();

// Configuration History
br(2);
display_heading("Recent Configuration Changes");

$history = $configService->getHistory(null, 20);

if (!empty($history)) {
    start_table(TABLESTYLE, "width='90%'");
    $th = array("Date/Time", "Configuration Key", "Old Value", "New Value", "Changed By", "Reason");
    table_header($th);
    
    foreach ($history as $record) {
        start_row();
        label_cell($record['changed_at']);
        label_cell($record['config_key']);
        label_cell($record['old_value']);
        label_cell($record['new_value']);
        label_cell($record['changed_by']);
        label_cell($record['change_reason'] ?: '-');
        end_row();
    }
    
    end_table(1);
} else {
    display_note("No configuration changes recorded yet.");
}

end_page();
?>
