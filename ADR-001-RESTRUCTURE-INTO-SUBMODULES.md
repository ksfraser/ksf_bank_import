# ADR-001: Restructure KSF Bank Import Module into Logical Submodules

**Status**: Proposed (Ready for Review & Discussion)  
**Date**: 2024  
**Authors**: Architecture Review Team  
**Stakeholders**: Development Team, QA, DevOps  

---

## Problem Statement

The `ksf_bank_import` module has grown organically to ~7,000 LOC over multiple development phases, resulting in:

1. **Cognitive Complexity**: Mixed concerns make it difficult for new developers to understand module structure
2. **Scattered Code**: Related functionality split across root, `src/`, and `Services/` directories
3. **Duplicated Components**: Services duplicated in two locations; Models scattered in legacy `.php` files and modern OO code
4. **Difficult Maintenance**: Changes to one subsystem risk breaking another due to tight coupling
5. **Limited Extensibility**: Adding new parsers, processors, or detection rules requires understanding entire codebase
6. **Testing Challenges**: Integration tests required for every change due to intertwined concerns

**Impact**: Team velocity decreases as codebase grows; new features take longer to implement; bugs harder to isolate.

---

## Decision

Restructure the `ksf_bank_import` module into **four independent logical submodules**, each with:
- Clear single responsibility
- Isolated dependencies
- Cohesive feature set
- Independent testing and deployment

**Proposed Structure**:

1. **Shared Infrastructure** (Foundation Layer)
   - Models, DTOs, Repositories, Config, Exceptions
   - Used by all submodules

2. **Import Pipeline** (Submodule A)
   - File upload → Parsing → Validation → Transformation → Review
   - Handles multi-format statement imports

3. **Transaction Processing** (Submodule B)
   - Transaction routing → Partner-specific processing → Result rendering
   - Recent Action Dispatcher refactoring already started this separation

4. **Duplicate Detection & Review** (Submodule C)
   - Duplicate detection algorithms → Review workflow → Audit system
   - Multi-level matching (exact → fuzzy → rules-based)

5. **Administration & Configuration** (Submodule D)
   - Parser configuration → Transfer rules → System settings
   - Admin dashboard consolidation

---

## Rationale

### Why Submodules?
- **Separation of Concerns**: Each submodule has ONE reason to change
- **Team Parallelization**: Multiple developers can work on different submodules simultaneously
- **Reduced Complexity**: New developers only need to understand their submodule + Shared layer
- **Testing**: Unit tests can be scoped to single submodule; integration tests simpler
- **Deployment Risk**: Updates to import don't require re-deployment of processing logic

### Why This Structure?
- **Import & Processing are Independent Workflows**: A user can review imports without processing any
- **Duplicate Detection is Orthogonal**: Runs independently of import/processing
- **Admin is Isolated**: Configuration changes don't affect operations
- **Shared Models Enable Data Flow**: All submodules use same canonical data structures

### Why Now?
- **Recent Event**: Dispatcher refactoring (Phase 1-4) has already done 30% of the work
- **Momentum**: Foundation classes are modern and well-structured
- **Team Experience**: Recent refactoring has built mental models for continued improvements
- **Technical Debt**: Legacy procedural code is known dependency; consolidation prevents further drift

---

## Implementation Plan

### Phase 1: Foundation Layer Extraction (2-3 weeks)
- ✅ Consolidate Models from `class.*.php` + `src/Model/` → `Shared/Models/`
- ✅ Consolidate Services from `Services/` + `src/Service/` → `Shared/Services/`
- ✅ Create `Shared/DTOs/`, `Shared/Database/`, `Shared/Config/`
- 🔄 Keep old locations working with deprecation warnings
- **Risk**: LOW - New files created, old files remain

### Phase 2: Import Pipeline Isolation (3-4 weeks)  
- Create `Import/` submodule with Controllers, Handlers, Services, Parsers, Views
- Move `import_statements.php` logic into `Import/Controllers/`
- Keep entry point, delete business logic
- Create parser plugin system
- Create import orchestrator
- **Risk**: MEDIUM - 1,700 LOC workflow is complex; needs thorough testing

