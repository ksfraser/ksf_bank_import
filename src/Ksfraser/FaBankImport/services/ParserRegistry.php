<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\ParserConfig;

/**
 * ParserRegistry discovers and returns available parsers.
 * Replaces global getParsers() function for SRP and testability.
 */
class ParserRegistry
{
    private $parsersDir;

    public function __construct($parsersDir = null)
    {
        // Default to <project-root>/Parsers
        $this->parsersDir = $parsersDir ?: dirname(__DIR__, 3) . '/Parsers';
    }

    /**
     * Returns an associative array of enabled parsers.
     * Each parser is keyed by its ID and contains config info.
     * Always includes legacy QFX parser.
     *
     * @return array
     */
    public function getAvailableParsers(): array
    {
        $parsers = [];
        if (!is_dir($this->parsersDir)) {
            // Only include QFX parser (legacy) if directory is missing
            $parsers['QFX'] = [
                'name' => 'QFX/OFX/Quickbooks (QBO) format',
                'select' => ['bank_account' => 'Select bank account'],
            ];
            return $parsers;
        }
        $dirs = scandir($this->parsersDir);
        $enabledStates = ParserConfig::getAll();
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $parserJson = $this->parsersDir . '/' . $dir . '/parser.json';
            if (is_file($parserJson)) {
                $config = json_decode(file_get_contents($parserJson), true);
                if (!empty($enabledStates[$dir])) {
                    $pid = $dir;
                    $parsers[$pid] = [
                        'name' => $config['name'],
                        'description' => $config['description'],
                        'namespace' => $config['namespace'],
                        'class' => $config['class'],
                        'filetype' => $config['filetype'],
                        'select' => ['bank_account' => 'Select bank account'],
                    ];
                }
            }
        }
        return $parsers;
    }
     /**
     * Returns an array of parser IDs => names for dropdowns, DTOs, and resolver.
     *
     * @return array
     */
    public function getParsersArray(): array
    {
        $available = $this->getAvailableParsers();
        $result = [];
        foreach ($available as $pid => $pdata) {
            $result[$pid] = $pdata['name'];
        }
        return $result;
    }
}
