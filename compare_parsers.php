#!/usr/bin/env php
<?php
/**
 * OFX Parser Comparison Tool
 * 
 * Compares the three OFX parser implementations to identify differences
 * and test compatibility with production QFX files.
 * 
 * Usage: php compare_parsers.php [test_file.ofx]
 * 
 * @since 2026-01-12
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== OFX Parser Comparison Tool ===\n\n";

// Paths to the three Parser.php implementations
$parsers = [
    'includes/vendor' => __DIR__ . '/includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser.php',
    'vendor' => __DIR__ . '/vendor/asgrim/ofxparser/lib/OfxParser/Parser.php',
    'ksf_fork' => __DIR__ . '/lib/ksf_ofxparser/src/Ksfraser/Parser.php',
    'includes/modified' => __DIR__ . '/includes/vendor/asgrim/ofxparser/lib/OfxParser/Parser_orig-mod.php'
];

echo "Checking parser files...\n";
foreach ($parsers as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $md5 = md5_file($path);
        echo "  ✓ $name: " . number_format($size) . " bytes (MD5: " . substr($md5, 0, 8) . "...)\n";
    } else {
        echo "  ✗ $name: NOT FOUND\n";
        unset($parsers[$name]);
    }
}
echo "\n";

// Function-level comparison
echo "=== Function-Level Comparison ===\n\n";

function extractFunctions($file) {
    $content = file_get_contents($file);
    preg_match_all('/(?:public|private|protected)\s+function\s+(\w+)\s*\(/i', $content, $matches);
    return $matches[1];
}

$all_functions = [];
foreach ($parsers as $name => $path) {
    $functions = extractFunctions($path);
    $all_functions[$name] = $functions;
    echo "$name:\n";
    foreach ($functions as $func) {
        echo "  - $func()\n";
    }
    echo "\n";
}

// Find differences
echo "=== Differences ===\n\n";

$base_funcs = $all_functions['includes/vendor'] ?? [];
foreach ($all_functions as $name => $funcs) {
    if ($name === 'includes/vendor') continue;
    
    $added = array_diff($funcs, $base_funcs);
    $removed = array_diff($base_funcs, $funcs);
    
    if (!empty($added) || !empty($removed)) {
        echo "$name vs includes/vendor:\n";
        if (!empty($added)) {
            echo "  Added functions: " . implode(', ', $added) . "\n";
        }
        if (!empty($removed)) {
            echo "  Removed functions: " . implode(', ', $removed) . "\n";
        }
    } else {
        echo "$name: Same functions as includes/vendor\n";
    }
    echo "\n";
}

// Compare specific methods
echo "=== Method Implementation Comparison ===\n\n";

function extractMethod($file, $method) {
    $content = file_get_contents($file);
    $pattern = '/(?:public|private|protected)\s+function\s+' . preg_quote($method) . '\s*\([^)]*\)\s*\{/i';
    if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
        $start = $match[0][1];
        // Find matching closing brace
        $brace_count = 0;
        $in_function = false;
        $end = $start;
        for ($i = $start; $i < strlen($content); $i++) {
            if ($content[$i] === '{') {
                $brace_count++;
                $in_function = true;
            } elseif ($content[$i] === '}') {
                $brace_count--;
                if ($in_function && $brace_count === 0) {
                    $end = $i + 1;
                    break;
                }
            }
        }
        return substr($content, $start, $end - $start);
    }
    return null;
}

$methods_to_compare = ['convertSgmlToXml', 'closeUnclosedXmlTags', 'conditionallyAddNewlines'];

foreach ($methods_to_compare as $method) {
    echo "Method: $method()\n";
    $implementations = [];
    foreach ($parsers as $name => $path) {
        $impl = extractMethod($path, $method);
        if ($impl) {
            $md5 = md5($impl);
            $lines = substr_count($impl, "\n") + 1;
            $implementations[$name] = ['md5' => $md5, 'lines' => $lines];
        }
    }
    
    // Group by MD5
    $groups = [];
    foreach ($implementations as $name => $data) {
        $groups[$data['md5']][] = $name;
    }
    
    if (count($groups) === 1) {
        echo "  ✓ All implementations are IDENTICAL\n";
    } else {
        echo "  ✗ Found " . count($groups) . " DIFFERENT implementations:\n";
        $group_num = 1;
        foreach ($groups as $md5 => $names) {
            echo "    Group $group_num (" . $implementations[$names[0]]['lines'] . " lines): " . implode(', ', $names) . "\n";
            $group_num++;
        }
    }
    echo "\n";
}

// Test file parsing (if provided)
if ($argc > 1 && file_exists($argv[1])) {
    echo "=== Testing with file: {$argv[1]} ===\n\n";
    
    // This would require loading each parser separately and testing
    // Left as TODO since it requires careful autoloader management
    echo "TODO: Implement actual parsing test with different versions\n";
} else {
    echo "Tip: Run with a test file to compare parsing results:\n";
    echo "  php compare_parsers.php includes/test.qfx\n";
}

echo "\n=== Comparison Complete ===\n";
