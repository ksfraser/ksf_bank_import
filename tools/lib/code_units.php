<?php
/**
 * Shared code-unit scanner.
 *
 * Returns the production code units (classes, methods, functions) of a file
 * together with their docblock, so the traceability audit and the @BABOK
 * injector agree on what a "unit" is.
 *
 * A unit is:
 *  - a class / interface / trait (anonymous classes excluded)
 *  - a method of a class
 *  - a named function (closures excluded)
 *
 * Each unit carries:
 *  kind    class|method|function
 *  fqn     fully-qualified name (FQCN::method for methods)
 *  owner   declaring class FQCN (methods only)
 *  offset  byte offset of the declaration
 *  line    line number of the declaration
 *  doc     [offset, length, text] of the preceding docblock, or null
 *
 * Usage: $units = code_units($path);
 */

declare(strict_types=1);

if (!function_exists('code_units')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function code_units(string $path): array
    {
        $src = (string) file_get_contents($path);
        $raw = token_get_all($src);

        // token_get_all() reports line numbers, not byte offsets: rebuild the
        // token stream with byte offsets so callers can edit safely.
        $tokens = [];
        $offset = 0;
        foreach ($raw as $token) {
            $text = is_array($token) ? $token[1] : $token;
            $tokens[] = [
                'id' => is_array($token) ? $token[0] : null,
                'text' => $text,
                'offset' => $offset,
                'line' => is_array($token) ? $token[2] : 0,
            ];
            $offset += strlen($text);
        }

        $count = count($tokens);
        $namespace = '';
        $decls = [];
        $lastDoc = null;
        $pendingClass = null;

        $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL, T_VAR];

        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if ($t['id'] === null) {
                continue;
            }
            if ($t['id'] === T_DOC_COMMENT) {
                $lastDoc = [$t['offset'], strlen($t['text']), $t['text']];
                continue;
            }
            if ($t['id'] === T_WHITESPACE || $t['id'] === T_COMMENT) {
                continue;
            }
            if (in_array($t['id'], $modifiers, true)) {
                continue; // modifiers sit between the docblock and the declaration
            }
            if ($t['id'] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if (in_array($tokens[$j]['id'], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                        $namespace .= $tokens[$j]['text'];
                    } elseif ($tokens[$j]['id'] === T_WHITESPACE) {
                        continue;
                    } else {
                        break;
                    }
                }
                continue;
            }
            if (in_array($t['id'], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                $prev = $i - 1;
                while ($prev >= 0 && $tokens[$prev]['id'] === T_WHITESPACE) {
                    $prev--;
                }
                if ($prev >= 0 && in_array($tokens[$prev]['id'], [T_DOUBLE_COLON, T_NEW], true)) {
                    continue; // ::class or an anonymous class
                }
                $name = null;
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j]['id'] === T_STRING) {
                        $name = $tokens[$j]['text'];
                        break;
                    }
                }
                if ($name === null) {
                    continue;
                }
                $fqn = $namespace === '' ? $name : $namespace . '\\' . $name;
                $decls[] = [
                    'kind' => 'class',
                    'name' => $name,
                    'fqn' => $fqn,
                    'owner' => null,
                    'offset' => $t['offset'],
                    'line' => $t['line'],
                    'doc' => $lastDoc,
                ];
                $pendingClass = $fqn;
                $lastDoc = null;
                continue;
            }
            if ($t['id'] === T_FUNCTION) {
                // A closure has no name: skip whitespace and '&', then require a T_STRING.
                $probe = $i + 1;
                while ($probe < $count && $tokens[$probe]['id'] === T_WHITESPACE) {
                    $probe++;
                }
                if ($probe < $count && $tokens[$probe]['text'] === '&') {
                    $probe++;
                    while ($probe < $count && $tokens[$probe]['id'] === T_WHITESPACE) {
                        $probe++;
                    }
                }
                if ($probe >= $count || $tokens[$probe]['id'] !== T_STRING) {
                    continue;
                }
                $name = $tokens[$probe]['text'];
                $decls[] = [
                    'kind' => $pendingClass === null ? 'function' : 'method',
                    'name' => $name,
                    'fqn' => $pendingClass === null
                        ? ($namespace === '' ? $name : $namespace . '\\' . $name)
                        : $pendingClass . '::' . $name,
                    'owner' => $pendingClass,
                    'offset' => $t['offset'],
                    'line' => $t['line'],
                    'doc' => $lastDoc,
                ];
                $lastDoc = null;
                continue;
            }
            $lastDoc = null; // any other token clears the pending docblock
        }

        return $decls;
    }
}
