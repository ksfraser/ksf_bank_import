<?php
/**
 * Signature compatibility checker.
 *
 * A class that extends or implements another one must declare compatible
 * method signatures. PHP refuses to declare an incompatible class, so the
 * defect only shows up when the class is loaded - which, for the migrated
 * tree, means it only shows up once the class is autoloadable.
 *
 * The checker compares the overridden methods of every class in src/ with the
 * declarations of the class it extends or implements.
 *
 * Usage: php tools/signature_check.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [];
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
    $files[] = $f->getPathname();
}
sort($files);

/**
 * Parse a file: namespace, classes with their parent/interfaces and methods.
 *
 * @return array<string, array>
 */
$parse = static function (string $path): array {
    $tokens = token_get_all((string) file_get_contents($path));
    $count = count($tokens);
    $namespace = '';
    $imports = [];
    $classes = [];
    $current = null;
    $pendingDoc = null;
    $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL, T_VAR];

    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        if ($t[0] === T_DOC_COMMENT) {
            $pendingDoc = $t[1];
            continue;
        }
        if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT) {
            continue;
        }
        if (in_array($t[0], $modifiers, true)) {
            continue;
        }
        if ($t[0] === T_USE && $current === null) {
            // use X\Y\Z;  or  use X\Y\Z as Alias;
            $target = '';
            $alias = '';
            $collecting = true;
            $next = $i + 1;
            while ($next < $count && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                $next++;
            }
            if ($next >= $count || !is_array($tokens[$next]) || $tokens[$next][0] !== T_STRING) {
                $pendingDoc = null;
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                $txt = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                if ($txt === ';' || $txt === '{') {
                    break;
                }
                if ($txt === 'as') {
                    $collecting = false;
                    continue;
                }
                if ($txt === ',' || $txt === '{' || $txt === '}') {
                    continue;
                }
                if ($collecting) {
                    $target .= $txt;
                } else {
                    $alias .= $txt;
                }
            }
            $target = trim($target, "\\ ");
            if ($target !== '' && strpos($target, ' ') === false) {
                $imports[$alias !== '' ? $alias : substr($target, (int) strrpos('\\' . $target, '\\'))] = $target;
            }
            $pendingDoc = null;
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
            $name = null;
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $name = $tokens[$j][1];
                    break;
                }
            }
            if ($name === null) {
                continue;
            }
            // extends / implements
            $parent = null;
            $interfaces = [];
            $mode = null;
            for ($j = $i; $j < $count; $j++) {
                if (!is_array($tokens[$j])) {
                    continue;
                }
                if ($tokens[$j][0] === T_EXTENDS) {
                    $mode = 'extends';
                    continue;
                }
                if ($tokens[$j][0] === T_IMPLEMENTS) {
                    $mode = 'implements';
                    continue;
                }
                if ($tokens[$j][0] === T_STRING && $mode !== null) {
                    $name = $tokens[$j][1];
                    if (isset($imports[$name])) {
                        $fqcn = $imports[$name];
                    } elseif (strpos($name, '\\') === false && $namespace !== '') {
                        $fqcn = $namespace . '\\' . $name;
                    } else {
                        $fqcn = $name;
                    }
                    if ($mode === 'extends') {
                        $parent = $fqcn;
                    } else {
                        $interfaces[] = $fqcn;
                    }
                }
                if ($tokens[$j][0] === '{') {
                    break;
                }
            }
            $current = [
                'fqn' => $namespace === '' ? $name : $namespace . '\\' . $name,
                'parent' => $parent,
                'interfaces' => $interfaces,
                'file' => $path,
                'methods' => [],
            ];
            $classes[$current['fqn']] = $current;
            $currentIndex = $current['fqn'];
            $pendingDoc = null;
            continue;
        }
        if ($t[0] === T_FUNCTION && $current !== null) {
            $probe = $i + 1;
            while ($probe < $count && is_array($tokens[$probe]) && $tokens[$probe][0] === T_WHITESPACE) {
                $probe++;
            }
            if ($probe >= $count || !is_array($tokens[$probe]) || $tokens[$probe][0] !== T_STRING) {
                continue;
            }
            $name = $tokens[$probe]['text'] ?? $tokens[$probe][1];

            // parameters
            $params = 0;
            $required = 0;
            $depth = 0;
            $seenOpen = false;
            for ($j = $probe; $j < $count; $j++) {
                if (!is_array($tokens[$j])) {
                    if ($tokens[$j] === '(') {
                        $depth++;
                        $seenOpen = true;
                        continue;
                    }
                    if ($tokens[$j] === ')') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                        continue;
                    }
                    continue;
                }
                if ($tokens[$j][0] === T_VARIABLE && $seenOpen && $depth === 1) {
                    $params++;
                }
                if ($tokens[$j][0] === T_STRING && $seenOpen && $depth === 1) {
                    // "mixed $x" - the variable follows
                }
            }
            // required parameters: those without a default value
            $body = '';
            for ($j = $probe; $j < $count; $j++) {
                $body .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                if ($body !== '' && substr($body, -1) === ')') {
                    break;
                }
                if (strlen($body) > 2000) {
                    break;
                }
            }
            $signature = substr($body, strpos($body, '(') + 1);
            $signature = substr($signature, 0, strrpos($signature, ')'));
            $parts = [];
            $depth = 0;
            $buf = '';
            foreach (str_split($signature) as $ch) {
                if ($ch === '(' || $ch === '[') {
                    $depth++;
                }
                if ($ch === ')' || $ch === ']') {
                    $depth--;
                }
                if ($ch === ',' && $depth === 0) {
                    $parts[] = $buf;
                    $buf = '';
                    continue;
                }
                $buf .= $ch;
            }
            if (trim($buf) !== '') {
                $parts[] = $buf;
            }
            $required = 0;
            foreach ($parts as $part) {
                if (strpos($part, '=') === false && trim($part) !== '') {
                    $required++;
                }
            }

            // return type
            $returnType = null;
            $after = '';
            $depth = 0;
            for ($j = $probe; $j < $count; $j++) {
                $txt = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                if ($txt === '(') {
                    $depth++;
                }
                if ($txt === ')') {
                    $depth--;
                }
                if ($depth === 0 && $txt === '{') {
                    break;
                }
                $after .= $txt;
            }
            if (preg_match('/\)\s*:\s*([\\\\?A-Za-z0-9_]+)/', $after, $m)) {
                $returnType = $m[1];
            }

            $classes[$currentIndex]['methods'][$name] = [
                'params' => count($parts),
                'required' => $required,
                'return' => $returnType,
            ];
            $pendingDoc = null;
            continue;
        }
        $pendingDoc = null;
    }
    return $classes;
};

