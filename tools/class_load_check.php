<?php
/**
 * Class loadability checker.
 *
 * PHP refuses to declare a class whose signature is incompatible with the
 * class or interface it extends or implements, and it refuses to declare a
 * class whose parent cannot be found. Those defects only show up when the
 * class is loaded - which, for the migrated tree, means they only show up
 * once the class is autoloadable.
 *
 * The checker loads every class of src/ in its own child process (so a fatal
 * error in one class does not hide the others) and reports the ones that
 * cannot be declared.
 *
 * Usage: php tools/class_load_check.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$runner = $root . '/tools/lib/class_load_runner.php';

$classes = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }
    if (strpos($f->getPathname(), '/tests/') !== false) {
        continue;
    }
    $tokens = token_get_all((string) file_get_contents($f->getPathname()));
    $count = count($tokens);
    $namespace = '';
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        if ($t[0] === T_NAMESPACE) {
            $namespace = '';
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                    $namespace .= $tokens[$j][1];
                } elseif (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                } else {
                    break;
                }
            }
            continue;
        }
        if (in_array($t[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], [T_DOUBLE_COLON, T_NEW], true)) {
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $classes[] = $namespace === '' ? $tokens[$j][1] : $namespace . '\\' . $tokens[$j][1];
                    break;
                }
            }
        }
    }
}
$classes = array_values(array_unique($classes));
sort($classes);

$failed = [];
foreach ($classes as $class) {
    $command = sprintf(
        '%s %s %s 2>&1',
        escapeshellarg(PHP_BINARY),
        escapeshellarg($runner),
        escapeshellarg($class)
    );
    $output = [];
    $status = 0;
    exec($command, $output, $status);
    if ($status !== 0) {
        $message = trim(implode(' ', $output));
        $message = preg_replace('/\s+/', ' ', $message);
        $failed[$class] = (string) $message;
    }
}

printf("classes: %d   that cannot be declared: %d\n", count($classes), count($failed));
foreach ($failed as $class => $message) {
    printf("  - %s\n      %s\n", $class, substr($message, 0, 300));
}
exit(count($failed) > 0 ? 1 : 0);
