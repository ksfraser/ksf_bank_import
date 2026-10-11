<?php
// scan_providers.php
// Usage: php scan_providers.php [root_dir] [out_dir]
// Scans PHP files for namespace/class/interface/trait declarations and usages
// Emits JSON mapping with declares + usages (including resolved require/include providers).

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(2);
}

$root = isset($argv[1]) ? rtrim($argv[1], "\/\\") : getcwd();
$outDir = isset($argv[2]) ? rtrim($argv[2], "\/\\") : ($root . DIRECTORY_SEPARATOR . 'tools');
if (!is_dir($root)) { fwrite(STDERR, "Root path not found: $root\n"); exit(2); }
if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }

// Regex patterns
$ns_re = '/^\s*namespace\s+([^;{]+)[;{]/im';
$class_re = '/^\s*(?:abstract\s+|final\s+)?class\s+([A-Za-z0-9_]+)/im';
$interface_re = '/^\s*interface\s+([A-Za-z0-9_]+)/im';
$trait_re = '/^\s*trait\s+([A-Za-z0-9_]+)/im';
$use_re = '/^\s*use\s+([^;]+);/im';
$require_re = '/\b(require_once|require|include_once|include)\s*\(?\s*["\']([^"\']+)["\']\s*\)?\s*;/i';
$new_re = '/new\s+([A-Za-z0-9_\\\\]+)/i';
$static_re = '/\b([A-Z][A-Za-z0-9_\\\\]+)::/';
$func_params_re = '/function\s+[A-Za-z0-9_]+\s*\(([^)]*)\)/i';
$param_type_re = '/\b([A-Z][A-Za-z0-9_\\\\]+)\b/';

// collect php files
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$ignoreDirs = [DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR];
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    if (strtolower($f->getExtension()) !== 'php') continue;
    $full = $f->getPathname();
    $norm = str_replace('\\', '/', $full);
    $skip = false;
    foreach ($ignoreDirs as $pat) {
        if (strpos($norm, str_replace('\\','/',$pat)) !== false) { $skip = true; break; }
    }
    if ($skip) continue;
    $files[] = $full;
}

// First pass: find declarations
$declarations = []; // fq -> array of files
$shortMap = []; // short -> set of fq
foreach ($files as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $txt = @file_get_contents($path);
    if ($txt === false) continue;
    $ns = '';
    if (preg_match($ns_re, $txt, $m)) $ns = trim($m[1]);
    if (preg_match_all($class_re, $txt, $mc)) {
        foreach ($mc[1] as $name) {
            $fq = $ns ? ($ns . '\\' . $name) : $name;
            $declarations[$fq][] = $rel;
            $shortMap[$name][$fq] = true;
        }
    }
    if (preg_match_all($interface_re, $txt, $mi)) {
        foreach ($mi[1] as $name) {
            $fq = $ns ? ($ns . '\\' . $name) : $name;
            $declarations[$fq][] = $rel;
            $shortMap[$name][$fq] = true;
        }
    }
    if (preg_match_all($trait_re, $txt, $mt)) {
        foreach ($mt[1] as $name) {
            $fq = $ns ? ($ns . '\\' . $name) : $name;
            $declarations[$fq][] = $rel;
            $shortMap[$name][$fq] = true;
        }
    }
}

// Prepare mapping skeleton
$mapping = [];
foreach ($declarations as $fq => $_) {
    $mapping[$fq] = ['declares' => array_values(array_unique($declarations[$fq])), 'usages' => []];
}

// Helper to resolve require/include relative to a file
function resolveInclude($baseFile, $includePath, $root)
{
    // if absolute
    if (preg_match('#^([A-Za-z]:)?[/\\]#', $includePath)) {
        return str_replace('\\', '/', $includePath);
    }
    $dir = dirname($baseFile);
    $cand = realpath($dir . DIRECTORY_SEPARATOR . $includePath);
    if ($cand && strpos($cand, realpath($root)) === 0) {
        return str_replace('\\', '/', substr($cand, strlen(realpath($root)) + 1));
    }
    // try relative without realpath
    $norm = str_replace('\\', '/', $dir . '/' . $includePath);
    // normalize .. and .
    $parts = preg_split('#/[\\/]#', $norm);
    $stack = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') { array_pop($stack); continue; }
        $stack[] = $part;
    }
    $resolved = implode('/', $stack);
    if (file_exists($root . '/' . $resolved)) {
        return $resolved;
    }
    return $includePath; // best-effort fallback
}

