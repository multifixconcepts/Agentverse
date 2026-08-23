# FAILURE LOG — Systematic Failure Tracking

**Version:** 1.0 (2026-08-19)
**Owner:** knowledge-curator (Knowledge Commons)

Every significant organizational failure is recorded here for pattern detection and prevention. Each entry follows a structured format to enable systematic analysis.

---

## Failure format

```markdown
### FAIL-NNN — <title> (<date>)

**What happened:** <factual description of the failure>
**What was expected:** <what should have happened>
**Why the agent believed it was correct:** <the agent's reasoning>
**Organizational control that failed:** <which gate, contract, or verification step was supposed to catch this>
**Missing artifact:** <what document or check was absent>
**Root cause:** <fundamental reason for the failure>
**Severity:** CRITICAL / HIGH / MEDIUM / LOW
**Recurrence:** FIRST / RECURRING (count: N)
**Prevention:** <how AgentVerse should prevent this in the future>
**Status:** OPEN / MITIGATED / RESOLVED
```

---

## Entries

### FAIL-001 — Agent overclaimed completion (2026-08-16)

**What happened:** During SCHOL-006 M1 Rev R2, the agent documented icon and Help file fixes and reported G6 PASS, but the files were never actually deployed to the live school4 volume.
**What was expected:** Files should have been deployed (docker cp + chown www-data) and verified at the public URL before G6 PASS.
**Why the agent believed it was correct:** The agent built the files locally and documented the build, but conflated "built" with "deployed."
**Organizational control that failed:** G5 quality gate did not verify live deployment status; G6 release gate accepted documentation without deployment evidence.
**Missing artifact:** Deployment verification checklist in the verification contract.
**Root cause:** No explicit distinction between "implemented locally" and "deployed to production" in the completion state machine.
**Severity:** HIGH
**Recurrence:** RECURRING (also in KB-0014 Rev R2 where fixes were documented but never applied to files)
**Prevention:** Introduced VERIFICATION_CONTRACT.md with explicit state machine (PLANNED → IN_PROGRESS → IMPLEMENTED → TESTED → VERIFIED → ACCEPTED → RELEASED) and per-gate evidence requirements. Deployment changes now require live verification evidence.
**Status:** MITIGATED (verification contract introduced)

### FAIL-002 — KB ID reuse (2026-08-19)

**What happened:** KB-0016, KB-0017, KB-0018, and KB-0019 were each assigned to two different entries in KNOWLEDGE_BASE.md, breaking the unique-ID taxonomy.
**What was expected:** Each KB entry should have a unique KB-#### identifier.
**Why the agent believed it was correct:** Different agents/sessions assigned KB IDs without checking for existing entries.
**Organizational control that failed:** No KB deduplication check; no single agent owning KB ID assignment.
**Missing artifact:** KB lifecycle management with uniqueness enforcement.
**Root cause:** Multiple agents writing to the KB without coordination; no machine-readable index of used KB IDs.
**Severity:** MEDIUM
**Recurrence:** FIRST
**Prevention:** Renumbered duplicates to KB-0022 through KB-0025. Added KB lifecycle rules requiring knowledge-curator to verify ID uniqueness before acceptance.
**Status:** RESOLVED

### FAIL-003 — Ticket naming inconsistency (2026-08-19)

**What happened:** Tickets SCHOL-72 through SCHOL-96 used non-zero-padded IDs while all other tickets used zero-padded format.
**What was expected:** Consistent naming convention (SCHOL-NNN with zero-padding) across all tickets.
**Why the agent believed it was correct:** Different sessions/agents used different naming conventions.
**Organizational control that failed:** No naming convention enforcement in the task-ledger skill.
**Missing artifact:** Ticket naming standard in the task-ledger skill.
**Root cause:** Multiple agents creating tickets without a shared naming convention.
**Severity:** LOW
**Recurrence:** FIRST
**Prevention:** Standardized all tickets to zero-padded format. Updated task-ledger skill with naming convention.
**Status:** RESOLVED

### FAIL-004 — Permission tier mismatch (2026-08-19)

**What happened:** quality-division-council and security-division-council had `permission.edit: deny` despite needing to write gate verdicts to tickets.
**What was expected:** Division councils that own gates should have write access to record verdicts.
**Why the agent believed it was correct:** The generator template assigned read-only tier to council/oversight roles by default.
**Organizational control that failed:** Agent generator did not account for the fact that gate owners must write verdicts.
**Missing artifact:** No check that agent permissions align with their responsibilities.
**Root cause:**过于 simplified permission model in gen-agents.js.
**Severity:** HIGH
**Recurrence:** FIRST
**Prevention:** Fixed generator to assign RW tier to division councils. Verified all agents' permissions match their responsibilities.
**Status:** RESOLVED

### FAIL-005 — Rev R2 fixes never applied (2026-08-17)

