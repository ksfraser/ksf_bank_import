<?php

namespace Ksfraser\Application;

use Ksfraser\Application\Config\Config;
use Ksfraser\Application\Events\TransactionProcessedEvent;
use Ksfraser\Application\Services\TransactionLogger;

class Bootstrap
{
    private $container;
    private $config;

    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->config = Config::getInstance();
        $this->setupEventListeners();
    }

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

    public function run(): void
    {
        // Initialize error handling
        $this->setupErrorHandling();

        // Verify database connection
        $this->verifyDatabaseConnection();

        // Set up logging directory
        $this->setupLogging();
    }

    private function setupErrorHandling(): void
    {
        error_reporting(E_ALL);
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

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

    private function setupLogging(): void
    {
        $logPath = $this->config->get('logging.path');
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
    }
}
