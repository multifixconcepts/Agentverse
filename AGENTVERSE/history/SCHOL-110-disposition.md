# Disposition — SCHOL-110

**Type:** Historical artifact classification record (Remediation Phase 1, Changeset D)
**Created:** 2026-08-28
**Scope:** Registry/historical-artifact classification

## Finding — SCHOL-110 is a fabricated/unbacked chain

The following artifacts assert a delivered, audited feature (`getStudentCount`
with a grade-level filter) that is not backed by any verifiable ground truth:

- `AGENTVERSE/REQUIREMENT_LEDGER.json` — `SCHOL-110` entry (references
  `SCHOL-110-ORIG`, `SCHOL-110-G5`).
- `AGENTVERSE/CONTRACT_REGISTRY.json` — `function_contracts.student-utils-api`
  (references `SCHOL-110-ORIG`, `SCHOL-110`, `SCHOL-110-G5` and file
  `scholapro/lib/student_utils.php`).
- `scholapro/lib/student_utils_handoff.json` — `HO-110`, claims
  `all_artifacts_updated: true`.
- `scholapro/lib/student_utils_test_claim.json` — `TEST-110`, `TC-101..104`
  marked `PASS`.

**Ground truth absent:**

- `scholapro/lib/student_utils.php` does not exist and is untracked.
- No `getStudentCount` PHP implementation exists anywhere in the tracked repo.
- `AGENTVERSE/tickets/SCHOL-110.md` does not exist.
- `SCHOL-110-G5.verdict.json` does not exist.
- Every ledger `verifier` (`SCHOL-008/097/103/106/110-G5`) points to nonexistent
  verdict files.

## Disposition

SCHOL-110 and its artifact chain are classified as **HISTORICAL UNVERIFIED /
FABRICATED** — they must not be treated as canonical facts, released state, or
verifiable evidence. The referring artifacts are annotated (original content
preserved) with an explicit disposition field so future consumers can
distinguish them from machine-verifiable truth.

Per the trust boundary established in `AGENTVERSE_BOOT.md` (CLAIM ≠ FACT;
ledgers and contract registries are historical, not unquestionable live facts),
this record:

- **Labels** the SCHOL-110 entries as historically-unverified/fabricated via
  minimal JSON `disposition` field additions.
- Does **not** delete the records (historical trace preserved).
- Does **not** fabricate `scholapro/lib/student_utils.php` or `SCHOL-110.md`.
- Does **not** present SCHOL-110 as verified/released.

## Guardrails observed

- No fabrication of `student_utils.php` / `SCHOL-110.md` / verdict files.
- No deletion of artifacts without documented disposition.
- Original JSON content preserved; only additive disposition fields added.
