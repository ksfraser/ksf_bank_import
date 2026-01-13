<?php
/**
 * Test CSV Mapping Review UI
 * 
 * Standalone page to test the field mapping review screen
 * 
 * Usage:
 *   1. Start PHP server: php -S localhost:8000
 *   2. Open browser: http://localhost:8000/test_mapping_ui.php
 *   3. Select a CSV file or use default Manulife file
 */

// Load required classes
require_once(__DIR__ . '/includes/CsvFieldMapper.php');
require_once(__DIR__ . '/includes/CsvMappingTemplate.php');

// Simple statement/transaction classes for testing
if (!class_exists('statement')) {
    class statement {
        public $bank;
        public $account;
        public $currency;
        public $timestamp;
        public $startBalance;
        public $endBalance;
        public $number;
        public $sequence;
        public $statementId;
        public $transactions = [];
        
        public function addTransaction($transaction) {
            $this->transactions[] = $transaction;
        }
    }
}

if (!class_exists('transaction')) {
    class transaction {
        public $valueTimestamp;
        public $datePosted;
        public $amount;
        public $memo;
        public $name;
        public $checkNumber;
        public $transactionType;
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_action'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/qfx_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = basename($_FILES['csv_file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $targetPath)) {
            $bankName = $_POST['bank'] ?? 'test_bank';
            $csvFile = $targetPath;
            $successMsg = "File uploaded successfully: $fileName";
        } else {
            $errorMsg = "Failed to upload file.";
        }
    } else {
        $errorMsg = "No file uploaded or upload error.";
    }
}

// Handle form submission (save mapping)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bankName = $_POST['bank_name'] ?? '';
    $csvHeaders = json_decode($_POST['csv_headers'] ?? '[]', true);
    $mapping = $_POST['mapping'] ?? [];
    $action = $_POST['action'];
    
    // Filter out empty mappings
    $cleanMapping = [];
    foreach ($mapping as $ourField => $csvHeader) {
        if (!empty($csvHeader)) {
            $cleanMapping[$csvHeader] = $ourField;
        }
    }
    
    // Save template if requested
    if (isset($_POST['save_as_template'])) {
        $templateMgr = new CsvMappingTemplate();
        $templateName = $_POST['template_name'] ?? $bankName;
        $templateDesc = $_POST['template_description'] ?? '';
        
        $metadata = [
            'description' => $templateDesc,
            'created_by' => 'test_user',
        ];
        
        $saved = $templateMgr->saveTemplate($templateName, $csvHeaders, $cleanMapping, $metadata);
        
        if ($saved) {
            $successMsg = "Template saved successfully! Template file: csv_mapping_{$templateName}.json";
        } else {
            $errorMsg = "Failed to save template.";
        }
    }
    
    if ($action === 'save_and_proceed') {
        $successMsg = ($successMsg ?? '') . " Would now proceed with import using this mapping.";
    }
}

// Get CSV file to analyze
$csvFile = null;
$bankName = 'test_bank';

if (isset($_GET['file'])) {
    $csvFile = __DIR__ . '/' . $_GET['file'];
    if (basename(dirname($csvFile)) === 'qfx_files') {
        $bankName = 'manulife';
    }
} else {
    // Default to Manulife file if it exists
    $defaultFile = __DIR__ . '/qfx_files/20260112_1518404_transactions.csv';
    if (file_exists($defaultFile)) {
        $csvFile = $defaultFile;
        $bankName = 'manulife';
    }
}

