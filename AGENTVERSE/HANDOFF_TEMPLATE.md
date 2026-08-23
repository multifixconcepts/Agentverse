# HANDOFF TEMPLATE — Agent Communication Protocol

**Version:** 1.0 (2026-08-19)
**Owner:** workflow-engineer (Tooling Council)

Agents MUST communicate through structured handoff artifacts, not through conversational memory. Every delegation, escalation, or task transfer uses this template.

---

## Handoff artifact format

```markdown
## HANDOFF

**From:** <agent-id>
**To:** <agent-id>
**Ticket:** <SCHOL-NNN>
**Timestamp:** <ISO-8601>
**Type:** delegation | escalation | review-request | verification-request

### TASK
<What was requested — the original user request or parent task>

### OBJECTIVE
<What the receiving agent should specifically accomplish>

### CURRENT STATE
<What exists now — files, DB state, deployment state, test results>

### ASSUMPTIONS
<What this agent assumed to be true — flag for verification>

### FILES TOUCHED
| File | Action | SHA256 |
|------|--------|--------|
| <path> | <created/modified/deleted> | <hash> |

### FILES NOT TO TOUCH
<Protected files, areas, or modules that must remain unchanged>

### DECISIONS MADE
<Choices and rationale — what was considered and why this path was chosen>

### OPEN QUESTIONS
<Unresolved items requiring receiving agent attention>

### TESTS RUN
| Command | Output (truncated) | Verdict |
|---------|-------------------|---------|
| <cmd> | <output> | <PASS/FAIL> |

### TESTS NOT RUN
<What was not tested and why — risks of not testing>

### KNOWN RISKS
<What could go wrong — known limitations, incomplete work>

### NEXT ACTION
<Specific next step for the receiving agent — be precise>

### ACCEPTANCE CRITERIA
<Definition of done for the receiving agent — what must be true when this handoff is complete>

### HANDOFF CHECKLIST
- [ ] Receiving agent has read the handoff artifact
- [ ] Receiving agent has verified assumptions
- [ ] Receiving agent has identified any new risks
- [ ] Receiving agent has confirmed understanding of NEXT ACTION
```

## Receiving agent verification

Before acting on a handoff, the receiving agent MUST:

1. **Read** the complete handoff artifact
2. **Verify** the current state matches what's described (spot-check 2-3 files)
3. **Validate** assumptions against current code and control planes
4. **Confirm** the objective is achievable with the stated scope
5. **Flag** any contradictions or missing information

If the receiving agent finds contradictions, it MUST escalate back to the sending agent or to the division council before proceeding.

## Handoff storage

Handoffs are recorded as sections within the ticket file (`AGENTVERSE/tickets/SCHOL-NNN.md`). Each handoff gets a timestamped section so the full delegation chain is traceable.

## Rules

- **Never** hand off without the artifact — conversational context is not a handoff
- **Never** assume the receiving agent has context from prior conversations
- **Always** include the verification checklist at the bottom
- **Always** flag assumptions — they may be wrong
- **Always** include next action — vague handoffs create confusion
