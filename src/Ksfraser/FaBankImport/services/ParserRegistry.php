<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\ParserConfig;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;

/**
 * ParserRegistry discovers and returns available parsers.
 * Manages active/inactive/new parser lists and provides warnings for new discoveries.
 */
class ParserRegistry
{
    private $parsersDir;
    private $configRepo;
    private $discoveredParsers = [];

    public function __construct(DatabaseConfigRepository $configRepo, $parsersDir = null)
    {
        $this->configRepo = $configRepo;
        // Default to <project-root>/Parsers
        $this->parsersDir = $parsersDir ?: dirname(__DIR__, 3) . '/Parsers';
    }

    /**
     * Discover all parsers from filesystem and Composer.
     */
    public function discoverParsers(): array
    {
        $this->discoveredParsers = [];

        // Scan Parsers/ directory for local parser packages
        if (is_dir($this->parsersDir)) {
            $this->scanDirectory($this->parsersDir);
        }

        // Scan vendor/ for Composer-installed parser packages
        $vendorDir = dirname(__DIR__, 4) . '/vendor';
        if (is_dir($vendorDir)) {
            $this->scanVendorDirectory($vendorDir);
        }

        return $this->discoveredParsers;
    }

    private function scanDirectory(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'parser.json') {
                $this->loadParserManifest($file->getPathname());
            }
        }
    }

    private function scanVendorDirectory(string $vendorDir): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($vendorDir));
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'parser.json' && strpos($file->getPathname(), '/vendor/') !== false) {
                $this->loadParserManifest($file->getPathname());
            }
        }
    }

    private function loadParserManifest(string $manifestPath): void
    {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if ($manifest && isset($manifest['class'])) {
            $parserId = basename(dirname($manifestPath)); // Use directory name as ID
            $this->discoveredParsers[$parserId] = [
                'manifest' => $manifest,
                'path' => dirname($manifestPath),
            ];
        }
    }

    /**
     * Get all discovered parsers.
     */
    public function getDiscoveredParsers(): array
    {
        if (empty($this->discoveredParsers)) {
            $this->discoverParsers();
        }
        return $this->discoveredParsers;
    }

    /**
     * Get active parsers from config.
     */
    public function getActiveParsers(): array
    {
        $active = $this->configRepo->get('parser.active_list', []);
        return is_array($active) ? $active : [];
    }

    /**
     * Get inactive parsers from config.
     */
    public function getInactiveParsers(): array
    {
        $inactive = $this->configRepo->get('parser.inactive_list', []);
        return is_array($inactive) ? $inactive : [];
    }

    /**
     * Get newly discovered parsers not in active/inactive lists.
     */
    public function getNewParsers(): array
    {
        $discovered = array_keys($this->getDiscoveredParsers());
        $active = $this->getActiveParsers();
        $inactive = $this->getInactiveParsers();
        $configured = array_merge($active, $inactive);
        return array_diff($discovered, $configured);
    }

    /**
     * Set active parsers.
     */
    public function setActiveParsers(array $parsers, string $username): void
    {
        $this->configRepo->set('parser.active_list', $parsers, $username, 'Update active parsers list');
    }

    /**
     * Set inactive parsers.
     */
    public function setInactiveParsers(array $parsers, string $username): void
    {
        $this->configRepo->set('parser.inactive_list', $parsers, $username, 'Update inactive parsers list');
    }

    /**
     * Returns an associative array of active parsers.
     * Each parser is keyed by its ID and contains config info.
     *
     * @return array
     */
    public function getAvailableParsers(): array
    {
        $parsers = [];
        $active = $this->getActiveParsers();
        $discovered = $this->getDiscoveredParsers();

        foreach ($active as $parserId) {
            if (isset($discovered[$parserId])) {
                $config = $discovered[$parserId]['manifest'];
                $parsers[$parserId] = [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'namespace' => $config['namespace'],
                    'class' => $config['class'],
                    'filetype' => $config['filetype'],
                    'select' => ['bank_account' => 'Select bank account'],
                ];
            }
        }

        // Always include legacy QFX parser if not discovered
        if (!isset($parsers['QFX'])) {
            $parsers['QFX'] = [
                'name' => 'QFX/OFX/Quickbooks (QBO) format',
                'select' => ['bank_account' => 'Select bank account'],
            ];
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

    /**
     * Load and instantiate a parser class.
     */
    public function loadParser(string $parserId): ?object
    {
        $discovered = $this->getDiscoveredParsers();
        if (!isset($discovered[$parserId])) {
            return null;
        }

        $info = $discovered[$parserId];
        $className = $info['manifest']['class'];
        $namespace = $info['manifest']['namespace'];

        $fullClass = $namespace . '\\' . $className;

        if (class_exists($fullClass)) {
            return new $fullClass();
        }

        // Try to include the class file
        $classFile = $info['path'] . '/' . $className . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            if (class_exists($fullClass)) {
                return new $fullClass();
            }
        }

        return null;
    }

    /**
     * Check for new parsers and return warning message if any found and user has access.
     */
    public function getNewParsersWarning(): ?string
    {
        $newParsers = $this->getNewParsers();
        if (empty($newParsers)) {
            return null;
        }

        $names = [];
        $discovered = $this->getDiscoveredParsers();
        foreach ($newParsers as $parserId) {
            if (isset($discovered[$parserId]['manifest']['name'])) {
                $names[] = $discovered[$parserId]['manifest']['name'];
            }
        }

        if (empty($names)) {
            return null;
        }

        return "New parser(s) detected: " . implode(', ', $names) . ". Please review and configure in parser settings.";
    }
}
