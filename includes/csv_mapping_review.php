<?php

/**
 * CSV Field Mapping Review Screen
 * 
 * Interactive UI for reviewing and adjusting CSV field mappings.
 * Displays suggested mappings and allows user to confirm, adjust, or create new mappings.
 * Saves approved mappings as templates for future use.
 * 
 * @author Kevin Fraser / GitHub Copilot
 * @since 20260112
 * @version 1.0.0
 */

require_once(__DIR__ . '/CsvFieldMapper.php');
require_once(__DIR__ . '/CsvMappingTemplate.php');

/**
 * Display mapping review screen
 * 
 * @param string $bankName Bank identifier
 * @param array $csvHeaders CSV headers from uploaded file
 * @param array $sampleData Sample rows for validation
 * @param array $existingMapping Optional existing mapping to edit
 */
function display_csv_mapping_review($bankName, $csvHeaders, $sampleData = [], $existingMapping = null) {
    $mapper = new CsvFieldMapper();
    $templateMgr = new CsvMappingTemplate();
    
    // If no existing mapping provided, generate suggestions
    if ($existingMapping === null) {
        $suggestedMapping = $mapper->suggestMapping($csvHeaders, $sampleData);
        $isNewMapping = true;
    } else {
        $suggestedMapping = $existingMapping;
        $isNewMapping = false;
    }
    
    // Evaluate mapping quality
    $evaluation = $mapper->evaluateMapping($suggestedMapping);
    $fieldDefs = $mapper->getFieldDefinitions();
    
    // Display HTML
    ?>
    <div class="csv-mapping-review">
        <h2>CSV Field Mapping Review</h2>
        <p><strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?></p>
        <p><strong>CSV Headers Found:</strong> <?php echo count($csvHeaders); ?></p>
        
        <?php if ($isNewMapping): ?>
            <div class="mapping-status <?php echo $evaluation['quality']; ?>">
                <h3>Auto-Detected Mapping Quality: <?php echo ucfirst($evaluation['quality']); ?> (<?php echo $evaluation['score']; ?>%)</h3>
                <?php if (!empty($evaluation['missing_required'])): ?>
                    <div class="warning">
                        <strong>Missing Required Fields:</strong>
                        <?php echo implode(', ', $evaluation['missing_required']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="info">
                <p>Editing existing template mapping</p>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="save_csv_mapping.php" id="mapping-form">
            <input type="hidden" name="bank_name" value="<?php echo htmlspecialchars($bankName); ?>">
            <input type="hidden" name="csv_headers" value="<?php echo htmlspecialchars(json_encode($csvHeaders)); ?>">
            <input type="hidden" name="sample_data" value="<?php echo htmlspecialchars(json_encode($sampleData)); ?>">
            
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
                <button type="button" onclick="history.back();" class="btn-cancel">
                    Cancel
                </button>
            </div>
        </form>
    </div>
    
    <style>
        .csv-mapping-review {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        
        .btn-cancel {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-cancel:hover {
            background: #e2e6ea;
        }
        
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .info {
            color: #0c5460;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
    
    <script>
        // Real-time validation
        document.querySelectorAll('.mapping-select').forEach(function(select) {
            select.addEventListener('change', function() {
                updateMappingStatus();
            });
        });
        
        function updateMappingStatus() {
            // Count mapped required fields
            const requiredFields = <?php echo json_encode(array_keys(array_filter($fieldDefs, function($def) { return $def['required']; }))); ?>;
            let mappedCount = 0;
            
            requiredFields.forEach(function(field) {
                const select = document.querySelector(`select[name="mapping[${field}]"]`);
                if (select && select.value !== '') {
                    mappedCount++;
                }
            });
            
            // Update status display
            console.log(`Mapped ${mappedCount} of ${requiredFields.length} required fields`);
        }
    </script>
    <?php
}

/**
 * Process saved mapping from review form
 * 
 * @param array $postData POST data from form
 * @return array ['success' => bool, 'message' => string, 'mapping' => array]
 */
function process_csv_mapping_save($postData) {
    $bankName = $postData['bank_name'] ?? '';
    $csvHeaders = json_decode($postData['csv_headers'] ?? '[]', true);
    $mapping = $postData['mapping'] ?? [];
    $action = $postData['action'] ?? 'save_only';
    $saveAsTemplate = isset($postData['save_as_template']);
    $templateName = $postData['template_name'] ?? $bankName;
    $templateDesc = $postData['template_description'] ?? '';
    
    // Filter out empty mappings
    $cleanMapping = [];
    foreach ($mapping as $ourField => $csvHeader) {
        if (!empty($csvHeader)) {
            $cleanMapping[$csvHeader] = $ourField;
        }
    }
    
    // Validate required fields
    $mapper = new CsvFieldMapper();
    $evaluation = $mapper->evaluateMapping($cleanMapping);
    
    if (!empty($evaluation['missing_required'])) {
        return [
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $evaluation['missing_required']),
            'mapping' => $cleanMapping
        ];
    }
    
    // Save template if requested
    if ($saveAsTemplate) {
        $templateMgr = new CsvMappingTemplate();
        $metadata = [
            'description' => $templateDesc,
            'created_by' => $_SESSION['user'] ?? 'system',
            'mapping_quality' => $evaluation['quality']
        ];
        
        $saved = $templateMgr->saveTemplate($templateName, $csvHeaders, $cleanMapping, $metadata);
        
        if (!$saved) {
            return [
                'success' => false,
                'message' => 'Failed to save template',
                'mapping' => $cleanMapping
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => $saveAsTemplate ? 'Template saved successfully' : 'Mapping validated',
        'mapping' => $cleanMapping,
        'action' => $action
    ];
}
