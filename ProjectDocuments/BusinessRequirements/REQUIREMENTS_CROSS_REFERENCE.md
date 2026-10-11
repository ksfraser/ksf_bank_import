# Requirements Cross-Reference (New Naming Convention)

## Naming Rules
- BR-XXX / BR-XXX-YYY / BR-XXX-YYY-ZZZ
- FR-XXX = BR-XXX; FR-XXX-YYY = BR-XXX-YYY; FR-XXX-YYY-ZZZ = BR-XXX-YYY-ZZZ
- ARCH-XXX designs BR-XXX; ARCH-XXX-YYY designs BR-XXX-YYY
- UC-XXX-YYY-ZZZ (use case)
- UAT-XXX / UAT-XXX-YYY

## Mapping (Verified from Existing Docs)

| BR | FR Match | ARCH Design | UC/UAT Related |
|---|---|---|---|
| BR-001 (Auto Pair Detection) | FR-001, FR-002, FR-003 | ARCH-001 | UC-001, UAT-001 |
| BR-002 (Direction Detection) | FR-004, FR-005 | ARCH-002 | UC-002, UAT-002 |
| BR-003 (Bank Transfer Creation) | FR-006, FR-007, FR-008 | ARCH-003 | UC-003, UAT-003 |
| BR-004 (Visual Indicators) | FR-009, FR-010 | ARCH-004 | UC-004, UAT-004 |
| BR-005 (Performance/Caching) | FR-012, FR-013 | ARCH-005 | UAT-005 |
| BR-006 (Availability) | NFR-004 | ARCH-006 | UAT-006 |
| BR-007 (Performance Standards) | NFR-001, NFR-002, NFR-003 | ARCH-007 | UAT-007 |
| BR-008 (Data Integrity) | NFR-006, FR-008 | ARCH-008 | UAT-008 |
| BR-009 (Usability) | NFR-007, FR-009 | ARCH-009 | UAT-009 |
| BR-010 (Compatibility) | NFR-011, NFR-012, NFR-013 | ARCH-010 | UAT-010 |

## File Locations (Refactored)
- docs/BusinessRequirements/BR-001-...md
- docs/FunctionalRequirements/FR-001-...md
- docs/Architecture/ARCH-001-...md
- docs/TestCases/TC-001-...md (derived from FR)
- docs/UAT/UAT-001-...md
