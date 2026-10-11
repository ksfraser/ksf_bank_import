# Partner Data Subsystem Refactoring Plan

## Current State Analysis

### Problems Identified

1. **Procedural Code in OOP Project**
   - `pdata.inc` contains global functions
   - `search_partner_keywords.inc` contains global functions
   - No dependency injection
   - Direct database access everywhere

2. **SOLID Violations**
   - **SRP**: Functions do multiple things (search + format)
   - **OCP**: Hard to extend without modifying existing code
   - **DIP**: High-level code depends on low-level database details
   - **ISP**: No interfaces, tight coupling

3. **Missing Architecture Patterns**
   - No Repository pattern (direct SQL everywhere)
   - No Service layer
   - No Value Objects for partner data
   - No DTOs for search results

4. **Code Quality Issues**
   - Inconsistent naming (`set_partner_data` vs `searchPartnerByData`)
   - Poor PHPDoc (missing `@throws`, `@return` types)
   - SQL injection risk (some queries not properly escaped)
   - No type hints
   - Commented-out debug code

5. **MVC Violations**
   - `build_partner_keyword_data.php` mixes UI, business logic, and data access
   - No View classes
   - No proper Controllers

## Proposed Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌───────────────────┐  ┌────────────────────────────────┐ │
│  │ ManagePartnersView│  │ BuildKeywordDataView           │ │
│  │ (HTML rendering)  │  │ (HTML rendering)               │ │
│  └───────────────────┘  └────────────────────────────────┘ │
└────────────────────────────────────────┬────────────────────┘
                                         │
┌────────────────────────────────────────┴────────────────────┐
│                   Controller Layer                           │
│  ┌───────────────────┐  ┌────────────────────────────────┐ │
│  │ PartnerDataCtrl   │  │ KeywordBuildController         │ │
│  │ (Handle requests) │  │ (Orchestrate keyword building) │ │
│  └───────────────────┘  └────────────────────────────────┘ │
└────────────────────────────────────────┬────────────────────┘
                                         │
┌────────────────────────────────────────┴────────────────────┐
│                    Service Layer                             │
│  ┌────────────────────┐  ┌──────────────────────────────┐  │
│  │ PartnerDataService │  │ KeywordMatchingService       │  │
│  │ - CRUD operations  │  │ - Keyword extraction         │  │
│  │ - Business logic   │  │ - Scoring algorithm          │  │
│  └────────────────────┘  │ - Confidence calculation     │  │
│                          └──────────────────────────────┘  │
└────────────────────────────────────────┬────────────────────┘
                                         │
┌────────────────────────────────────────┴────────────────────┐
│                   Repository Layer                           │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ PartnerDataRepositoryInterface                         │ │
│  └───────────────────────┬────────────────────────────────┘ │
│                          │                                   │
│  ┌───────────────────────▼────────────────────────────────┐ │
│  │ DatabasePartnerDataRepository (Implementation)         │ │
│  │ - find(), findByPartner(), findByKeyword()            │ │
│  │ - save(), update(), delete()                          │ │
│  │ - searchByKeywords() with scoring                     │ │
│  └────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────┬────────────────────┘
                                         │
┌────────────────────────────────────────┴────────────────────┐
│                   Domain Layer (Value Objects)               │
│  ┌──────────────────┐  ┌─────────────────┐ ┌─────────────┐ │
│  │ PartnerData      │  │ KeywordMatch    │ │ Keyword     │ │
│  │ - partnerId      │  │ - partnerId     │ │ - text      │ │
│  │ - partnerType    │  │ - score         │ │ - length    │ │
│  │ - data           │  │ - confidence    │ │ - isValid() │ │
│  │ - occurrenceCount│  │ - keywords[]    │ └─────────────┘ │
│  └──────────────────┘  └─────────────────┘                  │
└─────────────────────────────────────────────────────────────┘
```

## File Structure

```
src/Ksfraser/FaBankImport/
├── Domain/
│   ├── ValueObjects/
│   │   ├── PartnerData.php
│   │   ├── KeywordMatch.php
│   │   ├── Keyword.php
│   │   └── MatchConfidence.php
│   └── Exceptions/
│       ├── PartnerDataNotFoundException.php
│       └── InvalidKeywordException.php
│
├── Repository/
│   ├── PartnerDataRepositoryInterface.php
│   ├── DatabasePartnerDataRepository.php
│   └── KeywordRepositoryInterface.php (optional)
│
├── Services/
│   ├── PartnerDataService.php
│   ├── KeywordMatchingService.php
│   ├── KeywordExtractorService.php
│   └── ClusteringScoringService.php
│
├── Controllers/
│   ├── PartnerDataController.php
│   └── KeywordBuildController.php
│
└── Views/
    ├── ManagePartnersView.php
    └── BuildKeywordDataView.php

