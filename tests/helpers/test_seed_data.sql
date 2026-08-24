-- Test seed data for Bank Import integration tests
-- Includes minimal bank accounts, partners, transactions, and config

INSERT IGNORE INTO `0_bi_config` (`config_key`, `config_value`, `config_type`, `category`) VALUES
('bank_import_trans_ref_logging', '1', 'boolean', 'general'),
('bank_import_trans_ref_account', '0000', 'string', 'general');
