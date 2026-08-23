# OpenRouter Failover Strategy — AgentVerse 2.0

**Version:** 2.0.1
**Owner:** chief-architect
**Status:** Active

---

## Principle

Models become interchangeable reasoning workers. They don't own organizational memory, release truth, project state, or the authority to declare their own work complete.

---

## Architecture

```
                 OPENROUTER
                     │
              openrouter/free
                     │
        ┌────────────┼────────────┐
        ↓            ↓            ↓
      Model A      Model B      Model C
        │            │            │
        └────────────┼────────────┘
                     ↓
             AgentVerse Contract
                     ↓
              Durable State
                     ↓
            Independent Evidence
```

---

## How the OpenRouter Free Model Pool Works

- Primary model selected in `opencode.json`
- Rate limited → automatic failover to next free model
- Each model reads `AGENTVERSE_BOOT.md` on entry
- Each model loads durable state, not conversation
- Each model's output is verified against durable state

---

## Failover Procedure

1. Current model hits rate limit or error
2. OpenRouter routes to next available model
3. New model reads `AGENTVERSE_BOOT.md`
4. New model loads `CURRENT_STATE.json`
5. New model loads `REQUIREMENT_LEDGER.json`
6. New model determines LAST VERIFIED STATE
7. New model resumes from verified state, NOT from last model's claim
8. New model verifies any claims from previous model before accepting them

---

## What Each Model Receives

| File | Purpose |
|------|---------|
| `AGENTVERSE_BOOT.md` | Recovery procedure |
| `CURRENT_STATE.json` | Verified state |
| `ENVIRONMENT_STATE.json` | Environment health |
| `REQUIREMENT_LEDGER.json` | What needs to happen |
| `CONTRACT_REGISTRY.json` | What must be true |
| `TRUTH_HIERARCHY.md` | Conflict resolution |
| `FAILURE_LOG.md` | Known patterns |
| `KNOWLEDGE_BASE.md` | Organizational knowledge |
| `ACTIVE_TICKET.md` | Current work |

## What Each Model Does NOT Receive

- Previous model's conversational history
- Previous model's unverified claims
- Authority to self-approve

---

## Post-Failover Verification

After a failover, the new model MUST:

1. Run `_tools/verify-gate.sh` on any ticket claimed complete
2. Run `_tools/doctor.sh` for environment health
3. Only accept work with **PROVEN FACT** status
4. If any claim fails verification, halt and report

```
POST-FAILOVER DECISION TREE:

  Previous model claimed X complete?
       │
       ├── Yes → Run verify-gate.sh on X
       │           │
       │           ├── PASS → Accept X as proven
       │           │
       │           └── FAIL → Mark X as untrusted
       │                       Do NOT build on X
       │                       Report discrepancy
       │
       └── No → Resume from CURRENT_STATE.json
```

---

## Failover Identity

Every model operating under AgentVerse is bound to the same contract:

- **Identity:** Worker, not authority
- **Authority:** Delegated, not inherent
- **Memory:** Durable state, not conversation
- **Truth:** Verified facts, not claims
- **Completion:** Only the gate verdict declares completion
