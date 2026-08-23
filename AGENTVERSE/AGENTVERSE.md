# AGENTVERSE — Organization Ontology & Operating Model

**Version:** 2.0.1 (2026-08-19)
**Owner:** chief-architect (Council of Architects)
**Supersedes:** Agentverse Dungeon (rebuild-complete milestone)
**Org:** Agentverse 2.0 — a 70-agent professional software-engineering organization.
**Scope root:** `/home/coder/project`
**Product:** `scholapro/` — ScholaPro Educational Management Platform (a RosarioSIS 12.5 fork).
**Reconstruction basis:** Historical Agentverse (65+ agents) destroyed by a server migration; rebuilt from scratch on the recovered capability map. Control planes: `COHESION_MATRIX.md` (#1) and `MEMORY_INDEX.md` + `KNOWLEDGE_BASE.md` (#2). User-facing handbook: `USER_GUIDE.md`.

**Entry point:** `/summon <request>` command (`.opencode/command/summon.md`) delegates any task to the Summoner for full gate-chain execution.

---

## 1. Ontology

```
                        ┌────────────┐
                        │  SUMMONER  │  sole entry point; classifies, delegates, escalates
                        └─────┬──────┘
       ┌──────────────────────┼──────────────────────────┐
       │       OVERSIGHT COUNCILS & GUILDS (18)          │
       │  Council of Architects (3)   Agent Foundry (3)  │
       │  Knowledge Commons (3)       Quality Guardians(3)│
       │  Tooling Council (3)         Documentation Guild│
       └──────────────────────┬──────────────────────────┘
                              │ delegates delivery to
   ┌─────────┬─────────┬──────┴──────┬─────────┬─────────┐
 FEATURE   INTEG.    QUALITY       SECURITY   DATA    PLATFORM
 DIV(11)   DIV(8)    DIV(9)        DIV(8)    DIV(8)   DIV(7)
```

- **Summoner (1)** — the only agent end-users talk to.
- **Councils & Guilds (18)** — cross-cutting authority: architecture, agent engineering, knowledge, quality/release, tooling, docs.
- **Divisions (51)** — delivery bodies: Feature, Integration, Quality, Security, Data, Platform.
- **Total: 70 agents** (canonical list: `AGENT_REGISTRY.json`).

## 2. Capability map (historical names preserved)

`summoner` · `council_of_architects` · `agent_foundry` · `knowledge_commons` · `quality_guardians` · `tooling_council` · `documentation_guild` · `feature_division` · `integration_division` · `quality_division` · `security_division` · `data_division` · `platform_division`

## 3. Delegation model

1. Summoner triages the request → classifies by domain → records a **ticket**.
2. Summoner delegates to the owning **division council** (Feature for product work; Security/Quality for gates; etc.).
3. Division council allocates to **specialists**; cross-cutting concerns route to **councils/guilds** via the cohesion matrix.
4. Each delegate returns **evidence** (files, commands, test output), never bare assertions.
5. Blockers/escalations go up the registered `escalates_to` chain to the Summoner.

## 4. Review gates (control plane #1: COHESION_MATRIX.md)

Every change ships only after the gate chain. Order and owners:

| # | Gate | Owner | Enforces |
|---|------|-------|----------|
| G1 | Peer review | same-division specialist | code correctness, conventions |
| G2 | Division review | division council | scope, acceptance criteria |
| G3 | Architecture | Council of Architects | design integrity, structure |
| G4 | Security | Security Division | threats, secrets, XSS/injection |
| G5 | Quality | Quality Division | tests, regression, standards |
| G6 | Release | Quality Guardians | definition-of-done, changelog |

Small/isolated changes may fast-path G1+G5 with documented waiver (approved by division council + Quality Guardian).

## 5. Permission tiers

| Tier | Scope | Agents |
|------|-------|--------|
| `read` (R) | read-only; review/verify | architects, reviewers, auditors |
| `read-write` (RW) | edit project code | engineers, planners, testers |
| `production` (P) | host/prod touches, gated | host-ops, docker-ops, portainer-ops, db-admin, release, security ops |

Production agents must document every action; never expose credentials.

## 6. Operating rules

- Consult `AGENTVERSE/` control planes before acting.
- Report concrete evidence (`file:line`, commands, test output). No fabricated findings.
- Do not bypass gates. Request waivers explicitly.
- Knowledge decisions land in `KNOWLEDGE_BASE.md`; persisted operational state in `MEMORY_INDEX.md`.
