---
name: task-ledger
description: Opening, triaging, and closing Agentverse tickets with the cohesion gate ledger, KB numbering, and memory index updates. Use when starting or completing any change ticket (bug, feature, ops, query) or when recording KB/proficiency entries.
---

# Task Ledger Conventions

Governed by COHESION_MATRIX.md (gates), AGENTVERSE.md (delegation), and VERIFICATION_CONTRACT.md (evidence requirements). Ledger files: `AGENTVERSE/tickets/<TICKET>.md`.

## Ticket naming convention

- Format: `SCHOL-NNN` with zero-padded three-digit numbers
- Examples: SCHOL-001, SCHOL-072, SCHOL-109
- Sequential per org; reserved numbers still count
- KB IDs: `KB-NNNN` with zero-padded four-digit numbers

## Ticket state machine

Every ticket MUST follow this state machine (defined in VERIFICATION_CONTRACT.md):

```
PLANNED → IN_PROGRESS → IMPLEMENTED → EVIDENCE_GENERATED → TESTED → VERIFIED → ACCEPTED → RELEASED
                              ↓
                     VERIFICATION_BLOCKED → (resumes to VERIFIED when verifier available)
                              ↓
                         FAILED (if verifier rejects)
```

| State | Definition | Required evidence |
|-------|------------|-------------------|
| PLANNED | Ticket opened, scope defined | Acceptance criteria, affected files, risk assessment |
| IN_PROGRESS | Work started by assigned specialist | File changes in progress, intermediate state noted |
| IMPLEMENTED | All code changes applied | Changed files with checksums, syntax checks pass |
| EVIDENCE_GENERATED | Verification script has run against the implementation | Script output JSON, per-AC pass/fail, raw command output |
| TESTED | Automated verification executed | Actual command output (not assertions), test results |
| **VERIFICATION_BLOCKED** | **Implementation exists with evidence, but required independent verifier is unavailable** | **Evidence of verifier unavailability, escalation record, timestamped blocked-state entry** |
| VERIFIED | Independent verification passed | Independent mechanism confirms claims against artifacts |
| ACCEPTED | Acceptance criteria met | Each AC marked MET/NOT MET with proof |
| RELEASED | Shipped with full gate chain | G0–G6 all PASS, CHANGES.md updated, KB recorded |

### VERIFICATION_BLOCKED state rules

**Meaning:** The implementation may exist and may have evidence, but the required independent verifier is unavailable, so verification cannot proceed.

**VERIFICATION_BLOCKED MUST NOT mean:**
- VERIFIED
- ACCEPTED
- RELEASED
- PASS
- "probably verified"
- "verified by exception"

**Transitions into VERIFICATION_BLOCKED:**
- Only from IMPLEMENTED, EVIDENCE_GENERATED, or TESTED states
- Only when: (a) all available evidence has been generated, (b) the required verifier for the current gate has been identified, (c) the verifier is confirmed unavailable, (d) no qualified substitute verifier exists under separation-of-duties rules

**Transitions out of VERIFICATION_BLOCKED:**
- → VERIFIED: when a qualified verifier becomes available and independently verifies the work
- → FAILED: when a qualified verifier becomes available and rejects the work
- → IN_PROGRESS: when the implementer must redo work based on verifier feedback

**Prohibited transitions:**
- ✗ VERIFICATION_BLOCKED → VERIFIED (self-verification by implementer)
- ✗ VERIFICATION_BLOCKED → ACCEPTED (skipping verification)
- ✗ VERIFICATION_BLOCKED → RELEASED (releasing unverified work)
- ✗ Any state → VERIFICATION_BLOCKED → automatic PASS (no time-based expiry)

**Escalation requirements when VERIFICATION_BLOCKED:**
1. Implementer records blocked state with evidence of verifier unavailability
2. Implementer escalates to the division council (or Summoner if no council available)
3. Division council attempts to assign a qualified substitute verifier
4. If no qualified substitute exists, work remains blocked until a verifier becomes available
5. The blocked state is persisted in the ticket and in CURRENT_STATE.json
6. Release authority CANNOT bypass the blocked verification state

**Invariant:** IMPLEMENTED ≠ TESTED ≠ VERIFIED ≠ VERIFICATION_BLOCKED. Each state requires separate, distinct evidence.

