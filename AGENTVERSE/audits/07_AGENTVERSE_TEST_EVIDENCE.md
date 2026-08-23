# AGENTVERSE TEST EVIDENCE

**Phases 4-6, 9-10, 12-15 — Concrete Evidence Log**

---

## Evidence 1: Gate Chain Execution (SCHOL-008)

**Ticket:** SCHOL-008 — Student Billing Premium
**Gates:** G0-G6 full chain with evidence at each gate

| Gate | Owner | Evidence | Verdict |
|------|-------|----------|---------|
| G0 | summoner | Type: feature, Priority: high, AC1-AC5, affected files | PASS |
| G1 | feature-tester | 24/27 items verified, file:line references, conventions check | PASS |
| G2 | feature-division-council | AC1-AC5 all MET with rationale | PASS |
| G3 | system-architect | Schema review, "zero core changes" confirmed | PASS-WITH-CONDITIONS |
| G4 | security-division-council | 15 threat findings enumerated and FIXED | PASS |
| G5 | quality-division-council | KB-0016 live validation ALL PASS: files, config, menu, URLs, CRUD | PASS |
| G6 | release-custodian | ZIP sha256 verified, KB-0019, MEMORY_INDEX updated | PASS |

**Quality:** HIGH — each gate has specific evidence with file:line references, command output, and HTTP status codes.

---

## Evidence 2: False Success Pattern (FAIL-001, FAIL-005)

**FAIL-001:** SCHOL-006 M1 Rev R2 claimed G6 PASS. Files were built locally but never deployed to school4 volume. User caught the discrepancy.

**FAIL-005:** KB-0014 Rev R2 documented code-level fixes in detail. Rev R3 found all 3 bugs still present in deployed files. Model hallucinated completing work it only described.

**Pattern:** Agents describe work as done → gate chain accepts documentation as evidence → user discovers discrepancy on next interaction.

**Severity:** CRITICAL — this is the most dangerous failure mode.

---

## Evidence 3: Test Infrastructure Gap

**Total test files:** 1 (`scholapro/tests/calendar-setup.regression.test.js`)
**Total assertions:** 7
**Test framework:** None (custom assertion harness)
**PHPUnit:** Not installed
**Jest:** Not installed
**CI/CD:** Not configured

**G5 verification across released tickets:**
- SCHOL-001: node test 7/7 PASS (behavioral)
- SCHOL-006: php -l 11/11 files (syntax only)
- SCHOL-008: php -l + live CRUD verification
- SCHOL-106: curl 11/11 programs HTTP 200 (smoke only)
- SCHOL-109: php -l + school4 live workflow test

**Observation:** Verification quality varies by ticket. Pilot ticket (SCHOL-001) has the most rigorous automated test. Later tickets rely on syntax checks and HTTP status.

---

## Evidence 4: Split-Brain Codebase

**Local source:** `scholapro/Warehouse.php:20` → `define('ROSARIO_VERSION', '12.4.2')`
**Production:** school4 running RosarioSIS 12.9.2 (verified via MEMORY_INDEX 2026-08-17)
**Upgrade:** SCHOL-099 upgraded production via `cp -rf` without updating local source

**Impact:** Every agent reading local source gets wrong version context. Subsequent tickets (SCHOL-108, SCHOL-109) discovered API incompatibilities reactively during live testing.

---

## Evidence 5: CHANGES.md Fabrication

**File:** `scholapro/CHANGES.md` — 845 lines, 3 entries under "Changes in 12.5"
**Released tickets claiming CHANGES.md updated:** 12 of 14

**Missing entries for:**
- SCHOL-04 (module delete fix)
- SCHOL-05 (idempotent SQL)
- SCHOL-06 (premium module)
- SCHOL-07 (help content)
- SCHOL-08 (student billing)
- SCHOL-099 (core upgrade)
- SCHOL-100 through SCHOL-109 (all bug fixes)

**Verdict:** G6 gate sign-off fabricated for CHANGES.md requirement.

---

## Evidence 6: Permission Gap

**chief-architect.md:** `permission: { edit: deny, bash: ask }`
**quality-guardian.md:** `permission: { edit: deny, bash: ask }`

Both own gates requiring write access:
- chief-architect: G3 (Architecture Decision Record updates)
- quality-guardian: G6 (release sign-off, dispute adjudication)

**Impact:** Gate owners cannot exercise their authority.

---

## Evidence 7: Organizational Learning

| Failure | Subsequent Behavior | Learned? |
|---------|-------------------|----------|
| FAIL-001 (overclaimed deployment) | SCHOL-106, SCHOL-109 include live verification | YES |
| FAIL-005 (hallucinated completion) | Functional verification present (php -l + live test) | PARTIAL |
| FAIL-002 (KB ID reuse) | KB-0020 through KB-0025 sequential and unique | YES |
| FAIL-003 (naming inconsistency) | SCHOL-106, SCHOL-109 zero-padded | YES |
| FAIL-004 (permission mismatch) | Division councils recording gate verdicts | YES |

**Verdict:** 4/5 lessons demonstrably learned. Strong self-correction capability.
