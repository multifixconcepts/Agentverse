# Remedy Readiness Audit — AgentVerse 2.0.4 (post-PR #8)

**Repo:** `multifixconcepts/Agentverse` (public) · **Local:** `/home/coder/project`
**Baseline under audit:** AgentVerse 2.0.4, PR #8 merged, `master` = `ad0eb16d9c2b75e1e7ea3a01b90acc73db8c721e`
**Mode:** STRICT READ-ONLY. No files modified, no state regenerated, no commits/pushes/merges, no history rewritten, no branches/protection changed, no credentials rotated, no new CI jobs, nothing deleted. The only file written is this report.

---

## 1. VERIFIED CURRENT BASELINE

Independently cross-checked local and GitHub:

| Item | Local | GitHub (API / raw) | Verdict |
|---|---|---|---|
| Default branch | — | `master` | identical |
| `master` SHA | `ad0eb16d9c2b75e1e7ea3a01b90acc73db8c721e` | `ad0eb16d9c2b75e1e7ea3a01b90acc73db8c721e` | **match** |
| origin/master vs local master | `0 0` (rev-list) | — | aligned |
| Current checked-out branch | `merge/clean-history` (`ccad0b7`) | — | pre-merge branch, content = master |
| `VERSION` (repo root) | `2.0.4` | raw=`2.0.4` | **2.0.4 confirmed** |
| Working tree | no tracked `M`/`AM` changes | — | clean except untracked audits |
| Untracked files | 7 files under `AGENTVERSE/audits/` | — | audit reports only |
| Open PRs | 10 (6 dependabot + docs/add-cicd-report + 2 CODEOWNERS #9/#10 + …) | API | 10 confirmed |
| `agentverse.db` / `-shm` / `-wal` | **all 3 TRACKED**, unmodified; `-wal` is an empty blob | — | tracked runtime files (anti-pattern) |

**Corrections vs prior audits (Phase-1 level):**
- db-shm/db-wal are **not working-tree deletions**; all three `.db*` files are tracked and present (committed in `ccad0b7`).
- There are now **10 open PRs**, not 8.
- Additional tracked DB artifacts exist: `database.db`, `clientflow/backend/prisma/dev.db`.

---

## 2. FINDING-BY-FINDING VERIFICATION MATRIX

Every finding was reproduced from source and, where possible, by executing the real script against an **isolated /tmp copy** (never the repo). Classification: **CONFIRMED / PARTIALLY_CONFIRMED / REJECTED / OUTDATED / INSUFFICIENT_EVIDENCE**.

### A. Release integrity
- **A1 — RELEASED + FAIL/NOT_VERIFIED can coexist; `verify-release-set.sh` ignores `verdictResult` → CONFIRMED.**
  - Source: `_tools/verify-release-set.sh` records `verdict_result` (line 124) but the final decision (lines 127-134) depends only on `blockedCount`/`hasUnknown`/`hasAmbiguous`. For `status === 'RELEASED'` → `eligible=true, reason="Already released"` (lines 110-112) with **no verdict gate**.
  - **Empirically reproduced** with the real script on a /tmp project copy:
    | RELEASED + verdict | verdict_result | eligible | final | exit |
    |---|---|---|---|---|
    | PASS | PASS | true | ALLOW | 0 |
    | **FAIL** | **FAIL** | **true** | **ALLOW** | **0** |
    | **NOT_VERIFIED** | **NOT_VERIFIED** | **true** | **ALLOW** | **0** |
    | no verdict | (empty) | true | ALLOW | 0 |
- **A2 — verdict file selection bug → CONFIRMED (deeper than A1).** Line 81-88 reads `verdictFiles[0]` (first in `readdirSync` = lexicographic). For SCHOL-109 the order is `G0`(PASS) then `G5`(FAIL) → it records `verdict_result: "PASS"`, masking the failing G5. **Even if verdict were gated, it reads the wrong gate.**
- **A3 — SCHOL-109 current example → CONFIRMED, with correct characterization.** `SCHOL-109.md` line 3 = bare `**Status:** RELEASED`; `SCHOL-109-G5.verdict.json` = `verdict FAIL, NOT_VERIFIED`, evidence `/bin/sh: 1: php: not found` on all 3 `php -l` targets, HTTP-200 MET, timestamp 2026-08-19T20:09:05Z. The actual fix IS present (`scholapro/functions/ListOutput.fnc.php:12` has the `$group` param). **The FAIL is environmental/historical** (php binary absent on the verifier at that time; this box has `php` at `/home/coder/bin/php`), **but the mechanism** (FAIL verdict coexisting with RELEASED + ALLOW) is fully reproducible today (A1). Both `SCHOL-109-G0` (PASS) and `SCHOL-814-release-set-verification.json` (ALLOW) exist, proving the coexistence is recorded in-repo.

### B. State consistency
- **B1 — `verify-state-consistency.sh` never exits non-zero on semantic failure → CONFIRMED.** `set -euo pipefail` (line 15), but the only `exit N` is option-parsing (line 38). `all_pass` (line 269/284/304-308) only sets the printed `overall` field; the script always returns 0. Executed: **exit code 0, `overall: PASS`**.
- **B2 — CI `state-consistency` job cannot fail on semantic inconsistency → CONFIRMED (triply).**
  1. The job runs `verify-state-consistency.sh 2>&1 | tee` (lines 250-254) and **never checks PIPESTATUS nor greps `overall`/`FAIL`**; step success = tee's 0. Only JSON validity is checked (lines 228-246).
  2. The script hardcodes `jq` at `/home/coder/project/_tools/jq` (lines 107,108,217,237-239). On the runner the checkout is `/home/runner/work/Agentverse/Agentverse` → jq absent → agent-count, hash, and metadata checks **degrade to WARN/empty** (proven by running the same jq against a nonexistent path → empty). So `overall=PASS` regardless.
  3. Even the checks that DO run locally (hashes, metadata) are **byte-integrity/schema** checks, not semantic-state agreement.
- **B3 — byte/hash vs semantic distinction → CONFIRMED.** `verify-state-consistency.sh` verifies (a) ORG_CHECKSUM sha256 hashes (byte integrity; currently PASS — real), (b) agent count, (c) ORG_CHECKSUM metadata (version/ticket_count/tool_count), (d) RELEASED tickets have a verdict *file*. It does **not** check: released-set agreement with CURRENT_STATE, dangling refs (SCHOL-813), SCHOL-108 contradiction, ledger validity, or ticket status semantics.

### C. Doctor CI behavior
- **C1 — `doctor.sh --ci` DOES return non-zero on DEGRADED → CONFIRMED.** Lines 662-665: `exit 1` if `RESULT` is `BLOCKED` or `DEGRADED`. `DEGRADED` is set (line 648) whenever any `check_fail` occurred. `--ci` currently yields `HEALTHY_WITH_WARNINGS` (exit 0) — no failure today.
- **C2 — workflow discards that exit code → CONFIRMED.** Lines 167-171: `doctor.sh --ci 2>&1 | tee` and fail only `if grep -q "BLOCKED"`. The `| tee` pipeline's exit is tee's (0) unless PIPESTATUS is read (it isn't); and only a literal `BLOCKED` string triggers failure. A real `check_fail`→`DEGRADED`→exit 1 is therefore **masked**.
- **C3 — BLOCKED structurally unreachable in `--ci` → CONFIRMED.** `BLOCKED=true` occurs only at line 90 (source/prod version divergence). In `--ci`, line 83 forces `PROD_VERSION="$SOURCE_VERSION"` → line 86 comparison is always equal → never `BLOCKED`. So the only string the job greps for cannot appear.
- **Net:** the doctor-check job is non-blocking for real failures; today it passes only because there are no `check_fail`s, not because enforcement exists.

### D. Canonical version semantics
- **D1 — three distinct version dimensions overloaded under one `version` key → CONFIRMED (semantic ambiguity, NOT data corruption).**
  - `2.0.4` = **release version**: root `VERSION`, `ORG_CHECKSUM.json.version`, `POLYGLOT_TOOLCHAIN_REGISTRY.json.version`.
  - `2.0` = **hardcoded schema/format literal**: `CURRENT_STATE`/`ENVIRONMENT_STATE`/`STATE_MAP`/`AGENT_REGISTRY`/`CONTRACT_REGISTRY`/`REQUIREMENT_LEDGER`. Proven literal: `_tools/sync-state.sh:125` and `:198` hardcode `version: '2.0'`; no tool reads root `VERSION` into these JSON fields.
  - `2.0.1` = **document-format version**: `KNOWLEDGE_BASE`/`TRUTH_HIERARCHY`/`MEMORY_INDEX`/`CONTEXT_RECONSTRUCTION` headers (all dated 2026-08-19).
  - **Recommendation:** do NOT mass-rewrite the `"2.0"`/`2.0.1` strings. Instead introduce an explicit schema-version field and document it; the release version belongs only in root `VERSION` + derived registries.

### E. Tickets
- **E1 — canonical ticket count is actually 109 → REJECTS the "111 vs 109" mismatch.**
  - `ORG_CHECKSUM.ticket_count` = 109; canonical pattern `SCHOL-<digits>.md` = **109** (matches; `verify-state-consistency.sh` reported PASS on this).
  - The "111" came from counting 2 non-canonical files: `SCHOL-010-delegation.md`, `SCHOL-011-delegation.md`. These are naming-hygiene artifacts, **not** a state-consistency defect. Treat as a classification/naming nit, not a defect.
- **E2 — released-ticket set is genuinely inconsistent → CONFIRMED, with corrected count.**
  - **14 tickets** have a RELEASED **first** status line across both formats: SCHOL-001 (bare, annotated `RELEASED (resolved)…`), 007, 008, 099-104, 106, 107, 109.
  - **`CURRENT_STATE.released_tickets` lists only 3** (106, 107, 109).
  - **Root cause = status-formatting split:** `sync-state.sh` (line 60) and both verifiers use bare-only regex `/^\*\*Status:\*\*/`; 10 tickets (007,008,010,011,099,100,101,102,103,104) use bullet `- **Status:**` and are **silently missed**. SCHOL-010/011 use bullet `- **Status:** RELEASED (2026-08-17)`.
  - Tooling that does `grep -q RELEASED` on bare lines sees 4 (001,106,107,109); the robust semantic set is 14; CURRENT_STATE sees 3.
- **E3 — SCHOL-813 dangling reference → CONFIRMED.** `CURRENT_STATE.json.active_tickets.VERIFICATION_BLOCKED=["SCHOL-813"]`; **no `SCHOL-813.md`** exists; referenced nowhere else. It is a live dangling state reference that a boot would trust.
- **E4 — SCHOL-108 → CONFIRMED, and it is a 2-vs-1 conflict, not a clean 3-way.**
  - `SCHOL-108.md:3` = `**Status:** OPEN` (bare).
  - `CURRENT_STATE.OPEN_NEXT` includes SCHOL-108 → **consistent with the file**.
  - `MEMORY_INDEX.md` (2026-08-19) = "SCHOL-108 … RELEASED (G1+G2+G5+G6 PASS)" → **the stale/contradictory outlier** (rank-5 memory vs ranks 2-3 file/state). Also note SCHOL-108/109 are near-duplicate Billing_Elements tickets (108 left OPEN, 109 carried the RELEASED + verdicts) — likely superseded, never closed.
- **E5 — canonical ticket-status source → CONFIRMED as `AGENTVERSE/tickets/SCHOL-*.md`** (with a **normalized first-status-line rule that accepts both bare and bullet `**Status:**`**). `CURRENT_STATE` is a derived snapshot; `MEMORY_INDEX`/`KNOWLEDGE_BASE` are non-authoritative for status.

### F. Requirement / contract registries
- **F1 — ledger titles mismatch real tickets → CONFIRMED.**
  | Ledger id → ledger title | Actual on-disk title | Match |
  |---|---|---|
  | SCHOL-008 "Student Photo Upload" | "M2: Student Billing Premium module" | MISMATCH |
  | SCHOL-097 "…Program URL Routing" | "Resume Stuck Session…" | MISMATCH |
  | SCHOL-103 "Monthly Fees Billing Generation Logic" | "Invoices.php + Receipts.php Pixel-Perfect…" | MISMATCH |
  | SCHOL-106 "…ZIP Package and Deployment" | "Date Range Rows Jumbled…" | MISMATCH |
  | SCHOL-109 "ListOutput API Parameter Name Fix" | "Billing_Elements ListOutput TypeError Fix" | PARTIAL |
  | SCHOL-110 "Student Count API…" | **no file** | DANGLING |
- **F2 — SCHOL-110 is a fabricated/unbacked chain → CONFIRMED, most severe.** `REQUIREMENT_LEDGER.json` + `CONTRACT_REGISTRY.json` (refs `SCHOL-110-ORIG`, `SCHOL-110`, `SCHOL-110-G5`) + `scholapro/lib/student_utils_handoff.json` (HO-110, "all_artifacts_updated: true") + `scholapro/lib/student_utils_test_claim.json` (TEST-110, TC-101..104 PASS) all assert a delivered, audited feature. **Ground truth is absent:** `scholapro/lib/student_utils.php` does not exist and is untracked; **no `getStudentCount` PHP implementation exists anywhere** in the tracked repo; `SCHOL-110.md` does not exist; `SCHOL-110-G5.verdict.json` does not exist. Every ledger `verifier` (SCHOL-008/097/103/106/110-G5) also points to **nonexistent verdict files**.
- **F3 — registries are leaf/unreferenced → CONFIRMED.** No tool, script, workflow, or opencode config consumes `REQUIREMENT_LEDGER.json` or `CONTRACT_REGISTRY.json`. **BUT** `AGENTVERSE_BOOT.md` instructs the LLM to read them as authoritative and line 102 says "operate on facts only" citing the ledger. So the fabricated ledger would be **trusted by a fresh boot** — this is a trust/poisoning risk even though no script enforces it.
- **Classification:** both registries = **historical/documentation artifacts, partially fabricated, NOT authoritative, unreferenced by tooling, but trusted by the boot narrative.**

### G. SQLite
- **G1 — tracking → CONFIRMED.** `agentverse.db`, `agentverse.db-shm` (32 KB binary), `agentverse.db-wal` (empty blob) are **all tracked** and clean; plus `database.db` and `clientflow/backend/prisma/dev.db`.
- **G2 — schema vs consumers → CONFIRMED.** Committed DB has 6 tables: `units`(13), `agents`(70), `tickets`(**1**), `gate_ledger`(7), `proficiency`(4), `kb`(**3**). `session_logs` and `task_ledger` **do not exist**, yet `doctor.sh` queries `session_logs` (lines 250-282) and `task_ledger` (lines 483-492), and `task-ledger.py` INSERTs/SELECTs `task_ledger` (lines 24,35). **Nuance:** `doctor.sh` gracefully degrades the two missing tables to **WARN** (not FAIL); `task-ledger.py` is a manual util that would raise `OperationalError` on a missing table. DB is drastically stale vs files (1 ticket vs 109; 3 kb vs 25).
- **G3 — classification → CONFIRMED as stale runtime/experimental artifact.** NOT referenced by `AGENTVERSE_BOOT.md`/`CONTEXT_RECONSTRUCTION.md` (JSON/md only). Consumed only by doctor (WARN) + task-ledger.py (manual) + sync-state (container/DB status). It is **not authoritative** for reconstruction. Committing WAL/SHM binary runtime files is a **git-hygiene defect**.

### H. CI application testing
- **H1 — clientflow vitest not runnable from a cold checkout as-is → CONFIRMED.**
  - `package-lock.json` present (hermetic npm install possible).
  - **But** `tests/setup.js` is loaded for every suite (via `vitest.config.js` `setupFiles`) and does `new PrismaClient()` + `prisma.$connect()` + `cleanDatabase()` in `beforeAll`. Without a reachable PostgreSQL (`DATABASE_URL` via `.env`) **the whole suite (including the self-contained `invoice.test.js`)** fails at setup. Needs DB provisioning + `npm ci` + `prisma generate`.
- **H2 — scholapro phpunit not runnable from a cold checkout → CONFIRMED.**
  - `phpunit.xml` needs `vendor/phpunit` (xsd + bootstrap), but `scholapro/vendor/` is absent + gitignored, `composer.lock` is gitignored, and `composer.json` has **no `require-dev`/phpunit declaration**. Nothing installs PHPUnit. Running `phpunit` fails immediately.
- **H3 — these are NOT currently supported CI targets → CONFIRMED.** The 11 CI jobs are AgentVerse control-plane scope (lint/regression/adversarial/remediation/verify-release-set/doctor/secret-scan/state-consistency/polyglot/smokes); **zero** references to vitest/phpunit/clientflow app tests. Adding them requires a deliberate scope decision + dependency/DB changes, not a "just add a job" step.

### I. Browser testing
- **I1 — classification → CONFIRMED as optional/manual (MCP), not committed CI.**
  - Playwright MCP configured in `opencode.jsonc` (`.mcp/node_modules/@playwright/mcp/cli.js`), browsers at `~/.cache/ms-playwright`. Used ad-hoc for live validation (e.g., SCHOL-106 G5 "Playwright confirmed row heights").
  - **No committed Playwright spec files, no `playwright.config.*`, no CI browser job.** Only `.opencode/agents/e2e-test-engineer.md` (a persona). `.playwright-mcp/` is correctly excluded from git.
  - Belongs to: **not core CI, not a committed target-project capability; currently ad-hoc/manual.** Whether it becomes core CI or stays manual is an open decision (see Changeset G).

---

## 3. SOURCE OF TRUTH CONTRACT

One canonical source per category; every duplicate artifact is classified generated / validated / historical / deprecated / runtime-only.

| # | Category | Canonical source | Every other artifact → role |
|---|---|---|---|
| 1 | AgentVerse release version | **repo-root `VERSION`** | `ORG_CHECKSUM.version` → must validate `== VERSION`; all others deprecated as release-version carriers |
| 2 | artifact/schema version | **new explicit field** `schema_version` in each JSON (not release version) | current `version:"2.0"` literal → rename/split to `schema_version` (generated) |
| 3 | agent registry / count | **`AGENTVERSE/AGENT_REGISTRY.json`** | `CURRENT_STATE.agent_count` → generated; `doctor.sh`/verifier → validate vs registry |
| 4 | ticket existence | **`AGENTVERSE/tickets/SCHOL-<digits>.md`** (canonical pattern only) | `SCHOL-0XX-delegation.md` → move out of `tickets/` (historical/read-only + renamed) |
| 5 | ticket status | **first status line of each ticket file**, normalized to accept bare AND bullet `**Status:**` | `CURRENT_STATE`/`MEMORY_INDEX` → derived/validated only, never authoritative |
| 6 | released ticket set | **derived from canonical tickets** (robust parser) into `CURRENT_STATE.released_tickets` | everything else (incl. `SCHOL-8xx-release-set-verification.json`) → validated against it |
| 7 | requirement tracking | **`AGENTVERSE/tickets/*.md`** (per-ticket ACs) | `REQUIREMENT_LEDGER.json` → deprecated/empty or rebuilt only from real tickets (see Changeset D) |
| 8 | contract tracking | **source code + per-ticket docs** (and a future regenerated `CONTRACT_REGISTRY` if desired) | current `CONTRACT_REGISTRY.json` → freeze/historical pending rebuild; remove unbacked SCHOL-110 entries |
| 9 | gate verdicts | **`AGENTVERSE/tickets/<id>-G<n>.verdict.json`** (single real gate model) | verdict-chain arbitrary files → governed by a defined gate set (G0/G1/G5 real; others explicit) |
| 10 | release eligibility | **`_tools/verify-release-set.sh` output** — only after it gates on verdict PASS (Changeset A) | `SCHOL-814-…json` → recorded artifact, must reflect ALLOW only when gates pass |
| 11 | environment state | **`AGENTVERSE/ENVIRONMENT_STATE.json`** (regenerated) | `CURRENT_STATE.rosariosis_version` → generated snapshot |
| 12 | knowledge/history | **`AGENTVERSE/KNOWLEDGE_BASE.md` + `MEMORY_INDEX.md`** (append-only memory) | used for context, **never** as truth for current status |
| 13 | SQLite / runtime state | **runtime-only, gitignored** `AGENTVERSE/session.db` (tables matching consumers) | committed `agentverse.db*` → remove from git; `database.db`/`prisma dev.db` → evaluate per-host/gitignore |

**Non-canonical classification summary:** generated (`CURRENT_STATE`, `ENVIRONMENT_STATE` from tickets + real checks) · validated (`ORG_CHECKSUM` metadata must equal canonical) · historical/read-only (`REQUIREMENT_LEDGER`, `CONTRACT_REGISTRY` as-is pending rebuild, audit reports, `-delegation.md`) · deprecated (`version:"2.0"` as release meaning) · removed-from-git (`agentverse.db-shm`, `-wal`, and decision on `agentverse.db`) · runtime-only (`session.db`).

---

## 4. ROOT CAUSE ANALYSIS

1. **Release gate chain is advisory, not enforced.** `generate-verdict.sh` writes verdict JSONs, but no consumer makes a FAIL/NOT_VERIFIED verdict a blocker. `verify-release-set.sh` even reads the wrong gate (`verdictFiles[0]` = G0) and discards `verdictResult` from its decision. Result: RELEASED ≠ verified.
2. **A single overloaded `version` key.** `sync-state.sh` hardcodes `2.0` as a schema literal; docs use `2.0.1` as a doc-format; only some registries carry the release `2.0.4`. Nothing defines schema-vs-release.
3. **Ticket status format is not normalized.** Bare vs bullet `**Status:**` splits the released set (14 semantic vs 3 detected by tooling) and causes `sync-state.sh`/verifiers to under-count and contradict CURRENT_STATE.
4. **Validators are structurally non-failing.** `verify-state-consistency.sh` never exits non-zero; `doctor.sh --ci` BLOCKED is unreachable; and the CI jobs pipe through `tee` and ignore PIPESTATUS / grep the wrong token. Plus hardcoded `/home/coder/project/_tools/jq` disables checks on the runner. So "green CI" does not mean "state consistent" or "healthy."
5. **Self-referential claim chains with no ground truth (SCHOL-110).** Ledger + contract + handoff + test-claim all assert a delivered feature; the source file and ticket don't exist. The boot narrative tells the LLM to trust the ledger ("operate on facts only").
6. **Runtime artifacts committed + schema drift.** SQLite WAL/SHM binaries in git; DB schema no longer matches doctor/task-ledger consumers; DB contents far behind files.
7. **App tests exist but are outside CI scope** and are not cold-checkout-runnable (clientflow needs a DB; scholapro lacks a phpunit dependency), and browser testing is MCP/manual only.

---

## 5. PROPOSED CHANGESETS

Each independently reviewable. **None implemented.**

### CHANGESET A — Release integrity
1. **Root cause:** `verify-release-set.sh` ignores `verdictResult` and reads the wrong gate file.
2. **Files:** `_tools/verify-release-set.sh`.
3. **Before/after:** Before — RELEASED+FAIL → ALLOW/0. After — a RELEASED/eligible ticket with any `FAIL` or `NOT_VERIFIED` verdict (or an ambiguous/absent verdict) → DENY/1, unless an explicit `resolution: "explicit_override"` + rationale is present. Also require verdict selection to prefer the **latest/quality gate (G5)** and to read **all** `<id>-G*.verdict.json` (any FAIL blocks), not just `files[0]`.
4. **Migration risk:** may block the already-RELEASED SCHOL-109 (G5 FAIL is environmental) — needs the override marker or a one-time disposition record.
5. **Back-compat:** add a documented `allow_release_override` field; default OFF. Old behavior not retained silently.
6. **Tests:** unit cases — FAIL→DENY; NOT_VERIFIED→DENY; PASS→ALLOW; missing verdict→WARN/DENY-configurable; G0 PASS + G5 FAIL→DENY; explicit override→ALLOW.
7. **State regeneration:** only the `SCHOL-814-…json` artifact regenerates (via a post-fix run); no ticket/state rewrite.
8. **Git history:** preserved.
9. **GitHub Actions:** the `verify-release-set` job already honors output; no workflow change needed (behavior tightened).
10. **Rollback:** revert the single script file.

### CHANGESET B — State validation correctness
1. **Root cause:** `verify-state-consistency.sh` never exits nonzero; CI `state-consistency` ignores output; hardcoded jq path disables CI checks; `doctor.sh --ci` DEGRADED masked.
2. **Files:** `_tools/verify-state-consistency.sh`, `.github/workflows/agentverse-ci.yml` (`state-consistency`, `doctor-check` jobs).
3. **Before/after:** Before — always exit 0, overall=PASS on CI. After — script exits 1 on any FAIL and supports `--ci-no-jq` or portable jq invocation; workflow checks `${PIPESTATUS[0]}` (and/or greps `overall":"FAIL","`); doctor job checks doctor.sh's real exit code (DEGRADED→fail) in addition to `BLOCKED`.
4. **Migration risk:** with semantic checks added, the job may now flag the genuine released-set/SCHOL-813/SCHOL-108 drift → coordinate with Changeset C so CI goes green only after truth is corrected.
5. **Back-compat:** tooling keeps its JSON output shape.
6. **Tests:** seed temp tickets with real mismatches (wrong count, dangling ref, released-set drift) → expect exit 1; CI-shell test verifying PIPESTATUS propagation.
7. **State regeneration:** none.
8. **Git history:** preserved.
9. **GitHub Actions:** `state-consistency` and `doctor-check` become genuinely failing on their conditions.
10. **Rollback:** revert the two files.

### CHANGESET C — Canonical ticket/state reconciliation
1. **Root cause:** bullet-vs-bare status formatting; stale CURRENT_STATE; dangling SCHOL-813; SCHOL-108 contradiction; canonical count confused by `-delegation.md`.
2. **Files:** `_tools/sync-state.sh` (normalized status regex + schema-version split), `AGENTVERSE/CURRENT_STATE.json`, disposition of SCHOL-813/SCHOL-108, move `SCHOL-010/011-delegation.md` out of `tickets/`.
3. **Before/after:** Before — tooling sees 3 released, semantics show 14, OPEN_NEXT has a "RELEASED" memory entry, VERIFICATION_BLOCKED has a nonexistent ticket. After — a single robust parser defines the released set (14 -> decide final), state regenerated, SCHOL-813 either materialized or explicitly removed as historical, SCHOL-108's memory reconciled (mark memory as superseded by SCHOL-109; keep OPEN + OPEN_NEXT), delegation notes renamed/relocated.
4. **Migration risk:** deciding the intended released set (14 vs 3) must be evidence-based; do not silently DROP releases. SCHOL-813 must be resolved with a disposition record, not just deleted.
5. **Back-compat:** status remains human-readable `**Status:**`; bot formats normalized to bare for new tickets.
6. **Tests:** parser unit tests across bare/bullet/annotated; regenerate-state then diff against expected released set.
7. **State regeneration:** **YES** — CURRENT_STATE regenerated from normalized tickets.
8. **Git history:** preserved (no rewrite).
9. **GitHub Actions:** `state-consistency` may now report real drift until this lands; land C before enabling B's exits.
10. **Rollback:** restore previous CURRENT_STATE + script (state is regenerable).

### CHANGESET D — Registry and historical-artifact classification
1. **Root cause:** `REQUIREMENT_LEDGER.json`/`CONTRACT_REGISTRY.json` are disconnected/fabricated (SCHOL-110) yet told to the boot as authoritative facts.
2. **Files:** `AGENTVERSE/REQUIREMENT_LEDGER.json`, `AGENTVERSE/CONTRACT_REGISTRY.json`, `AGENTVERSE/AGENTVERSE_BOOT.md` (lines 43/45/49/102/112-113), `scholapro/lib/student_utils_handoff.json`, `scholapro/lib/student_utils_test_claim.json`.
3. **Before/after:** Before — boot trusts a ledger listing fabricated SCHOL-110 and mismatched titles. After — SCHOL-110 entries marked `"status":"HISTORICAL_UNVERIFIED"` (or removed with disposition); BOOT.md no longer calls these "facts" for current decisions; fabricated claim/handoff files flagged as unbacked; ledger either regenerated from real tickets or explicitly frozen as historical.
4. **Migration risk:** leaving a false "fact" trusted by boot is the risk today; the fix must not delete history, only re-label it.
5. **Back-compat:** JSON shape kept; content re-labeled (no field removal that breaks consumers — none exist).
6. **Tests:** grep assertions that no unbacked SCHOL-110 claim reads as "VERIFIED"; boot doc linter for forbidden "fact" claims.
7. **State regeneration:** ledger content re-labeled; no ticket regeneration.
8. **Git history:** preserved.
9. **GitHub Actions:** none (not consumed).
10. **Rollback:** restore the three JSON/md files.

### CHANGESET E — SQLite / runtime-artifact hygiene
1. **Root cause:** WAL/SHM binaries + stale DB schema/contents tracked in git.
2. **Files:** `AGENTVERSE/agentverse.db`, `-shm`, `-wal`, `.gitignore`, `_tools/doctor.sh` + `_tools/task-ledger.py` (schema alignment or explicit skip).
3. **Before/after:** Before — runtime files in git; doctor downgrades missing tables to WARN; task-ledger errors. After — `agentverse.db*` removed from index (runtime/ignored); a fresh runtime DB schema created matching doctor/task-ledger consumers, OR doctor/task-ledger updated to tolerate/seed the schema; `database.db`/`prisma dev.db` evaluated.
4. **Migration risk:** if `agentverse.db` is genuinely used by any process, removal breaks it — verify before removing (evidence: not in boot path; only doctor-WARN/task-ledger-manual reference it).
5. **Back-compat:** doctor remains WARN-tolerant if schema absent.
6. **Tests:** `doctor.sh --ci` still passes after DB removal (all-WARN path); `task-ledger.py` schema present when DB in use.
7. **State regeneration:** n/a.
8. **Git history:** a removal commit; history preserved (not rewritten).
9. **GitHub Actions:** none (agentverse.db not in CI path).
10. **Rollback:** re-add the db blobs from history if needed.

### CHANGESET F — CI target-project testing (decision-gated)
1. **Root cause:** scholapro/clientflow app tests exist but are out of CI scope and not cold-runnable.
2. **Files (conditional):** `.github/workflows/agentverse-ci.yml` (new jobs), `scholapro/composer.json` (add phpunit dev-dep + lock), `clientflow/backend` (DB service for tests + prisma generate).
3. **Before/after:** Before — app suites never run in CI; unit coverage invisible. After — (only if the org decides these are supported targets) a `scholapro-phpunit` job and a `clientflow-vitest` job with a Postgres service container; otherwise unchanged.
4. **Migration risk:** adds DB + dependency burden; flakiness risk if not hermetic.
5. **Back-compat:** additive jobs only.
6. **Tests:** the app suites themselves gate the change.
7. **State regeneration:** none.
8. **Git history:** preserved.
9. **GitHub Actions:** new jobs only when scope decision is positive.
10. **Rollback:** remove the jobs.
**DECISION REQUIRED (not for this read-only audit to make):** whether AgentVerse CI should cover the products it orchestrates, or remain framework-scoped.

### CHANGESET G — Browser / deployment maturity (optional, later)
1. **Root cause:** browser validation is MCP/manual only; no committed specs or CI job; no automated deploy rollback.
2. **Files:** new `playwright.config.*` + `tests/e2e` specs; optional CI browser job; deploy rollback tooling.
3. **Before/after:** Before — manual. After — committed, optionally CI-run; rollback scripted.
4. **Migration risk:** browser jobs are flaky/expensive; keep optional.
5. **Back-compat:** additive.
6. **Tests:** the specs themselves.
7. **State regeneration:** none.
8. **Git history:** preserved.
9. **GitHub Actions:** new optional job.
10. **Rollback:** remove specs/jobs.

---

## 6. TEST / VALIDATION STRATEGY

- **Unit (scripts):** verify-release-set verdict gating (A); verify-state-consistency exit/propagation on seeded mismatches (B); robust status parser (C); boot-doc "fact" linter (D); doctor all-WARN post-DB-removal (E).
- **Integration (temp /tmp, never repo):** reproduce RELEASED+FAIL → expect DENY after A; simulate CI jq-absence → expect FAIL-capable script after B.
- **CI level:** after B, assert `state-consistency`/`doctor-check` can genuinely fail; after A, assert `verify-release-set` can DENY.
- **Contract/data checks:** compare regenerated CURRENT_STATE released set == 14 (or the agreed set); assert no unbacked SCHOL-110 claim reads VERIFIED; assert woman-in-boot decision removes ledger "fact" language.
- **Cold-checkout check (for F):** `npm ci && vitest run` and `composer install && vendor/bin/phpunit` from a fresh clone with the required deps/DB.

---

## 7. RISKS AND MIGRATION CONCERNS

1. **Making validation genuinely blocking (A+B) will surface today's drift.** Order matters: reconcile truth (C/D) so the newly-failing jobs are green by evidence, not by being disabled.
2. **SCHOL-109 gate.** G5 FAIL is environmental; do not delete its FAIL verdict (history). Use an explicit override/disposition so release-blocking doesn't choke on a past false-negative.
3. **SCHOL-813 / SCHOL-108 / released-set.** These need evidence-based dispositions; NEVER silently delete tickets or drop releases. Preserve history; re-label, don't rewrite.
4. **Version string "fix".** Do NOT mass-rewrite 2.0/2.0.1; introduce `schema_version` and keep release version only in root `VERSION`.
5. **agentverse.db removal.** Confirm no live consumer before removing from git (evidence says WARN-only/side-artifact).
6. **SCHOL-110 trust poisoning.** Highest conceptual risk — fix the boot narrative (D) before it can mislead a fresh boot; do not promote a fabricated claim to current truth.
7. **CI scope creep (F/G).** Adding app/browser jobs increases cost/flake; gate on an explicit support decision.
8. **Do not weaken existing tests / do not convert history to current state without proof** — all changesets preserve and add checks, none loosen.

---

## 8. EXACT FILES THAT WOULD CHANGE

- `_tools/verify-release-set.sh` (A)
- `_tools/verify-state-consistency.sh` (B)
- `.github/workflows/agentverse-ci.yml` (B start, F/G if approved)
- `_tools/sync-state.sh` (C)
- `_tools/doctor.sh`, `_tools/task-ledger.py` (E schema alignment)
- `AGENTVERSE/CURRENT_STATE.json` (C regenerated)
- `AGENTVERSE/AGENTVERSE_BOOT.md` (D)
- `AGENTVERSE/REQUIREMENT_LEDGER.json` (D re-labeled)
- `AGENTVERSE/CONTRACT_REGISTRY.json` (D re-labeled)
- `scholapro/lib/student_utils_handoff.json`, `scholapro/lib/student_utils_test_claim.json` (D disposition)
- `AGENTVERSE/tickets/SCHOL-010-delegation.md`, `SCHOL-011-delegation.md` (C relocate/rename)
- `.gitignore` + git index for `agentverse.db*` (E)
- `AGENTVERSE/KNOWLEDGE_BASE.md`, `TRUTH_HIERARCHY.md`, `MEMORY_INDEX.md`, `CONTEXT_RECONSTRUCTION.md` (D schema-version headers — only if semantic proven; optional)
- `scholapro/composer.json` (+ lock), `clientflow/backend/...` (F, conditional)

---

## 9. ITEMS THAT MUST NOT CHANGE

- **No git history rewrite, no force-push, no rebase of historical commits** (standing immutable-history decision).
- **No deletion of tickets or database files without a documented disposition**; history preserved (re-label, don't destroy).
- **No mass rewrite of `version` strings** until schema-vs-release semantics are adopted (proven here: keep release in `VERSION`).
- **No credential rotation / no revealing secret values** (Portainer token, N8N/school4/demo credentials, `clientflow` secrets).
- **No weakening of existing tests or CI checks.**
- **No converting historical records into current state without proof** (esp. SCHOL-110).
- **No branch/protection/default-branch changes; no merges/pushes.**
- **No new CI jobs** until the scope decision (F) and browser decision (G) are made.
- **The AgentVerse boundary as a LOCAL engineering-orchestration framework**, not a deployed SaaS, is preserved.

---

## 10. RECOMMENDED IMPLEMENTATION ORDER

1. **C — Canonical ticket/state reconciliation** (normalize status, resolve SCHOL-813/108, decide released set, relocate delegation notes, introduce `schema_version`). Fix truth first so later validation is trustworthy.
2. **D — Registry/historical classification** (freeze/re-label REQUIREMENT_LEDGER & CONTRACT_REGISTRY; fix the boot "fact" directive; disposition SCHOL-110 fabrication chain). Prevents trust poisoning.
3. **B — State validation correctness** (real exit codes + CI propagation + portable jq). Now the CI can actually enforce; should be green per C/D.
4. **A — Release integrity** (verdict-gate verify-release-set). Closes the RELEASED-with-FAIL gap; use override markers for SCHOL-109.
5. **E — SQLite/runtime-artifact hygiene** (remove runtime files from git; align doctor/task-ledger schema).
6. **Decision gate:** is AgentVerse CI to cover its product projects?
7. **F — Target-project testing** (only if scope decision positive; add deps/DB as needed).
8. **G — Browser/deployment maturity** (optional, later).

Rationale: **truth → enforcement → integrity → hygiene → breadth.** This matches the user's directive (verify current truth → define canonical truth → minimal changesets → implement → validate from clean checkout → push/CI) and avoids "fixing" history or minting more inconsistent state. A→D must precede B's failing behavior so fixes are evidence-driven, not disabling validators.

---

*REMEDIATION DESIGN COMPLETE — NO FILES MODIFIED — AWAITING AUTHORIZATION.*