### Phase 3: Transaction Processing Isolation (2-3 weeks)
- Reorganize existing Action Dispatcher code into `Processing/` submodule
- Move Actions → `Processing/Actions/`
- Move Dispatcher → `Processing/Dispatcher/`
- Move Processors → `Processing/Processors/`
- Simplify `process_statements.php` to thin shell
- **Risk**: LOW - Code already refactored; mostly moving + organizing

### Phase 4: Duplicate Detection Isolation (2-3 weeks)
- Create `Duplicates/` submodule with Services, DTOs, Views, Handlers
- Move detection algorithms into module
- Create unified review workflow
- Move audit system into module
- **Risk**: LOW - Self-contained system; safe to reorganize

### Phase 5: Administration Consolidation (1-2 weeks)
- Create `Admin/` submodule with Parser, Rules, Settings managers
- Create unified admin dashboard
- Consolidate admin URLs and views
- **Risk**: VERY LOW - Isolated admin functions

**Total Duration**: ~10-14 weeks with careful sequencing  
**Cost**: Approximately 2-3 developer months of focused work  
**Benefit**: Reduced cognitive load, improved team velocity, easier future maintenance

---

## Alternatives Considered

### A1: Do Nothing (Status Quo)
- **Pros**: No refactoring cost, no risk
- **Cons**: Cognitive load continues to increase; velocity decreases; becoming unmaintainable
- **Decision**: REJECTED - Unsustainable long-term

### A2: Complete Rewrite
- **Pros**: Clean slate, no legacy code
- **Cons**: High risk, high cost, business disruption, loses working features
- **Decision**: REJECTED - Too risky, too expensive

### A3: Microservices Split
- **Pros**: Maximum independence, easiest testing, deployment per service
- **Cons**: Overkill for a module; adds network/IPC complexity; increases ops burden
- **Decision**: REJECTED - Premature for this use case

### A4: Gradual Refactoring Without Structure
- **Pros**: Can start immediately, incremental
- **Cons**: No clear target; refactoring can go sideways; hard to review
- **Decision**: REJECTED - Need clear architecture first

### A5: Proposed Submodule Separation (Selected)
- **Pros**: Clear target architecture; reduces risk through phases; maintainable; testable; team friendly
- **Cons**: Requires planning; some refactoring work; coordination needed
- **Decision**: SELECTED - Best balance of risk/benefit

---

## Acceptance Criteria

✅ **Architectural Structure**
- [ ] All files organized into appropriate submodules
- [ ] No files directly in Module root except entry points and config
- [ ] Shared infrastructure properly isolated in Foundation layer
- [ ] Clear dependency flow (no circular dependencies)

✅ **Code Quality**
- [ ] Each submodule has < 10 cyclomatic complexity
- [ ] Average submodule size 800-1,200 LOC
- [ ] 80%+ unit test coverage per submodule
- [ ] No files > 500 LOC (except controllers/views)

✅ **Documentation**
- [ ] Each submodule has README explaining its responsibility
- [ ] Architecture diagram in main docs
- [ ] Entry point flow documented
- [ ] Migration guide for developers familiar with old structure

✅ **Backward Compatibility**
- [ ] Old code paths still work (with deprecation warnings)
- [ ] New paths used by default
- [ ] No breaking changes to public APIs
- [ ] At least 1 release cycle before removing deprecated code

✅ **Testing**
- [ ] All existing tests pass
- [ ] New tests added for restructured code
- [ ] Integration tests cover full workflows
- [ ] No regression in functionality

✅ **Team Experience**
- [ ] New developer can find feature in < 5 min
- [ ] New developer understands module boundary in < 1 hour
- [ ] Code review time stayed same or decreased
- [ ] PR complexity appropriate to submodule

---

## Consequences

