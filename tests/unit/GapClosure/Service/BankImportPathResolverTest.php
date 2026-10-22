<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service;

use Ksfraser\FaBankImport\Service\BankImportPathResolver;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the path resolver.
 *
 * @see \Ksfraser\FaBankImport\Service\BankImportPathResolver
 */
final class BankImportPathResolverTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheCompanyPathIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('companyPath must not be empty');

        BankImportPathResolver::forCompanyPath('');
    }

    /**
     * @return void
     */
    public function testTheCompanyPathIsReportedBack(): void
    {
        $resolver = BankImportPathResolver::forCompanyPath('/srv/company/1/');

        $this->assertSame('/srv/company/1', $resolver->companyPath());
    }

    /**
     * @return void
     */
    public function testTheCompanyPathCanBeProvidedByACallback(): void
    {
        $resolver = BankImportPathResolver::forCurrentCompany(
            7,
            static function ($companyId): string {
                return '/srv/company/' . (int) $companyId;
            }
        );

        $this->assertSame('/srv/company/7', $resolver->companyPath());
    }

    /**
     * @return void
     */
    public function testTheModuleDirectoriesAreDerivedFromTheCompanyPath(): void
    {
        $resolver = BankImportPathResolver::forCompanyPath('/srv/company/1');

        $this->assertSame('/srv/company/1' . DIRECTORY_SEPARATOR . 'bank_imports', $resolver->baseDir());
        $this->assertSame(
            '/srv/company/1' . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'uploads',
            $resolver->uploadsDir()
        );
        $this->assertSame(
            '/srv/company/1' . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'logs',
            $resolver->logsDir()
        );
        $this->assertSame(
            '/srv/company/1' . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'pending',
            $resolver->pendingDir()
        );
    }

    /**
     * @return void
     */
    public function testAnEmptyDirectoryNameFallsBackToTheBaseDirectory(): void
    {
        $resolver = BankImportPathResolver::forCompanyPath('/srv/company/1');

        $this->assertSame($resolver->baseDir(), $resolver->dir('   '));
    }

    /**
     * @return void
     */
    public function testADirectoryNameIsAppendedToTheBaseDirectory(): void
    {
        $resolver = BankImportPathResolver::forCompanyPath('/srv/company/1');

        $this->assertSame(
            '/srv/company/1' . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'archive',
            $resolver->dir('archive')
        );
    }

    /**
     * @return void
     */
    public function testAnEmptyDirectoryPartIsSkipped(): void
    {
        $resolver = BankImportPathResolver::forCompanyPath('/srv/company/1');

        // The name is only a separator here, so nothing is appended to the
        // base directory.
        $this->assertSame($resolver->baseDir(), $resolver->dir('/'));
    }
}