$classes = [];
foreach ($files as $file) {
    foreach ($parse($file) as $fqn => $data) {
        $classes[$fqn] = $data;
    }
}

$problems = [];
foreach ($classes as $fqn => $data) {
    $parents = array_filter(array_merge([$data['parent']], $data['interfaces']));
    foreach ($parents as $parent) {
        if (!isset($classes[$parent])) {
            continue; // parent outside this tree
        }
        foreach ($data['methods'] as $method => $sig) {
            if ($method === '__construct') {
                continue; // constructors are exempt
            }
            if (!isset($classes[$parent]['methods'][$method])) {
                continue;
            }
            $psig = $classes[$parent]['methods'][$method];
            if ($psig['return'] !== null && $sig['return'] === null) {
                $problems[] = sprintf(
                    '%s::%s() drops the return type of %s::%s() (%s expected)',
                    $fqn,
                    $method,
                    $parent,
                    $method,
                    $psig['return']
                );
            }
            if ($sig['required'] > $psig['required']) {
                $problems[] = sprintf(
                    '%s::%s() requires %d parameter(s), %s::%s() requires %d',
                    $fqn,
                    $method,
                    $sig['required'],
                    $parent,
                    $method,
                    $psig['required']
                );
            }
            if ($sig['params'] < $psig['params']) {
                $problems[] = sprintf(
                    '%s::%s() declares %d parameter(s), %s::%s() declares %d',
                    $fqn,
                    $method,
                    $sig['params'],
                    $parent,
                    $method,
                    $psig['params']
                );
            }
        }
    }
}

printf("classes: %d   signature problems: %d\n", count($classes), count($problems));
foreach ($problems as $p) {
    echo '  - ' . $p . "\n";
}
exit(count($problems) > 0 ? 1 : 0);
