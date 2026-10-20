<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use Ksfraser\FaBankImport\Services\TransactionLogger;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Bootstrap.php; coverage: docs/TestCases/ (file rollup)
 */
class Bootstrap
{
    private $container;
    private $config;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->config = Config::getInstance();
        $this->setupEventListeners();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: setupEventListeners */
    private function setupEventListeners(): void
    {
        $eventDispatcher = $this->container->getEventDispatcher();
        $logger = new TransactionLogger();

        // Register event listeners
        $eventDispatcher->addListener(
            TransactionProcessedEvent::class,
            [$logger, 'logTransactionProcessed']
        );
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: run */
    public function run(): void
    {
        // Initialize error handling
        $this->setupErrorHandling();

        // Verify database connection
        $this->verifyDatabaseConnection();

        // Set up logging directory
        $this->setupLogging();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: setupErrorHandling */
    private function setupErrorHandling(): void
    {
        error_reporting(E_ALL);
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: verifyDatabaseConnection */
    private function verifyDatabaseConnection(): void
    {
        try {
            // Assuming db_connect is your database connection function
            db_connect(
                $this->config->get('db.host'),
                $this->config->get('db.name'),
                $this->config->get('db.user'),
                $this->config->get('db.pass')
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: setupLogging */
    private function setupLogging(): void
    {
        $logPath = $this->config->get('logging.path');
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
    }
}