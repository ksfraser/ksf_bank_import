# ARCH Mapping: Architecture Requirements to Service Modules

## ARCH-001: Service-Oriented Design (BR-001 / BO-001 / BO-004)
- Services/TransactionService.php
- Services/SupplierMatcher.php
- Services/CustomerMatcher.php
- Services/TransactionMatchingService.php
- Services/BankTransferFactory.php
- Services/ReferenceNumberService.php
- References: AGENTS.md (§Data dictionary/query builder), AGENTS_ARCH.md (§Namespace convention, Module naming)

## ARCH-002: Design Target (covered by ARCH-001 / BR-002 mapping)

## ARCH-003: Fine-Grained Exception Handling (BR-003 / FR-007 / FR-008)
- Services/TransactionValidator.php
- Services/TransactionGLValidator.php
- Exceptions/HandlerDiscoveryException.php
- Exceptions/TransactionValidationException.php

## ARCH-004: Handler Design (FR-014 / FR-015)
- Controllers/AbstractController.php
- Controllers/BankImportController.php
- Services/SimpleCommandBus.php
- References: HANDLER_DESIGN_REVIEW.md, AGENTS.md (§Hook system / Inter-module communication)

## ARCH-005: Handler Verification (FR-014 / FR-015 / Handler migration verification)
- Controllers/AdminController.php
- Controllers/BiLineItemController.php
- References: HANDLER_VERIFICATION.md

## ARCH-006: UML Diagrams / Design Artifacts (All architecture artifacts)
- UML_DIAGRAMS.md (ARCH-006-UML_DIAGRAMS.md)
- References: AGENTS.md (§UML references, Design patterns)

## ARCH-007: Performance Monitoring Architecture (BR-007 / BR-012 / NFR-001 / FR-012)
- Services/PerformanceMonitor.php
- Services/MetricsAggregator.php
- Services/TransactionCounter.php
- Services/AlertService.php
- References: KEYWORD_SCORING_SYSTEM.md, CLUSTERING_BONUS_EXPLAINED.md, ARCHITECTURE_MIGRATION.md

## ARCH-008: Audit Trail and Data Integrity Architecture (BR-008 / FR-018 / NFR-006)
- Services/TransactionLogger.php
- Services/TransactionUpdater.php
- Services/TransactionGLValidator.php
- References: ARCHITECTURE.md (§Audit Trail), AGENTS.md (§Audit and Logging / FA lifecycle hooks)

## ARCH-009: Usability and UI Architecture (BR-009 / FR-009 / FR-010)
- Controllers/BankImportController.php
- Controllers/BiLineItemController.php
- Services/TransactionViewService.php
- References: USER_GUIDE.md, ARCHITECTURE.md (§UI / Visual Indicators)

## ARCH-010: Compatibility and Deployment Architecture (BR-010 / NFR-011 through NFR-016)
- composer.json (PHP 7.4 pin)
- DEPLOYMENT_GUIDE.md
- SETUP.md
- References: PACKAGIST.md, AGENTS.md (§ComposerDependencies, Container PHP mismatch)

## ARCH-002: Design Target (BR-002 / FR-004 / FR-005 / Transfer Direction & Data Construction)
- Services/TransferDirectionAnalyzer.php (FR-004: analyze(); FR-005: buildTransferData())
- Services/TransactionService.php (FR-001 / BR-001 / BR-003 integration)
- Services/TransactionMatcherIntegration.php (integration with matching engine)
- References: docs/ARCHITECTURE.md (§Direction Analysis / Transfer Data Construction), docs/REQUIREMENTS_SPECIFICATION.md (FR-004, FR-005), docs/UAT/UAT-002-AutoPairUAT.md, docs/FunctionalRequirements/FR-004-DCIndicatorDirection.md, docs/FunctionalRequirements/FR-005-TransferDataConstruction.md
