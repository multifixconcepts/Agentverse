# Agentverse 2.0 — User Guide & Operating Handbook

How to effectively use the 70-agent software-engineering organization that runs inside OpenCode at `/home/coder/project`.

Companion docs: `AGENTVERSE.md` (ontology) · `COHESION_MATRIX.md` (control plane #1) · `MEMORY_INDEX.md` + `KNOWLEDGE_BASE.md` (control plane #2) · `OPENCODE_RUNTIME.md` (runtime mapping) · `AGENT_REGISTRY.json` (roster).

---

## 1. First things first

1. **Restart opencode** — agents, skills, commands, and `opencode.jsonc` are loaded once at startup. Quit and reopen opencode, then open `/home/coder/project`.
2. **Sanity check** after restart:
   - 70 agents: `ls .opencode/agents | wc -l` → `70`
   - Registry: `AGENTVERSE/AGENT_REGISTRY.json` → `"total_agents": 70`
   - Command: type `/` in the TUI → `summon` appears.
3. **Read this guide** (or point the Summoner at it).

## 2. The mental model

```
You (user)
   │  one request
   ▼
 SUMMONER ── triage, delegate, enforce gates, collect evidence
   │  ┌─────────────────────────────┐
   ├──▶ Division council (owner)   │  Feature · Integration · Quality · Security · Data · Platform
   │   └──▶ specialists            │
   ├──▶ Councils / Guilds (oversight)  Architecture · Agent Foundry · Knowledge Commons ·
   │                                   Quality Guardians · Tooling Council · Documentation Guild
   └──▶ Gates G1–G6                │  every change ships only after the chain
```

- You talk to **one agent**: the Summoner. It routes everything else.
- Every other agent is a **subagent**: it does one job, reports evidence, and is gone.
- Nothing ships without the **gate chain** (G1 peer → G2 division → G3 architecture → G4 security → G5 quality → G6 release).

## 3. Your three ways to drive the org

### 3a. `/summon <ticket>` — one-keystroke full workflow (recommended)
Type `/summon` then describe a task. The Summoner triages, delegates, runs all gates, and reports a ledger.

> `/summon Work SCHOL-002 (warehouse.js 12.5 regressions) through the full gate chain`
> `/summon Fix the school4 login session issue`
> `/summon Deploy the rebranded scholapro code to school4 with a rollback plan`

### 3b. Talk to a specialist directly
Switch the active agent via the agent picker (e.g. `frontend-engineer`, `security-division-council`, `data-division-council`) for a narrow, well-scoped job. Remember: **specialists are subagents** — they execute one task and return; they don't run the whole org.

### 3c. Delegate explicitly through Summoner
Tell Summoner which agent(s) to involve:

> "Have `security-tester` review this diff, then run quality gates."
> "Ask `data-division-council` to plan the SYEAR 2026 rollover."

## 4. The roster (what each unit does)

| Unit | Agents | When you need |
|------|--------|---------------|
| **Summoner** | 1 | everything — always start here |
| **Feature Division** | 11 | product features & bug fixes in `scholapro` |
| **Integration Division** | 8 | MCP, webhooks, n8n, Docker, Portainer, DNS, SSH |
| **Quality Division** | 9 | test strategy, unit/integration/e2e/perf/a11y tests, regression |
| **Security Division** | 8 | threat modeling, vulns, secrets, hardening, auth, incidents |
| **Data Division** | 8 | schema/SQL, DB admin, backups, ETL, analytics, reports |
| **Platform Division** | 7 | host ops, SRE, CI/CD, monitoring, nginx, DR |
| **Council of Architects** | 3 | design authority (architecture gate) |
| **Agent Foundry** | 3 | the org's own agents/skills/registry |
| **Knowledge Commons** | 3 | knowledge & memory (control plane #2) |
| **Quality Guardians** | 3 | definition-of-done + release gate |
| **Tooling Council** | 3 | scripts, MCP, workflows |
| **Documentation Guild** | 3 | docs, API docs, runbooks |

Full list with responsibilities: `AGENTVERSE/AGENT_REGISTRY.json`.

## 5. The gate chain (why changes are trustworthy)

Every change must pass, in order:

| # | Gate | Who | What it proves |
|---|------|-----|----------------|
| G0 | Triage | Summoner | request is classified, ticket opened |
| G1 | Peer review | same-division peer | code is correct & conventional |
| G2 | Division review | division council | scope + acceptance criteria met |
| G3 | Architecture | Council of Architects | design is sound (fast-path waiver for isolated frontend changes) |
| G4 | Security | Security Division | no XSS/injection/secrets/auth issues |
| G5 | Quality | Quality Division | tests pass, no regressions |
| G6 | Release | Quality Guardians | definition-of-done, changelog updated |

Gate verdicts + evidence are recorded per ticket in `AGENTVERSE/tickets/`. You can always audit why something shipped (or didn't).

**Rules of engagement:**
- Agents report **evidence** (`file:line`, commands run, test output) — never bare assertions.
- If a gate blocks, the ticket names exactly what's wrong.
- You can override a gate decision, but it's recorded (that's the point).

## 6. Where knowledge lives (control plane #2)

- **`AGENTVERSE/KNOWLEDGE_BASE.md`** — validated decisions, issue records (KB-####), proficiency records, lessons. Read it before starting similar work.
- **`AGENTVERSE/MEMORY_INDEX.md`** — operational index + shared facts (deployments, environment, recovery state).
- **`.memory/memory.json`** — shared graph memory (memory MCP server) for durable entity facts.
- **`AGENTVERSE/tickets/`** — per-ticket gate ledgers (e.g. `SCHOL-001.md`).

Convention: *search KB first, then code. If they disagree, code wins — file a correction.*

## 7. Skills that trigger automatically

Loaded from `.opencode/skills/`; each fires when its description matches the task:
- **delegate** — Summoner's routing workflow
- **review-gate** — gate-chain protocol for reviewers
- **scholapro** — product conventions + how to verify on PHP 8.1 / node
- **mcp-ops** — using the MCP servers (sqlite, git, curl enabled)

## 8. Production access (permission tiers)

| Tier | What | Example agents |
|------|------|----------------|
| read (R) | review/verify only | architects, auditors, reviewers |
| read-write (RW) | edit project code | engineers, planners, testers |
| production (P) | host/prod, gated | host-ops-specialist, docker-ops, portainer-ops, db-admin, release-custodian |

Production agents must log every action in the ticket and never expose credentials. Host access uses the recovered SSH `extravus-prod` path; MCP secrets (portainer/n8n) are env-only.

## 9. Example session scripts

**Full feature pilot:**
```
/summon Implement the <thing> feature in scholapro with tests, CHANGES.md, and full gates.
```

**Bug hunt + fix:**
```
/summon Find and fix the <symptom> in scholapro. Route through Feature Division; run all gates.
```

**Security review of a change:**
```
/summon Have security-division-council review the recent scholapro changes for XSS/injection/secrets.
```

**Production maintenance (host):**
```
/summon Have host-ops-specialist + portainer-ops check the health of the school4 stack and report.
```

**Research / decision:**
```
/summon Ask search-librarian and council_of_architects whether we should upgrade scholapro to upstream 12.9.
```

## 10. Keeping the org healthy

- **Regenerate agents after roster edits:** edit `_tools/gen-agents.js`, then `/usr/lib/code-server/lib/node _tools/gen-agents.js`.
- **Add agents/skills/commands:** drop files in `.opencode/agents|skills|commands/`, update the registry (`gen-agents.js`), **restart opencode**.
- **Skills bundled:** `delegate`, `mcp-ops`, `review-gate`, `scholapro`, `school4-ops` (live prod runbook), `task-ledger` (ticket/gate conventions).
- **Session audit trail:** the `session-ledger` plugin appends session lifecycle events to `AGENTVERSE/.sessions/session.log.ndjson`.
- **Knowledge hygiene:** route durable learnings to `KNOWLEDGE_BASE.md` via `knowledge-curator`; retire stale entries with a reason.
- **Tuning:** run a real ticket per division (like SCHOL-001) and update the proficiency records in `KNOWLEDGE_BASE.md` §4; adjust `COHESION_MATRIX.md` when the org disagrees.

## 11. Troubleshooting

| Symptom | Fix |
|---------|-----|
| `summon` command / agents / skills not visible | restart opencode (config loads at startup) |
| `/summon` errors "agent summoner not found" | verify `mode: primary` in `.opencode/agents/summoner.md` and restart |
| MCP server missing | servers: filesystem, memory, sqlite, git, curl, playwright; reinstall via `.mcp/` (see `OPENCODE_RUNTIME.md`) |
| Agent produced no evidence | push back: "report file:line + commands + test output, no assertions" |
| Org bypasses a gate | fast-path waiver only for isolated frontend changes; demand the waiver in the ticket |
| Conflicting KB vs code | trust code; have memory-steward file a correction |
| Want a full diff of what the org changed | `git status` / `git diff` in `/home/coder/project` (scholapro is currently untracked) |

## 12. Current state (as of this writing)

- **Pilot complete:** SCHOL-001 (calendar date-picker regression) fixed + released through all gates; ledger in `AGENTVERSE/tickets/SCHOL-001.md`.
- **Resolved:** SCHOL-004 — school4 custom-module delete fixed live (www-data ownership); see `AGENTVERSE/tickets/SCHOL-004.md`, KB-0008.
- **Tooling:** 15 code-server extensions + Playwright MCP (browser E2E) + 2 new skills + session-ledger plugin + config tuning (KB-0009).
- **Queued:** SCHOL-002 (warehouse.js mobile `#!`/CSP regressions); KB-0004 (school4 prod drift + SYEAR 2026 rollover).
- **Good next task:** `/summon Work SCHOL-002 through the full gate chain.`
