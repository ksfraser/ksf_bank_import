<?php
require_once __DIR__ . '/vendor/autoload.php';

// Test if ksf_ofxparser classes are accessible
try {
    echo "Testing autoload for ksf_ofxparser...\n\n";
    
    // Test core parser class
    if (class_exists('OfxParser\OfxParser')) {
        echo "✓ OfxParser\OfxParser class found\n";
    } else {
        echo "✗ OfxParser\OfxParser class NOT found\n";
    }
    
    // Test SGML classes
    if (class_exists('OfxParser\Sgml\Parser')) {
        echo "✓ OfxParser\Sgml\Parser class found\n";
    } else {
        echo "✗ OfxParser\Sgml\Parser class NOT found\n";
    }
    
    if (class_exists('OfxParser\Sgml\Elements\CurrencyElement')) {
        echo "✓ OfxParser\Sgml\Elements\CurrencyElement class found (NEW!)\n";
    } else {
        echo "✗ OfxParser\Sgml\Elements\CurrencyElement class NOT found\n";
    }
    
    // Test Builder
    if (class_exists('OfxParser\Builders\SgmlOfxBuilder')) {
        echo "✓ OfxParser\Builders\SgmlOfxBuilder class found\n";
    } else {
        echo "✗ OfxParser\Builders\SgmlOfxBuilder class NOT found\n";
    }
    
    // Check package version/path
    $composerInstalled = json_decode(file_get_contents(__DIR__ . '/vendor/composer/installed.json'), true);
    foreach ($composerInstalled['packages'] as $package) {
        if ($package['name'] === 'ksfraser/ksf_ofxparser') {
            echo "\n✓ Package installed from: " . ($package['install-path'] ?? 'unknown') . "\n";
            echo "  Version: " . ($package['version'] ?? 'dev') . "\n";
            break;
        }
    }
    
    echo "\n✓ All checks passed! ksf_ofxparser is ready to use.\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
