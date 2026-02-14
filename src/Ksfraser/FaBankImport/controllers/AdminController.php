<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AdminController [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AdminController.
 */
namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Services\PerformanceMonitor;
use Ksfraser\FaBankImport\Services\MetricsAggregator;
use Ksfraser\FaBankImport\Config\Config;

class AdminController extends AbstractController
{
    private $monitor;
    private $aggregator;

    public function __construct()
    {
        parent::__construct();
        $this->monitor = PerformanceMonitor::getInstance();
        $config = Config::getInstance();
        $this->aggregator = new MetricsAggregator($config->get('logging.path'));
    }

    public function dashboard(): void
    {
        $metrics = [
            'transaction_processing' => $this->monitor->getAverageMetrics('process_transaction', 60),
            'list_view' => $this->monitor->getAverageMetrics('list_transactions', 60)
        ];

        // Get historical trends
        $trends = $this->aggregator->getHistoricalTrends('process_transaction', 7);

        $this->render('admin/dashboard', [
            'metrics' => $metrics,
            'trends' => $trends
        ]);
    }

    public function performanceReport(): void
    {
        $startDate = $this->request->getQuery('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->getQuery('end_date', date('Y-m-d'));

        $metrics = $this->aggregator->aggregateMetrics($startDate, $endDate);
        $anomalies = $this->aggregator->detectPerformanceAnomalies($metrics);

        $data = [
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'metrics' => $metrics,
            'anomalies' => $anomalies
        ];

        if ($this->request->getQuery('format') === 'json') {
            $this->json($data);
            return;
        }

        $this->render('admin/performance_report', $data);
    }

    public function exportMetrics(): void
    {
        $startDate = $this->request->getQuery('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->getQuery('end_date', date('Y-m-d'));

        $metrics = $this->aggregator->aggregateMetrics($startDate, $endDate);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="performance_metrics.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Metric', 'Count', 'Avg Time (ms)', 'Avg Memory (KB)', 'Max Time (ms)', 'Max Memory (KB)']);

        foreach ($metrics as $name => $metric) {
            fputcsv($output, [
                $name,
                $metric['count'],
                number_format($metric['avg_time'] * 1000, 2),
                number_format($metric['avg_memory'] / 1024, 2),
                number_format($metric['max_time'] * 1000, 2),
                number_format($metric['max_memory'] / 1024, 2)
            ]);
        }

        fclose($output);
    }

    public function anomalyReport(): void
    {
        $days = (int)$this->request->getQuery('days', '7');
        $threshold = (float)$this->request->getQuery('threshold', '2.0');

        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $metrics = $this->aggregator->aggregateMetrics($startDate, $endDate);
        $anomalies = $this->aggregator->detectPerformanceAnomalies($metrics, $threshold);

        if ($this->request->getQuery('format') === 'json') {
            $this->json(['anomalies' => $anomalies]);
            return;
        }

        $this->render('admin/anomaly_report', [
            'anomalies' => $anomalies,
            'days' => $days,
            'threshold' => $threshold
        ]);
    }
}