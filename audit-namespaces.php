<?php
// Namespace and Import Audit Script
$issues = [];

// Scan src/ directory
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/src/', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$srcDir = realpath(__DIR__ . '/src/');
$allClasses = [];

// First pass: collect all class definitions
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $path = $file->getRealPath();
    $relativePath = str_replace($srcDir . '/', '', $path);
    $content = file_get_contents($path);
    
    if (preg_match('/^namespace\s+([^;]+);/m', $content, $nsMatch)) {
        $namespace = $nsMatch[1];
        if (preg_match('/^class\s+(\w+)/m', $content, $clsMatch)) {
            $fullClass = $namespace . '\\' . $clsMatch[1];
            $allClasses[$fullClass] = [
                'file' => $relativePath,
                'namespace' => $namespace,
                'line' => substr_count(substr($content, 0, strpos($content, 'class')), "\n") + 1
            ];
        }
    }
}

// Second pass: check for issues
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/src/', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $path = $file->getRealPath();
    $relativePath = str_replace($srcDir . '/', '', $path);
    $content = file_get_contents($path);
    $lineNum = 1;
    
    if (!preg_match('/^namespace\s+([^;]+);/m', $content, $nsMatch)) {
        continue;
    }
    
    $namespace = $nsMatch[1];
    $lineNum = substr_count(substr($content, 0, strpos($content, 'namespace')), "\n") + 1;
    
    // ISSUE 1: Service/ directory with Service singular namespace
    if (preg_match('~/Service/~', $relativePath) && strpos($namespace, 'Services\\') !== false) {
        $issues[] = [
            'type' => 'NAMESPACE_MISMATCH',
            'severity' => 'HIGH',
            'file' => $relativePath,
            'line' => $lineNum,
            'message' => "File in Service/ uses Services namespace. Autoload maps Services (plural) -> services/ (lowercase)",
            'namespace' => $namespace
        ];
    }
    
    // ISSUE 2: model/ directory (singular) with Model namespace but no autoload
    if (preg_match('~/model/~i', $relativePath) && strpos($namespace, 'Model\\') === false && strpos($namespace, 'Models\\') === false) {
        $issues[] = [
            'type' => 'AUTOLOAD_NOT_FOUND',
            'severity' => 'CRITICAL',
            'file' => $relativePath,
            'line' => $lineNum,
            'message' => "File in model/ directory, but no PSR4 mapping for this namespace",
            'namespace' => $namespace
        ];
    } elseif (preg_match('~/model/~i', $relativePath) && preg_match('/Model\\\\/', $namespace)) {
        $issues[] = [
            'type' => 'AUTOLOAD_MISMATCH',
            'severity' => 'CRITICAL',
            'file' => $relativePath,
            'line' => $lineNum,
            'message' => "File uses Model namespace, but autoload maps Models => models/",
            'namespace' => $namespace
        ];
    }
    
    // ISSUE 3: view/ directory (singular) with View namespace but no autoload
    if (preg_match('~/view/~i', $relativePath) && preg_match('/View\\\\/', $namespace)) {
        $issues[] = [
            'type' => 'AUTOLOAD_MISMATCH',
            'severity' => 'CRITICAL',
            'file' => $relativePath,
            'line' => $lineNum,
            'message' => "File uses View namespace, but autoload maps Views => views/",
            'namespace' => $namespace
        ];
    }
    
    // ISSUE 4: Check for Views namespace prefix without full Ksfraser\FaBankImport prefix
    if (preg_match('/^namespace Views/m', $content)) {
        $issues[] = [
            'type' => 'WRONG_NAMESPACE_PREFIX',
            'severity' => 'CRITICAL',
            'file' => $relativePath,
            'line' => $lineNum,
            'message' => "Namespace is 'Views' but should be 'Ksfraser\\FaBankImport\\Views'",
            'namespace' => $namespace
        ];
    }
    
    // ISSUE 5: Check for DTO namespace not using Shared\DTOs
    if (preg_match('/\bDTO\b/', $namespace) && strpos($namespace, 'Shared\\DTOs') === false && strpos($namespace, 'Shared\\DTO') === false) {
        // Extract imports to check what DTOs are being used
        if (preg_match_all('/use\s+([^;]+?DTO[^;]*);/m', $content, $dtoImports)) {
            foreach ($dtoImports[1] as $import) {
                if (strpos($import, 'Shared\\DTOs') === false && !preg_match('/\\\\DTO\\\\/', $import)) {
                    $issues[] = [
                        'type' => 'WRONG_DTO_IMPORT',
                        'severity' => 'MEDIUM',
                        'file' => $relativePath,
                        'line' => $lineNum,
                        'message' => "Makes reference to DTO but not using Shared\\DTOs namespace",
                        'import' => $import,
                        'namespace' => $namespace
                    ];
                }
            }
        }
    }
    
    // ISSUE 6: Check for undefined parent classes
    if (preg_match('/extends\s+([A-Za-z_][A-Za-z0-9_\\\\]*)/', $content, $exMatch)) {
        $parentClass = $exMatch[1];
        if (!preg_match('/^\\\\/', $parentClass)) { // not fully qualified
            // Try common patterns
            if (class_exists($namespace . '\\' . $parentClass)) {
                $fullParent = $namespace . '\\' . $parentClass;
            } elseif (class_exists('Ksfraser\\FaBankImport\\App\\' . $parentClass)) {
                $fullParent = 'Ksfraser\\FaBankImport\\App\\' . $parentClass;
            } else {
                $fullParent = $parentClass;
            }
        } else {
            $fullParent = $parentClass;
        }
        
        if (!isset($allClasses[$fullParent]) && !class_exists($fullParent)) {
            $issues[] = [
                'type' => 'UNDEFINED_PARENT_CLASS',
                'severity' => 'CRITICAL',
                'file' => $relativePath,
                'line' => $lineNum,
                'message' => "Extends undefined parent class",
                'parent_class' => $parentClass,
                'namespace' => $namespace
            ];
        }
    }
}

// Sort issues by severity
usort($issues, function($a, $b) {
    $severityOrder = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3];
    return ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99);
});

echo json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
