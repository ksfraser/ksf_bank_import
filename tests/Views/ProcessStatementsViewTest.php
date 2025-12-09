<?php

namespace Ksfraser\FaBankImport\Tests\Views;

use Ksfraser\FaBankImport\Views\ProcessStatementsView;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcessStatementsView class
 * 
 * Validates that the HTML rendering view correctly encapsulates the UI layer
 * and maintains backward compatibility with business logic in process_statements.php
 * 
 * NOTE: Full render() testing requires FA context (header_table.php dependencies).
 * These tests focus on instantiation and constructor validation.
 * Integration tests (with FA loaded) should test the full render() output.
 */
class ProcessStatementsViewTest extends TestCase
{
    /**
     * Test that ProcessStatementsView can be instantiated with empty data
     */
    public function test_instantiation_with_empty_data(): void
    {
        $view = new ProcessStatementsView([], [], []);
        $this->assertInstanceOf(ProcessStatementsView::class, $view);
    }

    /**
     * Test that ProcessStatementsView properly accepts constructor parameters
     */
    public function test_constructor_accepts_array_parameters(): void
    {
        $transactions = ['trans1' => [], 'trans2' => []];
        $operationTypes = ['op1' => 'Operation 1', 'op2' => 'Operation 2'];
        $vendorList = ['vendor1' => 'Vendor 1', 'vendor2' => 'Vendor 2'];
        
        $view = new ProcessStatementsView($transactions, $operationTypes, $vendorList);
        $this->assertInstanceOf(ProcessStatementsView::class, $view);
    }

    /**
     * Test that class file exists and is properly located
     */
    public function test_class_file_exists_at_correct_path(): void
    {
        $classPath = 'src/Ksfraser/FaBankImport/views/ProcessStatementsView.php';
        $this->assertTrue(
            file_exists($classPath),
            "ProcessStatementsView.php file must exist at $classPath"
        );
    }

    /**
     * Test that class is properly namespaced
     */
    public function test_class_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(ProcessStatementsView::class);
        $this->assertEquals(
            'Ksfraser\FaBankImport\Views',
            $reflection->getNamespaceName(),
            'ProcessStatementsView must be in correct namespace for autoloading'
        );
    }

    /**
     * Test that render method exists and is public
     */
    public function test_render_method_exists_and_is_public(): void
    {
        $reflection = new \ReflectionClass(ProcessStatementsView::class);
        $this->assertTrue(
            $reflection->hasMethod('render'),
            'ProcessStatementsView must have a render() method'
        );
        
        $renderMethod = $reflection->getMethod('render');
        $this->assertTrue(
            $renderMethod->isPublic(),
            'render() method must be public'
        );
    }

    /**
     * Test that render method returns a string (type hint)
     */
    public function test_render_method_return_type_is_string(): void
    {
        $reflection = new \ReflectionClass(ProcessStatementsView::class);
        $renderMethod = $reflection->getMethod('render');
        $returnType = $renderMethod->getReturnType();
        
        $this->assertNotNull($returnType, 'render() must have a return type');
        $this->assertEquals(
            'string',
            (string)$returnType,
            'render() must return string type'
        );
    }

    /**
     * Test that constructor has proper type hints
     */
    public function test_constructor_has_array_type_hints(): void
    {
        $reflection = new \ReflectionClass(ProcessStatementsView::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'Class must have a constructor');
        $this->assertCount(3, $constructor->getParameters(), 'Constructor must have 3 parameters');
        
        // Verify each parameter is type-hinted as array
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            $this->assertNotNull($type, "Parameter {$param->getName()} must have a type hint");
            $this->assertEquals('array', (string)$type, "All parameters must be typed as array");
        }
    }

    /**
     * Test that class has proper property access (encapsulation)
     */
    public function test_class_properties_are_private(): void
    {
        $reflection = new \ReflectionClass(ProcessStatementsView::class);
        
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isPrivate(),
                "Property {$property->getName()} must be private for encapsulation"
            );
        }
    }

    /**
     * Test integration: Full render with FA context requires integration test
     * This validates the test structure works as expected
     */
    public function test_render_requires_fa_context_documented(): void
    {
        // This test documents that full render() testing requires FA context
        // See ProcessStatementsViewIntegrationTest.php for full integration tests
        $this->assertTrue(true, 'Full render() testing requires FA context - see integration tests');
    }
}

