 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */
<?php

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
