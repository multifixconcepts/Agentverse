# AGENTVERSE MODEL RESILIENCE AUDIT

**Phases 8 — Model Change / Provider Interruption Simulation**

---

## Continuity Test Results

### Could a new model resume work using control planes?

**PARTIAL** — with significant gaps:

**Works:**
- MODEL_FAILOVER_PROTOCOL.md provides 5-step procedure
- ORG_CHECKSUM.json provides integrity hashes for 5 files
- TRUTH_HIERARCHY.md provides conflict resolution
- COHESION_MATRIX provides gate chain
- AGENT_REGISTRY.json provides 70-agent roster

**Fails:**
- scholapro/Warehouse.php says 12.4.2 but prod is 12.9.2 (wrong version context)
- Ticket files use free-form Markdown (fragile to parse)
- HANDOFF_TEMPLATE exists but tickets don't use its format
- No entrypoint that auto-executes the recovery protocol

---

## Bootstrapping Problem

The MODEL_FAILOVER_PROTOCOL says "The replacement model MUST read these files before resuming work." But HOW does it know to find this file?

**The paradox:** The knowledge that recovery documentation exists is itself contextual knowledge that would be lost on a model switch. No auto-executing entrypoint, no `init.md`, no self-bootstrapping mechanism.

**Impact:** Works if a human directs the new model; fails for fully autonomous recovery.

---

## Session Recovery

**Session log:** 164 entries, ALL with `"sessionID":null`. Session correlation impossible.

**Actual session recovery:** Done by querying opencode SQLite DB directly (`sqlite3 ... "SELECT id FROM session ORDER BY time_created DESC LIMIT 1"`), completely bypassing the NDJSON log.

**Impact:** The session-ledger plugin produces useless output. Session-level memory is unrecoverable via the documented mechanism.

---

## Durable Artifact Coverage

| Artifact Type | Durable? | Coverage |
|---------------|----------|----------|
| Requirements | YES — in ticket files | GOOD |
| Decisions | YES — in KB entries | GOOD |
| Gate verdicts | YES — in ticket gate ledger | ADEQUATE |
| Implementation state | PARTIAL — ticket Status field only | WEAK |
| Test results | PARTIAL — in ticket evidence sections | ADEQUATE |
| Architecture | PARTIAL — AGENTVERSE.md, no ARD | WEAK |
| Conversational context | NO — trapped in session | MISSING |

**Key gap:** Conversation-trapped knowledge includes: the decision process behind KB-0017's NPM cache purge, user aesthetic preferences, operational passwords, and the 12.5-fork vs 12.4.2-prod drift rationale.

---

## Model-Specific Knowledge

The system depends on knowledge that is specific to the OpenCode runtime:
- How to invoke subagents via the task tool
- How to read/write files via the filesystem MCP
- How to run bash commands via the bash tool
- How to use the SQLite MCP for database queries

This knowledge is NOT documented in any control plane. A model from a different provider (e.g., one that uses a different agent invocation API) would not know how to operate the system.

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| New model can't find recovery protocol | MEDIUM | HIGH | Add init.md or startup hook |
| New model gets wrong version context | HIGH | HIGH | Sync codebase to 12.9.2 |
| New model doesn't know OpenCode API | LOW (if same platform) | HIGH | Document API in control planes |
| Session context lost | CERTAIN | MEDIUM | Fix session log or accept loss |
| Gate chain not followed | MEDIUM | CRITICAL | Add automated gate enforcement |
