<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for TransactionRepository.php
 *
 * Originally documented the PRODUCTION state of TransactionRepository.php
 * (simple 5-method repository with direct db_query() calls).
 *
 * INVERTED (refactor-psr): The repository has been refactored to the modern
 * architecture, so this baseline now guards the CURRENT state:
 *
 * 1. Repository in Ksfraser\FaBankImport\Repositories namespace
 * 2. Implements TransactionRepositoryInterface
 * 3. Constructor dependency injection of TransactionQueryBuilder
 * 4. Core methods preserved: findById, findAll, update
 * 5. Advanced methods present: findByFilters, reset, prevoid, findNormalPairing
 *
 * Behavior contract unchanged: same table (bi_transactions), same CRUD semantics.
 *
 * @package KsfBankImport\Tests\Integration
 */
class TransactionRepositoryProductionBaselineTest extends TestCase
{
    private $repoFile;

    protected function setUp(): void
    {
        $this->repoFile = __DIR__ . '/../../src/Ksfraser/FaBankImport/repositories/TransactionRepository.php';
        $this->assertFileExists($this->repoFile, 'TransactionRepository.php must exist');
    }

    /**
     * @test
     * BASELINE: Class exists in correct namespace
     */
    public function testProdBaseline_ClassExists()
    {
        $content = file_get_contents($this->repoFile);

        $this->assertStringContainsString(
            'namespace Ksfraser\FaBankImport\Repositories;',
            $content,
            'BASELINE: Must be in Ksfraser\FaBankImport\Repositories namespace'
        );

        $this->assertStringContainsString(
            'class TransactionRepository',
            $content,
            'BASELINE: TransactionRepository class must exist'
        );
    }

    /**
     * @test
     * BASELINE: Implements TransactionRepositoryInterface
     */
    public function testProdBaseline_ImplementsInterface()
    {
        $content = file_get_contents($this->repoFile);

        $this->assertStringContainsString(
            'implements \Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface',
            $content,
            'BASELINE: Must implement TransactionRepositoryInterface'
        );
    }

    /**
     * @test
     * BASELINE: Has findById method
     */
    public function testProdBaseline_HasFindByIdMethod()
    {
        $content = file_get_contents($this->repoFile);

        $this->assertStringContainsString(
            'public function findById(int $id): ?array',
            $content,
            'BASELINE: Must have findById method returning ?array'
        );
    }

    /**
     * @test
     * BASELINE: Has findAll method
     */
    public function testProdBaseline_HasFindAllMethod()
    {
        $content = file_get_contents($this->repoFile);

        $this->assertStringContainsString(
            'public function findAll(): array',
            $content,
            'BASELINE: Must have findAll method returning array'
        );
    }

    /**
     * @test
     * BASELINE: Uses TransactionQueryBuilder via constructor injection
     */
    public function testProdBaseline_UsesQueryBuilderInjection()
    {
        $content = file_get_contents($this->repoFile);

        $this->assertStringContainsString(
            'use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;',
            $content,
            'BASELINE: Must import TransactionQueryBuilder'
        );

        $this->assertMatchesRegularExpression(
            '/public function __construct\(\s*TransactionQueryBuilder\s+\$queryBuilder\s*\)/',
            $content,
            'BASELINE: Constructor must accept injected TransactionQueryBuilder'
        );
    }

    /**
     * @test
     * BASELINE: Advanced methods are present
     */
    public function testProdBaseline_HasAdvancedMethods()
    {
        $content = file_get_contents($this->repoFile);

        foreach (['findByFilters', 'reset(', 'prevoid(', 'findNormalPairing'] as $method) {
            $this->assertStringContainsString(
                $method,
                $content,
                "BASELINE: Expected {$method} to be part of repository API"
            );
        }
    }
}
