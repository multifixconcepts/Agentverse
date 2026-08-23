# SCHOL-814: Post-Stress-Test Mechanical Enforcement Remediation

**Status:** IN_PROGRESS
**Type:** infra
**Priority:** high
**Source:** AGENTVERSE_2_0_1_PRODUCTIVITY_STRESS_TEST_V2
**Created:** 2026-08-23

## Purpose

Remediate only weaknesses empirically demonstrated by the rigorous productivity stress test (15 scenarios, 47/50 ACs met, L3+ confirmed, L4 not confirmed).

This ticket does NOT redesign AgentVerse. It adds mechanical enforcement where documentation-only enforcement was demonstrated to be weak.

## A. Empirically Demonstrated Weaknesses

| # | Finding | Test | Severity | Evidence |
|---|---------|------|----------|----------|
| 1 | No mechanical release-block for VERIFICATION_BLOCKED tickets | TEST 13 | P0 | No tool in _tools/ prevents releasing a set containing blocked tickets |
| 2 | Self-verification prohibition not propagated into deployed agent definitions | TEST 12 | P0 | quality-guardian.md contains zero SoD language; STATE_MAP.json missing |

## B. Existing/Documented Limitations

| # | Limitation | Status |
|---|-----------|--------|
| 1 | No CI/CD pipeline active | No .github/ directory; no automated enforcement possible |
| 2 | verify-gate.sh depends on local PHP | Safe but operationally incomplete |
| 3 | STATE_MAP.json referenced by SoD but never created | Dead reference |

## C. New Controls Being Introduced

| # | Control | Type | Traces To |
|---|---------|------|-----------|
| 1 | `_tools/verify-release-set.sh` | New tool | Finding 1 |
| 2 | SoD clauses in generated agent definitions | gen-agents.js fix | Finding 2 |
| 3 | STATE_MAP.json creation | New artifact | Limitation 3 |
| 4 | Docker-aware PHP verification in verify-gate.sh | Tool improvement | Limitation 2 |

**Important:** These controls were NOT validated by the original stress test. They are subsequent engineering work.

## Acceptance Criteria

- AC-01: `verify-release-set.sh` exists and fails closed when release set contains VERIFICATION_BLOCKED tickets
- AC-02: `verify-release-set.sh` passes when release set contains no blocked tickets
- AC-03: Generated verifier agent definitions contain self-verification prohibition language
- AC-04: STATE_MAP.json exists with valid role assignments from authoritative sources
- AC-05: verify-gate.sh uses Docker PHP when local PHP unavailable
- AC-06: All existing regression tests (30/30) still pass
- AC-07: All existing adversarial tests (28/28) still pass
- AC-08: New tests (R1-R16) pass
- AC-09: ORG_CHECKSUM.json recomputed
- AC-10: CURRENT_STATE.json updated

## Files Affected

- `_tools/verify-release-set.sh` (new)
- `_tools/gen-agents.js` (modified — SoD clauses)
- `_tools/verify-gate.sh` (modified — Docker fallback)
- `_tools/doctor.sh` (modified — release-block check)
- `AGENTVERSE/STATE_MAP.json` (new)
- `AGENTVERSE/SEPARATION_OF_DUTIES.md` (reference fix)
- `.opencode/agents/*.md` (regenerated)
- `_tests/remediation-tests.sh` (new)
