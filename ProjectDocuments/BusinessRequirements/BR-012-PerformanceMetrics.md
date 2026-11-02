# BR-012: Performance Metrics Aggregation

## Requirement
The system SHALL aggregate performance metrics over configurable time windows from log files for monitoring and alerting.

## Sub-Requirements
- BR-012-001: Read log files matching `METRICS_FILE_PATTERN` (`performance_*.log`).
- BR-012-002: Aggregate between `startDate` (e.g., `date('Y-m-d', strtotime('-1 hour'))`) and `endDate`.
- BR-012-003: Return aggregated array for `AlertService::checkAndSendAlerts()`.

## Related
- FR-012: Alert Monitoring Service
- ARCH-007: Performance Monitoring Architecture
- NFR-001: Response Time targets

## BABOK References
- `docs/REQUIREMENTS_SPECIFICATION.md` (FR-012, NFR-001)
- `ProjectDocuments/FunctionalRequirements/FR-012-AlertMonitoring.md`
