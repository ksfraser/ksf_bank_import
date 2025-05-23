<!DOCTYPE html>
<html>
<head>
    <title>Performance Report</title>
    <style>
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .export-button {
            float: right;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <h1>Performance Report</h1>
        
        <div class="filters">
            <form method="GET" action="">
                <label>
                    Start Date:
                    <input type="date" name="start_date" value="<?= htmlspecialchars($date_range['start']) ?>">
                </label>
                <label>
                    End Date:
                    <input type="date" name="end_date" value="<?= htmlspecialchars($date_range['end']) ?>">
                </label>
                <button type="submit">Apply Filter</button>
                <button type="button" class="export-button" onclick="exportReport()">Export JSON</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Average Time (ms)</th>
                    <th>Memory Usage (KB)</th>
                    <th>Request Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Transaction Processing</td>
                    <td><?= number_format($metrics['avg_time'] * 1000, 2) ?></td>
                    <td><?= number_format($metrics['avg_memory'] / 1024, 2) ?></td>
                    <td><?= $metrics['count'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
    function exportReport() {
        const params = new URLSearchParams(window.location.search);
        params.set('format', 'json');
        window.location.href = window.location.pathname + '?' + params.toString();
    }
    </script>
</body>
</html>