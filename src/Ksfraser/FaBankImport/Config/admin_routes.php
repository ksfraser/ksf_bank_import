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