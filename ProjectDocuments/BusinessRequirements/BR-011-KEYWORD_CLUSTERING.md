# BR-011: Keyword Matching and Clustering Score for Bank Transaction to Partner Matching

## Requirement
The system SHALL use keyword-based pattern matching with occurrence scoring and co-occurrence clustering bonus to improve matching between bank file transaction descriptions and customers/suppliers (partners).

## Sub-Requirements
- BR-011-001: Break transaction description text into keywords and track occurrence counts per partner (`0_bi_partners_data.occurrence_count`).
- BR-011-002: Score matches based on keyword occurrence frequency (`KEYWORD_SCORING_SYSTEM.md`).
- BR-011-003: Apply multiplicative clustering bonus (`CLUSTERING_FACTOR`) when multiple keywords co-occur with same `partner_id` / `partner_detail_id` / `partner_type` (`CLUSTERING_BONUS_EXPLAINED.md`).
- BR-011-004: Ensure clustering bonus does not eliminate isolated keyword matches entirely (balanced scoring: `CLUSTERING_FACTOR = 0.2`).

## Related Shared Docs (read-only, hardlinked)
- `Project Documents/KEYWORD_SCORING_SYSTEM.md` — algorithm details, DB schema (`sql/add_occurrence_count_to_bi_partners_data.sql`), keyword extraction
- `Project Documents/CLUSTERING_BONUS_EXPLAINED.md` — multiplicative clustering bonus formula, multiplier table, vendor name change use case (SHOPPERS DRUG MART → SHOPPERS PHARMACY)
- `Project Documents/PATTERN_MATCHING_CONFIG_GUIDE.md` — config settings (`pattern_matching.keyword_clustering_factor`, `min_confidence_threshold`, `max_suggestions`, `min_keyword_length`), DB config (`0_bi_config.category = 'pattern_matching'`)
- `Project Documents/PATTERN_MATCHING_QUICK_REFERENCE.md` — quick reference for pattern matching settings

## Design / Architecture Link
- ARCH-011: Pattern Matching and Scoring Architecture (`docs/Specs/ARCH-011-PDF-CC-RECONCILIATION.md` is separate; scoring architecture covered in `Project Documents/ARCHITECTURE_MIGRATION.md` and `KEYWORD_SCORING_SYSTEM.md`)

## FR Mapping
- FR-001 (Auto Pair Detection) relies on BR-011-001/002/003 for partner identification before amount/date matching.
- FR-010 (Operation Type Selection) uses scoring output for BT/SP/CU/QE selection.

## UAT
- UAT-011: Verify vendor name changes still match with lower but non-zero score.
- UAT-011-YYY: Confirm clustering bonus applies for co-occurring keywords only.