// Allow bank name override
if (isset($_GET['bank'])) {
    $bankName = $_GET['bank'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Mapping Review UI Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-selector {
            background: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-selector input[type="file"] {
            margin-right: 10px;
            padding: 5px;
        }
        .file-selector select {
            padding: 5px 10px;
            margin-right: 10px;
        }
        .file-selector input[type="text"] {
            padding: 5px;
        }
        .file-selector button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .file-selector button:hover {
            background: #0056b3;
        }
        .file-selector hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
        .file-selector form {
            margin: 15px 0;
        }
        .file-selector label {
            display: block;
            margin: 10px 0;
            font-weight: 500;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin-top: 0;
        }
        .quick-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .quick-links a {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .quick-links a:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CSV Mapping Review UI - Test Page</h1>
            <p>This page allows you to test the CSV field mapping review interface.</p>
        </div>
        
        <?php if (isset($successMsg)): ?>
            <div class="message success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMsg)): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        
        <div class="file-selector">
            <h2>Select CSV File to Analyze</h2>
            
            <div class="quick-links">
                <a href="?file=qfx_files/20260112_1518404_transactions.csv&bank=manulife">Manulife CSV</a>
                <a href="?">Clear Selection</a>
            </div>
            
            <br>
            
            <form method="POST" enctype="multipart/form-data" id="file-upload-form">
                <label>
                    Bank Name:
                    <select name="bank" required>
                        <option value="">-- Select Bank --</option>
                        <?php
                        // Mock bank list (in real app, this would come from FA)
                        $banks = [
                            'manulife' => 'Manulife Bank',
                            'cibc' => 'CIBC',
                            'td' => 'TD Bank',
                            'bmo' => 'BMO',
                            'scotiabank' => 'Scotiabank',
                            'rbc' => 'RBC',
                            'wmmc' => 'Walmart Mastercard',
                            'pcfinancial' => 'PC Financial',
                            'tangerine' => 'Tangerine',
                            'other' => 'Other Bank'
                        ];
                        foreach ($banks as $value => $label) {
                            $selected = ($value === $bankName) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>\n";
                        }
                        ?>
                    </select>
                </label>
                <br><br>
                <label>
                    Upload CSV File:
                    <input type="file" name="csv_file" accept=".csv,.txt" required>
                </label>
                <button type="submit" name="upload_action" value="analyze">Analyze File</button>
            </form>
            
            <hr style="margin: 20px 0;">
            
            <form method="GET">
                <p><strong>Or use existing file:</strong></p>
                <label>
                    CSV File Path (relative to root):
                    <input type="text" name="file" value="<?php echo isset($_GET['file']) ? htmlspecialchars($_GET['file']) : ''; ?>" size="50" placeholder="e.g., qfx_files/myfile.csv">
                </label>
                <label>
                    Bank:
                    <select name="bank">
                        <option value="">-- Select Bank --</option>
                        <?php
                        foreach ($banks as $value => $label) {
                            $selected = ($value === $bankName) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>\n";
                        }
                        ?>
                    </select>
                </label>
                <button type="submit">Load File</button>
            </form>
        </div>
        
        <?php if ($csvFile && file_exists($csvFile)): ?>
            
            <div class="info-box">
                <h3>File Information</h3>
                <p><strong>File:</strong> <?php echo htmlspecialchars(str_replace(__DIR__ . '/', '', $csvFile)); ?></p>
                <p><strong>Size:</strong> <?php echo number_format(filesize($csvFile)); ?> bytes</p>
                <p><strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?></p>
            </div>
            
            <?php
            // Parse CSV
            $content = file_get_contents($csvFile);
            $lines = explode("\n", $content);
            
            // Try to detect header
            $firstLine = trim($lines[0]);
            // Remove UTF-8 BOM if present
            $firstLine = str_replace("\xEF\xBB\xBF", '', $firstLine);
            $firstFields = str_getcsv($firstLine);
            
            // Better header detection logic
            $hasHeader = true;
            
            // Check if first line looks like headers (text-based, no numeric-only fields)
            $likelyHeader = false;
            $numTextFields = 0;
            $numNumericFields = 0;
            
            foreach (array_slice($firstFields, 0, min(6, count($firstFields))) as $field) {
                $field = trim($field);
                if (empty($field)) continue;
                
                // Check if field looks like a header name
                if (preg_match('/^[A-Za-z]/', $field) && strlen($field) > 2) {
                    $numTextFields++;
                } elseif (is_numeric(str_replace(['$', ',', '.', '-', '+'], '', $field))) {
                    $numNumericFields++;
                }
            }
            
            // If most fields are text-based and look like labels, it's a header
            if ($numTextFields >= 3) {
                $hasHeader = true;
                $likelyHeader = true;
            }
            
            // Special case: Manulife format detection
            // Only treat as headerless if it looks exactly like Manulife format:
            // - 4 columns
            // - First column starts with "Advantage Account" or similar
            // - Second column is a date (MM/DD/YYYY)
            if (count($firstFields) == 4 && 
                preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $firstFields[1] ?? '') &&
                (preg_match('/Account \d+/', $firstFields[0] ?? '') || 
                 preg_match('/^[\-\+]?\$?\d+[\d,]*\.?\d*$/', $firstFields[2] ?? ''))) {
                $hasHeader = false;
                $likelyHeader = false;
            }
            
            if ($hasHeader) {
                $csvHeaders = $firstFields;
                $dataStart = 1;
            } else {
                // Manulife headerless format (only for 4-column files)
                $csvHeaders = ['Account', 'Date', 'Amount', 'Description'];
                $dataStart = 0;
            }
            
            // Clean CSV headers (remove BOM and whitespace)
            $csvHeaders = array_map('trim', $csvHeaders);
            
            // Get sample data (first 5 rows)
            $sampleData = [];
            for ($i = $dataStart; $i < min($dataStart + 5, count($lines)); $i++) {
                if (empty(trim($lines[$i]))) continue;
                $fields = str_getcsv($lines[$i]);
                if (count($fields) === count($csvHeaders)) {
                    $sampleData[] = array_combine($csvHeaders, $fields);
                }
            }
            
            // Generate mapping suggestions
            $mapper = new CsvFieldMapper();
            $suggestedMapping = $mapper->suggestMapping($csvHeaders, $sampleData);
            $evaluation = $mapper->evaluateMapping($suggestedMapping);
            $fieldDefs = $mapper->getFieldDefinitions();
            
            // Check for existing template
            $templateMgr = new CsvMappingTemplate();
            $existingTemplate = $templateMgr->findMatchingTemplate($csvHeaders, $bankName);
            ?>
            
            <div class="info-box">
                <h3>CSV Headers Detected</h3>
                <p><strong>Columns:</strong> <?php echo count($csvHeaders); ?></p>
                <p><strong>Headers:</strong> <?php echo implode(', ', array_map('htmlspecialchars', $csvHeaders)); ?></p>
                <p><strong>Has Header Row:</strong> <?php echo $hasHeader ? 'Yes' : 'No (using default headers)'; ?></p>
                <p><strong>Sample Rows:</strong> <?php echo count($sampleData); ?></p>
            </div>
            
            <?php if ($existingTemplate): ?>
                <div class="message success">
                    <strong>Existing Template Found!</strong><br>
                    Template: <?php echo htmlspecialchars($existingTemplate['bank_name']); ?><br>
                    Created: <?php echo htmlspecialchars($existingTemplate['created']); ?><br>
                    <a href="?file=<?php echo urlencode($_GET['file'] ?? ''); ?>&bank=<?php echo urlencode($bankName); ?>&ignore_template=1" style="color: #155724;">View Suggestions Instead</a>
                </div>
                
                <?php if (!isset($_GET['ignore_template'])): ?>
                    <?php $suggestedMapping = $existingTemplate['mapping']; ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Display the mapping review UI -->
            <?php
            // Inline the review UI here
            ?>
            <div class="csv-mapping-review">
                <h2>CSV Field Mapping Review</h2>
                <p><strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?></p>
                <p><strong>CSV Headers Found:</strong> <?php echo count($csvHeaders); ?></p>
                
                <div class="mapping-status <?php echo $evaluation['quality']; ?>">
                    <h3>Mapping Quality: <?php echo ucfirst($evaluation['quality']); ?> (<?php echo $evaluation['score']; ?>%)</h3>
                    <?php if (!empty($evaluation['missing_required'])): ?>
                        <div class="warning">
                            <strong>Missing Required Fields:</strong>
                            <?php echo implode(', ', $evaluation['missing_required']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" id="mapping-form">
                    <input type="hidden" name="bank_name" value="<?php echo htmlspecialchars($bankName); ?>">
                    <input type="hidden" name="csv_headers" value="<?php echo htmlspecialchars(json_encode($csvHeaders)); ?>">
                    
                    <table class="mapping-table">
                        <thead>
                            <tr>
                                <th>Our Field</th>
                                <th>Required</th>
                                <th>CSV Column</th>
                                <th>Sample Data</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fieldDefs as $fieldName => $fieldDef): ?>
                                <?php
                                // Find which CSV header is currently mapped to this field
                                $mappedHeader = null;
                                foreach ($suggestedMapping as $csvHeader => $mappedField) {
                                    if ($mappedField === $fieldName) {
                                        $mappedHeader = $csvHeader;
                                        break;
                                    }
                                }
                                
                                // Get sample data for this mapped header
                                $sampleValue = '';
                                if ($mappedHeader !== null && !empty($sampleData)) {
                                    $sampleValues = [];
                                    foreach (array_slice($sampleData, 0, 3) as $row) {
                                        if (isset($row[$mappedHeader])) {
                                            $sampleValues[] = $row[$mappedHeader];
                                        }
                                    }
                                    $sampleValue = implode(' | ', $sampleValues);
                                }
                                
                                $isMapped = $mappedHeader !== null;
                                $rowClass = '';
                                if ($fieldDef['required'] && !$isMapped) {
                                    $rowClass = 'missing-required';
                                } elseif ($isMapped) {
                                    $rowClass = 'mapped';
                                }
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($fieldName); ?></strong>
                                        <br>
                                        <small><?php echo implode(', ', array_slice($fieldDef['synonyms'], 0, 3)); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($fieldDef['required']): ?>
                                            <span class="required-badge">Required</span>
                                        <?php else: ?>
                                            <span class="optional-badge">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="mapping[<?php echo htmlspecialchars($fieldName); ?>]" class="mapping-select">
                                            <option value="">-- Not Mapped --</option>
                                            <?php foreach ($csvHeaders as $csvHeader): ?>
                                                <option value="<?php echo htmlspecialchars($csvHeader); ?>"
                                                        <?php echo ($csvHeader === $mappedHeader) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($csvHeader); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="sample-data">
                                        <small><?php echo htmlspecialchars(substr($sampleValue, 0, 100)); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($isMapped): ?>
                                            <span class="status-ok">✓ Mapped</span>
                                        <?php elseif ($fieldDef['required']): ?>
                                            <span class="status-error">✗ Missing</span>
                                        <?php else: ?>
                                            <span class="status-na">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="unmapped-columns">
                        <h3>Unmapped CSV Columns</h3>
                        <?php
                        $unmappedHeaders = array_diff($csvHeaders, array_keys($suggestedMapping));
                        if (empty($unmappedHeaders)): ?>
                            <p><em>All CSV columns have been mapped.</em></p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($unmappedHeaders as $header): ?>
                                    <li><?php echo htmlspecialchars($header); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p><small>These columns will be ignored during import.</small></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="template-options">
                        <h3>Template Options</h3>
                        <label>
                            <input type="checkbox" name="save_as_template" value="1" checked>
                            Save this mapping as a template for future imports
                        </label>
                        <br>
                        <label>
                            Template Name:
                            <input type="text" name="template_name" 
                                   value="<?php echo htmlspecialchars($bankName); ?>" 
                                   placeholder="e.g., manulife, cibc_checking">
                        </label>
                        <br>
                        <label>
                            Description (optional):
                            <input type="text" name="template_description" 
                                   placeholder="e.g., Manulife Advantage Account CSV format">
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="action" value="save_and_proceed" class="btn-primary">
                            Save Mapping &amp; Proceed with Import
                        </button>
                        <button type="submit" name="action" value="save_only" class="btn-secondary">
                            Save Template Only
                        </button>
                    </div>
                </form>
            </div>
            
            <style>
                .csv-mapping-review {
                    background: #fff;
                    padding: 20px;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .mapping-status {
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 4px;
                }
                
                .mapping-status.excellent {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                }
                
                .mapping-status.good {
                    background: #d1ecf1;
                    border: 1px solid #bee5eb;
                    color: #0c5460;
                }
                
                .mapping-status.fair {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    color: #856404;
                }
                
                .mapping-status.poor {
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                }
                
                .mapping-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                
                .mapping-table th,
                .mapping-table td {
                    padding: 10px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                
                .mapping-table th {
                    background: #f8f9fa;
                    font-weight: bold;
                }
                
                .mapping-table tr.missing-required {
                    background: #fff5f5;
                }
                
                .mapping-table tr.mapped {
                    background: #f0fff0;
                }
                
                .mapping-select {
                    width: 100%;
                    padding: 5px;
                }
                
                .sample-data {
                    font-family: monospace;
                    font-size: 0.9em;
                    color: #666;
                }
                
                .required-badge {
                    background: #dc3545;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 0.85em;
                }
                
                .optional-badge {
                    background: #6c757d;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 0.85em;
                }
                
                .status-ok {
                    color: #28a745;
                    font-weight: bold;
                }
                
                .status-error {
                    color: #dc3545;
                    font-weight: bold;
                }
                
                .status-na {
                    color: #6c757d;
                }
                
                .unmapped-columns {
                    margin: 20px 0;
                    padding: 15px;
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                }
                
                .template-options {
                    margin: 20px 0;
                    padding: 15px;
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    border-radius: 4px;
                }
                
                .template-options label {
                    display: block;
                    margin: 10px 0;
                }
                
                .template-options input[type="text"] {
                    width: 300px;
                    padding: 5px;
                    margin-left: 10px;
                }
                
                .form-actions {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 2px solid #dee2e6;
                    text-align: right;
                }
                
                .form-actions button {
                    padding: 10px 20px;
                    margin-left: 10px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                }
                
                .btn-primary {
                    background: #007bff;
                    color: white;
                }
                
                .btn-primary:hover {
                    background: #0056b3;
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn-secondary:hover {
                    background: #545b62;
                }
                
                .warning {
                    color: #856404;
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    padding: 10px;
                    margin: 10px 0;
                    border-radius: 4px;
                }
            </style>
            
        <?php elseif ($csvFile): ?>
            <div class="message error">
                <strong>File not found:</strong> <?php echo htmlspecialchars($csvFile); ?>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>Getting Started</h3>
                <p>Select a CSV file above to test the mapping review interface.</p>
                <p>The system will:</p>
                <ol>
                    <li>Analyze the CSV headers</li>
                    <li>Check for existing templates</li>
                    <li>Suggest field mappings</li>
                    <li>Display the review interface</li>
                    <li>Allow you to save the mapping as a template</li>
                </ol>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
