# Context Reconstruction — Model-Independent Recovery

**Version:** 2.0.1
**Owner:** chief-architect
**Status:** Active

---

## Purpose

When a new model enters, it must reconstruct full context without conversation history. Context reconstruction is the mandatory first action after any model failover or session restart.

---

## Step-by-Step Procedure

1. **Read `AGENTVERSE_BOOT.md`** — the entrypoint. This defines the recovery procedure and model responsibilities.

2. **Load all durable state files:**
   - `CURRENT_STATE.json` — last verified organizational state
   - `ENVIRONMENT_STATE.json` — environment health snapshot
   - `REQUIREMENT_LEDGER.json` — open requirements and their status
   - `CONTRACT_REGISTRY.json` — contracts that must be satisfied
   - `AGENT_REGISTRY.json` — agent identities and roles
   - `FAILURE_LOG.md` — known failure patterns
   - `KNOWLEDGE_BASE.md` — organizational knowledge and decisions
   - `ACTIVE_TICKET.md` — current work in progress
   - `TRUTH_HIERARCHY.md` — conflict resolution rules

3. **Determine current environment health** — run `_tools/doctor.sh` or read `ENVIRONMENT_STATE.json`.

4. **Identify active tickets** — from `CURRENT_STATE.json`, extract any ticket not yet closed.

5. **Load requirement details** — from `REQUIREMENT_LEDGER.json`, load full details for each active ticket.

6. **Load contracts** — from `CONTRACT_REGISTRY.json`, identify contracts tied to active tickets.

7. **Load failure patterns** — from `FAILURE_LOG.md`, identify patterns relevant to current work.

8. **Load domain knowledge** — from `KNOWLEDGE_BASE.md`, load validated decisions and lessons.

9. **Determine the VERIFIED STATE of all active work** — for each active ticket, determine what is proven vs. claimed.

10. **Begin work only from verified state** — do not assume any unverified work is complete.

---

## Anti-Patterns

| Anti-Pattern | Why It Fails |
|---|---|
| "Continue where the previous model left off" | Too vague. No specific resumption point. |
| "The previous agent said X was done" | Unverified claim. No evidence attached. |
| "Looking at the conversation history" | Conversation is not durable state. Lost on failover. |
| "I assume the previous step succeeded" | Assumptions are not verification. |
| "The logs show no errors" | Absence of errors is not proof of correctness. |

---

## Correct Patterns

| Pattern | Example |
|---|---|
| Resuming from verified state | "Resuming from CURRENT_STATE.json verified state as of [timestamp]" |
| Verifying previous claims | "Previous model CLAIMED X. Verifying X against durable artifacts..." |
| Incremental verification | "X is VERIFIED. Y is UNVERIFIED. Proceeding with Y verification..." |
| Citing evidence | "X passes because test suite at `_tests/test_X.py` exits 0 as of [commit]." |
| Declaring uncertainty | "Cannot verify Z. No durable artifact exists. Treating as incomplete." |
| Recognizing blocked state | "Ticket T is in VERIFICATION_BLOCKED. Verifier was unavailable. Preserving blocked state; not self-approving." |

---

## Recovery Time Target

- **< 60 seconds** from model switch to productive work
- First action: read `AGENTVERSE_BOOT.md`
- Second action: load durable state
- Third action: verify active claims
- Fourth action: resume work from verified state

---

## Verification Before New Work

- **100%** of claimed completions must be verified before starting new tasks
- If any claimed completion fails verification, create a `FAILURE_LOG.md` entry and halt that line of work
- Only proceed with tasks whose prerequisites are verified as complete
