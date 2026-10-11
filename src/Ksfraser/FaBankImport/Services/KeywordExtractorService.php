<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\Exceptions\InvalidKeywordException;
use Ksfraser\FaBankImport\Config\ConfigService;

/**
 * Service for extracting and validating keywords from text
 *
 * Handles tokenization, normalization, stopword filtering, and validation
 * for keyword-based pattern matching.
 *
 * @package Ksfraser\FaBankImport\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class KeywordExtractorService
{
    /**
     * @var array<string> Stopwords to filter out
     */
    private array $stopwords;

    /**
     * @var int Minimum keyword length
     */
    private int $minKeywordLength;

    /**
     * @var ConfigService|null Configuration service
     */
    private ?ConfigService $configService;

    /**
     * Default stopwords (common English words with no semantic value)
     */
    private const DEFAULT_STOPWORDS = [
        'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were',
        'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'should', 'could', 'may', 'might', 'must',
        'can', 'this', 'that', 'these', 'those', 'a', 'an', 'you', 'i', 'we'
    ];

    /**
     * Constructor
     *
     * @param array<string>|null $stopwords Custom stopwords list (optional)
     * @param int|null           $minKeywordLength Minimum keyword length (optional)
     * @param ConfigService|null $configService Configuration service (optional)
     */
    public function __construct(
        ?array $stopwords = null,
        ?int $minKeywordLength = null,
        ?ConfigService $configService = null
    ) {
        $this->configService = $configService;
        $this->stopwords = $stopwords ?? self::DEFAULT_STOPWORDS;
        
        // Try to load from config, fallback to parameter or default
        if ($minKeywordLength !== null) {
            $this->minKeywordLength = $minKeywordLength;
        } elseif ($this->configService !== null) {
            $this->minKeywordLength = (int)$this->configService->get(
                'pattern_matching.min_keyword_length',
                3
            );
        } else {
            $this->minKeywordLength = 3;
        }
    }

    /**
     * Extract keywords from text
     *
     * Tokenizes text, normalizes keywords, filters stopwords, and validates.
     *
     * @param string $text Text to extract keywords from
     *
     * @return array<Keyword> Array of valid Keyword value objects
     */
    public function extract(string $text): array
    {
        // Tokenize: split on whitespace and special characters
        $tokens = $this->tokenize($text);

        // Convert to Keyword objects, filtering invalid ones
        $keywords = [];
        foreach ($tokens as $token) {
            try {
                $keyword = new Keyword($token);
                
                // Filter by length
                if ($keyword->getLength() < $this->minKeywordLength) {
                    continue;
                }
                
                // Filter stopwords
                if ($keyword->isStopword($this->stopwords)) {
                    continue;
                }
                
                // Check for duplicates
                $isDuplicate = false;
                foreach ($keywords as $existing) {
                    if ($existing->equals($keyword)) {
                        $isDuplicate = true;
                        break;
                    }
                }
                
                if (!$isDuplicate) {
                    $keywords[] = $keyword;
                }
                
            } catch (\InvalidArgumentException $e) {
                // Skip invalid keywords
                continue;
            }
        }

        return $keywords;
    }

    /**
     * Extract keywords as strings
     *
     * Convenience method that returns string array instead of Keyword objects.
     *
     * @param string $text Text to extract keywords from
     *
     * @return array<string> Array of keyword strings
     */
    public function extractAsStrings(string $text): array
    {
        $keywords = $this->extract($text);
        return array_map(fn(Keyword $k) => $k->getText(), $keywords);
    }

    /**
     * Validate a keyword
     *
     * Checks if a keyword meets all validation criteria.
     *
     * @param string $keyword Keyword to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $keyword): bool
    {
        try {
            $keywordObj = new Keyword($keyword);
            
            if ($keywordObj->getLength() < $this->minKeywordLength) {
                return false;
            }
            
            if ($keywordObj->isStopword($this->stopwords)) {
                return false;
            }
            
            return true;
            
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Add stopword to the list
     *
     * @param string $stopword Stopword to add
     *
     * @return void
     */
    public function addStopword(string $stopword): void
    {
        $normalized = strtolower(trim($stopword));
        if ($normalized && !in_array($normalized, $this->stopwords)) {
            $this->stopwords[] = $normalized;
        }
    }

    /**
     * Get current stopwords list
     *
     * @return array<string>
     */
    public function getStopwords(): array
    {
        return $this->stopwords;
    }

    /**
     * Get minimum keyword length
     *
     * @return int
     */
    public function getMinKeywordLength(): int
    {
        return $this->minKeywordLength;
    }

    /**
     * Set minimum keyword length
     *
     * @param int $length Minimum length (must be positive)
     *
     * @return void
     * @throws InvalidKeywordException If length is invalid
     */
    public function setMinKeywordLength(int $length): void
    {
        if ($length < 1) {
            throw InvalidKeywordException::tooShort('', $length);
        }
        $this->minKeywordLength = $length;
    }

    /**
     * Tokenize text into words
     *
     * Splits text on whitespace and special characters, preserving hyphens.
     *
     * @param string $text Text to tokenize
     *
     * @return array<string> Array of tokens
     */
    private function tokenize(string $text): array
    {
        // Replace multiple spaces with single space
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Split on whitespace and most special characters, but keep hyphens
        // This regex splits on: spaces, punctuation (except hyphens), and other separators
        $tokens = preg_split('/[\s,;.!?(){}[\]"\'<>\/\\\\|+=*&^%$#@~`]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if ($tokens === false) {
            return [];
        }
        
        return array_filter($tokens, fn($token) => trim($token) !== '');
    }
}
