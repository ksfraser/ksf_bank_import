<?php
$currentPath = $_SERVER['REQUEST_URI'];
?>
<nav class="admin-nav">
    <style>
        .admin-nav {
            background: #34495e;
            padding: 15px;
            margin-bottom: 20px;
        }
        .admin-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .admin-nav a:hover {
            background: #2c3e50;
        }
        .admin-nav a.active {
            background: #2980b9;
        }
    </style>
    <ul>
        <li>
            <a href="/admin/dashboard" 
               class="<?= strpos($currentPath, '/admin/dashboard') !== false ? 'active' : '' ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="/admin/performance" 
               class="<?= strpos($currentPath, '/admin/performance') !== false ? 'active' : '' ?>">
                Performance Report
            </a>
        </li>
        <li>
            <a href="/admin/metrics/anomalies" 
               class="<?= strpos($currentPath, '/admin/metrics/anomalies') !== false ? 'active' : '' ?>">
                Anomalies
            </a>
        </li>
        <li>
            <a href="/admin/metrics/export" 
               class="<?= strpos($currentPath, '/admin/metrics/export') !== false ? 'active' : '' ?>">
                Export Metrics
            </a>
        </li>
    </ul>
</nav>