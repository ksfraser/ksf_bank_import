<?php

/**
 * Transaction Processor
 *
 * Coordinates transaction processing by delegating to appropriate handler classes.
 * Replaces the large switch statement in process_statements.php with Strategy pattern.
 *
 * @package    Ksfraser\FaBankImport
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;
use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Transaction Processor
 *
 * Single Responsibility: Route transactions to appropriate handlers
 * Open/Closed: Open for extension (add new handlers), closed for modification
 * Dependency Inversion: Depends on TransactionHandlerInterface abstraction
 *
 * Auto-discovers handlers in the Handlers/ directory on instantiation.
 */
class TransactionProcessor
{
    /**
     * Registered transaction handlers
     *
     * @var array<string, TransactionHandlerInterface>
     */
    protected array $handlers = [];

    /**
     * Constructor - Auto-discovers and registers handlers
     *
     * Scans the Handlers/ directory for classes implementing TransactionHandlerInterface
     * and automatically registers them. This eliminates manual registration in client code.
     *
     * @param array<TransactionHandlerInterface>|null $customHandlers Optional custom handlers for testing/override
     */
    public function __construct(?array $customHandlers = null)
    {
        if ($customHandlers !== null) {
            // Use provided handlers (useful for testing)
            foreach ($customHandlers as $handler) {
                $this->registerHandler($handler);
            }
        } else {
            // Auto-discover handlers from Handlers/ directory
            $this->discoverAndRegisterHandlers();
        }
    }

