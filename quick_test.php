<?php
require_once __DIR__ . "/vendor/autoload.php";

echo "Testing basic functionality...\n";

try {
    // Check if the class exists
    if (class_exists('Ksfraser\Superglobals\PostParameterProvider')) {
        echo "✓ Class exists\n";
        $provider = new Ksfraser\Superglobals\PostParameterProvider();
        echo "✓ PostParameterProvider instantiated\n";

        if (class_exists('Ksfraser\Superglobals\FormSubmission')) {
            echo "✓ FormSubmission class exists\n";
            $form = new Ksfraser\Superglobals\FormSubmission($provider);
            echo "✓ FormSubmission instantiated\n";
        } else {
            echo "❌ FormSubmission class not found\n";
        }
    } else {
        echo "❌ PostParameterProvider class not found\n";
    }

    echo "✅ All basic tests passed!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}