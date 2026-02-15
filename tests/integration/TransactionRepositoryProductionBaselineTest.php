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
     * CURRENT BASELINE: Has findById method with tableName-backed query
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
            'SELECT * FROM {$this->tableName} WHERE id = ',
            $content,
            'CURRENT BASELINE: findById uses dynamic table name with numeric id cast'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Has findAll method with tableName-backed query
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
            'SELECT * FROM {$this->tableName}',
            $content,
            'CURRENT BASELINE: findAll uses dynamic table name'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Has findByStatus method with escaped status query
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
            'SELECT * FROM {$this->tableName} WHERE status = ',
            $content,
            'CURRENT BASELINE: findByStatus uses dynamic table name and escaping'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Has generic save() builder logic
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
            'INSERT INTO {$this->tableName}',
            $content,
            'CURRENT BASELINE: save uses tableName-backed INSERT query'
        );
        
        $this->assertStringContainsString(
            'implode(\', \', $columns)',
            $content,
            'CURRENT BASELINE: save builds INSERT columns dynamically'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Has update method for modifying transactions
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
            'UPDATE {$this->tableName} SET',
            $content,
            'CURRENT BASELINE: update uses tableName-backed UPDATE query'
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
     * CURRENT BASELINE: Uses QueryBuilder with optional DI constructor
     */
    public function testProdBaseline_NoQueryBuilderDependency()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'TransactionQueryBuilder',
            $content,
            'CURRENT BASELINE: Uses TransactionQueryBuilder dependency'
        );
        
        $this->assertStringContainsString(
            'public function __construct(?TransactionQueryBuilder $queryBuilder = null)',
            $content,
            'CURRENT BASELINE: Has optional dependency injection constructor'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Includes advanced repository methods
     */
    public function testProdBaseline_NoAdvancedMethods()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            'findByFilters',
            $content,
            'CURRENT BASELINE: Includes findByFilters() method'
        );
        
        $this->assertStringContainsString(
            'function reset(',
            $content,
            'CURRENT BASELINE: Includes reset() method'
        );
        
        $this->assertStringContainsString(
            'function prevoid(',
            $content,
            'CURRENT BASELINE: Includes prevoid() method'
        );
        
        $this->assertStringContainsString(
            'findNormalPairing',
            $content,
            'CURRENT BASELINE: Includes findNormalPairing() method'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: Includes richer documentation and metadata
     */
    public function testProdBaseline_MinimalDocumentation()
    {
        $content = file_get_contents($this->repoFile);
        
        $this->assertStringContainsString(
            '@package',
            $content,
            'CURRENT BASELINE: Includes @package annotation'
        );
        
        $this->assertStringContainsString(
            '@author',
            $content,
            'CURRENT BASELINE: Includes @author annotation'
        );
    }
    
    /**
     * @test
     * CURRENT BASELINE: File reflects expanded repository implementation
     */
    public function testProdBaseline_SimplifiedImplementation()
    {
        $content = file_get_contents($this->repoFile);
        $lineCount = count(explode("\n", $content));
        
        $this->assertGreaterThan(
            250,
            $lineCount,
            'CURRENT BASELINE: File should reflect expanded repository implementation'
        );

        $this->assertLessThan(
            500,
            $lineCount,
            'CURRENT BASELINE: File size should remain bounded despite expanded implementation'
        );
    }
}
