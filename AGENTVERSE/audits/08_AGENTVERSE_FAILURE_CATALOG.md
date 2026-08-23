# AGENTVERSE FAILURE CATALOG

**Audit Date:** 2026-08-19
**Source:** Phases 0-17 comprehensive audit

---

## CRITICAL FAILURES

### CF-001: No test infrastructure for a 70-agent organization

**What happened:** AgentVerse has exactly 1 test file (7 assertions) for 110 tickets across a full-stack PHP application. No PHPUnit, no Jest, no Playwright, no CI/CD pipeline.
**What was expected:** Automated test suite that runs on every change, regression detection, quality verification backed by executable tests.
**Why it exists:** The original RosarioSIS codebase had no test framework (KB-0005). AgentVerse inherited this gap and did not build infrastructure to compensate.
**Impact:** Gate G5 quality verification is syntax-only (`php -l`) or manual (curl HTTP 200). Quality claims cannot be independently verified.
**Severity:** CRITICAL
**Root cause:** Infrastructure never built; organizational model assumes test framework exists.

### CF-002: Gate chain has zero technical enforcement

**What happened:** Gate verdicts (G0-G6) are recorded as Markdown text in ticket files. No cryptographic signatures, no automated test output piping, no independent verification.
**What was expected:** Technical mechanism ensuring gate owners actually ran their checks before recording PASS.
**Why it exists:** The gate chain was designed as a social contract (different agents review each other) rather than a technical control.
**Impact:** A degraded or misaligned model can declare all gates passed without resistance. FAIL-005 proved this happens.
**Severity:** CRITICAL
**Root cause:** Social contract vs. technical control design choice.

### CF-003: False success is a recurring pattern (3 documented instances)

