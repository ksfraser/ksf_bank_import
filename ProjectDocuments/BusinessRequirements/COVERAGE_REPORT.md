# Requirements & Code Coverage Report

## Tagged with @BABOK (verified against docs)
- TransactionService.php → BR-001 / FR-001 / ARCH-001
- AmountMatchRule.php → BR-001 / FR-003 / AC-003.1/003.2 / KEYWORD_SCORING
- ReferenceNumberService.php → ARCH-001 / Service-Oriented Design

## RTM Updates Added
- FR-001: 100% req / 100% test (BABOK: BR-001, ARCH-001 docs)
- FR-003: 100% req / 100% test (BABOK: BR-011, KEYWORD_SCORING, CLUSTERING_BONUS)
- BR-011: 100% req / 0% test (new clustering/scoring requirement; tests needed)
- ARCH-001: 95% req / 85% test (BABOK: ARCH-001-ServiceDesign.md, ARCHITECTURE_MIGRATION.md, AGENTS.md)

## Gaps
- BR-011-001/002/003/004 (sub-requirements) need dedicated test cases (TC-011 series)
- ARCH-003 through ARCH-010 need full service mapping to Services/ files
- ProjectManagement (PMBOK) docs: PM-001-Scope through PM-007-Comm need mapping to requirements
- 100% test coverage requires TC-011 (clustering score) and TC-012 (scoring engine integration)

## Protocol
- No "through" ranges in file names: Type-XXX-ShortDesc.md
- Last segment is description (e.g., AutoMatch, AutoPairDetect, ServiceDesign)
- Shared AGENTS docs remain unmodified; local notes only via AGENTS.local.md

## Execution Status (Updated)
- TC-011-A/B/C: Specs created; PHPUnit stubs created (tests/unit/Services/Scoring/TC-011-*.md)
- TC-012-A: Spec + PHPUnit stub created (tests/unit/Services/Performance/TC-012-A.md)
- TC-019-A: Spec + PHPUnit stub created (tests/unit/Services/Alert/TC-019-A.md)
- TC execution: PENDING (tests need PHPUnit framework execution; TC stubs reference TC specs correctly)
- RTM: All new sub-req rows added (BR-011-001 through 004, BR-012-001 through 003, FR-012-001 through 003)
- Code tagging: Complete (58+ files)
- Entities: Tagged; mapping verified (PartnerEntity → BR-001/ARCH-001; PartnerType → BR-001/ARCH-001; UploadedFile → BR-010/ARCH-003; PartnerMatchResult → BR-001/ARCH-001)
- Types: Tagged; mapping verified (BankTransferTransaction → BR-003/ARCH-003; CreditTransaction → BR-002/ARCH-002; DebitTransaction → BR-002/ARCH-002)
- Views: Tagged; mapping verified (ProcessStatementsView/MatchingGLS/DisplaySettledTransactions/HtmlTransactionView → FR-009/FR-010/BR-009/ARCH-009)
