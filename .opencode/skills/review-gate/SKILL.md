---
name: review-gate
description: Review gate chain protocol (G1-G6). Use when reviewing a change at any gate: peer, division, architecture, security, quality, or release. Enforces the Agentverse cohesion matrix gate chain.
---

# Review Gate Protocol

Authoritative gate chain: `AGENTVERSE/COHESION_MATRIX.md` §1.
Evidence requirements: `AGENTVERSE/VERIFICATION_CONTRACT.md`.

## Gate sequence

| Gate | Owner | Focus |
|------|-------|-------|
| G1 Peer | same-division peer | correctness, conventions |
| G2 Division | division council | scope + acceptance criteria |
| G3 Architecture | Council of Architects | design integrity |
| G4 Security | Security Division | threats, secrets, XSS/injection |
| G5 Quality | Quality Division | tests, regression, standards |
| G6 Release | Quality Guardians | definition-of-done |

## Performing a gate review

1. Confirm the prior gate passed (evidence present, no open findings).
2. Read the diff + plan; verify against the gate's checks (matrix §1).
3. Run or request the gate-specific verification (e.g. `php -l`, node tests, grep for secrets).
4. Return a verdict: **PASS** / **PASS WITH NITS** / **FAIL** / **BLOCKED** with:
   - specific `file:line` findings,
   - commands run + output (evidence),
   - the reason any requirement was waived.
   - **BLOCKED** = verification cannot proceed because the required verifier is unavailable (see VERIFICATION_CONTRACT.md §1a).
5. Sign off with your agent id; record in the ticket + KNOWLEDGE_BASE.md.

### Verdict vocabulary

| Verdict | Meaning | Effect on gate |
|---------|---------|----------------|
| **PASS** | All checks pass, evidence present | Gate advances |
| **PASS WITH NITS** | All checks pass, minor non-blocking findings noted | Gate advances with notes |
| **FAIL** | One or more checks fail | Gate does NOT advance; work returns to implementer |
| **BLOCKED** | Verification cannot proceed (verifier unavailable) | Gate does NOT advance; work enters VERIFICATION_BLOCKED state |

**BLOCKED is NOT a pass.** A BLOCKED verdict means the gate has NOT been verified. Work in VERIFICATION_BLOCKED cannot be released, accepted, or treated as verified.

## G4 Secret scanning (mandatory)

Every G4 gate MUST run automated secret detection on changed files:

```bash
# Pattern-based secret detection
grep -rn -E "(password|secret|api_key|token|credential)\s*[:=]\s*['\"][^'\"]+['\"]" <changed-files>
grep -rn -E "([A-Za-z0-9+/]{40,})" <changed-files>  # Base64 blobs
grep -rn -E "-----BEGIN (RSA |EC )?PRIVATE KEY-----" <changed-files>
grep -rn -E "(\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b)" <changed-files>  # IP addresses
```

**Blocking rule:** Any finding BLOCKS gate progression until resolved or explicitly accepted with risk justification.

## Rules

- Read-only roles (architects, auditors) never modify files during review.
- A FAIL must name exactly what blocks; a PASS must cite evidence.
- Fast-path (G3/G4 waiver) only for isolated frontend, non-security changes — requires written waiver signed by division council + Quality Guardian.
- After release, promote durable learnings to KNOWLEDGE_BASE.md (KB-####).
