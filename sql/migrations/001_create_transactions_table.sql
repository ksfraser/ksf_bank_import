CREATE TABLE IF NOT EXISTS bi_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    valueTimestamp DATE NOT NULL,
    memo VARCHAR(255),
    transactionDC CHAR(1) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    our_account VARCHAR(50),
    ourBankAccountName VARCHAR(100),
    ourBankAccountCode VARCHAR(20),
    otherBankAccount VARCHAR(50),
    otherBankAccountName VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_transaction_dc (transactionDC),
    INDEX idx_value_timestamp (valueTimestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;