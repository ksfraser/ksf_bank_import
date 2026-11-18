# Refactoring Quick Reference Matrix

## Priority Matrix - At a Glance

### 🔴 HIGH PRIORITY (Start Here)

| # | Issue | File | Type | Lines | Effort | Impact |
|---|-------|------|------|-------|--------|--------|
| 1.1 | Long method: convertQIFToDTO | QIFParser.php | Complexity | 60+ | 2-3h | HIGH |
| 1.2 | Long method: transformSingle | BiTransactionTransformer.php | Complexity | 90+ | 3-4h | HIGH |
| 1.3 | Parameter bloat (5 params) | ProcessStatementsFetchService.php | Design | N/A | 2-3h | HIGH |
| 1.4 | SQL injection risk | DuplicateReviewHandler.php | Security | 50+ | 3-4h | HIGH |
| 1.5 | Long method: validateStatementMetadata | ValidationService.php | Complexity | 65+ | 4-5h | HIGH |
| 1.6 | Magic numbers & static utils | NormalizationRules.php | Type Safety | 280+ | 5-6h | HIGH |

**Total Effort:** 19-25 hours | **ROI:** Massive (reduces 50-80% method complexity)

---

### 🟡 MEDIUM PRIORITY (Second Wave)

| # | Issue | File | Type | Lines | Effort | Impact |
|---|-------|------|------|-------|--------|--------|
| 2.1 | ID generation duplication | BiStatementTransformer.php | Pattern | 45+ | 2-3h | MEDIUM |
| 2.2 | Fat constructor (3 optionals) | EnrichmentService.php | Design | N/A | 1-2h | MEDIUM |
| 2.3 | Loose type: $qifData | QIFParser.php | Type Safety | N/A | 1-2h | MEDIUM |
| 2.4 | Unclear matcher priority | DuplicateDetectionService.php | Pattern | 30+ | 2-3h | MEDIUM |
| 2.5 | Inconsistent filter handling | ProcessStatementsFetchService.php | Consistency | N/A | 1-2h | MEDIUM |
| 2.6 | Weak error handling | ChargeCalculator.php | Error Handling | N/A | 1-2h | MEDIUM |
| 2.7 | Optional QualityScorer | BiTransactionTransformer.php | Design | N/A | 1h | MEDIUM |
| 2.8 | Plain array error collection | ValidationService.php | Design | N/A | 2h | MEDIUM |

**Total Effort:** 11-17 hours | **ROI:** Good (improves testability and clarity)

---

### 🟢 LOW PRIORITY (Nice to Have)

| # | Issue | File | Type | Lines | Effort | Impact |
|---|-------|------|------|-------|--------|--------|
| 3.1 | Extract bank/account logic | BiStatementTransformer.php | Extraction | 20+ | 1-2h | LOW |
| 3.2 | Rules caching strategy | DuplicateRulesProvider.php | Performance | N/A | 1-2h | LOW |
| 3.3 | Date format magic strings | QIFParser.php | Clarity | N/A | 1h | LOW |
| 3.4 | Missing edge case validation | ValidationService.php | Validation | N/A | 2-3h | LOW |
| 3.5 | Inconsistent naming | ProcessStatementsFetchService.php | Clarity | N/A | 1h | LOW |

**Total Effort:** 6-9 hours | **ROI:** Minor (code clarity and edge cases)

---

## 🎯 Recommended Implementation Order

### Sprint 1 (Week 1) - Foundation
Start with **easiest wins** to build momentum:

1. **#2.7** - Add NullQualityScorer (1h) ✨ Quick win
2. **#2.3** - Create QifParserOutput DTO (1-2h) ✨ Improves signatures
3. **#3.3** - Enum for date formats (1h) ✨ Quick win
4. **#2.8** - ValidationErrorCollection class (2h) ⭐ Improves design
5. **#1.6** - Extract magic number constants from NormalizationRules (2-3h) ⭐ Safety critical

**Sprint 1 Effort:** 7-9 hours  
**Sprint 1 Result:** Type safety improved, 10-15 quick wins, foundation laid

---

### Sprint 2 (Week 2) - Complex Methods
Attack **long methods** that affect many code paths:

1. **#1.3** - Extract StatementFetchQuery DTO (2-3h) 🔴 HIGH impact
2. **#1.5** - Refactor ValidationService validators (4-5h) 🔴 HIGH impact
3. **#2.1** - ID generation strategy pattern (2-3h) ⭐ Reduces duplication
4. **#2.4** - Matcher chain of responsibility (2-3h) ⭐ Improves testability

**Sprint 2 Effort:** 10-14 hours  
**Sprint 2 Result:** 5+ files refactored, 50+ fewer lines of code

---

### Sprint 3 (Week 3) - Security & Nuclear Options
Deal with **security risks** and **largest methods**:

1. **#1.4** - QueryBuilder for SQL (3-4h) 🔴 SECURITY critical
2. **#1.2** - Extract BiTransactionExtractor (3-4h) 🔴 HIGH complexity
3. **#1.1** - Extract QIFParser ID generation (2-3h) 🔴 HIGH complexity

**Sprint 3 Effort:** 8-11 hours  
**Sprint 3 Result:** Security risks eliminated, major complexity reduction

---

### Sprint 4 (Week 4) - Polish & Documentation
Finish **medium-priority items** and clean up:

