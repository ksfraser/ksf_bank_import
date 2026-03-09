<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the refactored transaction classes to ensure PSR-4 compliance
 * and backward compatibility after splitting class.bi_transaction.php
 */
class TransactionRefactoringTest extends TestCase
{
    public function testNewNamespaceClassesExist()
    {
        $this->assertTrue(class_exists(\Ksfraser\FaBankImport\SquareTransaction::class));
        $this->assertTrue(class_exists(\Ksfraser\FaBankImport\ThirdPartyTransaction::class));
        $this->assertTrue(interface_exists(\Ksfraser\FaBankImport\ThirdPartyTransactionInterface::class));
    }

    public function testLegacyNamespaceAliasesExist()
    {
        $this->assertTrue(class_exists('Models\\SquareTransaction'));
        $this->assertTrue(class_exists('Models\\ThirdPartyTransaction'));
        $this->assertTrue(interface_exists('Models\\ThirdPartyTransactionInterface'));
    }

    public function testSquareTransactionInstantiation()
    {
        $instance = new \Ksfraser\FaBankImport\SquareTransaction();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\SquareTransaction::class, $instance);
    }

    public function testLegacySquareTransactionInstantiation()
    {
        $instance = new \Models\SquareTransaction();
        $this->assertInstanceOf(\Ksfraser\FaBankImport\SquareTransaction::class, $instance);
    }

    public function testThirdPartyTransactionIsAbstract()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\ThirdPartyTransaction::class);
        $this->assertTrue($reflection->isAbstract());
    }

    public function testLegacyThirdPartyTransactionIsAbstract()
    {
        $reflection = new ReflectionClass('Models\\ThirdPartyTransaction');
        $this->assertTrue($reflection->isAbstract());
    }

    public function testSquareTransactionExtendsThirdPartyTransaction()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\SquareTransaction::class);
        $parentClass = $reflection->getParentClass();

        $this->assertNotFalse($parentClass);
        $this->assertEquals(\Ksfraser\FaBankImport\ThirdPartyTransaction::class, $parentClass->getName());
    }

    public function testThirdPartyTransactionImplementsInterface()
    {
        $interfaces = class_implements(\Ksfraser\FaBankImport\ThirdPartyTransaction::class);
        $this->assertContains(\Ksfraser\FaBankImport\ThirdPartyTransactionInterface::class, $interfaces);
    }

    public function testLegacyAndNewClassesAreIdentical()
    {
        $newInstance = new \Ksfraser\FaBankImport\SquareTransaction();
        $legacyInstance = new \Models\SquareTransaction();

        $this->assertEquals(get_class($newInstance), get_class($legacyInstance));
    }

    public function testNamespaceConsistency()
    {
        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\SquareTransaction::class);
        $this->assertEquals('Ksfraser\\FaBankImport', $reflection->getNamespaceName());

        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\ThirdPartyTransaction::class);
        $this->assertEquals('Ksfraser\\FaBankImport', $reflection->getNamespaceName());

        $reflection = new ReflectionClass(\Ksfraser\FaBankImport\ThirdPartyTransactionInterface::class);
        $this->assertEquals('Ksfraser\\FaBankImport', $reflection->getNamespaceName());
    }
}