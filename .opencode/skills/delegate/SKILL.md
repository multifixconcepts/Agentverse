---
name: delegate
description: Summoner delegation workflow. Use when a ticket or request must be routed to a division/council, when assigning specialists, or when collecting gate evidence across the organization.
---

# Delegation Workflow

Use the Agentverse delegation model (`AGENTVERSE/AGENTVERSE.md` §3) and cohesion matrix (`AGENTVERSE/COHESION_MATRIX.md`).

## Steps

1. **Triage** — classify the request (feature / bug / infra / security / data / docs / org).
2. **Route** — map to the owning division council (Feature for product; Security/Quality for gates; Integration for connectivity; Data for schema/reports; Platform for infra; councils for cross-cutting).
3. **Create the ticket** — record: id (e.g. `SCHOL-NNN`), type, priority, affected files, acceptance criteria, requested evidence.
4. **Assign** — the division council allocates to specialists. Cross-cutting work CC's the relevant councils/guilds.
5. **Collect evidence** — every delegate returns concrete evidence (`file:line`, commands run, test output). No bare assertions.
6. **Record gates** — after each gate (G1–G6 per COHESION_MATRIX §1) write the verdict + sign-off into the ticket and KNOWLEDGE_BASE.md.
7. **Close or escalate** — resolve, or escalate via the registered `escalates_to` chain to the Summoner.

## Rules

- Route exactly one owning unit per ticket; other units are CC/consulted.
- Never bypass a gate without a written waiver (division council + Quality Guardian).
- Update MEMORY_INDEX.md with durable facts and KNOWLEDGE_BASE.md with issue/proficiency records.

## Structured Handoff Protocol

When delegating work, the delegate MUST return a structured handoff in this format:

```markdown
## HANDOFF: [TICKET_ID]

### Implementation Summary
[What was actually done — not claimed, done]

### Unresolved Questions
[Questions that need answers before this work can proceed]

### Changed Contracts
[Any changes to API signatures, DB schemas, or field names]

### Tests Run
[Test command and actual output — not "tests passed" but the actual results]

### Evidence
[Files modified, curl results, php -l results, verification script output]

### Remaining Risk
[What could still go wrong — the delegate should be honest about uncertainty]

### Claim Status
For each acceptance criteria:
- AC-01: [VERIFIED|UNVERIFIED|FAILED|BLOCKED] — [evidence reference]
- AC-02: [VERIFIED|UNVERIFIED|FAILED|BLOCKED] — [evidence reference]
...
```

**Rules:**
- A handoff without evidence is a CLAIM, not a FACT.
- The receiving agent must verify the handoff before accepting it.
- No handoff should contain the word "probably" or "should" in the Claim Status section.
- Every claim status must reference specific evidence (file:line, command output, script output).
- Use the Verification Orchestrator vocabulary: VERIFIED, FAILED, BLOCKED, UNVERIFIED, NOT_APPLICABLE.
