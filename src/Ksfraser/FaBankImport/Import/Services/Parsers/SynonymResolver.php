<?php

namespace Ksfraser\FaBankImport\Import\Services\Parsers;

/**
 * Synonym Resolver - Manages column name synonyms for flexible CSV parsing
 *
 * Provides hierarchical synonym resolution:
 * 1. Runtime-provided synonyms (highest priority)
 * 2. Config file synonyms (medium priority)
 * 3. Hardcoded default synonyms (fallback)
 *
 * Supports parser-specific and universal synonyms.
 *
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Import\Services\Parsers
 * @since 2.2.1
 */
class SynonymResolver
{
    /**
     * Default synonyms (fallback when no others provided)
     *
     * @var array<string, array<string>>
     */
    private array $defaultSynonyms = [
        'transactionDate' => ['Date', 'Transaction Date', 'Posted Date', 'valueDate'],
        'amount' => ['Amount', 'Transaction Amount', 'Sum'],
        'merchant' => ['Merchant Name', 'Beneficiary', 'Counterparty', 'account Name'],
        'description' => ['Description', 'Activity Type', 'Transaction Type', 'memo'],
        'reference' => ['Reference Number', 'Reference', 'Transaction Code', 'Check Number'],
        'category' => ['Merchant Category', 'Category', 'Type'],
        'account' => ['Account', 'Card Number', 'Account Number', 'acctid'],
        'currency' => ['Currency', 'Currency Code', 'ISO Code'],
    ];

    /**
     * Synonyms loaded from config file
     *
     * @var array<string, array<string>>|null
     */
    private ?array $configSynonyms = null;

    /**
     * Runtime-provided synonyms (highest priority)
     *
     * @var array<string, array<string>>|null
     */
    private ?array $runtimeSynonyms = null;

    /**
     * Parser-specific synonyms from config/runtime
     *
     * @var array<string, array<string, array<string>>>
     */
    private array $parserSpecificSynonyms = [];

    /**
     * Constructor
     *
     * @param string|null $configFilePath Optional path to synonym config file (JSON/YAML)
     */
    public function __construct(?string $configFilePath = null)
    {
        if ($configFilePath && file_exists($configFilePath)) {
            $this->loadConfigFile($configFilePath);
        }
    }

    /**
     * Load synonyms from configuration file (JSON)
     *
     * Expected format:
     * ```json
     * {
     *   "synonyms": {
     *     "transactionDate": ["Date", "Posted Date"],
     *     ...
     *   },
     *   "parserSpecific": {
     *     "csv": {
     *       "amount": ["Amount", "Sum"]
     *     },
     *     "ofx": { ... }
     *   }
     * }
     * ```
     *
     * @param string $filePath Path to configuration file
     * @return void
     * @throws \RuntimeException If file cannot be read or parsed
     */
    public function loadConfigFile(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("Cannot read config file: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $json = file_get_contents($filePath);
            if ($json === false) {
                throw new \RuntimeException("Failed to read config file: {$filePath}");
            }

            $config = json_decode($json, true);
            if (!is_array($config)) {
                throw new \RuntimeException("Invalid JSON config file: {$filePath}");
            }
        } else {
            throw new \RuntimeException("Unsupported config file format: {$ext}");
        }

        // Load universal synonyms
        if (!empty($config['synonyms']) && is_array($config['synonyms'])) {
            $this->configSynonyms = $config['synonyms'];
        }

        // Load parser-specific synonyms
        if (!empty($config['parserSpecific']) && is_array($config['parserSpecific'])) {
            $this->parserSpecificSynonyms = $config['parserSpecific'];
        }
    }

    /**
     * Set runtime synonyms (overrides all others)
     *
     * @param array<string, array<string>> $synonyms Field → Synonym list mapping
     * @return self Fluent interface
     */
    public function setRuntimeSynonyms(array $synonyms): self
    {
        $this->runtimeSynonyms = $synonyms;
        return $this;
    }