// Second pass: find usages and resolve requires
foreach ($files as $path) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    $txt = @file_get_contents($path);
    if ($txt === false) continue;

    // use statements (may be multiple per line separated by commas)
    if (preg_match_all($use_re, $txt, $mu)) {
        foreach ($mu[1] as $uraw) {
            // split multi-use like: use A\B, C\D as D;
            foreach (preg_split('/,\s*/', $uraw) as $useEntry) {
                $useEntry = trim($useEntry);
                if ($useEntry === '') continue;
                $parts = preg_split('/\s+as\s+/i', $useEntry);
                $used = trim($parts[0]);
                $short = basename(str_replace('\\', '/', $used));
                // if fully-qualified points to a declaration, map directly
                if (isset($mapping[$used])) {
                    $mapping[$used]['usages'][] = ['file'=>$rel,'type'=>'use','raw'=>$used];
                } else {
                    // map to short name candidates
                    if (isset($shortMap[$short])) {
                        foreach (array_keys($shortMap[$short]) as $fq) {
                            $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'use','raw'=>$used];
                        }
                    }
                }
            }
        }
    }

    // require/include
    if (preg_match_all($require_re, $txt, $mr, PREG_SET_ORDER)) {
        foreach ($mr as $m) {
            $inc = $m[2];
            $resolved = resolveInclude($path, $inc, $root);
            // record this require for any declarations that this file may use by short name (best-effort)
            // also tag the resolved provider in the usage entry
            // find all short names that appear in this file and attribute the require usage
            foreach ($shortMap as $short => $fqs) {
                if (stripos($txt, $short) !== false) {
                    foreach (array_keys($fqs) as $fq) {
                        $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'require','raw'=>$inc,'provider_resolved'=>$resolved];
                    }
                }
            }
        }
    }

    // new expressions
    if (preg_match_all($new_re, $txt, $mn)) {
        foreach ($mn[1] as $n) {
            $n = ltrim($n, '\\'); $short = basename(str_replace('\\','/',$n));
            if (isset($mapping[$n])) {
                $mapping[$n]['usages'][] = ['file'=>$rel,'type'=>'new','raw'=>$n];
            } elseif (isset($shortMap[$short])) {
                foreach (array_keys($shortMap[$short]) as $fq) {
                    $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'new','raw'=>$short];
                }
            }
        }
    }

    // static calls A::foo
    if (preg_match_all($static_re, $txt, $ms)) {
        foreach ($ms[1] as $n) {
            $n = ltrim($n, '\\'); $short = basename(str_replace('\\','/',$n));
            if (isset($mapping[$n])) {
                $mapping[$n]['usages'][] = ['file'=>$rel,'type'=>'static','raw'=>$n];
            } elseif (isset($shortMap[$short])) {
                foreach (array_keys($shortMap[$short]) as $fq) {
                    $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'static','raw'=>$short];
                }
            }
        }
    }

    // param type hints (basic)
    if (preg_match_all($func_params_re, $txt, $mp)) {
        foreach ($mp[1] as $params) {
            if (!preg_match_all($param_type_re, $params, $mt)) continue;
            foreach ($mt[1] as $t) {
                $t = ltrim($t, '\\'); $short = basename(str_replace('\\','/',$t));
                if (isset($mapping[$t])) {
                    $mapping[$t]['usages'][] = ['file'=>$rel,'type'=>'param','raw'=>$t];
                } elseif (isset($shortMap[$short])) {
                    foreach (array_keys($shortMap[$short]) as $fq) {
                        $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'param','raw'=>$short];
                    }
                }
            }
        }
    }

    // extends/implements handled by class regex earlier via detection of class lines (simple heuristic)
    if (preg_match_all('/^\s*(?:abstract\s+|final\s+)?class\s+[A-Za-z0-9_]+\s+(?:extends\s+([A-Za-z0-9_\\\\]+))?(?:\s+implements\s+([A-Za-z0-9_\\\\,\\s]+))?/im', $txt, $mc, PREG_SET_ORDER)) {
        foreach ($mc as $m) {
            if (!empty($m[1])) {
                $name = ltrim($m[1], '\\'); $short = basename(str_replace('\\','/',$name));
                if (isset($mapping[$name])) $mapping[$name]['usages'][] = ['file'=>$rel,'type'=>'extends','raw'=>$name];
                elseif (isset($shortMap[$short])) foreach (array_keys($shortMap[$short]) as $fq) $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'extends','raw'=>$short];
            }
            if (!empty($m[2])) {
                foreach (explode(',', $m[2]) as $part) {
                    $name = trim($part); $name = ltrim($name, '\\'); $short = basename(str_replace('\\','/',$name));
                    if (isset($mapping[$name])) $mapping[$name]['usages'][] = ['file'=>$rel,'type'=>'implements','raw'=>$name];
                    elseif (isset($shortMap[$short])) foreach (array_keys($shortMap[$short]) as $fq) $mapping[$fq]['usages'][] = ['file'=>$rel,'type'=>'implements','raw'=>$short];
                }
            }
        }
    }
}

// convert usage sets to arrays and collapse duplicates
$out = ['generated'=>date('c'), 'root'=>$root, 'symbols'=>[]];
foreach ($mapping as $fq => $info) {
    $us = [];
    $seen = [];
    foreach ($info['usages'] as $u) {
        $key = $u['file'].'|'.$u['type'].'|'.($u['raw']??'');
        if (isset($seen[$key])) continue; $seen[$key]=true;
        $us[] = $u;
    }
    $out['symbols'][] = ['symbol'=>$fq,'declares'=>$info['declares'],'usages_count'=>count($us),'usages_sample'=>array_slice($us,0,20)];
}

$outPath = $outDir . DIRECTORY_SEPARATOR . 'providers_php.json';
file_put_contents($outPath, json_encode($out, JSON_PRETTY_PRINT));
fwrite(STDOUT, "Wrote: $outPath\nSymbols: " . count($out['symbols']) . "\n");

// exit
exit(0);
