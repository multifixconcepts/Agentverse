# Disposition — SCHOL-108

**Type:** Historical disposition record (Remediation Phase 1, Changeset C)
**Created:** 2026-08-28
**Scope:** Canonical ticket/state reconciliation

## Finding

- `AGENTVERSE/tickets/SCHOL-108.md` (`SCHOL-108 — Billing_Elements ListOutput Fatal Error on 12.9.2`) carries `**Status:** OPEN`.
- It contains a filled gate ledger with all gates (G1–G6) marked PASS, which is
  internally inconsistent with an OPEN status.
- The same root cause and fix are addressed by `AGENTVERSE/tickets/SCHOL-109.md`
  (`SCHOL-109 — Billing_Elements ListOutput TypeError Fix (12.9.2 Compatibility)`),
  which is `RELEASED` with a full evidence chain and KB-0025 recorded.
- SCHOL-108's observed behavior and fix were superseded by SCHOL-109. SCHOL-108
  was effectively folded into and closed-by SCHOL-109 but was never itself closed.
- `AGENTVERSE/MEMORY_INDEX.md` (lines ~135–136) lists SCHOL-108 as
  `RELEASED (G1+G2+G5+G6 PASS)`, which contradicts the ticket's canonical OPEN
  status. MEMORY_INDEX is a derived, non-authoritative summary for live status
  and is the stale outlier.

## Assessment

The canonical status of SCHOL-108 is **OPEN** (as recorded in the ticket and in
`CURRENT_STATE.json` `OPEN_NEXT`). The MEMORY_INDEX entry is stale and must not
be treated as authoritative for live status. The ticket is superseded by
SCHOL-109 but was never formally closed; it therefore remains OPEN.

## Disposition

- SCHOL-108 **retains** canonical status `OPEN` / grouped under `OPEN_NEXT` —
  it is **not** changed to RELEASED. Changing it would silently alter a
  canonical ticket's status, which is out of scope and prohibited.
- `AGENTVERSE/MEMORY_INDEX.md` SCHOL-108 entry is annotated (original text
  preserved) as stale/superseded by SCHOL-109 and non-authoritative for status.
- This record documents: SCHOL-108 was superseded by SCHOL-109 (which is
  RELEASED with full evidence) and should eventually be closed as a duplicate,
  but its canonical status remains OPEN pending an authorized closure.

## Guardrails observed

- No silent change of SCHOL-108 to RELEASED.
- No modification of canonical SCHOL-108 status.
- MEMORY_INDEX original text preserved; only an explicit correction note added.
