<?php

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Production Baseline Test for TransactionRepository.php
 * 
 * This test documents the PRODUCTION state of TransactionRepository.php.
 * 
 * Purpose: Verify that the TransactionRepository class on main branch maintains
 * backward compatibility with the production version.
 * 
 * File under test: src/Ksfraser/FaBankImport/repositories/TransactionRepository.php
 * 
 * Key behaviors documented (PROD):
 * 1. Simple repository with 5 basic CRUD methods
 * 2. Direct db_query() calls (no QueryBuilder dependency)
 * 3. Implements TransactionRepositoryInterface
 * 4. Methods: findById, findAll, findByStatus, save, update
 * 
 * Changes on main (expected):
 * - MAJOR REFACTORING: Added QueryBuilder dependency injection (294 lines added)
 * - Added comprehensive PHPDoc documentation
 * - Added findByFilters() with complex query building
 * - Added reset() method for void operations
 * - Added prevoid() hook method
 * - Added findNormalPairing() for automated matching
 * - Changed from direct queries to QueryBuilder pattern
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
     * PROD BASELINE: Class exists in correct namespace
     */
    public function testProdBaseline_ClassExists()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'namespace Ksfraser\FaBankImport\Repositories;',
            $content,
            'PROD BASELINE: Must be in Ksfraser\FaBankImport\Repositories namespace'
        );
        
        $this->assertStringContainsString(
            'class TransactionRepository',
            $content,
            'PROD BASELINE: TransactionRepository class must exist'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Implements TransactionRepositoryInterface
     */
    public function testProdBaseline_ImplementsInterface()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'use Ksfraser\FaBankImport\Interfaces\TransactionRepositoryInterface;',
            $content,
            'PROD BASELINE: Must import TransactionRepositoryInterface'
        );
        
        $this->assertStringContainsString(
            'implements TransactionRepositoryInterface',
            $content,
            'PROD BASELINE: Must implement TransactionRepositoryInterface'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has findById method with simple query
     */
    public function testProdBaseline_HasFindByIdMethod()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'public function findById(int $id): ?array',
            $content,
            'PROD BASELINE: Must have findById method'
        );
        
        $this->assertStringContainsString(
            'SELECT * FROM bi_transactions WHERE id = ?',
            $content,
            'PROD BASELINE: findById uses direct SQL query'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has findAll method with simple query
     */
    public function testProdBaseline_HasFindAllMethod()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'public function findAll(): array',
            $content,
            'PROD BASELINE: Must have findAll method'
        );
        
        $this->assertStringContainsString(
            'SELECT * FROM bi_transactions',
            $content,
            'PROD BASELINE: findAll uses direct SQL query'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has findByStatus method with simple query
     */
    public function testProdBaseline_HasFindByStatusMethod()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'public function findByStatus(string $status): array',
            $content,
            'PROD BASELINE: Must have findByStatus method'
        );
        
        $this->assertStringContainsString(
            'SELECT * FROM bi_transactions WHERE status = ?',
            $content,
            'PROD BASELINE: findByStatus uses direct SQL query'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has save method for inserting transactions
     */
    public function testProdBaseline_HasSaveMethod()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'public function save(array $transaction): bool',
            $content,
            'PROD BASELINE: Must have save method'
        );
        
        $this->assertStringContainsString(
            'INSERT INTO bi_transactions',
            $content,
            'PROD BASELINE: save uses INSERT query'
        );
        
        $this->assertStringContainsString(
            'amount, valueTimestamp, memo, status',
            $content,
            'PROD BASELINE: save inserts specific fields'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Has update method for modifying transactions
     */
    public function testProdBaseline_HasUpdateMethod()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'public function update(int $id, array $data): bool',
            $content,
            'PROD BASELINE: Must have update method'
        );
        
        $this->assertStringContainsString(
            'UPDATE bi_transactions SET',
            $content,
            'PROD BASELINE: update uses UPDATE query'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Uses direct db_query() calls (no QueryBuilder)
     */
    public function testProdBaseline_UsesDirectDbQuery()
    {
        $content = file_get_contents($this->repoFile);
        
        // Count db_query occurrences (should be 5: findById, findAll, findByStatus, save, update)
        $dbQueryCount = substr_count($content, 'db_query(');
        
        $this->assertGreaterThanOrEqual(
            5,
            $dbQueryCount,
            'PROD BASELINE: Should use db_query() at least 5 times (once per method)'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Does NOT use QueryBuilder (added in main)
     */
    public function testProdBaseline_NoQueryBuilderDependency()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringNotContainsString(
            'TransactionQueryBuilder',
            $content,
            'PROD BASELINE: Should NOT use TransactionQueryBuilder (added in main)'
        );
        
        $this->assertStringNotContainsString(
            '__construct(',
            $content,
            'PROD BASELINE: Should NOT have constructor (no dependency injection on prod)'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Does NOT have advanced methods (added in main)
     */
    public function testProdBaseline_NoAdvancedMethods()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringNotContainsString(
            'findByFilters',
            $content,
            'PROD BASELINE: Should NOT have findByFilters() method (added in main)'
        );
        
        $this->assertStringNotContainsString(
            'function reset(',
            $content,
            'PROD BASELINE: Should NOT have reset() method (added in main)'
        );
        
        $this->assertStringNotContainsString(
            'function prevoid(',
            $content,
            'PROD BASELINE: Should NOT have prevoid() method (added in main)'
        );
        
        $this->assertStringNotContainsString(
            'findNormalPairing',
            $content,
            'PROD BASELINE: Should NOT have findNormalPairing() method (added in main)'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: Simple implementation without extensive PHPDoc
     */
    public function testProdBaseline_MinimalDocumentation()
    {
        $content = file_get_contents($this->repoFile);
        
        // PROD has minimal comment: "/* Original DB queries replaced by repository pattern */"
        $this->assertStringContainsString(
            '/* Original DB queries replaced by repository pattern */',
            $content,
            'PROD BASELINE: Should have simple comment about repository pattern'
        );
        
        // Main adds extensive @package, @author, @since, @version documentation
        $this->assertStringNotContainsString(
            '@package',
            $content,
            'PROD BASELINE: Should NOT have @package annotation (added in main)'
        );
        
        $this->assertStringNotContainsString(
            '@author',
            $content,
            'PROD BASELINE: Should NOT have @author annotation (added in main)'
        );
    }
    
    /**
     * @test
     * PROD BASELINE: File is approximately 55 lines (simple implementation)
     */
    public function testProdBaseline_SimplifiedImplementation()
    {
        $content = file_get_contents($this->repoFile);
        $lineCount = count(explode("\n", $content));
        
        $this->assertLessThan(
            100,
            $lineCount,
            'PROD BASELINE: File should be under 100 lines (simple implementation). Main adds 294 lines for QueryBuilder pattern.'
        );
        
        $this->assertGreaterThan(
            40,
            $lineCount,
            'PROD BASELINE: File should have at least 40 lines (5 methods)'
        );
    }
}
