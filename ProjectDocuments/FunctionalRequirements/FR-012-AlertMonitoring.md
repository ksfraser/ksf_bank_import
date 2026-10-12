# FR-012: Alert Monitoring Service

## Requirement
The system SHALL provide configurable alert monitoring based on aggregated metrics, with optional email/status notifications.

## Sub-Requirements
- FR-012-001: Aggregate metrics over configurable time windows (`date('Y-m-d', strtotime('-1 hour'))` to `date('Y-m-d H:i:s')`).
- FR-012-002: Check `alerts.enabled` config before sending.
- FR-012-003: Singleton instance pattern (`getInstance()`) for service access.

## Related
- ARCH-001 (Service-Oriented Design / Singleton pattern)
- BR-007 (Performance Standards / Metrics)
- BR-011 (Scoring / Alert thresholds based on confidence)

## BABOK References
- `ProjectDocuments/BusinessRequirements/BR-011-KEYWORD_CLUSTERING.md`
- `docs/REQUIREMENTS_SPECIFICATION.md` (NFR-001 / Performance)
- `AGENTS.md` / `AGENTS_ARCH.md` (Service conventions)

## Code Module
- `Services/AlertService.php`
- `Services/MetricsAggregator.php`
- `Config/Config.php`
