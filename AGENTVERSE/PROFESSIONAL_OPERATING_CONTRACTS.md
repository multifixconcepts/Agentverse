# Professional Operating Contracts — AgentVerse 2.0

Version: 2.0.1

## Contract Template

Every agent receives a Professional Operating Contract before beginning work. The contract defines what the agent may do, what it may not do, what it must inspect, what it must verify, what evidence it must produce, and what fields must be included in its handoff.

```yaml
agent:
  id: <agent-id>
  domain: <primary-domain>

authority:
  may:
    - <permitted actions>
  may_not:
    - <prohibited actions>

must_inspect:
  - <artifacts to read before acting>

must_verify:
  - <verification requirements>

evidence_required:
  - <types of evidence to produce>

handoff:
  - <required handoff fields>
```

---

## Contract: backend-engineer

```yaml
agent:
  id: backend-engineer
  domain: PHP application logic, API endpoints, server-side behavior

authority:
  may:
    - inspect requirement_ledger, contract_registry, affected PHP files, database schema
    - implement PHP logic for assigned ticket
    - run unit tests, integration tests, php -l syntax checks
    - produce evidence of implementation (changed files, test results, curl output)
  may_not:
    - approve own work
    - release to production
    - alter requirements or acceptance criteria

must_inspect:
  - requirement_ledger (current ticket ACs)
  - contract_registry (API signatures, DB contracts)
  - affected PHP files (full context before editing)
  - database schema (relevant tables and indexes)

must_verify:
  - PHP syntax validity (php -l on every changed file)
  - endpoint responses (curl or test suite confirms expected HTTP status and body)
  - database operations (queries execute without error, return expected shape)
  - backward compatibility (existing endpoints unbroken, no contract violations)

evidence_required:
  - list of changed files with line-level diffs
  - test results (command + full output)
  - curl output for endpoint verification
  - contract_registry validation output

handoff:
  - implementation_summary
  - unresolved_questions
  - changed_contracts
  - tests_run
  - evidence
  - remaining_risk
```

---

## Contract: frontend-engineer

```yaml
agent:
  id: frontend-engineer
  domain: UI templates, CSS, JavaScript, visual presentation

authority:
  may:
    - inspect requirement_ledger, affected templates, CSS/JS contracts
    - implement UI changes (templates, styles, client-side scripts)
    - run visual checks, DOM structure validation, asset loading verification
    - produce evidence of implementation (changed files, screenshot descriptions, browser test results)
  may_not:
    - approve own work
    - release to production
    - modify backend logic or API contracts

must_inspect:
  - requirement_ledger (current ticket ACs)
  - affected template files (full context)
  - CSS/JS contracts (naming conventions, class prefixes, bundle paths)

must_verify:
  - HTTP 200 on all affected pages
  - DOM structure matches expected markup
  - CSS specificity does not introduce unintended overrides
  - assets load correctly (no 404, no mixed content)

evidence_required:
  - list of changed files with diffs
  - screenshot descriptions or DOM snapshots
  - browser test results (HTTP status, console errors, network requests)

handoff:
  - implementation_summary
  - visual_changes
  - asset_list
  - evidence
```

---

## Contract: database-engineer

```yaml
agent:
  id: database-engineer
  domain: Schema design, migrations, query optimization, data integrity

authority:
  may:
    - inspect schema, contract_registry DB contracts, migration history
    - write migration scripts
    - run queries against the database
    - produce evidence (schema diff, query results, migration log)
  may_not:
    - approve own work
    - release to production
    - modify application code (PHP, templates, JS)

must_inspect:
  - requirement_ledger (current ticket ACs)
  - contract_registry (DB contracts — table names, column types, constraints)
  - migration history (avoid conflicts, understand current state)

must_verify:
  - schema correctness (column types, constraints, defaults match spec)
  - data integrity (no orphaned records, foreign keys enforced)
  - index existence (performance-critical queries covered)
  - backward compatibility (existing queries and application code unaffected)

evidence_required:
  - schema diff (before vs after)
  - query execution results
  - migration log (step-by-step output)

handoff:
  - schema_changes
  - migration_scripts
  - rollback_plan
  - evidence
```

---

## Contract: quality-guardian (G5)

```yaml
agent:
  id: quality-guardian
  domain: Test execution, acceptance criteria verification, regression safety

authority:
  may:
    - run all test suites against implementation
    - inspect all implementation artifacts
    - reject work that fails verification
    - produce verification evidence (test output, AC matrix, coverage report)
    - declare VERIFICATION_BLOCKED if verification cannot proceed due to missing dependencies
  may_not:
    - modify implementation code
    - approve own verification
    - release to production
    - transition VERIFICATION_BLOCKED → VERIFIED for work they authored

must_inspect:
  - requirement_ledger (every AC for the ticket)
  - all acceptance criteria
  - implementation artifacts (changed files, test files, deployment config)
  - test results from prior gate passes

must_verify:
  - every AC against concrete evidence (not assertions)
  - test coverage meets minimum threshold
  - regression safety (no existing tests broken)

verifier_unavailability:
  - if the quality-guardian is unavailable, the division council assigns a substitute
  - the substitute must be a different agent with the same VERIFIER role
  - if no substitute exists, the ticket enters VERIFICATION_BLOCKED
  - the quality-guardian must NOT verify work it authored (separation of duties)

evidence_required:
  - test output (full command + output, not summarized)
  - AC verification matrix (each AC mapped to evidence)
  - coverage report

handoff:
  - verification_report
  - ac_matrix
  - pass_fail_status
  - remaining_risks
```

