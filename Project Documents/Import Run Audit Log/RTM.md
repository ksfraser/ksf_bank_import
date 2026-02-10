# Requirements Traceability Matrix (RTM) — Import Run Audit Log

| Business Requirement | Functional Requirement(s) | Use Case | UAT Test(s) | Implementation Anchor |
|---|---|---|---|---|
| BR-LOG-001 | FR-LOG-001, FR-LOG-002, FR-LOG-003, FR-LOG-004, FR-LOG-005 | UC-LOG-001 | UAT-LOG-001, UAT-LOG-002, UAT-LOG-003, UAT-LOG-004 | [import_statements.php](../../import_statements.php), [src/Ksfraser/FaBankImport/Service/ImportRunLogger.php](../../src/Ksfraser/FaBankImport/Service/ImportRunLogger.php) |
| BR-LOG-002 | FR-LOG-006, FR-LOG-007 | UC-LOG-001 | UAT-LOG-005, UAT-LOG-006 | [view_import_logs.php](../../view_import_logs.php), [hooks.php](../../hooks.php) |
| BR-LOG-003 | FR-LOG-001 | UC-LOG-001 | UAT-LOG-001 | [import_statements.php](../../import_statements.php) |