## Epistemic States (CLAIM ≠ FACT)

Agents operate in three distinct epistemic states. Confusing them is the root cause of false-positive gate advances.

### States

| State | Definition | Trust level |
|-------|------------|-------------|
| **INTENT** | What the user or requirement says should happen. The requirement itself. | Trust as requirement, not as completion |
| **CLAIM** | What an agent says it did (e.g., "AC-04 complete", "Tests passed", "Gate G2 PASS"). Self-reported status. | Always UNVERIFIED until proven |
| **PROVEN FACT** | What an independent mechanism can verify: file exists, test passes, curl returns expected status, hash matches, `php -l` exits 0. | Trust fully — verified by independent mechanism |

### Rules

1. **Only PROVEN FACT can advance a gate.** CLAIM status is always UNVERIFIED until independently proven.
2. **Never infer completion from conversational history.** "The previous model said it was done" is a CLAIM, not a PROVEN FACT.
3. **Always verify against durable artifacts.** Files on disk, command exit codes, HTTP response codes — these are reality. Agent statements are not.

### Verification mechanisms

- `_tools/verify-gate.sh` — Runs per-ticket verification checks (syntax, existence, curl, hash) and outputs a JSON verdict.
- `_tools/generate-verdict.sh` — Produces a structured verdict from verification output.
- `AGENTVERSE_CURRENT_STATE.json` — The single source of truth for what has been verified. Only entries with `PROVEN FACT` status are trusted.

### Example flow

1. Agent claims: `"SCHOL-109 AC-01 through AC-05 complete"`
2. Verification script runs: `php -l` checks, `curl` checks, file existence checks
3. Script outputs JSON verdict with per-AC verification:
   ```json
   {
     "ticket": "SCHOL-109",
     "verdict": "PARTIAL",
     "acs": {
       "AC-01": "PROVEN FACT",
       "AC-02": "PROVEN FACT",
       "AC-03": "PROVEN FACT",
       "AC-04": "CLAIM",
       "AC-05": "PROVEN FACT"
     }
   }
   ```
4. Gate advances only if all ACs are `PROVEN FACT`
5. `AGENTVERSE_CURRENT_STATE.json` is updated with the verified result

## Ticket lifecycle

1. **Triage (G0):** classify type (bug/feature/ops/query/escalation), priority, product, affected files.
2. **Open ticket:** `AGENTVERSE/tickets/SCHOL-NNN.md` — Status, Type, Priority, Product, Files, Request, Acceptance criteria, then Gate ledger table. Triage first (runbook the classification) before doing work.
3. **Delegate** per cohesion matrix; keep ONE ledger entry per ticket.
4. **Gates:** G1 peer → G2 division → G3 architecture → G4 security → G5 quality → G6 release. Record verdict + concrete evidence (file:line, command output, test results). For info-only/query tickets or zero-diff decisions, record gates as **N/A** with rationale — never fabricate verdicts.
5. **Close:** Status (RELEASED / RESOLVED / CLOSED), CHANGES.md entry if a product diff, KB entry + proficiency record (KNOWLEDGE_BASE.md), MEMORY_INDEX line, and one-line summary to the user.

## Numbering rules

- Ticket IDs are sequential per org (`SCHOL-NNN`); **reserved numbers still count** (SCHOL-002 reserved for warehouse.js work — do not reuse). Query/info tickets take the next free ID.
- KB IDs: `KB-NNNN`; highest used + 1. KB lifecycle: propose → review → accept → index → retire.
- Proficiency rows go in the table under `## 4. Proficiency validation records` with Score 1–5 and evidence.

## Evidence-first rule

Every gate verdict and every KB entry must carry concrete evidence (command output, file:line, HTTP codes, DB state). Claims without evidence get rejected at review (lesson: KB-0006 false positive).

See VERIFICATION_CONTRACT.md for complete evidence requirements per gate.

## Changelog

| Version | Date | Change |
|---------|------|--------|
| 1.0 | 2026-08-19 | Initial skill definition |
| 2.0.1 | 2026-08-19 | Added Epistemic States (CLAIM ≠ FACT) model; added EVIDENCE_GENERATED and VERIFIED states to state machine; defined verification mechanisms and example flow |

**Version:** 2.0.1