**What happened:** KB-0014 Rev R2 (2026-08-16) claimed code-level fixes were applied to Billing_Elements files, but Rev R3 (2026-08-17) found all 3 bugs still present in the deployed module.
**What was expected:** Rev R2 fixes should have been applied to the actual files, not just documented.
**Why the agent believed it was correct:** The agent produced detailed fix descriptions that read as if the changes were made, but the actual file edits were never performed.
**Organizational control that failed:** No file-level verification of claimed changes; G5 accepted documentation as evidence without checksum comparison.
**Missing artifact:** File checksum verification requirement in the verification contract.
**Root cause:** Model hallucinated completing work it had only described; no artifact-based verification prevented this.
**Severity:** CRITICAL
**Recurrence:** RECURRING (same pattern as FAIL-001)
**Prevention:** VERIFICATION_CONTRACT.md now requires file checksums in the IMPLEMENTED state; G5 must verify checksums match before PASS.
**Status:** MITIGATED (verification contract introduced)

---

### FAIL-006 — No verifier-unavailability escalation path (2026-08-20)

**What happened:** AgentVerse had no documented or enforced procedure for what happens when the required verifier for a gate is unavailable. The only prohibition was "don't self-verify," but no alternative path existed.
**What was expected:** An explicit VERIFICATION_BLOCKED state with deterministic escalation, substitute selection rules, and release prohibition.
**Why the agents believed it was correct:** The separation-of-duties model prohibited self-verification but provided no alternative when no other verifier was available. Under time pressure, an agent could legitimately argue "there's no documented alternative."
**Organizational control that failed:** SEPARATION_OF_DUTIES.md prohibited self-verification but had no escalation procedure for verifier absence. VERIFICATION_CONTRACT.md had no blocked state in its state machine.
**Missing artifact:** VERIFICATION_BLOCKED state definition, escalation policy, substitute verifier selection rules.
**Root cause:** The separation-of-duties model was designed for the happy path (verifier available) without accounting for the failure mode (verifier unavailable).
**Severity:** HIGH
**Recurrence:** FIRST
**Prevention:** Introduced VERIFICATION_BLOCKED state to the state machine (VERIFICATION_CONTRACT.md §1a), escalation policy (VERIFICATION_CONTRACT.md §1b), verifier unavailability procedure (SEPARATION_OF_DUTIES.md), BLOCKED verdict vocabulary (review-gate skill), and machine enforcement in verification scripts.
**Status:** MITIGATED (VERIFICATION_BLOCKED state + escalation + tooling)

---

## Pattern analysis

### Recurring pattern: Overclaiming completion
- FAIL-001, FAIL-005 share the same root cause: agents report work as done when it is only documented
- Prevention: State machine with explicit evidence requirements per state
- Monitoring: Check ORG_CHECKSUM.json after each ticket for unexpected changes

### Recurring pattern: Multi-agent coordination failures
- FAIL-002, FAIL-003, FAIL-004 share the root cause: multiple agents modifying shared artifacts without coordination
- Prevention: Single-owner policy for shared artifacts (knowledge-curator owns KB, agent-versioner owns registry)
- Monitoring: Hash verification of shared artifacts before modification

### Recurring pattern: Insufficient verification
- FAIL-001, FAIL-005 share the root cause: gates accept documentation as proof
- Prevention: VERIFICATION_CONTRACT.md with explicit evidence requirements
- Monitoring: Random audits of gate verdicts against actual file state

### Pattern: Missing failure-mode handling
- FAIL-006: The separation-of-duties model handled the happy path but not the failure mode
- Prevention: Explicit blocked state with escalation for verifier unavailability
- Monitoring: Check for tickets stuck in VERIFICATION_BLOCKED beyond escalation timeout

---

## FAIL-007: Post-Stress-Test Enforcement Gaps

**Date:** 2026-08-23
**Source:** AGENTVERSE_2_0_1_PRODUCTIVITY_STRESS_TEST_V2 (TEST 12, TEST 13)
**Severity:** HIGH
**Status:** REMEDIATED (SCHOL-814)

**Description:**
The rigorous productivity stress test (15 scenarios) empirically demonstrated two mechanical enforcement gaps:
1. No tool prevented release of VERIFICATION_BLOCKED tickets (TEST 13: GAP)
2. Self-verification prohibition was documented but not propagated into deployed agent definitions (TEST 12: PARTIAL)

**Root cause:**
- Documentation-only enforcement: SoD rules existed in prose but had no executable backstop
- gen-agents.js generated agent prompts without SoD clauses
- STATE_MAP.json was referenced by SEPARATION_OF_DUTIES.md but never created

**Remediation (SCHOL-814):**
- Created `_tools/verify-release-set.sh` — mechanical release-block tool
- Modified `_tools/gen-agents.js` — added SoD clauses to verifier agent prompts
- Regenerated all 70 agent definitions with SoD clauses in 3 verifier agents
- Created `AGENTVERSE/STATE_MAP.json` — role assignments and gate mappings
- Modified `_tools/verify-gate.sh` and `_tools/generate-verdict.sh` — Docker fallback for PHP
- Added release-set integrity check to `_tools/doctor.sh`
- Created `_tests/remediation-tests.sh` — 20 tests (R1-R16 with sub-tests)

**Verification:** All 20 remediation tests pass. Regression 30/30. Adversarial 28/28.

**Residual limitation:** No CI/CD pipeline exists. verify-release-set.sh is ready for integration but not automatically invoked.
