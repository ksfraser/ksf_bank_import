<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * Bank Import Path Resolver - Resolves directory paths for bank imports
 * 
 * Provides convenient access to subdirectories within the bank_imports module
 * Handles path normalization and trailing slashes consistently
 */
final class BankImportPathResolver
{
    /** @var string Base path to company directory */
    private string $companyPath;
    
    /** @var string Base path to bank_imports directory */
    private string $baseDir;
    
    /**
     * Create resolver for given company path
     * 
     * @param string $companyPath Company base directory path
     * @return self
     */
    public static function forCompanyPath(string $companyPath): self
    {
        return new self($companyPath);
    }
    
    /**
     * Constructor (private, use factory method)
     * 
     * @param string $companyPath Company base directory path
     */
    private function __construct(string $companyPath)
    {
        $this->companyPath = rtrim($companyPath, '/\\');
        $this->baseDir = $this->companyPath . DIRECTORY_SEPARATOR . 'bank_imports';
    }
    
    /**
     * Get base bank_imports directory
     * 
     * @return string
     */
    public function baseDir(): string
    {
        return rtrim($this->baseDir, '/\\');
    }
    
    /**
     * Get uploads subdirectory
     * 
     * @return string
     */
    public function uploadsDir(): string
    {
        return $this->dir('uploads');
    }
    
    /**
     * Get logs subdirectory
     * 
     * @return string
     */
    public function logsDir(): string
    {
        return $this->dir('logs');
    }
    
    /**
     * Get pending subdirectory
     * 
     * @return string
     */
    public function pendingDir(): string
    {
        return $this->dir('pending');
    }
    
    /**
     * Get custom subdirectory
     * 
     * @param string $subdir Subdirectory name
     * @return string
     */
    public function dir(string $subdir): string
    {
        return rtrim($this->baseDir, '/\\') . DIRECTORY_SEPARATOR . ltrim($subdir, '/\\');
    }
}
