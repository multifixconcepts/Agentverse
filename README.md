<div align="center">

# AgentVerse 2.0

**A 70-agent software-engineering organization framework that runs locally inside [OpenCode](https://opencode.ai).**

AgentVerse is an engineering *orchestration* environment, not a deployed product. It coordinates a team of specialized AI agents to design, build, test, and deploy the software projects it is pointed at.

`LICENSE` · `CONTRIBUTING.md` · `SECURITY.md` · **Version 2.0.4**

</div>

---

## What AgentVerse is

AgentVerse is a **local multi-agent software-engineering organization**. It is a process specification plus an organizational model plus an OpenCode configuration that together let a coordinated team of AI agents act like a professional engineering org:

- **70 agents** in a real org topology — a Summoner entry point, oversight councils and guilds, and Feature / Integration / Quality / Security / Data / Platform divisions ([`AGENTVERSE/AGENT_REGISTRY.json`](AGENTVERSE/AGENT_REGISTRY.json)).
- **A mechanical review-gate chain** (G0–G6) through which every change must pass (peer review, division review, architecture, security, quality, release).
- **A file-based control plane** of JSON + Markdown state (`CURRENT_STATE.json`, `ENVIRONMENT_STATE.json`, `ORG_CHECKSUM.json`, `TRUTH_HIERARCHY.md`, `COHESION_MATRIX.md`, …) under [`AGENTVERSE/`](AGENTVERSE/).
- **Polyglot engineering capability** — validated toolchains across 13 languages in 3 execution tiers.
- **Tool integration** through stdio MCP servers (filesystem, memory, git, curl, sqlite, portainer, n8n) — all running inside the OpenCode TUI.
- **CI / validation** — a full GitHub Actions pipeline (regression, adversarial, remediation, polyglot, framework smoke, secret scan, doctor, state consistency, release-set verification).

## What AgentVerse is NOT

AgentVerse is **not** a deployed application. It has **no**:

- browser UI,
- web server / HTTP service,
- hostname or public endpoint of its own,
- database of its own,
- Docker stack or SaaS offering.

Its **only** interface is the OpenCode terminal. Everything else in this repository is a **target project** that AgentVerse can develop, test, or deploy.

## Target projects (developed/orchestrated by AgentVerse)

These live in this repository as workspaces AgentVerse works on. Their infrastructure and deployments are theirs, not AgentVerse's:

| Project | Purpose |
|---------|---------|
| `scholapro/` | ScholaPro — educational management platform (RosarioSIS fork) |
| `clientflow/` | ClientFlow — multi-tenant SaaS (Node.js + PostgreSQL) |
| `extravus-sis/` | RosarioSIS rebranding project |

## Getting started

### Prerequisites

- A local OpenCode installation (the TUI is AgentVerse's interface).
- Node.js for the MCP server tooling.
- The optional integration targets (Portainer, n8n) are configured via environment variables — AgentVerse runs fine without them.

### Run AgentVerse

```bash
git clone https://github.com/multifixconcepts/Agentverse.git
cd Agentverse
# launch OpenCode in this repository; agentverse loads from .opencode/ and AGENTVERSE/
opencode
```

Then invoke the org through its single entry point:

```
/summon <request>
```

The Summoner agent classifies the request, records a ticket, delegates to the owning division, and drives the result through the gate chain.

### Operating handbooks

- [`AGENTVERSE/AGENTVERSE.md`](AGENTVERSE/AGENTVERSE.md) — organization ontology and operating model
- [`AGENTVERSE/USER_GUIDE.md`](AGENTVERSE/USER_GUIDE.md) — operating handbook
- [`AGENTVERSE/OPENCODE_RUNTIME.md`](AGENTVERSE/OPENCODE_RUNTIME.md) — file-based system architecture

## Agent model

AgentVerse models a professional engineering organization:

```
                        ┌────────────┐
                        │  SUMMONER  │  sole entry point
                        └─────┬──────┘
    ┌─────────────────────────┼─────────────────────────┐
    │       OVERSIGHT COUNCILS & GUILDS (18)            │
    │  Architects · Agent Foundry · Knowledge · Quality │
    │  Tooling · Documentation                          │
    └─────────────────────────┬─────────────────────────┘
    ┌─────────┬─────────┬──────┴──────┬─────────┬─────────┐
 FEATURE   INTEG.    QUALITY       SECURITY   DATA    PLATFORM
 DIV(11)   DIV(8)    DIV(9)        DIV(8)    DIV(8)   DIV(7)
```

- **1 Summoner** — the only agent end-users talk to
- **18 Councils/Guilds** — cross-cutting authority
- **51 Division agents** — delivery bodies across 6 divisions
- **70 agents total**

Every agent reads and updates the file-based control plane, returns **evidence** (not bare assertions), and escalates blockers up a registered chain.

## Review and release gates

Every change ships only after the mechanical gate chain (see [`AGENTVERSE/COHESION_MATRIX.md`](AGENTVERSE/COHESION_MATRIX.md)):

| Gate | Owner | Enforces |
|------|-------|----------|
| G1 | Peer review | code correctness, conventions |
| G2 | Division review | scope, acceptance criteria |
| G3 | Architecture | design integrity, structure |
| G4 | Security | threats, secrets, XSS/injection |
| G5 | Quality | tests, regression, standards |
| G6 | Release | versioning, readiness |

## Polyglot & toolchain support

AgentVerse validates runtimes and frameworks as part of its CI (see [`AGENTVERSE/POLYGLOT_TOOLCHAIN_REGISTRY.json`](AGENTVERSE/POLYGLOT_TOOLCHAIN_REGISTRY.json)):

- **13 languages** in 3 execution tiers (local host, remote dev, container-provisioned Tier 3)
- **12 frameworks** documented
- **6 databases** documented
- Validation pipeline: detect → compile → execute → test → lint → format → package

## CI and validation model

GitHub Actions runs the full validation matrix (see [`.github/workflows/agentverse-ci.yml`](.github/workflows/agentverse-ci.yml)):

- `lint-and-syntax`
- `regression-tests`, `adversarial-tests`, `remediation-tests`
- `polyglot-validation` (per language), `polyglot-toolchain-verify`
- `framework-smoke-suite`, `smoke-project-suite`
- `doctor-check`, `state-consistency`, `verify-release-set`, `secret-scan`

## Repository structure

```
AGENTVERSE/          # the organization framework (control planes, registry, tickets, audits)
.opencode/           # OpenCode runtime config, agents, skills
_tools/              # gate, doctor, secret-scan, verification scripts
_tests/              # test suites (regression, adversarial, remediation, polyglot, smoke)
clientflow/          # target project — ClientFlow SaaS
extravus-sis/        # target project — RosarioSIS rebrand
scholapro/           # target project — ScholaPro educational platform
*.server.js          # stdio MCP servers (git, curl, sqlite, portainer, n8n)
```

## Security

- See [`SECURITY.md`](SECURITY.md) for the responsible-disclosure policy.
- The repository is public. Do **not** commit secrets, credentials, API keys, or connection strings. Secrets belong in environment variables, configured at deployment time.
- A `secret-scan` CI job enforces this on every change.
- `extravus.com` is an **expired** domain. Current operational references use the `edunaija.online` domain family or env-configurable endpoints. Historical records and target-project files may reference older names for accuracy.

## Contributing

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for the development workflow. All contributions flow through AgentVerse's own gate chain and must pass CI.

## Project maturity

AgentVerse 2.0.4 is a mature local engineering framework. It has shipped the 2.0.x line with a full CI pipeline, redacted its git history of leaked secrets, and is operated daily through OpenCode. It is not marketed as a finished SaaS product; it is a powerful engineering environment whose target projects are deployed independently.