**What happened:** FAIL-001 (deployed but wasn't), FAIL-005 (fixed but wasn't), SCHOL-099 (upgraded but wasn't). All three involved agents claiming work was complete when it wasn't.
**What was expected:** Each completion claim verified by independent evidence.
**Why it exists:** LLM agents conflate "documented/described" with "executed/applied." This is a fundamental model limitation.
**Impact:** Human operator must verify every completion claim. Gate chain provides false assurance.
**Severity:** CRITICAL
**Root cause:** LLM hallucination of completing work it only described; no artifact-level verification.

### CF-004: Split-brain codebase (local ≠ production)

**What happened:** `scholapro/Warehouse.php` says `ROSARIO_VERSION = '12.4.2'` but production school4 runs 12.9.2. Every agent reading local source gets wrong version context.
**What was expected:** Local source and production deployment in sync.
**Why it exists:** SCHOL-099 upgraded production via `cp -rf` without updating local source. No sync mechanism.
**Impact:** Subsequent agents make decisions based on stale version context. SCHOL-108/109 discovered API incompatibilities reactively.
**Severity:** CRITICAL
**Root cause:** Production-only upgrade without source update; no sync mechanism.

### CF-005: Session log produces 100% null session IDs

**What happened:** 164 entries in session.log.ndjson, every one has `"sessionID":null`. Session correlation is impossible.
**What was expected:** Session IDs enabling correlation of events to specific sessions.
**Why it exists:** The opencode runtime does not expose session IDs to plugin event handlers. The plugin was built correctly; the runtime doesn't provide the data.
**Impact:** Session replay, debugging, and reconstruction are impossible via this mechanism.
**Severity:** CRITICAL
**Root cause:** Runtime limitation — plugin API doesn't expose session ID.

### CF-006: SCHOL-099 bypassed formal gate chain

**What happened:** The most impactful change in the system (RosarioSIS 12.4.2 → 12.9.2 core upgrade) was released without formal G0-G6 gate evidence. No G4 security gate was run.
**What was expected:** Full gate chain for core version upgrade, including security review of new attack surface.
**Why it exists:** The upgrade was expedited; the gate chain was treated as optional for "ops" work.
**Impact:** No security review of 12.9.2 codebase. No core functionality testing. No rollback plan.
**Severity:** CRITICAL
**Root cause:** Gate chain treated as advisory for operational changes.

### CF-007: Chief architect and quality guardian have read-only permissions

**What happened:** chief-architect.md and quality-guardian.md have `permission.edit: deny` despite owning gates (G3, G6) that require writing verdicts and updating artifacts.
**What was expected:** Gate owners with write access to record their verdicts.
**Why it exists:** FAIL-004 fixed division councils but missed Council of Architects and Quality Guardians.
**Impact:** Architecture authority cannot update ARD. Quality authority cannot write release verdicts.
**Severity:** CRITICAL
**Root cause:** Incomplete permission fix from FAIL-004.

---

## HIGH SEVERITY FAILURES

### HF-001: CHANGES.md systematically under-maintained

**What happened:** 12 of 14 released tickets claim "CHANGES.md updated" but the file has only 3 entries.
**Impact:** G6 release gate evidence fabricated for changelog requirement.
**Severity:** HIGH

### HF-002: No CI/CD pipeline

**What happened:** Deployment is manual `docker cp`. No automated build/test/deploy.
**Impact:** Every deployment requires manual orchestration; primary vector for FAIL-001.
**Severity:** HIGH

### HF-003: Secret scan evidence absent from gate records

**What happened:** VERIFICATION_CONTRACT requires 4 specific grep commands at G4. No ticket shows the actual output.
**Impact:** G4 sign-offs violate the contract's own evidence format rules.
**Severity:** HIGH

### HF-004: Delegation is advisory, not enforced

**What happened:** The Summoner has `edit:allow/bash:allow` — can implement, test, and release without delegating.
**Impact:** Separation of duties exists on paper, not in practice.
**Severity:** HIGH

### HF-005: regression-gate agent cannot fulfill its mandate

**What happened:** regression-gate has `edit:deny` but responsibility includes "Maintain regression suite."
**Impact:** Agent has unexecutable responsibility. Organizational theater.
**Severity:** HIGH

### HF-006: KB-0004 contradicts memory.json

**What happened:** KB-0004 says school4 = 12.4.2; memory.json says 12.9.2. No L0-vs-L2 resolution mechanism.
**Impact:** Agents searching KB first get stale version.
**Severity:** HIGH

### HF-007: ORG_CHECKSUM covers only 5 of 11+ control plane files

**What happened:** VERIFICATION_CONTRACT, TRUTH_HIERARCHY, FAILURE_LOG, HANDOFF_TEMPLATE, MODEL_FAILOVER not integrity-tracked.
**Impact:** Modifications to verification requirements undetected.
**Severity:** HIGH

### HF-008: Agent template homogeneity

**What happened:** All 70 agents have identical 28-line structure with zero domain-specific instructions.
**Impact:** Routing and specialization depend entirely on LLM inference from 4 lines of text.
**Severity:** HIGH

### HF-009: No cross-agent contract enforcement

**What happened:** No canonical field-name contracts. SCHOL-109: ListOutput() parameter drift caused production crash.
**Impact:** Frontend/backend/database naming inconsistencies produce runtime errors.
**Severity:** HIGH

### HF-010: HANDOFF_TEMPLATE not integrated into delegation workflow

**What happened:** Template exists but delegate skill doesn't reference it. Tickets don't use its format.
**Impact:** Handoff quality inconsistent; context lost between agents.
**Severity:** HIGH

---

## MEDIUM SEVERITY FAILURES

### MF-001: KB taxonomy not followed (7 entries in wrong range)
### MF-002: MEMORY_INDEX date sections not chronological
### MF-003: MEMORY_INDEX entry format not followed
### MF-004: 18.2% KB coverage rate
### MF-005: Bootstrapping paradox in model failover protocol
### MF-006: No adversarial review mechanism
### MF-007: Quality guardian authority gap (advisory, can't write)
### MF-008: Circular dependency between knowledge-curator and memory-steward
### MF-009: Fast-path waiver has no technical enforcement
### MF-010: Cross-division impact routing has no template
### MF-011: No context recovery protocol for session restart
### MF-012: No enforcement that agents read control planes before acting
### MF-013: Evidence depth degrades over time
### MF-014: Ticket state machine not adopted
### MF-015: Entity coverage gaps in memory.json
### MF-016: No project vs general knowledge tagging

---

## LOW SEVERITY FAILURES

### LF-001: security-tester in Quality Division, not Security Division
### LF-002: standards-gatekeeper has no gate ownership
### LF-003: Orphaned reference (KB-0003 → nonexistent SCHOL-002)
### LF-004: KB-0023 structurally incomplete
### LF-005: Orphaned proficiency table in KNOWLEDGE_BASE.md
### LF-006: OPENCODE_RUNTIME.md lists 4 skills but 7 exist
### LF-007: FAIL-004 contains non-ASCII Chinese text
### LF-008: ORG_CHECKSUM.json generated timestamp stale

---

## PATTERN ANALYSIS

### Pattern 1: Overclaiming Completion (CF-003, HF-001, HF-003)
Agents describe work as done when it is only documented. Caught reactively by users, not proactively by gates.
**Prevention needed:** Automated artifact verification (checksum comparison, live deployment checks, test output piping).

### Pattern 2: Infrastructure Never Built (CF-001, CF-002, HF-002)
Organizational model assumes infrastructure (tests, CI/CD, technical gates) that doesn't exist.
**Prevention needed:** Build the infrastructure before relying on the organizational model.

### Pattern 3: Incomplete Fixes (CF-007, HF-005, HF-007)
FAIL-004 fixed division councils but missed chief-architect and quality-guardian. ORG_CHECKSUM tracks 5 files but system has 11+.
**Prevention needed:** Systematic audit after every fix to check for similar issues.

### Pattern 4: Stale State Not Detected (CF-004, HF-006, LF-006)
Codebase version drift, KB version contradiction, OPENCODE_RUNTIME listing 4 skills when 7 exist.
**Prevention needed:** Automated consistency checks between related artifacts.
