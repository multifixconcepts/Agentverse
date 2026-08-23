# AgentVerse Boot Sequence

## Purpose

You are resuming an AgentVerse engineering session. This file tells you how to reconstruct organizational state without relying on conversation history.

**Do not trust conversation summaries. Do not assume prior context is valid. Rebuild state from durable artifacts.**

## Boot Sequence (Execute in Order)

### Step 1 — Read Organizational State

Read `AGENTVERSE/CURRENT_STATE.json` to determine last verified state.

This file contains:
- Last verified timestamp and verifier
- Active ticket statuses
- Release history
- Gate chain position
- Truth principle reference

### Step 2 — Verify Environment Consistency

Read `AGENTVERSE/ENVIRONMENT_STATE.json` to verify source/production consistency.

This file contains:
- Source code version and path
- Production container version and URL
- Database version and status
- Consistency check results
- MCP server configuration

**If `consistency.source_production_match` is `false`, source/production divergence exists. Do not proceed with development until resolved or acknowledged.**

### Step 3 — Read Conflict Resolution Rules

Read `AGENTVERSE/TRUTH_HIERARCHY.md` for conflict resolution.

This file defines the precedence order when conflicting information exists between artifacts.

### Step 4 — Read Active Requirements

Read `AGENTVERSE/REQUIREMENT_LEDGER.json` for active requirements.

This file contains machine-readable requirement tracking with acceptance criteria, verification status, and evidence references for all tickets.

### Step 5 — Read Field/API Contracts

Read `AGENTVERSE/CONTRACT_REGISTRY.json` for field/API contracts.

This file contains:
- API contracts (expected HTTP statuses, endpoints)
- Database contracts (table schemas)
- Field name contracts (breaking changes between versions)
- Module contracts (file structure conventions)

### Step 6 — Read Known Failure Patterns

Read `AGENTVERSE/FAILURE_LOG.md` for known failure patterns.

This file documents recurring mistakes, anti-patterns, and their resolutions to prevent repeat failures.

### Step 7 — Read Organizational Knowledge

Read `AGENTVERSE/KNOWLEDGE_BASE.md` for organizational knowledge.

This file contains accumulated knowledge about the project, architecture decisions, and operational procedures.

### Step 8 — Determine Last Verified State

The last verified state is determined by the `last_verified` timestamp and `last_verifier` in `CURRENT_STATE.json`.

**This is NOT the last thing a model said. This is the last thing that was independently verified against evidence.**

### Step 8a — Check for Verification-Blocked Tickets

Read `CURRENT_STATE.json` and check `active_tickets.VERIFICATION_BLOCKED`. If any tickets are in this state:

1. These tickets have implementation + evidence but no independent verification
2. The required verifier was unavailable when the blocked state was recorded
3. **Do NOT self-verify these tickets.** They require a qualified, independent verifier.
4. Check if a verifier is now available. If so, route verification to the verifier.
5. If no verifier is available, the tickets remain blocked.

**VERIFICATION_BLOCKED is NOT a pass. Do not treat blocked tickets as verified.**

### Step 9 — Resume from Verified State Only

Begin work from the verified state. If any step above reveals inconsistencies, flag them before proceeding.

## Core Principle

> **CLAIM ≠ FACT**
>
> The previous agent's claims are unverified until independently proven.
> Every assertion must be backed by machine-verifiable evidence.

## Anti-Pattern

> **NEVER infer completion from conversational history. ALWAYS verify against durable artifacts.**

A conversation summary says "SCHOL-064 is done." That is a claim. The `REQUIREMENT_LEDGER.json` shows SCHOL-064 with all acceptance criteria at `VERIFIED` with evidence references. That is a fact. Operate on facts only.

## File Inventory

| File | Format | Purpose |
|------|--------|---------|
| `AGENTVERSE_BOOT.md` | Markdown | This file — recovery entrypoint |
| `CURRENT_STATE.json` | JSON | Machine-readable organizational state |
| `ENVIRONMENT_STATE.json` | JSON | Source/production/database state |
| `TRUTH_HIERARCHY.md` | Markdown | Conflict resolution precedence |
| `REQUIREMENT_LEDGER.json` | JSON | Requirement tracking with ACs |
| `CONTRACT_REGISTRY.json` | JSON | API, database, field, module contracts |
| `FAILURE_LOG.md` | Markdown | Known failure patterns |
| `KNOWLEDGE_BASE.md` | Markdown | Organizational knowledge |
