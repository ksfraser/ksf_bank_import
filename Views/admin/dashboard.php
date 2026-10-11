<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Performance Monitoring</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; }
        .metrics-container { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            padding: 20px;
        }
        .metric-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            min-width: 300px;
        }
        .chart-container { 
            margin: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .trend-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-good { background: #00C851; }
        .status-warning { background: #ffbb33; }
        .status-critical { background: #ff4444; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../admin/_nav.php'; ?>

    <div class="metrics-container">
        <div class="metric-card">
            <h3>Transaction Processing Performance</h3>
            <div>
                <p>
                    <span class="status-indicator <?= $metrics['transaction_processing']['avg_time'] < 0.1 ? 'status-good' : ($metrics['transaction_processing']['avg_time'] < 0.5 ? 'status-warning' : 'status-critical') ?>"></span>
                    Average Processing Time: <?= number_format($metrics['transaction_processing']['avg_time'] * 1000, 2) ?> ms
                </p>
                <p>
                    <span class="status-indicator <?= $metrics['transaction_processing']['avg_memory'] < 5242880 ? 'status-good' : ($metrics['transaction_processing']['avg_memory'] < 10485760 ? 'status-warning' : 'status-critical') ?>"></span>
                    Memory Usage: <?= number_format($metrics['transaction_processing']['avg_memory'] / 1024, 2) ?> KB
                </p>
                <p>Transactions Processed: <?= $metrics['transaction_processing']['count'] ?></p>
            </div>
        </div>

        <div class="metric-card">
            <h3>List View Performance</h3>
            <div>
                <p>
                    <span class="status-indicator <?= $metrics['list_view']['avg_time'] < 0.1 ? 'status-good' : ($metrics['list_view']['avg_time'] < 0.5 ? 'status-warning' : 'status-critical') ?>"></span>
                    Average Load Time: <?= number_format($metrics['list_view']['avg_time'] * 1000, 2) ?> ms
                </p>
                <p>
                    <span class="status-indicator <?= $metrics['list_view']['avg_memory'] < 5242880 ? 'status-good' : ($metrics['list_view']['avg_memory'] < 10485760 ? 'status-warning' : 'status-critical') ?>"></span>
                    Memory Usage: <?= number_format($metrics['list_view']['avg_memory'] / 1024, 2) ?> KB
                </p>
                <p>Page Views: <?= $metrics['list_view']['count'] ?></p>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <canvas id="performanceChart"></canvas>
        <div class="trend-info">
            <h4>7-Day Performance Trend</h4>
            <?php
            $trendData = array_values($trends);
            $latestAvg = end($trendData)['avg_time'];
            $firstAvg = reset($trendData)['avg_time'];
            $improvement = (($firstAvg - $latestAvg) / $firstAvg) * 100;
            ?>
            <p>
                Performance has <?= $improvement > 0 ? 'improved' : 'degraded' ?> by 
                <?= number_format(abs($improvement), 1) ?>% over the last 7 days.
            </p>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($trends)) ?>,
            datasets: [{
                label: 'Processing Time (ms)',
                data: <?= json_encode(array_map(function($metric) {
                    return $metric['avg_time'] * 1000;
                }, $trends)) ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Average Processing Time (ms)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
    </script>
</body>
</html>