    /**
     * Get resolved synonyms for a field
     *
     * Resolution order:
     * 1. Parser-specific runtime synonyms
     * 2. Parser-specific config synonyms
     * 3. Universal runtime synonyms
     * 4. Universal config synonyms
     * 5. Default synonyms
     *
     * @param string $fieldName Standard field name (e.g., 'transactionDate')
     * @param string $parserType Parser type identifier (csv, ofx, qif)
     * @return array<string> List of synonyms for this field
     */
    public function getSynonymsForField(string $fieldName, string $parserType = 'csv'): array
    {
        // Check parser-specific runtime synonyms
        if ($this->runtimeSynonyms &&
            isset($this->parserSpecificSynonyms[$parserType][$fieldName])) {
            return $this->parserSpecificSynonyms[$parserType][$fieldName];
        }

        // Check parser-specific config synonyms
        if (isset($this->parserSpecificSynonyms[$parserType][$fieldName])) {
            return $this->parserSpecificSynonyms[$parserType][$fieldName];
        }

        // Check universal runtime synonyms
        if ($this->runtimeSynonyms && isset($this->runtimeSynonyms[$fieldName])) {
            return $this->runtimeSynonyms[$fieldName];
        }

        // Check universal config synonyms
        if ($this->configSynonyms && isset($this->configSynonyms[$fieldName])) {
            return $this->configSynonyms[$fieldName];
        }

        // Fall back to defaults
        return $this->defaultSynonyms[$fieldName] ?? [];
    }

    /**
     * Get all resolved synonyms for a parser type
     *
     * @param string $parserType Parser type (csv, ofx, qif)
     * @return array<string, array<string>> Field → Synonym list mapping
     */
    public function getAllSynonyms(string $parserType = 'csv'): array
    {
        $resolved = [];

        // Start with defaults
        foreach ($this->defaultSynonyms as $field => $synonyms) {
            $resolved[$field] = $synonyms;
        }

        // Override with config
        if ($this->configSynonyms) {
            foreach ($this->configSynonyms as $field => $synonyms) {
                $resolved[$field] = $synonyms;
            }
        }

        // Override with parser-specific config
        if (isset($this->parserSpecificSynonyms[$parserType])) {
            foreach ($this->parserSpecificSynonyms[$parserType] as $field => $synonyms) {
                $resolved[$field] = $synonyms;
            }
        }

        // Override with runtime
        if ($this->runtimeSynonyms) {
            foreach ($this->runtimeSynonyms as $field => $synonyms) {
                $resolved[$field] = $synonyms;
            }
        }

        return $resolved;
    }

    /**
     * Find matching field name for a header
     *
     * Matches header value against synonym lists using case-insensitive comparison
     *
     * @param string $headerValue CSV column header value
     * @param string $parserType Parser type (csv, ofx, qif)
     * @return string|null Matched field name, or null if no match
     */
    public function getFieldNameForHeader(string $headerValue, string $parserType = 'csv'): ?string
    {
        $headerLower = strtolower($headerValue);
        $allSynonyms = $this->getAllSynonyms($parserType);

        foreach ($allSynonyms as $fieldName => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (strtolower($synonym) === $headerLower) {
                    return $fieldName;
                }
            }
        }

        return null;
    }

    /**
     * Add custom synonym at runtime
     *
     * Useful for adding one-off synonyms without loading full config
     *
     * @param string $fieldName Standard field name
     * @param string $synonym New synonym to add
     * @param string $parserType Parser type (csv|ofx|qif|ALL)
     * @return self Fluent interface
     */
    public function addSynonym(string $fieldName, string $synonym, string $parserType = 'csv'): self
    {
        if (!$this->runtimeSynonyms) {
            $this->runtimeSynonyms = [];
        }

        if ($parserType === 'ALL' || $parserType === '*') {
            // Add to universal runtime synonyms
            if (!isset($this->runtimeSynonyms[$fieldName])) {
                $this->runtimeSynonyms[$fieldName] = [];
            }
            if (!in_array($synonym, $this->runtimeSynonyms[$fieldName])) {
                $this->runtimeSynonyms[$fieldName][] = $synonym;
            }
        } else {
            // Add to parser-specific
            if (!isset($this->parserSpecificSynonyms[$parserType])) {
                $this->parserSpecificSynonyms[$parserType] = [];
            }
            if (!isset($this->parserSpecificSynonyms[$parserType][$fieldName])) {
                $this->parserSpecificSynonyms[$parserType][$fieldName] = [];
            }
            if (!in_array($synonym, $this->parserSpecificSynonyms[$parserType][$fieldName])) {
                $this->parserSpecificSynonyms[$parserType][$fieldName][] = $synonym;
            }
        }

        return $this;
    }

    /**
     * Get list of supported field names
     *
     * @return array<int, string> Standard field names
     */
    public function getSupportedFields(): array
    {
        return array_keys($this->defaultSynonyms);
    }
}
