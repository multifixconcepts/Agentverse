# Learning Verification — AgentVerse 2.0

Version: 2.0.1

## Principle

An organization that learns but cannot prove it learned has only pretended to learn.

Every lesson recorded in KNOWLEDGE_BASE.md, every failure logged in FAILURE_LOG.md, and every pattern identified must be verifiable. If the organization cannot demonstrate that knowledge is being applied, the knowledge is decorative, not operational.

---

## Verification Categories

### a) Post-Implementation Verification

After every ticket is closed, verify that the implementation matches the original requirement — not a reinterpretation, not an approximation, not a "close enough" substitute.

**What to verify:**
- The implemented behavior matches the ACs in the requirement_ledger.
- No requirement was silently dropped or reinterpreted.
- The evidence produced by the implementing agent actually supports the claim.

**Procedure:**
1. Run `_tools/verify-gate.sh` against the ticket.
2. Compare verification output to the original requirement_ledger entry.
3. Flag any discrepancy between what was requested and what was delivered.

### b) Cross-Session Verification

When a new model or session takes over work, verify that it loads and applies lessons from previous sessions before making changes.

**What to verify:**
- The new session reads AGENTVERSE_BOOT.md.
- The new session reads FAILURE_LOG.md and identifies entries relevant to the current task.
- The new session reads KNOWLEDGE_BASE.md and references applicable lessons.

**Procedure:**
1. Confirm the new session has loaded the three core files.
2. Check that the session's first actions reference relevant historical context.
3. If the session proceeds without consulting history on a task with known failures, flag as UNVERIFIED learning application.

### c) Failure Pattern Matching

When a new task matches a known failure pattern documented in FAILURE_LOG.md, verify that the agent checks for and accounts for the pattern before proceeding.

**What to verify:**
- Agent cites relevant FAILURE_LOG entries before beginning implementation.
- Agent's approach explicitly addresses the identified failure mode.
- Agent's evidence demonstrates the failure pattern was avoided.

**Procedure:**
1. Compare the new task description against FAILURE_LOG.md entries.
2. If a pattern match exists, verify the agent referenced it.
3. If the agent did not reference a relevant failure pattern, flag as UNVERIFIED.

### d) Knowledge Currency

Verify that KNOWLEDGE_BASE.md entries are referenced within 30 days of their creation or flagged for review. Entries that go unverified become stale and potentially misleading.

**What to verify:**
- Each KB entry has been referenced or verified within the last 30 days.
- Entries that have not been referenced are flagged for review.
- Flagged entries are either re-verified, updated, or removed.

**Procedure:**
1. Monthly scan of all KB entries.
2. Check reference timestamps against current date.
3. Flag entries older than 30 days without a recent reference.
4. Report flagged entries for review.

### e) Contradiction Detection

Verify that no two entries in KNOWLEDGE_BASE.md contradict each other. Contradictory knowledge leads to inconsistent behavior across sessions.

**What to verify:**
- No two KB entries make conflicting claims about the same topic.
- No KB entry contradicts a FAILURE_LOG entry.
- No KB entry contradicts the current contract_registry.

**Procedure:**
1. Compare KB entries pairwise for conflicting statements.
2. Cross-reference KB entries against FAILURE_LOG entries.
3. Cross-reference KB entries against contract_registry.
4. Report any contradictions found.

---

## Verification Procedures Summary

| Category | Trigger | Tool/Method | Output |
|----------|---------|-------------|--------|
| Post-implementation | Ticket closed | `_tools/verify-gate.sh` | Match/dismatch report |
| Cross-session | New session starts | File load check | Load confirmation or flag |
| Failure pattern | New task created | FAILURE_LOG.md scan | Pattern match + citation check |
| Knowledge currency | Monthly | KB entry scan | Stale entry report |
| Contradiction | Monthly | Pairwise comparison | Contradiction report |

---

## Metrics

**LEARNING_VERIFICATION_RATE** = entries verified / total entries targeted

**Target: 100%**

Any entry that cannot be verified is flagged. Any flag that is not resolved within 7 days is escalated. An organization that accepts unverified knowledge accepts unreliable behavior.
