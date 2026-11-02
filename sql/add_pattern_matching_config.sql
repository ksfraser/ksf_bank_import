-- Add Pattern Matching Configuration
-- Date: 2025-10-20
-- Purpose: Add keyword clustering factor setting for partner pattern matching

INSERT INTO `0_bi_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_system`) VALUES
('pattern_matching.keyword_clustering_factor', '0.2', 'float', 'Clustering bonus multiplier per additional keyword (0.1=conservative, 0.2=balanced, 0.3=aggressive)', 'pattern_matching', 0),
('pattern_matching.min_confidence_threshold', '30', 'integer', 'Minimum confidence percentage to auto-suggest partner (0-100)', 'pattern_matching', 0),
('pattern_matching.max_suggestions', '5', 'integer', 'Maximum number of partner suggestions to return', 'pattern_matching', 0),
('pattern_matching.min_keyword_length', '3', 'integer', 'Minimum keyword length to index (characters)', 'pattern_matching', 0);

