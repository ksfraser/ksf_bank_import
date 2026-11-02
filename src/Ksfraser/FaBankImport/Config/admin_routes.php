<?php
/**
 * @BABOK RTM: FR-002 / FR-051 / ARCH-001; file: Config/admin_routes.php; coverage: docs/TestCases/ (file rollup)
 */

return [
    'admin/dashboard' => [
        'controller' => 'AdminController',
        'action' => 'dashboard',
        'middleware' => ['auth', 'admin']
    ],
    'admin/performance' => [
        'controller' => 'AdminController',
        'action' => 'performanceReport',
        'middleware' => ['auth', 'admin']
    ],
    'admin/metrics/export' => [
        'controller' => 'AdminController',
        'action' => 'exportMetrics',
        'middleware' => ['auth', 'admin']
    ],
    'admin/metrics/anomalies' => [
        'controller' => 'AdminController',
        'action' => 'anomalyReport',
        'middleware' => ['auth', 'admin']
    ]
];