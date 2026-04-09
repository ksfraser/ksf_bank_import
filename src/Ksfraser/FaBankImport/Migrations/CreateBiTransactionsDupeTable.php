<?php

namespace Ksfraser\FaBankImport\Migrations;

use PDO;

/**
 * Migration 001: Create Duplicate Transaction Staging Table
 *
 * Creates the bi_transactions_dupe table for Phase-1 duplicate review system.
 * Includes audit table and dashboard view.
 *
 * @package Ksfraser\FaBankImport\Migrations
 * @since 2026-04-09
 */
class CreateBiTransactionsDupeTable implements MigrationInterface
{
    public function getVersion(): string
    {
        return '001_create_bi_transactions_dupe_table';
    }

    public function getDescription(): string
    {
        return 'Create bi_transactions_dupe table with audit trail for Phase-1 duplicate review system';
    }

    public function up(PDO $pdo): bool
    {
        try {
            $pdo->beginTransaction();

            // Create main duplicate staging table
            $pdo->exec($this->getMainTableSQL());

            // Create audit table for decision history
            $pdo->exec($this->getAuditTableSQL());

            // Create dashboard view
            $pdo->exec($this->getDashboardViewSQL());

            // Create migration tracking record
            $this->recordMigration($pdo);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Migration up failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function down(PDO $pdo): bool
    {
        try {
            $pdo->beginTransaction();

            // Drop in reverse order
            $pdo->exec("DROP VIEW IF EXISTS v_pending_duplicates");
            $pdo->exec("DROP TABLE IF EXISTS bi_transactions_dupe_audit");
            $pdo->exec("DROP TABLE IF EXISTS bi_transactions_dupe");

            // Remove migration tracking record
            $pdo->exec("DELETE FROM db_migrations WHERE version = ?", [$this->getVersion()]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \RuntimeException("Migration down failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function getMainTableSQL(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS bi_transactions_dupe (
    duplicate_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Core transaction fields
    transaction_code VARCHAR(50) NOT NULL,
    trans_date DATE NOT NULL,
    amount DECIMAL(19, 4) NOT NULL,
    counterparty_name VARCHAR(250),
    description TEXT,
    reference_number VARCHAR(100),
    bank_account_id INT NOT NULL,
    partner_type ENUM('SUPPLIER', 'CUSTOMER', 'BANK_TRANSFER', 'QUICK_ENTRY'),
    bank_code VARCHAR(20),
    
    -- Matching/detection fields
    matched_to_code VARCHAR(50),
    confidence_score DECIMAL(5, 2),
    match_type ENUM('EXACT_MATCH', 'FUZZY_MATCH', 'CODE_AND_AMOUNT'),
    
    -- Decision tracking
    decision_status ENUM('PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE') DEFAULT 'PENDING',
    decided_by VARCHAR(100),
    decided_at DATETIME NULL,
    reason TEXT,
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    UNIQUE KEY uq_duplicate_pair (transaction_code, matched_to_code),
    CONSTRAINT fk_bank_account FOREIGN KEY (bank_account_id) REFERENCES 0_bank_account(id) ON DELETE RESTRICT,
    
    -- Indexes for performance
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_trans_date (trans_date),
    INDEX idx_decision_status (decision_status),
    INDEX idx_bank_account_id (bank_account_id),
    INDEX idx_confidence_score (confidence_score),
    INDEX idx_review_dashboard (decision_status, confidence_score DESC, trans_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    private function getAuditTableSQL(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS bi_transactions_dupe_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    duplicate_id INT NOT NULL,
    decision_status VARCHAR(50),
    decided_by VARCHAR(100),
    reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_audit_duplicate FOREIGN KEY (duplicate_id) REFERENCES bi_transactions_dupe(duplicate_id) ON DELETE CASCADE,
    INDEX idx_duplicate_id (duplicate_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    private function getDashboardViewSQL(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW v_pending_duplicates AS
SELECT 
    duplicate_id,
    transaction_code,
    trans_date,
    amount,
    counterparty_name,
    matched_to_code,
    confidence_score,
    match_type,
    bank_account_id,
    partner_type,
    created_at
FROM bi_transactions_dupe
WHERE decision_status = 'PENDING'
ORDER BY confidence_score DESC, trans_date DESC;
SQL;
    }

    private function recordMigration(PDO $pdo): void
    {
        // Ensure migrations table exists
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS db_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    batch INT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        // Record this migration
        $stmt = $pdo->prepare("
            INSERT INTO db_migrations (version, description, batch)
            VALUES (?, ?, (SELECT COALESCE(MAX(batch), 0) + 1 FROM db_migrations))
        ");
        $stmt->execute([$this->getVersion(), $this->getDescription()]);
    }
}
