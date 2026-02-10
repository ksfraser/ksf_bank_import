<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Service;

use Ksfraser\FaBankImport\Service\BankImportPathResolver;
use PHPUnit\Framework\TestCase;

final class BankImportPathResolverTest extends TestCase
{
    public function test_it_resolves_module_directories_under_bank_imports(): void
    {
        $base = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fa_company_1' . DIRECTORY_SEPARATOR;
        $resolver = BankImportPathResolver::forCompanyPath($base);

        $this->assertSame(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'bank_imports', $resolver->baseDir());
        $this->assertSame(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'uploads', $resolver->uploadsDir());
        $this->assertSame(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'logs', $resolver->logsDir());
        $this->assertSame(rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'pending', $resolver->pendingDir());
    }

    public function test_dir_allows_custom_subdirectories(): void
    {
        $base = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fa_company_2';
        $resolver = BankImportPathResolver::forCompanyPath($base);

        $this->assertSame(
            rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'reports',
            $resolver->dir('reports')
        );
    }
}
