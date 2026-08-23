# COHESION MATRIX — Control Plane #1

**Version:** 2.0.1 (2026-08-19)
**Owner:** chief-architect (Council of Architects)

The cohesion matrix is a **core control plane** of Agentverse, not a documentation afterthought. It governs who works with whom, who owns what, and what the release gates check. Every delegation routes through this matrix.

Scope: `/home/coder/project` · Product: `scholapro/` (ScholaPro / RosarioSIS 12.5 fork).

---

## 1. Review gate chain (authoritative)

A change is **done** only after all gates pass. Evidence required at each gate.

| # | Gate | Owner | Inputs required | Checks performed | Pass criteria | Sign-off |
|---|------|-------|-----------------|------------------|---------------|----------|
| G1 | Peer review | same-division peer specialist | diff, self-review notes | correctness, conventions, no dead code | no unresolved correctness/standards issues | peer (id + note) |
| G2 | Division review | division council | G1 pass, acceptance criteria | scope complete, acceptance criteria met, no scope creep | all criteria met or explicitly waived | council member |
| G3 | Architecture | Council of Architects | plan/diff touching structure | design integrity, interface contracts, tech fit | no structural objection; ARD updated if needed | chief-architect |
| G4 | Security | Security Division | G3 pass, threat check | XSS, injection, secrets, authn/authz, CSP | no open findings; evidence logged | security-division-council |
| G5 | Quality | Quality Division | G4 pass, test evidence | tests pass, regression pass, standards | tests green; no regression; standards clean | quality-division-council |
| G6 | Release | Quality Guardians | G5 pass, changelog | definition-of-done, version/changelog updated | DoD complete | release-custodian |

**Fast-path:** isolated, frontend-only, non-security changes may skip G3/G4 with a written waiver from the division council + Quality Guardian.

**Gate ledger:** each pilot records per-gate verdicts in the ticket + `KNOWLEDGE_BASE.md`.

**Verification-blocked gate:** If the gate owner is unavailable, the gate enters BLOCKED state (see VERIFICATION_CONTRACT.md §1a). BLOCKED is NOT a pass. Work in BLOCKED state cannot be released. The implementer escalates per SEPARATION_OF_DUTIES.md §Verifier Unavailability Procedure.

---

## 2. Agent↔agent collaboration pairs (who routinely works together)

| Primary | Works with | Why |
|---------|-----------|-----|
| summoner | all division councils | delegation & status |
| feature-planner | backend/frontend/fullstack-engineer | plan → implementation |
| frontend-engineer | ui-ux-engineer, a11y-tester, api-engineer | UI behavior + contracts |
| backend-engineer | db-engineer, sql-optimizer, feature-tester | backend + data + verification |
| api-engineer | frontend-engineer, integration-division | contract enforcement |
| db-engineer | data-division-council, backup-engineer, migration-engineer | schema safety |
| migration-engineer | data-division, release-custodian | migrations & releases |
| release-engineer | release-custodian, ci-cd-engineer | release prep |
| mcp-engineer | mcp-specialist, toolsmith | MCP servers |
| docker-ops | portainer-ops, container-hardener, platform-division | containers |
| secure-connector | host-ops-specialist, incident-responder | host access |
| test-architect | unit/integration/e2e/perf-test-engineers | test strategy |
| regression-gate | release-custodian | pre-release regression |
| security-tester | vuln-scanner, secrets-auditor | security evidence |
| threat-modeler | system-architect, auth-engineer | design-time security |
| sql-optimizer | report-builder, analytics-engineer | query perf |
| sre | monitoring-specialist, incident-responder, docker-ops | reliability |
| docs-lead | api-docs-writer, release-engineer | docs in release |
| knowledge-curator | memory-steward, all divisions | KB lifecycle |
| agent-forge | agent-reviewer, workflow-engineer | org self-engineering |

## 3. Division↔division dependencies

| Producer → Consumer | Dependency | Notes |
|---------------------|-----------|-------|
| Feature → Quality | every feature change | Quality runs tests/gates |
| Feature → Security | auth/data/input changes | Security gate G4 |
| Feature → Data | schema/query changes | DB standards |
| Integration → Platform | containers/connectivity | ops handoff |
| Integration → Security | webhooks/MCP/n8n | exposure review |
| Data → Feature | reporting/analytics features | report builder support |
| Data → Platform | backups/DB hosting | backup schedules |
| Security → Feature | findings → fixes | vuln remediation |
| Platform → Quality | infra changes | SRE/reliability review |
| All → Knowledge Commons | decisions/knowledge | KB entries mandatory |

## 4. Council↔division oversight matrix

| Council/Guild | Oversight over | Mechanism |
|---------------|----------------|-----------|
| Council of Architects | all divisions | architecture gate G3, ARD |
| Agent Foundry | the org itself | agent definitions, registry, skills |
| Knowledge Commons | all knowledge | KB acceptance, memory index |
| Quality Guardians | Quality Division + releases | release gate G6, DoD |
| Tooling Council | tooling across divisions | scripts, MCP, workflows |
| Documentation Guild | docs everywhere | docs review, release docs |

## 5. RACI for key activities

Legend: R=responsible, A=accountable, C=consulted, I=informed.

| Activity | Summoner | Division council | Specialists | Architects | Security | Quality Div | Quality Guardians | Knowledge Commons |
|----------|----------|------------------|-------------|------------|----------|-------------|-------------------|-------------------|
| Ticket triage | A | R | I | I | I | I | I | I |
| Plan & spec | I | A | R | C | C | C | I | I |
| Implementation | I | A | R | I | I | I | I | I |
| Peer review | I | A | R | I | I | I | I | I |
| Architecture gate | I | C | I | A/R | I | I | I | I |
| Security gate | I | C | C | I | A/R | C | I | I |
| Testing/quality gate | I | C | C | I | C | A/R | C | I |
| Release decision | I | C | I | I | I | C | A | I |
| Knowledge entry | I | I | I | C | I | I | I | A/R |
| Escalation | A | C | R | C | C | C | C | I |

## 6. Conflict resolution

1. Within a division → division council decides.
2. Between divisions → the accountable division council negotiates; deadlock → Summoner.
3. Quality vs Feature disagreement → Quality Guardians adjudicate (A at release).
4. Architecture objections override feature expediency unless Summoner escalates to the user.

---

**Control plane #2:** `MEMORY_INDEX.md` (memory architecture) and `KNOWLEDGE_BASE.md` (knowledge lifecycle) operate alongside this matrix.
