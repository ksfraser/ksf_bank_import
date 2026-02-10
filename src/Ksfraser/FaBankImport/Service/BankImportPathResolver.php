<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * BankImportPathResolver
 *
 * Centralizes all filesystem paths for this module.
 *
 * Convention: all module-owned files live under:
 *   company/#/bank_imports/{uploads,logs,pending,...}
 *
 * Note: This is a good candidate to extract into ksfraser/FILE later.
 */
final class BankImportPathResolver
{
    /** @var string */
    private $companyPath;

    private function __construct(string $companyPath)
    {
        $companyPath = rtrim($companyPath, "/\\");
        if ($companyPath === '') {
            throw new \InvalidArgumentException('companyPath must not be empty');
        }
        $this->companyPath = $companyPath;
    }

    public static function forCompanyPath(string $companyPath): self
    {
        return new self($companyPath);
    }

    /**
     * FrontAccounting integration helper.
     *
     * For library extraction: prefer forCompanyPath() and inject the company path from your host app.
     * This method exists to keep the module wiring terse.
     */
    public static function forCurrentCompany(?int $companyId = null, ?callable $companyPathProvider = null): self
    {
        if ($companyPathProvider !== null) {
            $path = (string)$companyPathProvider($companyId);
            return new self($path);
        }

        if (!function_exists('company_path')) {
            throw new \RuntimeException('company_path() is not available; use forCompanyPath() instead');
        }

        /** @var string $path */
        $path = company_path($companyId ?? 0);
        return new self($path);
    }

    public function companyPath(): string
    {
        return $this->companyPath;
    }

    public function baseDir(): string
    {
        return $this->join('bank_imports');
    }

    public function uploadsDir(): string
    {
        return $this->join('bank_imports', 'uploads');
    }

    public function logsDir(): string
    {
        return $this->join('bank_imports', 'logs');
    }

    public function pendingDir(): string
    {
        return $this->join('bank_imports', 'pending');
    }

    public function dir(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $this->baseDir();
        }
        return $this->join('bank_imports', $name);
    }

    /**
     * @param string ...$parts
     */
    private function join(string ...$parts): string
    {
        $path = $this->companyPath;
        foreach ($parts as $part) {
            $part = trim($part, "/\\");
            if ($part === '') {
                continue;
            }
            $path .= DIRECTORY_SEPARATOR . $part;
        }
        return $path;
    }
}
