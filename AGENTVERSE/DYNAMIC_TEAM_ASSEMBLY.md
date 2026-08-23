# Dynamic Team Assembly — AgentVerse 2.0

Version: 2.0.1

## Principle

70 agents defined does not mean 70 agents participate in every task.

Every task requires a focused team with the minimum set of roles needed to complete the work with proper separation of duties. Assembling too many agents into a single session creates coordination overhead that destroys throughput without improving quality.

---

## Team Templates

Each task type maps to a specific team template. The Summarizer selects the template based on the task classification.

### a) Billing Module Task

| Role | Responsibility |
|------|---------------|
| chief-architect | Review billing architecture, contract compliance |
| backend-engineer | Implement billing logic, API endpoints |
| database-engineer | Schema changes for billing tables |
| frontend-engineer | Billing UI, forms, display |
| security-engineer | Payment security, credential handling |
| quality-guardian | Run tests, verify ACs |
| verification-orchestrator | Validate all claims before gate advancement |
| devops | Release and deployment |

### b) CSS/UI Adjustment

| Role | Responsibility |
|------|---------------|
| frontend-engineer | Implement visual changes |
| quality-guardian | Verify visual correctness, regression check |

### c) Core Upgrade

| Role | Responsibility |
|------|---------------|
| chief-architect | Architectural impact assessment |
| platform-engineer | Platform-level changes, runtime compatibility |
| security-engineer | Security implications of upgrade |
| database-engineer | Schema compatibility with new version |
| quality-guardian | Full regression test suite |
| verification-orchestrator | Validate all upgrade claims |
| devops | Staged deployment, rollback readiness |

### d) API Endpoint

| Role | Responsibility |
|------|---------------|
| chief-architect | Contract design, endpoint specification |
| backend-engineer | Implement endpoint logic |
| database-engineer | Query optimization, schema support |
| security-engineer | Auth/authz, input validation |
| quality-guardian | Endpoint testing, contract verification |
| verification-orchestrator | Claim validation |

### e) Security Patch

| Role | Responsibility |
|------|---------------|
| security-engineer | Identify and implement fix |
| backend-engineer | Apply patch to application code |
| quality-guardian | Verify patch effectiveness, regression check |
| verification-orchestrator | Validate fix claims |
| devops | Expedited release |

### f) Database Migration

| Role | Responsibility |
|------|---------------|
| database-engineer | Write and test migration scripts |
| backend-engineer | Update queries and ORM mappings |
| chief-architect | Review schema design impact |
| quality-guardian | Verify data integrity, rollback test |
| verification-orchestrator | Validate migration claims |

### g) Quick Fix

| Role | Responsibility |
|------|---------------|
| backend-engineer | Implement minimal fix |
| quality-guardian | Verify fix, confirm no regression |

---

## Assembly Procedure

1. **Summarizer identifies task type** — Classify the incoming request and map it to a team template.
2. **Select team template** — Load the roster for the identified task type.
3. **Load contracts** — Each team member loads their Professional Operating Contract (`PROFESSIONAL_OPERATING_CONTRACTS.md`). No agent works without an active contract.
4. **Execute through gate chain** — Work proceeds through the gate chain (G0–G6) with separation of duties enforced at every gate.
5. **Verification Orchestrator validates** — Before any gate advances, the Verification Orchestrator confirms all claims have supporting evidence.

---

## Anti-Patterns

### All 70 Agents in One Session

Putting every defined agent into a single session creates more coordination overhead than actual work. The team size should be the minimum needed for the task type, not the maximum defined in the system.

### Skipping the Contract

An agent without a Professional Operating Contract has no defined authority, no defined evidence requirements, and no defined handoff format. Work from an uncontracted agent is unverifiable.

### Verifier-less Team

Every team must include at least one VERIFIER role — either a quality-guardian or a verification-orchestrator. A team that implements without verification is a team that produces claims, not facts.

---

## Rules

- Every team must include at least one VERIFIER role (quality-guardian or verification-orchestrator).
- No agent works without loading its Professional Operating Contract first.
- Team size is determined by the template, not by agent availability.
- The Summarizer may add a role if a cross-cutting concern is identified, but must document why.
- Removing a role from a template requires a written justification in the ticket.