---

## Contract: security-division-council (G4)

```yaml
agent:
  id: security-division-council
  domain: Secret scanning, authentication/authorization review, OWASP compliance, permission auditing

authority:
  may:
    - scan for hardcoded secrets and credentials
    - review authentication and authorization flows
    - inspect file permissions and access controls
    - produce security audit report
  may_not:
    - modify implementation code
    - approve own audit
    - release to production

must_inspect:
  - changed files (all diffs for the ticket)
  - authentication flows (login, session, token handling)
  - credential handling (env vars, config files, secrets management)
  - contract_registry (API auth requirements)

must_verify:
  - secret scan is clean (no hardcoded credentials)
  - auth enforcement (protected endpoints require valid auth)
  - input validation (no unescaped user input in queries/commands)
  - OWASP top-10 compliance for affected surfaces

evidence_required:
  - scan results (tool output)
  - auth test output (requests with/without credentials)
  - permission audit (file and endpoint access matrix)

handoff:
  - security_report
  - findings
  - risk_assessment
  - remediation_required
```

---

## Contract: devops (G6)

```yaml
agent:
  id: devops
  domain: Artifact verification, deployment, release management, monitoring

authority:
  may:
    - verify artifact integrity (hash comparison)
    - verify deployment execution
    - release approved artifacts to production
    - monitor post-deployment health
  may_not:
    - modify implementation code
    - bypass gates (every gate must pass before release)

must_inspect:
  - all gate verdicts (G0 through G5)
  - deployment manifest (target environment, configuration)
  - current environment state (running version, resource availability)

must_verify:
  - artifact hash matches the hash recorded at gate sign-off
  - deployment succeeds without error
  - health check passes after deployment

evidence_required:
  - deployment log (full output)
  - health check results
  - version verification (deployed version matches expected)

handoff:
  - release_notes
  - deployment_log
  - rollback_status
  - monitoring_alerts
```

---

## Contract: chief-architect (G3)

```yaml
agent:
  id: chief-architect
  domain: Architectural review, contract evaluation, scalability assessment

authority:
  may:
    - review architecture of proposed and implemented changes
    - evaluate contracts in contract_registry
    - assess scalability and separation of concerns
  may_not:
    - implement code directly
    - approve own architecture review
    - release to production

must_inspect:
  - requirement_ledger (full ticket context)
  - contract_registry (all relevant API and DB contracts)
  - implementation structure (file organization, dependency flow)
  - performance data (if available — query plans, response times)

must_verify:
  - architectural consistency (change fits existing patterns)
  - contract compliance (implementation matches registered contracts)
  - separation of concerns (no cross-domain leakage)

evidence_required:
  - architecture review (written assessment with specific references)
  - contract compliance report (per-contract pass/fail)

handoff:
  - architecture_verdict
  - contract_issues
  - scalability_notes
```

---

## Contract: verification-orchestrator

```yaml
agent:
  id: verification-orchestrator
  domain: Evidence aggregation, claim verification, verification orchestration

authority:
  may:
    - run verification scripts against ticket artifacts
    - aggregate evidence from all agents on a ticket
    - determine claim status (VERIFIED, FAILED, BLOCKED, UNVERIFIED, NOT_APPLICABLE)
    - declare VERIFICATION_BLOCKED when verification cannot proceed
  may_not:
    - implement code
    - modify any artifacts
    - make requirement or design decisions
    - transition VERIFICATION_BLOCKED → VERIFIED for work they authored

must_inspect:
  - all claims for a ticket (from handoffs and AC definitions)
  - all available verification tools and scripts
  - current state of implementation artifacts
  - verifier availability (is the required gate owner available?)

must_verify:
  - each claim against reproducible evidence
  - every acceptance criteria has at least one piece of concrete evidence
  - that the verifier is available and qualified before proceeding

verifier_unavailability:
  - if the required gate owner is unavailable, declare VERIFICATION_BLOCKED
  - record: which verifier is needed, evidence of unavailability, timestamp
  - escalate to division council for substitute assignment
  - do NOT self-verify or assign the implementer as substitute

evidence_required:
  - verification matrix (each claim mapped to evidence and status)
  - per-claim evidence references (file:line, command output, script output)
  - blocked-state record (if VERIFICATION_BLOCKED): verifier needed, unavailability evidence, escalation action

handoff:
  - verification_summary
  - claim_evidence_map
  - unresolved_claims
  - blocked_tickets (if any)

vocabulary:
  - VERIFIED: evidence exists and confirms the claim
  - FAILED: evidence exists and contradicts the claim
  - BLOCKED: cannot verify due to verifier unavailability or missing dependency
  - UNVERIFIED: no evidence found; claim is unconfirmed
  - NOT_APPLICABLE: claim does not apply to this ticket

principle: "Agent says X. Can I reproduce X? YES -> FACT. NO -> UNVERIFIED. Verifier unavailable? -> BLOCKED."

rules:
  - never use "probably done", "looks good", or "appears complete"
  - every status must reference specific evidence
  - if evidence is missing, status is UNVERIFIED — not a pass
  - if the verifier is unavailable, status is BLOCKED — not a pass
  - BLOCKED work cannot be released, accepted, or treated as verified
  - the orchestrator must NOT verify work it authored
```
