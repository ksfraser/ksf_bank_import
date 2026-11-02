<!DOCTYPE html>
<html>
<head>
    <title>Performance Anomalies Report</title>
    <style>
        .anomaly-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .anomaly-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .high-severity { border-left: 4px solid #ff4444; }
        .medium-severity { border-left: 4px solid #ffbb33; }
        .low-severity { border-left: 4px solid #00C851; }
        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="anomaly-container">
        <h1>Performance Anomalies Report</h1>

        <div class="filters">
            <form method="GET" action="">
                <label>
                    Days to Analyze:
                    <input type="number" name="days" value="<?= htmlspecialchars($days) ?>" min="1" max="30">
                </label>
                <label>
                    Threshold:
                    <input type="number" name="threshold" value="<?= htmlspecialchars($threshold) ?>" min="1.5" step="0.1">
                </label>
                <button type="submit">Update</button>
            </form>
        </div>

        <?php if (empty($anomalies)): ?>
            <p>No anomalies detected in the specified time period.</p>
        <?php else: ?>
            <?php foreach ($anomalies as $anomaly): ?>
                <?php
                    $severityClass = 'low-severity';
                    if ($anomaly['deviation'] > 5) {
                        $severityClass = 'high-severity';
                    } elseif ($anomaly['deviation'] > 3) {
                        $severityClass = 'medium-severity';
                    }
                ?>
                <div class="anomaly-card <?= $severityClass ?>">
                    <h3><?= htmlspecialchars($anomaly['metric']) ?></h3>
                    <p>Deviation: <?= number_format($anomaly['deviation'] * 100, 1) ?>% above average</p>
                    <p>Average Time: <?= number_format($anomaly['avg_time'] * 1000, 2) ?> ms</p>
                    <p>Maximum Time: <?= number_format($anomaly['max_time'] * 1000, 2) ?> ms</p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>