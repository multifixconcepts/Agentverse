# VERIFICATION CONTRACT — Control Plane Extension

**Version:** 2.0 (2026-08-20)
**Owner:** quality-guardian (Quality Guardians)
**Supersedes:** 1.0

Every gate verdict and every completion claim MUST satisfy this contract. An agent saying "done" without meeting these requirements is a verification failure.

---

## 1. Completion state machine

```
PLANNED → IN_PROGRESS → IMPLEMENTED → TESTED → VERIFIED → ACCEPTED → RELEASED
                              ↓
                     VERIFICATION_BLOCKED → (resumes to VERIFIED when verifier available)
                              ↓
                         FAILED (if verifier rejects)
```

**Invariant:** IMPLEMENTED ≠ TESTED ≠ VERIFIED ≠ VERIFICATION_BLOCKED. Each state requires separate, distinct evidence.

| State | Definition | Required evidence |
|-------|------------|-------------------|
| PLANNED | Ticket opened, scope defined | Acceptance criteria, affected files, risk assessment |
| IN_PROGRESS | Work started by assigned specialist | File changes in progress, intermediate state noted |
| IMPLEMENTED | All code changes applied | Changed files with checksums, `php -l` / syntax checks pass |
| TESTED | Automated verification executed | Actual command output (not assertions), test results |
| **VERIFICATION_BLOCKED** | **Verifier unavailable; cannot complete independent verification** | **Verifier unavailability evidence, escalation record, timestamped blocked-state entry** |
| VERIFIED | Independent reviewer confirmed | Peer or gate-owner verification with evidence |
| ACCEPTED | Acceptance criteria met | Each AC marked MET/NOT MET with proof |
| RELEASED | Shipped with full gate chain | G0–G6 all PASS, CHANGES.md updated, KB recorded |

## 1a. VERIFICATION_BLOCKED state definition

**Purpose:** Explicitly represent work that is implemented and has evidence generated, but cannot be independently verified because the required verifier is unavailable.

**This state is NOT:**
- A synonym for VERIFIED
- A shortcut to bypass verification
- An automatic timeout to release
- A fallback for lazy verification

**Transition rules:**

| From | To | Condition |
|------|----|-----------|
| IMPLEMENTED | VERIFICATION_BLOCKED | All available evidence generated; required verifier confirmed unavailable; no qualified substitute exists |
| EVIDENCE_GENERATED | VERIFICATION_BLOCKED | Required verifier confirmed unavailable; no qualified substitute exists |
| TESTED | VERIFICATION_BLOCKED | Required verifier confirmed unavailable; no qualified substitute exists |
| VERIFICATION_BLOCKED | VERIFIED | Qualified verifier becomes available; independent verification passes |
| VERIFICATION_BLOCKED | FAILED | Qualified verifier becomes available; independent verification rejects |
| VERIFICATION_BLOCKED | IN_PROGRESS | Verifier returns feedback requiring rework |

**Prohibited transitions:**

| From | To | Why prohibited |
|------|----|----------------|
| VERIFICATION_BLOCKED | VERIFIED (by implementer) | Self-verification violates separation of duties |
| VERIFICATION_BLOCKED | ACCEPTED | Skipping verification violates gate chain |
| VERIFICATION_BLOCKED | RELEASED | Releasing unverified work violates quality standards |
| Any | VERIFICATION_BLOCKED (automatic) | Must be explicitly recorded with evidence of unavailability |
| VERIFICATION_BLOCKED | PASS (time-based) | No automatic expiry; work remains blocked until verified |

## 1b. Escalation policy for verifier unavailability

### When verifier unavailability is detected

1. **Implementer records the blocked state** in the ticket with:
   - Which verifier is required (gate + agent ID)
   - Evidence that the verifier is unavailable (e.g., no agent file, no response, session timeout)
   - Timestamp of blocked-state entry
   - All evidence generated so far

2. **Implementer escalates to the division council** (or Summoner if no council is available).

3. **Division council attempts substitute assignment:**
   - Search for another agent with the same VERIFIER role type
   - Verify the substitute has no conflict of interest (did not author the code)
   - If a qualified substitute exists, assign verification and document in the ticket
   - If no qualified substitute exists, record that fact

4. **Work remains blocked** until a qualified verifier becomes available.

5. **Release authority checks** — before any release, the release authority MUST verify that no ticket in the release set has status VERIFICATION_BLOCKED. If any ticket is blocked, the release is BLOCKED.

### Verifier unavailability detection

A verifier is considered unavailable when:
- The agent file does not exist in `.opencode/agents/`
- The agent's Professional Operating Contract is not loaded
- The agent has been assigned but has not responded within the session timeout
- The agent's division council confirms the role is unfilled
- Model failover has occurred and the new model cannot locate the verifier

