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

    <?php
    // The dashboard has to survive a controller that has no metrics yet.
    $metrics = $metrics ?? [];
    $trends = $trends ?? [];
    $processing = $processing ?? ['avg_time' => 0.0, 'avg_memory' => 0, 'count' => 0];
    $listView = $listView ?? ['avg_time' => 0.0, 'avg_memory' => 0, 'count' => 0];
    ?>

    <div class="metrics-container">
        <div class="metric-card">
            <h3>Transaction Processing Performance</h3>
            <div>
                <p>
                    <span class="status-indicator <?= $processing['avg_time'] < 0.1 ? 'status-good' : ($processing['avg_time'] < 0.5 ? 'status-warning' : 'status-critical') ?>"></span>
                    Average Processing Time: <?= number_format($processing['avg_time'] * 1000, 2) ?> ms
                </p>
                <p>
                    <span class="status-indicator <?= $processing['avg_memory'] < 5242880 ? 'status-good' : ($processing['avg_memory'] < 10485760 ? 'status-warning' : 'status-critical') ?>"></span>
                    Memory Usage: <?= number_format($processing['avg_memory'] / 1024, 2) ?> KB
                </p>
                <p>Transactions Processed: <?= $processing['count'] ?></p>
            </div>
        </div>

        <div class="metric-card">
            <h3>List View Performance</h3>
            <div>
                <p>
                    <span class="status-indicator <?= $listView['avg_time'] < 0.1 ? 'status-good' : ($listView['avg_time'] < 0.5 ? 'status-warning' : 'status-critical') ?>"></span>
                    Average Load Time: <?= number_format($listView['avg_time'] * 1000, 2) ?> ms
                </p>
                <p>
                    <span class="status-indicator <?= $listView['avg_memory'] < 5242880 ? 'status-good' : ($listView['avg_memory'] < 10485760 ? 'status-warning' : 'status-critical') ?>"></span>
                    Memory Usage: <?= number_format($listView['avg_memory'] / 1024, 2) ?> KB
                </p>
                <p>Page Views: <?= $listView['count'] ?></p>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <canvas id="performanceChart"></canvas>
        <div class="trend-info">
            <h4>7-Day Performance Trend</h4>
            <?php
            $trendData = array_values($trends);
            $latestAvg = $trendData === [] ? 0.0 : (float) end($trendData)['avg_time'];
            $firstAvg = $trendData === [] ? 0.0 : (float) reset($trendData)['avg_time'];
            // Without a sample the improvement is unknown, not infinite.
            $improvement = $firstAvg > 0 ? (($firstAvg - $latestAvg) / $firstAvg) * 100 : 0.0;
            ?>
            <p>
                <?php if ($firstAvg > 0) { ?>
                Performance has <?= $improvement > 0 ? 'improved' : 'degraded' ?> by
                <?= number_format(abs($improvement), 1) ?>% over the last 7 days.
                <?php } else { ?>
                No performance samples for the last 7 days yet.
                <?php } ?>
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