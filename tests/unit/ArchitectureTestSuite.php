<?php

/**
 * Comprehensive Architecture Test Suite
 *
 * Tests abstract types, inheritance hierarchies, and namespace consistency
 * across the entire Ksfraser\FaBankImport codebase.
 */
class ArchitectureTestSuite
{
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->passed++;
            echo "✅ $message\n";
        } else {
            $this->failed++;
            $this->errors[] = $message;
            echo "❌ $message\n";
        }
    }

    private function assertTrue($condition, $message)
    {
        $this->assert($condition === true, $message);
    }

    private function assertFalse($condition, $message)
    {
        $this->assert($condition === false, $message);
    }

    private function assertEquals($expected, $actual, $message)
    {
        $this->assert($expected === $actual, $message . " (expected: $expected, actual: $actual)");
    }

    private function assertContains($needle, $haystack, $message)
    {
        $this->assert(in_array($needle, $haystack), $message);
    }

    private function assertInstanceOf($expectedClass, $object, $message)
    {
        $this->assert($object instanceof $expectedClass, $message);
    }

    // ===== ABSTRACT CLASS TESTS =====

    public function testAbstractControllerIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Controllers\AbstractController::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractController should be abstract');
    }

    public function testAbstractPartnerTypeIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\PartnerTypes\AbstractPartnerType::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractPartnerType should be abstract');
    }

    public function testAbstractQfxParserIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Parsers\AbstractQfxParser::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractQfxParser should be abstract');
    }

    public function testAbstractTransactionHandlerIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractTransactionHandler should be abstract');
    }

    public function testAbstractRepositoryIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Repositories\AbstractRepository::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractRepository should be abstract');
    }

    public function testAbstractThirdPartyTransactionRepositoryIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Repository\AbstractThirdPartyTransactionRepository::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractThirdPartyTransactionRepository should be abstract');
    }

    // ===== INHERITANCE HIERARCHY TESTS =====

    public function testControllerInheritance()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Controllers\BankImportController::class);
        $parentClass = $reflection->getParentClass();

        $this->assertFalse($parentClass === false, 'BankImportController should have a parent class');
        $this->assertEquals(\Ksfraser\FaBankImport\Controllers\AbstractController::class, $parentClass->getName(), 'BankImportController should extend AbstractController');
    }

    public function testProcessStatementsControllerInheritance()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\Controllers\ProcessStatementsController::class);
        $parentClass = $reflection->getParentClass();

        $this->assertFalse($parentClass === false, 'ProcessStatementsController should have a parent class');
        $this->assertEquals(\Ksfraser\FaBankImport\Controllers\AbstractController::class, $parentClass->getName(), 'ProcessStatementsController should extend AbstractController');
    }

    public function testPartnerTypeInheritance()
    {
        $partnerTypes = [
            \Ksfraser\PartnerTypes\SupplierPartnerType::class,
            \Ksfraser\PartnerTypes\CustomerPartnerType::class,
            \Ksfraser\PartnerTypes\BankTransferPartnerType::class,
            \Ksfraser\PartnerTypes\QuickEntryPartnerType::class,
            \Ksfraser\PartnerTypes\ManualSettlementPartnerType::class,
            \Ksfraser\PartnerTypes\MatchedPartnerType::class,
            \Ksfraser\PartnerTypes\UnknownPartnerType::class,
        ];

        foreach ($partnerTypes as $partnerTypeClass) {
            $reflection = new ReflectionClass($partnerTypeClass);
            $parentClass = $reflection->getParentClass();

            $this->assertFalse($parentClass === false, "$partnerTypeClass should have a parent class");
            $this->assertEquals(\Ksfraser\PartnerTypes\AbstractPartnerType::class, $parentClass->getName(),
                "$partnerTypeClass should extend AbstractPartnerType");
        }
    }

    public function testQfxParserInheritance()
    {
        $parserClasses = [
            \Ksfraser\FaBankImport\Parsers\CibcQfxParser::class,
            \Ksfraser\FaBankImport\Parsers\ManuQfxParser::class,
            \Ksfraser\FaBankImport\Parsers\PcmcQfxParser::class,
        ];

        foreach ($parserClasses as $parserClass) {
            $reflection = new ReflectionClass($parserClass);
            $parentClass = $reflection->getParentClass();

            $this->assertFalse($parentClass === false, "$parserClass should have a parent class");
            $this->assertEquals(\Ksfraser\FaBankImport\Parsers\AbstractQfxParser::class, $parentClass->getName(),
                "$parserClass should extend AbstractQfxParser");
        }
    }

    // public function testHtmlElementInheritance()
    // {
    //     $htmlClasses = [
    //         \Ksfraser\FaBankImport\Views\TransType::class,
    //         \Ksfraser\FaBankImport\Views\TransTitle::class,
    //         \Ksfraser\FaBankImport\Views\OurBankAccount::class,
    //         \Ksfraser\FaBankImport\Views\OtherBankAccount::class,
    //         \Ksfraser\FaBankImport\Views\AmountCharges::class,
    //     ];

    //     foreach ($htmlClasses as $htmlClass) {
    //         $reflection = new ReflectionClass($htmlClass);
    //         $parentClass = $reflection->getParentClass();

    //         $this->assertFalse($parentClass === false, "$htmlClass should have a parent class");
    //         $this->assertEquals(\Ksfraser\FaBankImport\Views\LabelRowBase::class, $parentClass->getName(),
    //             "$htmlClass should extend LabelRowBase");
    //     }
    // }

    // ===== NAMESPACE CONSISTENCY TESTS =====

    public function testKsfraserFaBankImportNamespaceConsistency()
    {
        $classes = [
            \Ksfraser\FaBankImport\SquareTransaction::class,
            \Ksfraser\FaBankImport\ThirdPartyTransaction::class,
            \Ksfraser\FaBankImport\ThirdPartyTransactionInterface::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertEquals('Ksfraser\\FaBankImport', $reflection->getNamespaceName(),
                "$class should be in Ksfraser\\FaBankImport namespace");
        }
    }

    public function testKsfraserPartnerTypesNamespaceConsistency()
    {
        $classes = [
            \Ksfraser\PartnerTypes\AbstractPartnerType::class,
            \Ksfraser\PartnerTypes\SupplierPartnerType::class,
            \Ksfraser\PartnerTypes\CustomerPartnerType::class,
            \Ksfraser\PartnerTypes\BankTransferPartnerType::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertEquals('Ksfraser\\PartnerTypes', $reflection->getNamespaceName(),
                "$class should be in Ksfraser\\PartnerTypes namespace");
        }
    }

    public function testKsfraserHtmlNamespaceConsistency()
    {
        $classes = [
            \Ksfraser\HTML\Composites\LabelRowBase::class,
        ];

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertTrue(strpos($reflection->getNamespaceName(), 'Ksfraser') === 0,
                "$class should be in a Ksfraser namespace");
        }
    }

    // ===== INTERFACE IMPLEMENTATION TESTS =====

    public function testPartnerTypeInterfaceImplementation()
    {
        $partnerTypes = [
            \Ksfraser\PartnerTypes\SupplierPartnerType::class,
            \Ksfraser\PartnerTypes\CustomerPartnerType::class,
            \Ksfraser\PartnerTypes\BankTransferPartnerType::class,
        ];

        foreach ($partnerTypes as $partnerTypeClass) {
            $interfaces = class_implements($partnerTypeClass);
            $this->assertContains(\Ksfraser\PartnerTypes\PartnerTypeInterface::class, $interfaces,
                "$partnerTypeClass should implement PartnerTypeInterface");
        }
    }

    // public function testHtmlElementInterfaceImplementation()
    // {
    //     $htmlClasses = [
    //         \Ksfraser\HTML\Composites\LabelRowBase::class,
    //         \Ksfraser\FaBankImport\Views\TransType::class,
    //     ];

    //     foreach ($htmlClasses as $htmlClass) {
    //         $interfaces = class_implements($htmlClass);
    //         $this->assertContains(\Ksfraser\HTML\HtmlElementInterface::class, $interfaces,
    //             "$htmlClass should implement HtmlElementInterface");
    //     }
    // }

    // ===== BACKWARD COMPATIBILITY TESTS =====

    public function testLegacyNamespaceAliases()
    {
        // Test that legacy Models namespace still works
        $this->assertTrue(class_exists('Models\\SquareTransaction'), 'Models\\SquareTransaction should exist');
        $this->assertTrue(class_exists('Models\\ThirdPartyTransaction'), 'Models\\ThirdPartyTransaction should exist');
        $this->assertTrue(interface_exists('Models\\ThirdPartyTransactionInterface'), 'Models\\ThirdPartyTransactionInterface should exist');

        // Test that legacy classes are identical to new ones
        $newInstance = new \Ksfraser\FaBankImport\SquareTransaction();
        $legacyInstance = new \Models\SquareTransaction();

        $this->assertEquals(get_class($newInstance), get_class($legacyInstance), 'Legacy and new classes should be identical');
    }

    public function runAllTests()
    {
        echo "Running Architecture Test Suite...\n\n";

        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                try {
                    $this->$method();
                } catch (Exception $e) {
                    $this->errors[] = "$method failed: " . $e->getMessage();
                    $this->failed++;
                }
            }
        }

        echo "\n📊 Test Results: {$this->passed} passed, {$this->failed} failed\n";

        if (!empty($this->errors)) {
            echo "\n❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }

        return $this->failed === 0;
    }
}