### Substitute verifier selection rules

1. The substitute MUST be a different agent than the implementer
2. The substitute MUST have the same role type (VERIFIER) as the original
3. The substitute MUST NOT have authored any code in the ticket
4. The substitute MUST have a valid Professional Operating Contract
5. If multiple substitutes are available, the division council assigns; if no council, the Summoner assigns

### Self-verification prohibition

**Under no circumstances may an implementer transition VERIFICATION_BLOCKED → VERIFIED for its own work.**

This prohibition is absolute and applies regardless of:
- Time pressure
- Verifier availability
- The implementer's confidence in its own work
- The completeness of generated evidence
- Whether the work is trivial or complex

The only entity that can transition work to VERIFIED is a qualified, independent verifier.

### Emergency override

If the architecture permits an emergency override:
1. The override must be explicitly authorized by the Summoner or user
2. The override must be recorded in the ticket with rationale
3. The override must be auditable (logged in the ticket's evidence chain)
4. The override MUST NOT silently convert blocked work into verified work
5. The override MUST NOT bypass the separation-of-duties invariant
6. Emergency overrides are reviewed in the next audit cycle

## 2. Per-gate evidence requirements

### G0 — Triage (Summoner)
- Ticket opened with: type, priority, affected files, acceptance criteria
- Classification: feature / bug / infra / security / data / docs / query

### G1 — Peer review (same-division peer)
- Diff reviewed with `file:line` references for every finding
- Conventions check: PHP tabs, single quotes, `_( 'Text' )`, DB access via DBGet/DBQuery
- No dead code, no debug artifacts, no secrets
- Verdict: PASS / PASS WITH NITS / FAIL (with specific findings)

### G2 — Division review (division council)
- Acceptance criteria checklist: each AC marked MET/NOT MET with evidence
- Scope check: no scope creep, no unrelated changes
- Cross-division impact identified and routed

### G3 — Architecture (Council of Architects)
- Design integrity assessment
- Interface contract verification
- Impact on affected modules documented
- Architecture Decision Record updated if needed
- Fast-path waiver: only for isolated frontend, non-security changes (requires signed waiver)

### G4 — Security (Security Division)
- **Automated secret scan** (see §3 below)
- XSS / injection surface review
- Authentication / authorization impact
- CSP / headers impact
- No credentials in code, config, or logs
- All findings must be FIXED or EXPLICITLY ACCEPTED with risk justification

### G5 — Quality (Quality Division)
- `php -l` on all touched PHP files
- Unit / integration / E2E tests executed with actual output
- Regression check: prior behavior not broken
- Standards compliance verified
- Live validation on school4 (for deployment changes): HTTP status codes, body error grep, smoke CRUD

### G6 — Release (Quality Guardians)
- All prior gates PASS (evidence present)
- CHANGES.md updated
- KB entry recorded (KB-####)
- MEMORY_INDEX.md updated
- Zip integrity verified (if deliverable is a zip)
- DoD complete

## 3. Automated secret scanning (G4 requirement)

Every G4 gate MUST run the following checks on the diff:

```bash
# Pattern-based secret detection
grep -rn -E "(password|secret|api_key|token|credential)\s*[:=]\s*['\"][^'\"]+['\"]" <changed-files>
grep -rn -E "([A-Za-z0-9+/]{40,})" <changed-files>  # Base64 blobs
grep -rn -E "-----BEGIN (RSA |EC )?PRIVATE KEY-----" <changed-files>
grep -rn -E "(\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b)" <changed-files>  # IP addresses
```

**Blocking rule:** Any finding in the secret scan BLOCKS gate progression. The finding must be resolved (fixed or explicitly accepted with risk justification documented in the ticket) before G4 can PASS.

## 4. Evidence format

All evidence must include:
- **Command or file reference:** exact command run, or `file:line` reference
- **Output:** actual output (truncated if >20 lines, with "..." and full output available on request)
- **Verdict:** PASS / FAIL with reasoning

**Never accepted as evidence:**
- "Tests pass" (without showing output)
- "No issues found" (without showing what was checked)
- "Looks correct" (without file:line references)
- "I verified it" (without the verification command/output)

## 5. Gate bypass policy

- Gates may ONLY be bypassed via a written waiver
- Waiver requires: division council approval + Quality Guardian approval
- Waiver must be recorded in the ticket with rationale
- Fast-path (G3/G4 waiver) only for isolated frontend, non-security changes
- No gate may be bypassed retroactively (if you forgot to run it, run it now)
