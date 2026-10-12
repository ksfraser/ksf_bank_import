 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */
<?php

return [
    'logging' => [
        'path' => __DIR__ . '/../../../../logs',
        'retention_days' => 30,
        'metrics_file_pattern' => 'performance_*.log'
    ],
    'monitoring' => [
        'enabled' => true,
        'anomaly_threshold' => 2.0,
        'slow_transaction_threshold' => 1000, // milliseconds
        'high_memory_threshold' => 10 * 1024 * 1024, // 10MB
        'sampling_rate' => 1.0 // Monitor 100% of requests
    ],
    'alerts' => [
        'enabled' => true,
        'email' => [
            'to' => 'kevin@ksfraser.com',
            'from' => 'kevin@ksfraser.com',
            'subject_prefix' => '[APP Monitor]'
        ]
    ]
];
