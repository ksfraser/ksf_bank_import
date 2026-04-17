<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Exception;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Exception\PartnerNotFoundException;
use Ksfraser\FaBankImport\Exception\PartnerValidationException;
use Ksfraser\FaBankImport\Exception\PartnerPersistenceException;
use Ksfraser\FaBankImport\Exception\TrainingException;
use Ksfraser\FaBankImport\Exception\PartnerException;

class ExceptionTest extends TestCase
{
    /**
     * Test PartnerNotFoundException factory methods
     */
    public function testPartnerNotFoundExceptionByIdFactory(): void
    {
        $exc = PartnerNotFoundException::byId(42);
        
        $this->assertInstanceOf(PartnerNotFoundException::class, $exc);
        $this->assertStringContainsString('42', $exc->getMessage());
    }

    /**
     * Test PartnerNotFoundException by name
     */
    public function testPartnerNotFoundExceptionByNameFactory(): void
    {
        $exc = PartnerNotFoundException::byName('ABC Corp');
        
        $this->assertInstanceOf(PartnerNotFoundException::class, $exc);
        $this->assertStringContainsString('ABC Corp', $exc->getMessage());
    }

    /**
     * Test PartnerValidationException factory methods
     */
    public function testPartnerValidationExceptionEmptyName(): void
    {
        $exc = PartnerValidationException::emptyName();
        
        $this->assertInstanceOf(PartnerValidationException::class, $exc);
        $this->assertStringContainsString('empty', strtolower($exc->getMessage()));
    }

    /**
     * Test PartnerValidationException for occurrence count
     */
    public function testPartnerValidationExceptionInvalidOccurrenceCount(): void
    {
        $exc = PartnerValidationException::invalidOccurrenceCount(-5);
        
        $this->assertInstanceOf(PartnerValidationException::class, $exc);
        $this->assertStringContainsString('-5', $exc->getMessage());
    }

    /**
     * Test PartnerValidationException for name length
     */
    public function testPartnerValidationExceptionNameExceedsMaxLength(): void
    {
        $exc = PartnerValidationException::nameExceedsMaxLength(300);
        
        $this->assertInstanceOf(PartnerValidationException::class, $exc);
        $this->assertStringContainsString('300', $exc->getMessage());
    }

    /**
     * Test PartnerPersistenceException wraps throwable
     */
    public function testPartnerPersistenceExceptionFromThrowable(): void
    {
        $previous = new \Exception('Database connection failed');
        $exc = PartnerPersistenceException::fromThrowable($previous, 'INSERT');
        
        $this->assertInstanceOf(PartnerPersistenceException::class, $exc);
        $this->assertSame($previous, $exc->getPrevious());
        $this->assertStringContainsString('INSERT', $exc->getMessage());
        $this->assertStringContainsString('Database connection failed', $exc->getMessage());
    }

    /**
     * Test PartnerPersistenceException update without ID
     */
    public function testPartnerPersistenceExceptionUpdateWithoutId(): void
    {
        $exc = PartnerPersistenceException::updateWithoutId();
        
        $this->assertInstanceOf(PartnerPersistenceException::class, $exc);
        $this->assertStringContainsString('id', strtolower($exc->getMessage()));
    }

    /**
     * Test TrainingException database error
     */
    public function testTrainingExceptionDatabaseError(): void
    {
        $previous = new \Exception('Query timeout');
        $exc = TrainingException::databaseError($previous);
        
        $this->assertInstanceOf(TrainingException::class, $exc);
        $this->assertSame($previous, $exc->getPrevious());
        $this->assertStringContainsString('database', strtolower($exc->getMessage()));
    }

    /**
     * Test TrainingException no partners found
     */
    public function testTrainingExceptionNoPartnersFound(): void
    {
        $exc = TrainingException::noPartnersFound();
        
        $this->assertInstanceOf(TrainingException::class, $exc);
        $this->assertStringContainsString('no partners', strtolower($exc->getMessage()));
    }

    /**
     * Test all exceptions inherit from PartnerException
     */
    public function testAllPartnerExceptionsInheritBase(): void
    {
        $this->assertInstanceOf(PartnerException::class, new PartnerException('test'));
        $this->assertInstanceOf(PartnerException::class, PartnerNotFoundException::byId(1));
        $this->assertInstanceOf(PartnerException::class, PartnerValidationException::emptyName());
        $this->assertInstanceOf(PartnerException::class, PartnerPersistenceException::updateWithoutId());
        $this->assertInstanceOf(PartnerException::class, TrainingException::noPartnersFound());
    }
}
