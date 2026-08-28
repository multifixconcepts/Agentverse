# TRUTH HIERARCHY — Source-of-Truth Precedence

**Version:** 2.0.1 (2026-08-19)
**Owner:** chief-architect (Council of Architects)

When information conflicts between sources, the following hierarchy determines which source wins. Higher-ranked sources override lower-ranked sources.

---

## Hierarchy (highest to lowest authority)

| Rank | Source | Location | Scope |
|------|--------|----------|-------|
| 1 | **Current running system** | Live school4 deployment | What is actually deployed and running |
| 2 | **Verified architectural state** | `AGENTVERSE/AGENTVERSE.md` | Organization structure, delegation model, gate chain |
| 3 | **Verified cohesion rules** | `AGENTVERSE/COHESION_MATRIX.md` | Gate ownership, RACI, collaboration pairs |
| 4 | **Verified project knowledge** | `AGENTVERSE/KNOWLEDGE_BASE.md` | Validated decisions, issue records, lessons learned |
| 5 | **Approved memory** | `AGENTVERSE/MEMORY_INDEX.md` + `.memory/memory.json` | Operational facts, deployment state *— MEMORY_INDEX is NON-AUTHORITATIVE for live ticket status (may be stale; see SCHOL-108); canonical status comes from the ticket files* |
| 6 | **Agent registry** | `AGENTVERSE/AGENT_REGISTRY.json` | Agent identities, roles, permissions |
| 7 | **Agent assumptions** | Agent prompt / contract | What the agent believes to be true |
| 8 | **Conversational context** | Current chat session | What was said in this conversation |

## Conflict resolution rules

### Rule 1: Code beats documentation
If `scholapro/` source code contradicts `KNOWLEDGE_BASE.md`, the code wins. File a KB correction with the memory-steward.

### Rule 2: Live system beats repo
If the live school4 deployment differs from the scholapro repo, the live system is the truth. File a drift ticket.

### Rule 3: Verified facts beat assumptions
If an agent's assumption contradicts a verified fact in the KB or memory, the verified fact wins. The agent must update its understanding.

### Rule 4: Architectural decisions beat expedience
If a shortcut would violate an architectural decision in AGENTVERSE.md, the decision wins unless the Summoner explicitly escalates to the user.

### Rule 5: Security findings block
A G4 security finding BLOCKS all lower-priority work until resolved or explicitly accepted with risk justification.

### Rule 6: Verification-unavailability blocks
When the required verifier for a gate is unavailable, the ticket enters VERIFICATION_BLOCKED state. This state CANNOT be bypassed by:
- The implementer self-verifying
- Time pressure or urgency claims
- The release authority overriding
- A model assuming "it would probably pass"

VERIFICATION_BLOCKED is a blocking state with the same authority as a G4 security finding. The work cannot proceed past the blocked gate until a qualified, independent verifier is available and performs verification.

### Rule 7: Contradictions require escalation
When two sources of equal rank contradict each other, escalate to the owning council:
- KB vs KB → knowledge-curator decides
- Agent vs Agent → division council decides
- Division vs Division → Summoner decides
- Architecture vs Feature → chief-architect decides (unless user overrides)

### Rule 8: Canonical ticket status overrides derived claims
For **live ticket status**, the canonical source is the ticket file under
`AGENTVERSE/tickets/` (matched by `SCHOL-<digits>.md`). A contradictory
assertion in `MEMORY_INDEX.md`, `REQUIREMENT_LEDGER.json`,
`CONTRACT_REGISTRY.json`, or `CURRENT_STATE.json` is a **derived/stale claim**
and loses. `CURRENT_STATE.json` is regenerated from the ticket files and
should be re-derived via `_tools/sync-state.sh` on any conflict.
Registries marked `HISTORICAL_UNVERIFIED` are never authoritative.

## When to apply

Apply the truth hierarchy when:
- An agent's instruction conflicts with a control-plane document
- A model change produces different understanding of the organization
- A new agent joins and must reconstruct organizational context
- A deployment differs from the repository
- Two agents produce incompatible assumptions about the same code
- A ticket contains conflicting information from different gates

## Model Failover Trust Rules

When a model failover occurs:

1. The previous model's **CONVERSATIONAL CLAIMS** are **UNVERIFIED** by default
2. The previous model's **DURABLE STATE CHANGES** (files written to disk) are **CLAIMS** until verified
3. The previous model's **VERIFICATION SCRIPT OUTPUTS** are **PROVEN FACTS** if the scripts are reproducible
4. **PROVEN FACTS** survive model failover completely
5. **CLAIMS** must be re-verified by the new model before being treated as facts
6. The new model's **FIRST DUTY** is to verify all claims from the previous model
7. Only after verification should the new model proceed with new work

### Trust Chain During Failover

| Source | Trust Level | Action |
|--------|-------------|--------|
| DURABLE STATE (`CURRENT_STATE.json`, verdicts, test results) | **TRUSTED** | Accept as-is |
| FILESYSTEM ARTIFACTS (committed code, test output) | **VERIFY** | Verify before trusting |
| CONVERSATION HISTORY | **DO NOT TRUST** | Ignore completely |
| PREVIOUS MODEL'S CLAIMS | **DO NOT TRUST UNTIL VERIFIED** | Run verification scripts |

### Verification Procedure

After failover, run `_tools/post-failover-verify.sh` to produce a structured verification report. The report classifies each claim as trusted or untrusted and recommends either RESUME or HALT_AND_VERIFY.

## Verification

After any model change or session restart, the agent should:
1. Read `AGENTVERSE/ORG_CHECKSUM.json` to confirm control-plane integrity
2. Verify the checksums match the actual files
3. If checksums mismatch, the control planes have been modified — read them fresh