Legacy wrapper (backward compatibility):
includes/
└── pdata.inc (thin wrapper calling new services)
```

## Refactoring Steps

### Phase 1: Domain Layer (Value Objects)
1. Create `PartnerData` value object
2. Create `KeywordMatch` value object  
3. Create `Keyword` value object
4. Create custom exceptions

### Phase 2: Repository Layer
1. Create `PartnerDataRepositoryInterface`
2. Implement `DatabasePartnerDataRepository`
3. Add unit tests for repository

### Phase 3: Service Layer
1. Create `PartnerDataService` (CRUD + business logic)
2. Create `KeywordMatchingService` (search with scoring)
3. Create `KeywordExtractorService` (tokenization)
4. Add unit tests for services

### Phase 4: Controller Layer
1. Create `PartnerDataController`
2. Create `KeywordBuildController`
3. Add integration tests

### Phase 5: View Layer
1. Create `ManagePartnersView` using HTML classes
2. Create `BuildKeywordDataView` using HTML classes

### Phase 6: Migration & Backward Compatibility
1. Update `pdata.inc` to wrap new services
2. Update `search_partner_keywords.inc` to use new service
3. Update handlers to use new service (or keep wrapper)
4. Deprecation notices on old functions

### Phase 7: Update Documentation
1. Generate UML diagrams
2. Update PHPDoc with proper types
3. Create migration guide

## Benefits

### SOLID Compliance
- ✅ **SRP**: Each class has one responsibility
- ✅ **OCP**: Extend via interfaces, don't modify
- ✅ **LSP**: Repository interface allows swapping implementations
- ✅ **ISP**: Small, focused interfaces
- ✅ **DIP**: Services depend on interfaces, not concrete implementations

### Architecture Patterns
- ✅ **Repository Pattern**: Clean data access abstraction
- ✅ **Service Layer**: Business logic separation
- ✅ **Value Objects**: Immutable domain objects
- ✅ **Dependency Injection**: Testable, flexible
- ✅ **MVC**: Proper separation of concerns

### Code Quality
- ✅ **Type Safety**: PHP 7.4+ type hints everywhere
- ✅ **PHPDoc**: Complete with `@param`, `@return`, `@throws`
- ✅ **Naming**: Consistent PSR-12 standards
- ✅ **Testing**: Unit + integration tests
- ✅ **Security**: Prepared statements, no SQL injection

## Backward Compatibility Strategy

```php
// Old code still works:
set_partner_data($partnerId, $partnerType, $detailId, $data);

// But internally calls:
$service = PartnerDataService::getInstance();
$partnerData = new PartnerData($partnerId, $partnerType, $detailId, $data);
$service->save($partnerData);

// With deprecation notice:
@deprecated Use PartnerDataService::save() instead
```

## Timeline Estimate

- **Phase 1 (Value Objects)**: 4 hours
- **Phase 2 (Repository)**: 6 hours
- **Phase 3 (Services)**: 8 hours
- **Phase 4 (Controllers)**: 4 hours
- **Phase 5 (Views)**: 6 hours
- **Phase 6 (Migration)**: 4 hours
- **Phase 7 (Documentation)**: 4 hours

**Total: ~36 hours** (could be split across multiple sessions)

## Priority

**High Priority Files:**
1. Repository + Service (core functionality)
2. Backward compatibility wrapper
3. PHPDoc + type hints

**Medium Priority:**
4. Controllers (improve but not critical)
5. Views (improve HTML rendering)

**Low Priority:**
6. Complete test coverage
7. UML diagrams
8. Migration docs

## Questions for User

1. **Scope**: Do you want full refactoring now, or phase it in?
2. **BC**: Keep backward compatibility or breaking change OK?
3. **Testing**: Unit tests required for all new code?
4. **Priority**: Which phase should I start with?

## Recommendation

**Start with Phase 1-3** (Value Objects, Repository, Services) as these provide the most architectural benefit and can be used immediately without breaking existing code. Controllers and Views can follow later.

Would you like me to proceed with Phase 1?