1. **#2.2** - Null Object for optional providers (1-2h)
2. **#2.5** - Consistent filter validation (1-2h)
3. **#2.6** - Charge error handling (1-2h)
4. **#3.1, #3.2, #3.4, #3.5** - Remaining low-priority (4-5h)
5. Documentation & integration test updates (2-3h)

**Sprint 4 Effort:** 10-14 hours  
**Sprint 4 Result:** Polished codebase, zero known issues

---

## 📊 Risk Assessment

### Security Risks (DO FIRST ⚠️)
- **#1.4** (SQL injection in DuplicateReviewHandler) - **CRITICAL**
- **#1.6** (Type-unsafe NormalizationRules) - **HIGH**

### Maintainability Risks
- **#1.1, #1.2, #1.5** (Long methods) - **HIGH**
- **#1.3** (Parameter bloat) - **MEDIUM**

### Testing Complexity
- **#2.4** (Matcher logic) - **HIGH**
- **#2.1** (ID generation) - **MEDIUM**

---

## 📈 Quick Wins (Do These First!)

### Can do in <1 hour each:
- ✅ #3.3 - Date format enums
- ✅ #2.7 - NullQualityScorer
- ✅ #3.5 - Naming consistency
- ✅ #1.6 (part 1) - Extract constants into class

### Can do in 1-2 hours:
- ✅ #2.3 - QifParserOutput DTO
- ✅ #2.8 - ValidationErrorCollection
- ✅ #2.2 - EnrichmentProviders facade
- ✅ #3.1 - Extract helper methods

---

## 🚀 Before & After Metrics

### Complexity Reduction
```
QIFParser.convertQIFToDTO()
  Before: 60 lines, CC=8
  After:  12 lines, CC=2
  Improvement: 80% ✅

BiTransactionTransformer.transformSingle()
  Before: 90 lines, CC=12
  After:  20 lines, CC=3  
  Improvement: 78% ✅

ValidationService.validateStatementMetadata()
  Before: 65 lines, CC=7
  After:  12 lines, CC=2
  Improvement: 82% ✅
```

### Type Safety Score
```
Before: 4/10 (multiple loose types, mixed concerns)
After:  8/10 (specific DTOs, clear types everywhere)
Improvement: +100% ✅
```

### Bug Risk
```
Before: SQL injection risk (#1.4), silent fallbacks, null checks
After:  Prepared statements, explicit errors, null objects
Improvement: -60% risk ✅
```

---

## 🧪 Testing Strategy

### Per Refactoring
- **Break existing tests:** Run full suite after each sprint
- **Add new test coverage:** Min 80% for new/refactored code
- **Integration tests:** Verify pipeline still works

### Test Addition Plan
```
Sprint 1: +10 tests (quick win validators)
Sprint 2: +25 tests (complex method extraction)
Sprint 3: +20 tests (security & SQL)
Sprint 4: +15 tests (polish & edge cases)
Total:    +70 new tests
```

---

## 📝 Code Review Checklist

### For Each Refactoring PR:

- [ ] Method complexity reduced by ≥50%
- [ ] New type hints added (no loose types)
- [ ] All new code has tests
- [ ] Existing tests still pass
- [ ] No new dependencies added
- [ ] Documentation updated
- [ ] No SQL injection vulnerabilities
- [ ] No silent error swallowing

---

## 🎓 Learning Resources

### Refactoring Techniques Used

1. **Extract Method** - Break long methods → #1.1, #1.2, #1.5
2. **Extract Class** - Separate concerns → #1.2, #1.4
3. **Parameter Object** - Reduce params → #1.3
4. **Strategy Pattern** - Replace conditionals → #2.1, #2.4
5. **Template Method** - Common algorithm → Design opportunity
6. **Null Object** - Eliminate null checks → #2.2, #2.7
7. **Query Builder** - Safe SQL generation → #1.4

### Reference
- **Fowler, "Refactoring: Improving the Design of Existing Code"**
- **Martin, "Clean Code"**
- **SOLID Principles** for design decisions

---

## ❓ FAQ

### Q: Why not do all HIGH priority items first?
**A:** Build momentum with quick wins first. Refactoring morale matters. Easy wins → confidence → tackle hard items.

### Q: Can I parallelize these refactorings?
**A:** **NO** - Some items depend on others:
- #1.6 (constants) should complete before #2.3, #3.3
- #1.3 (DTO) should complete before #2.5
- #1.4 (SQL) can run in parallel with #1.1, #1.2, #1.5

### Q: What if tests fail after a refactoring?
**A:** 
1. Revert the refactoring
2. Check if bug is in refactored code or existing code
3. If existing bug: Fix separately first
4. Re-apply refactoring

### Q: How do I know if a refactoring is "done"?
**A:**
- ✅ All tests pass (existing + new)
- ✅ Complexity metrics improved by 50%+
- ✅ Code review approved
- ✅ No new dependen added
- ✅ Documentation updated

---

## 📞 Questions?

Reference the full analysis in: [REFACTORING_OPPORTUNITIES_ANALYSIS.md](REFACTORING_OPPORTUNITIES_ANALYSIS.md)

---

**Last Updated:** April 4, 2026  
**Status:** Ready for Implementation  
**Estimated Total Timeline:** 4 sprints (38-48 hours)