    /**
     * Discover and register all handler classes
     *
     * Scans Handlers/ directory for PHP files and automatically discovers
     * classes implementing TransactionHandlerInterface. This is true auto-discovery
     * - drop a new handler file in the directory and it's automatically registered.
     *
     * Uses fine-grained exception handling:
     * - HandlerDiscoveryException: Expected errors (missing deps, invalid constructor)
     * - ReflectionException: Reflection errors (class doesn't exist, can't be analyzed)
     * - Other exceptions: Unexpected errors (bubbled up for investigation)
     *
     * Excludes:
     * - Abstract classes (AbstractTransactionHandler)
     * - Interfaces (TransactionHandlerInterface)
     * - Handlers that don't implement TransactionHandlerInterface
     * - Handlers that can't be instantiated with ReferenceNumberService
     *
     * @return void
     * @throws \RuntimeException If unexpected error occurs during discovery
     */
    protected function discoverAndRegisterHandlers(): void
    {
        $handlersDir = __DIR__ . '/Handlers';
        
        if (!is_dir($handlersDir)) {
            return; // No handlers directory
        }

        // Create shared reference service for all handlers
        $referenceService = new ReferenceNumberService();

        // Scan directory for PHP files ending in "Handler.php"
        $files = glob($handlersDir . '/*Handler.php');
        
        if ($files === false) {
            return; // Error reading directory
        }

        foreach ($files as $file) {
            // Extract class name from filename (e.g., CustomerTransactionHandler.php -> CustomerTransactionHandler)
            $className = basename($file, '.php');
            
            // Skip abstract classes, interfaces, and known non-transaction handlers
            if (strpos($className, 'Abstract') === 0 || 
                strpos($className, 'Interface') !== false ||
                $className === 'ErrorHandler' ||
                $className === 'ProcessTransactionCommandHandler') {
                continue;
            }
            
            // Build fully qualified class name
            $fqcn = "Ksfraser\\FaBankImport\\Handlers\\{$className}";
            
            // Check if class exists
            if (!class_exists($fqcn)) {
                continue;
            }
            
            try {
                // Use reflection to check if class is instantiable (not abstract)
                $reflection = new ReflectionClass($fqcn);
                
                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    // Skip non-instantiable classes
                    continue;
                }
                
                // Try to instantiate with reference service
                $handler = new $fqcn($referenceService);
                
                // Only register if it implements TransactionHandlerInterface
                if ($handler instanceof TransactionHandlerInterface) {
                    $this->registerHandler($handler);
                }
                
            } catch (ReflectionException $e) {
                // Reflection failed (class analysis error) - skip this handler
                // This is expected for malformed classes
                continue;
                
            } catch (\ArgumentCountError $e) {
                // Constructor signature mismatch - handler expects different parameters
                // This is expected for handlers with custom constructors
                throw HandlerDiscoveryException::invalidConstructor(
                    $fqcn,
                    'wrong number of arguments',
                    $e
                );
                
            } catch (\TypeError $e) {
                // Type error during instantiation - wrong parameter types
                // This is expected for handlers with different type requirements
                throw HandlerDiscoveryException::invalidConstructor(
                    $fqcn,
                    'type mismatch',
                    $e
                );
                
            } catch (\Error $e) {
                // Missing dependency class (e.g., "Class 'Monolog\Logger' not found")
                if (strpos($e->getMessage(), 'not found') !== false) {
                    // Extract missing class name from error message
                    preg_match('/Class ["\']([^"\']+)["\']/', $e->getMessage(), $matches);
                    $missingClass = $matches[1] ?? 'unknown';
                    
                    throw HandlerDiscoveryException::missingDependency(
                        $fqcn,
                        $missingClass,
                        $e
                    );
                }
                
                // Other Error - this is unexpected, rethrow for investigation
                throw new \RuntimeException(
                    "Unexpected error discovering handler {$fqcn}: {$e->getMessage()}",
                    0,
                    $e
                );
                
            } catch (HandlerDiscoveryException $e) {
                // Expected discovery error - skip this handler gracefully
                // Could log this for debugging if needed
                continue;
                
            } catch (\Exception $e) {
                // Unexpected exception during instantiation - this should be investigated
                throw new \RuntimeException(
                    "Unexpected exception discovering handler {$fqcn}: {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Register a transaction handler for a specific partner type
     *
     * @param TransactionHandlerInterface $handler The handler to register
     * @return self For method chaining
     */
    public function registerHandler(TransactionHandlerInterface $handler): self
    {
        $this->handlers[$handler->getPartnerType()] = $handler;
        return $this;
    }

    /**
     * Process a transaction using the appropriate handler
     *
     * Orchestrates transaction processing with clear separation of concerns:
     * 1. Validates handler exists and can process this partner type
     * 2. Extracts form inputs (POST data) separate from database data
     * 3. Delegates processing to appropriate handler
     *
     * DATA FLOW ARCHITECTURE:
     * - $transaction: ALL database fields from bi_transactions table (already queried)
     *   Example: ['id' => 123, 'transactionAmount' => 100.00, 'transactionDC' => 'D',
     *            'valueTimestamp' => '2025-10-20', 'transactionTitle' => 'Payment',
     *            'status' => 0, 'fa_trans_type' => 0, 'fa_trans_no' => 0, ...]
     * 
     * - $transactionPostData: User's form inputs for THIS specific transaction
     *   Example: ['partnerId' => 5, 'invoice' => 'INV-001', 'comment' => 'Urgent', ...]
     *   Extracted by extractTransactionPostData() from field names like 'partnerId_123'
     *
     * This separation ensures handlers receive clean, focused data without coupling
     * to either the database schema or HTML form structure.
     *
     * @param string $partnerType Partner type code (SP, CU, QE, BT, MA, ZZ)
     * @param array $transaction Database row with ALL transaction fields from bi_transactions table
     * @param array $postData Complete $_POST array from form submission (all transactions)
     * @param int $transactionId Database transaction ID (used to extract specific form inputs)
     * @param string $collectionIds Comma-separated related transaction IDs (e.g., charges)
     * @param array $ourAccount Our bank account information ['id', 'bank_account_name', ...]
     * @return TransactionResult Processing result (success/error/warning)
     * @throws InvalidArgumentException If no handler registered for partner type
     */
    public function process(
        string $partnerType,
        array $transaction,
        array $postData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult {
        if (!isset($this->handlers[$partnerType])) {
            throw new InvalidArgumentException(
                "No handler registered for partner type: {$partnerType}"
            );
        }

        $handler = $this->handlers[$partnerType];

        // Check if handler can process this partner type
        if (!$handler->canProcess($partnerType)) {
            return TransactionResult::error(
                "Handler cannot process partner type: {$partnerType}"
            );
        }

        // Extract transaction-specific POST data
        // This decouples handlers from the full POST array structure
        $transactionPostData = $this->extractTransactionPostData($postData, $transactionId);

        return $handler->process(
            $transaction,
            $transactionPostData,  // Filtered data, not entire POST
            $transactionId,
            $collectionIds,
            $ourAccount
        );
    }

    /**
     * Extract transaction-specific user input from POST array
     *
     * IMPORTANT: This extracts FORM INPUTS ONLY, not database fields.
     * 
     * The $transaction parameter passed to process() already contains ALL database fields
     * (valueTimestamp, transactionAmount, transactionDC, transactionTitle, status, 
     * fa_trans_type, fa_trans_no, etc.) from the bi_transactions table query.
     * 
     * This method extracts the USER'S FORM SELECTIONS/INPUTS for a specific transaction:
     * - Which partner/customer/supplier they selected
     * - Invoice number they entered
     * - Comments they added
     * - Branch/location they selected
     *
     * This separation decouples handlers from POST structure and follows SRP:
     * - Database data comes via $transaction parameter
     * - Form inputs come via $transactionPostData parameter
     *
     * @param array $postData Full POST data array from form submission
     * @param int $transactionId Transaction ID (used to build field names like 'partnerId_123')
     * @return array Transaction-specific form inputs (not database fields)
     */
    private function extractTransactionPostData(array $postData, int $transactionId): array
    {
        return [
            'partnerId' => $postData['partnerId_' . $transactionId] ?? null,
            'invoice' => $postData['Invoice_' . $transactionId] ?? null,
            'comment' => $postData['comment_' . $transactionId] ?? null,
            'partnerDetailId' => $postData['partnerDetailId_' . $transactionId] ?? null,
        ];
    }

    /**
     * Check if a handler is registered for a partner type
     *
     * @param string $partnerType Partner type code
     * @return bool True if handler exists, false otherwise
     */
    public function hasHandler(string $partnerType): bool
    {
        return isset($this->handlers[$partnerType]);
    }

    /**
     * Get all registered partner types
     *
     * @return array<string> Array of partner type codes
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Get all registered handler instances.
     *
     * @return array<int, TransactionHandlerInterface>
     */
    public function getRegisteredHandlers(): array
    {
        return array_values($this->handlers);
    }

    /**
     * Get a specific handler by partner type
     *
     * @param string $partnerType Partner type code
     * @return TransactionHandlerInterface|null Handler or null if not found
     */
    public function getHandler(string $partnerType): ?TransactionHandlerInterface
    {
        return $this->handlers[$partnerType] ?? null;
    }
}
