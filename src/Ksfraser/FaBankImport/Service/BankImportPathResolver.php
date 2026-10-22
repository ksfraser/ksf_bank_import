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
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
 */
final class BankImportPathResolver
{
    /** @var string */
    private $companyPath;

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    private function __construct(string $companyPath)
    {
        $companyPath = rtrim($companyPath, "/\\");
        if ($companyPath === '') {
            throw new \InvalidArgumentException('companyPath must not be empty');
        }
        $this->companyPath = $companyPath;
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public static function forCompanyPath(string $companyPath): self
    {
        return new self($companyPath);
    }

    /**
     * FrontAccounting integration helper.
     *
     * For library extraction: prefer forCompanyPath() and inject the company path from your host app.
     * This method exists to keep the module wiring terse.
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
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

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public function companyPath(): string
    {
        return $this->companyPath;
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public function baseDir(): string
    {
        return $this->join('bank_imports');
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public function uploadsDir(): string
    {
        return $this->join('bank_imports', 'uploads');
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public function logsDir(): string
    {
        return $this->join('bank_imports', 'logs');
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
    public function pendingDir(): string
    {
        return $this->join('bank_imports', 'pending');
    }

    /**
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
     */
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
 * @BABOK FR-IMP-003, FR-LOG-001, FR-UPL-004 | BR-IMP-003 | UAT-IMP-006
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
