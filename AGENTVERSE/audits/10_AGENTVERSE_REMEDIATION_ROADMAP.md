# AGENTVERSE REMEDIATION ROADMAP

**Date:** 2026-08-19
**Priority:** ordered by impact × feasibility

---

## TIER 1 — CRITICAL (address before autonomous operation)

### R-001: Install PHP test framework
**Addresses:** CF-001, testing rigor (30/100)
**Action:** Install PHPUnit in scholapro/. Create test suite covering: billing CRUD, module activation, core RosarioSIS functions, API endpoints.
**Impact:** G5 quality verification becomes evidence-based, not syntax-only.
**Effort:** 2-3 days

### R-002: Build CI/CD pipeline
**Addresses:** HF-002, regression resistance (25/100)
**Action:** GitHub Actions workflow: php -l → PHPUnit → deploy to staging → smoke test → deploy to production.
**Impact:** Automated regression detection; deployment不再 manual.
**Effort:** 2-3 days

### R-003: Sync local codebase to 12.9.2
**Addresses:** CF-004, context preservation (55/100)
**Action:** Update scholapro/ to match production school4 (12.9.2). Commit. Verify Warehouse.php version.
**Impact:** All agents read correct version context.
**Effort:** 1 day

### R-004: Fix chief-architect and quality-guardian permissions
**Addresses:** CF-007, RISK-002/003
**Action:** In gen-agents.js, assign RW tier to chief-architect and quality-guardian. Regenerate.
**Impact:** Gate owners can record verdicts and update artifacts.
**Effort:** 1 hour

### R-005: Fix session log session IDs
**Addresses:** CF-005, session traceability
**Action:** Investigate opencode plugin API to determine if session ID is available via different event property. If not, log session start/end timestamps with correlatable data.
**Impact:** Session correlation becomes possible.
**Effort:** 1 day

---

## TIER 2 — HIGH (address for reliable supervised operation)

### R-006: Expand ORG_CHECKSUM.json to all control planes
**Addresses:** HF-007, integrity verification
**Action:** Add SHA256 hashes for VERIFICATION_CONTRACT, TRUTH_HIERARCHY, FAILURE_LOG, HANDOFF_TEMPLATE, MODEL_FAILOVER, all 7 skill files.
**Impact:** Integrity verification covers full organizational infrastructure.
**Effort:** 2 hours

### R-007: Integrate HANDOFF_TEMPLATE into delegate skill
**Addresses:** HF-010, delegation quality (65/100)
**Action:** Update delegate/SKILL.md to reference HANDOFF_TEMPLATE.md. Require handoff artifacts for all cross-agent delegations.
**Impact:** Handoff quality becomes consistent.
**Effort:** 2 hours

### R-008: Update CHANGES.md for all released tickets
**Addresses:** HF-001, G6 compliance
**Action:** Retroactively add CHANGES.md entries for SCHOL-004 through SCHOL-109 core-impacting changes.
**Impact:** G6 release gate evidence becomes truthful.
**Effort:** 1 day

### R-009: Fix KB-0004 version contradiction
**Addresses:** HF-006, memory reliability (58/100)
**Action:** Update KB-0004 to reflect RosarioSIS 12.9.2. Add cross-reference to SCHOL-009 upgrade.
**Impact:** Knowledge retrieval returns correct version.
**Effort:** 30 minutes

### R-010: Implement automated secret scanning
**Addresses:** HF-003, security discipline (72/100)
**Action:** Create shell script that runs the 4 VERIFICATION_CONTRACT grep patterns. Wire into G4 gate as mandatory artifact.
**Impact:** G4 secret scan produces actual evidence.
**Effort:** 2 hours

---

## TIER 3 — MEDIUM (address for autonomous operation readiness)

### R-011: Add domain-specific instructions to agent definitions
**Addresses:** HF-008, agent specialization (62/100)
**Action:** Extend gen-agents.js template to include domain-specific instructions (tools, files, commands) per agent role.
**Impact:** Agents can differentiate their workflow, not just their label.
**Effort:** 2-3 days

### R-012: Implement ticket state machine enforcement
**Addresses:** MF-014, verification rigor
**Action:** Add validation script that checks ticket state transitions follow VERIFICATION_CONTRACT machine.
**Impact:** State machine becomes enforced, not aspirational.
**Effort:** 1 day

### R-013: Add G3 architecture checkpoint to state machine
**Addresses:** RISK-004, implementation before architecture
**Action:** VERIFICATION_CONTRACT: require G3 PASS before PLANNED → IN_PROGRESS transition.
**Impact:** Architecture review happens before implementation starts.
**Effort:** 2 hours

### R-014: Implement cross-agent contract registry
**Addresses:** HF-009, cross-stack consistency (55/100)
**Action:** Create machine-readable contract file defining field names, API signatures, database column names.
**Impact:** Frontend/backend/database naming enforced.
**Effort:** 1 day

### R-015: Add learning verification mechanism
**Addresses:** MF-012, organizational learning (75/100)
**Action:** After each FAILURE_LOG entry, add checklist item to subsequent tickets verifying the fix was applied.
**Impact:** Lessons are verified applied, not just documented.
**Effort:** 2 hours

---

## TIER 4 — LOW (address for exceptional operation)

### R-016: Add adversarial review agent
### R-017: Implement cryptographic gate verdict signatures
### R-018: Build monitoring/alerting for school4
### R-019: Implement blue-green deployment
### R-020: Add offsite backup for production data

---

## ESTIMATED TIMELINE

| Tier | Items | Effort | Impact |
|------|-------|--------|--------|
| Tier 1 (Critical) | R-001 through R-005 | ~1 week | Unblocks autonomous operation |
| Tier 2 (High) | R-006 through R-010 | ~3 days | Enables reliable supervised operation |
| Tier 3 (Medium) | R-011 through R-015 | ~1 week | Approaches autonomous readiness |
| Tier 4 (Low) | R-016 through R-020 | ~2 weeks | Achieves exceptional operation |
| **Total** | **20 items** | **~5 weeks** | **62 → 85+ scorecard** |
