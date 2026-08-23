# Model Failover Protocol — Worker Substitution

**Version:** 2.0.1 (2026-08-19)
**Owner:** chief-architect (Council of Architects)

**Core principle:** *The model is replaceable. The engineering state is not.*

---

## 1. What model failover IS

Model failover is **worker substitution**. When a model fails or rate-limits, the replacement model takes over the same way a new engineer joins a project: by reading durable state, not by guessing from hallway conversation.

The replacement model does NOT resume from conversation history. Conversation history is ephemeral, model-specific, and unreliable as a source of truth.

## 2. What model failover IS NOT

- It is NOT an organizational reset
- It is NOT a chance to redesign the architecture
- It is NOT a continuation of the previous model's claims
- It is NOT "pick up where we left off" (too vague, too dangerous)

## 3. Recovery sequence

When a model fails, the replacement model follows this exact sequence from `AGENTVERSE_BOOT.md`:

1. **Read `AGENTVERSE_BOOT.md`** — Understand the organizational boot process
2. **Load `CURRENT_STATE.json`** — Last verified state (PROVEN FACT only)
3. **Load `ENVIRONMENT_STATE.json`** — Environment health and configuration
4. **Load `REQUIREMENT_LEDGER.json`** — What needs to happen (INTENT)
5. **Load `CONTRACT_REGISTRY.json`** — What must be true (verification contracts)
6. **Load `FAILURE_LOG.md`** — Known patterns to avoid
7. **Determine LAST VERIFIED STATE** — Not the last model's claim, but what independent verification confirmed
8. **Resume from verified state only** — Ignore all unverified claims
9. **Begin with verification of prior claims before any new work** — Run `_tools/verify-gate.sh` on any claimed completions

## 4. Anti-patterns

| Anti-pattern | Why it's dangerous |
|---|---|
| "Continue where the previous model left off" | Too vague. The previous model may have claimed completions that are false. |
| "Re-read the conversation history" | Conversation is ephemeral and model-specific. Claims in conversation are unverified. |
| "Trust the ticket status field" | Ticket status is a CLAIM until backed by PROVEN FACT evidence. |
| "Skip verification to save time" | Skipping verification is how false positives propagate. |
| "Redesign the org because the new model sees improvements" | The org is state. The model is a worker. Workers don't redesign the org on day one. |

## 5. Correct pattern

> **Resume from durable organizational state. Never infer completion from conversational history.**

The replacement model's first action is always: verify what the previous model claimed, against what actually exists on disk.

## 6. Post-Failover Verification Checklist

Before resuming any production work, the replacement model MUST complete:

- [ ] Read and understand `AGENTVERSE_BOOT.md`
- [ ] Load and validate all durable state files (Section 3, steps 2–6)
- [ ] Verify all active tickets' claimed states against actual artifacts (file existence, test results, command output)
- [ ] Run `_tools/verify-gate.sh` on any ticket claimed to have passed a gate
- [ ] Run `_tools/doctor.sh` for environment health
- [ ] Only accept work that has `PROVEN FACT` status in `AGENTVERSE_CURRENT_STATE.json`
- [ ] Log any discrepancies in `FAILURE_LOG.md`

## 7. Trust Levels for Failover

| Level | Definition | Failover behavior |
|---|---|---|
| **PROVEN FACT** | Verified by an independent mechanism (script output, file hash, exit code) | Trust fully. Resume from this state. |
| **CLAIM** | Agent self-reported status (e.g., "tests passed", "AC-03 complete") | Do not trust until independently verified. Run verification script. |
| **INTENT** | Requirement statement (e.g., "should return 200 on valid input") | Trust as requirement, not as completion. Use to know what to verify. |

## 8. What NEVER changes on model switch

- Agent identities and roles (`AGENT_REGISTRY.json`)
- Organizational hierarchy (`AGENTVERSE.md`)
- Gate chain (`COHESION_MATRIX.md`)
- Knowledge state (`KNOWLEDGE_BASE.md`)
- Memory state (`MEMORY_INDEX.md` + `.memory/memory.json`)
- Ticket state (`AGENTVERSE/tickets/*.md`)
- Acceptance criteria for active tickets
- Verification requirements (`VERIFICATION_CONTRACT.md`)
- Truth hierarchy (`TRUTH_HIERARCHY.md`)
- Permission tiers and authority boundaries
- Architectural decisions and patterns
- Product conventions (`scholapro/` conventions)

## 9. Failure detection

After a model switch, watch for these signs of organizational degradation:

- Agents claiming completion without evidence
- Tickets being re-opened with different acceptance criteria
- Gate chain being bypassed or shortened
- Knowledge base entries being duplicated or contradicted
- Architectural decisions being revisited without cause
- Agent roles being redefined or confused
- Truth hierarchy being ignored
- Any state advancing without a corresponding PROVEN FACT entry in `CURRENT_STATE.json`

If any of these are detected, halt work and re-execute the recovery sequence (Section 3).

## 10. Rollback

If the new model cannot maintain organizational coherence:

1. Revert to the previous model if possible
2. If not possible, document the specific failures in `AGENTVERSE/FAILURE_LOG.md`
3. Escalate to the user with evidence of what went wrong
4. Do NOT continue production work until the issue is resolved
