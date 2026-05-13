<?php
/**
 * Remove legacy require_once statements for HTML classes.
 * These are now handled by the PSR-4 autoloader (ksfraser/html library).
 */

$dirs = [
    __DIR__ . '/src',
    __DIR__ . '/views',
    __DIR__ . '/tests',
];

$pattern = '/^require_once\s*\(?\s*__DIR__\s*\.\s*[\'"][^\'"]*?\/HTML\/[^\n]+\n?/m';

$count = 0;
foreach ($dirs as $dir) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $new = preg_replace($pattern, '', $content);
        if ($new !== $content) {
            file_put_contents($path, $new);
            echo "Cleaned: " . basename($path) . " ($path)\n";
            $count++;
        }
    }
}

echo "\nTotal files cleaned: $count\n";