### Positive Consequences
✅ **Improved Maintainability**: Related code grouped logically; easier to find and modify  
✅ **Reduced Cognitive Load**: Developers work with smaller, focused submodules  
✅ **Parallelization**: Multiple developers can work on different submodules  
✅ **Better Testing**: Unit tests can be isolated; integration tests simpler  
✅ **Future Extensibility**: New features fit clearly into one submodule  
✅ **Onboarding**: New developers have clear mental model of codebase  
✅ **Technical Debt Reduction**: Consolidates duplicated code; modernizes legacy code  

### Potential Challenges
⚠️ **Refactoring Effort**: ~200-300 developer hours of focused work  
⚠️ **Temporary Duplication**: Old and new code paths coexist during transition  
⚠️ **Testing Coverage**: Need thorough tests to ensure no regressions  
⚠️ **Team Coordination**: Must synchronize updates to shared classes  

### How to Mitigate Challenges
- Use feature branch for all restructuring (parallel development)
- Comprehensive test suite before migration
- Code review process for consolidation work
- Clear deprecation timeline (1-2 release cycles)
- Team communication plan for parallel development

---

## Monitoring & Evaluation

### Success Metrics (Post-Implementation)

**Developer Velocity**
- Measure: Time to implement new feature in different submodule
- Target: 20-30% faster than pre-restructuring
- How: Track during Phase 2-5 implementation

**Code Quality**
- Measure: Cyclomatic complexity per submodule, test coverage %
- Target: <10 complexity, 80%+ coverage
- How: Static analysis tools (PHPUNIT, PHPSTAN)

**Bug Rate**
- Measure: Bugs per 1000 LOC
- Target: No increase post-restructuring
- How: Bug tracking system

**Team Feedback**
- Measure: Survey developers on code clarity, findability, testability
- Target: Positive feedback on structure
- How: Quarterly retrospectives

---

## Timeline & Phasing

| Phase | Duration | Risk | Effort | Status |
|-------|----------|------|--------|--------|
| Phase 0a: Foundation (Models) | 1 week | LOW | 40 hrs | Proposed |
| Phase 0b: Foundation (Services) | 1 week | LOW | 40 hrs | Proposed |
| Phase 1: Import Isolation | 3-4 weeks | MEDIUM | 120 hrs | Proposed |
| Phase 2: Processing Isolation | 2-3 weeks | LOW | 80 hrs | Proposed |
| Phase 3: Duplicates Isolation | 2-3 weeks | LOW | 80 hrs | Proposed |
| Phase 4: Admin Consolidation | 1-2 weeks | VERY LOW | 40 hrs | Proposed |
| **Total** | **10-14 weeks** | **LOW AVG** | **400 hrs** | **Proposed** |

---

## References

- [ARCHITECTURAL_BLUEPRINT.md](ARCHITECTURAL_BLUEPRINT.md) - Detailed restructuring proposal
- [DISPATCHER_REFACTORING_COMPLETE.md](DISPATCHER_REFACTORING_COMPLETE.md) - Recent Phase 1-4 refactoring
- [STRUCTURAL_ANALYSIS.md](STRUCTURAL_ANALYSIS.md) - Current structure analysis
- Git history: Commits 9a031b3 → 033b265 (dispatcher refactoring foundation)

---

## Approval & Sign-Off

- [ ] Architecture Team Review
- [ ] Development Team Sign-Off
- [ ] QA Team Review
- [ ] Project Lead Approval
- [ ] CTO/Tech Lead Final Approval

**Approval Date**: ___________  
**Approved By**: ___________  
**Status**: Ready for Team Review

---

## Future ADRs

This ADR enables future decisions:
- ADR-002: Submodule-specific architectural decisions (e.g., Import parser plugin system)
- ADR-003: Shared infrastructure improvements (e.g., Repository pattern implementation)
- ADR-004: API contract definitions between submodules

---

## Revision History

| Date | Author | Version | Change |
|------|--------|---------|--------|
| 2024 | Architecture Team | 1.0 | Initial proposal |
