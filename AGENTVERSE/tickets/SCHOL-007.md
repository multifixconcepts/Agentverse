# SCHOL-007 — Help content: explicit per-user-type usage (M1 Billing Elements, pattern for all modules)

- **Status:** RELEASED
- **Type:** feature (module Help content / UX documentation)
- **Priority:** HIGH
- **Product:** ScholaPro premium modules (applies to M1 Billing Elements first; the structure becomes the standard for all future modules)
- **Opened:** 2026-08-16
- **Request (user):** "Each help content needs to be explicitly detailed per user type on how to use — not generic/half generic as this idea would be used for the rest of the modules to be created."
- **Affected files:**
  - `/home/coder/premium-modules/Billing_Elements/Help_en.php` (rewrite)
  - `/home/coder/premium-modules/Billing_Elements/README.md` (note if needed)
  - `/home/coder/premium-modules/Billing_Elements.zip` (rebuild)
  - live `/var/www/html/modules/Billing_Elements/Help_en.php` (school4 deploy)
- **Acceptance criteria:**
  - AC1: each of the 8 programs' Help text has an explicit per-user-type section (Admin / Teacher / Student / Parent) explaining what that user type can do and step-by-step how to do it — no generic role-agnostic prose.
  - AC2: content matches the code-enforced access model (verified against Menu.php + per-program `User('PROFILE')`/`AllowEdit()` checks) — no invented capabilities.
  - AC3: `php -l` clean; help endpoints return the new texts on live school4 (all 8 `?bottomfunc=help&modname=Billing_Elements/<Program>.php`).
  - AC4: zip rebuilt + integrity-verified (zipdetails 0 warnings, single root, sha1s == live).
  - AC5: the per-user-type help structure is recorded as a convention (KB entry) so all future modules follow it.

## Code-verified role model (evidence from source, pre-implementation)

| Program | Admin | Teacher | Student | Parent | Code evidence |
|---|---|---|---|---|---|
| Elements.php | yes | no | no | no | `User('PROFILE')!=='admin'` → ErrorMessage (Elements.php:14-20) |
| MassAssignElements.php | yes | no | no | no | MassAssignElements.php:15-21 |
| MonthlyElements.php | yes | no | no | no | MonthlyElements.php:15-21 |
| CategoryBreakdown.php | yes | menu lists teacher, but code blocks | no | no | Menu.php:22-23 teacher list vs CategoryBreakdown.php:15-21 (**mismatch finding**) |
| StudentElements.php | yes | yes | no | no | no profile check; save gated `AllowEdit() && UserStudentID()` (StudentElements.php:20-22); per-student scope `WHERE sbe.STUDENT_ID=UserStudentID()` (line 110) |
| DailyTransactions.php | yes | yes (school-wide) | no | no | no profile check; no staff scoping in SQL |
| Store.php | yes | no | yes | yes | requires UserStudentID() (Store.php:23); grade-level filter `_be_element_applies_to_grade` (line 46); auto course enrollment when COURSE_PERIOD_ID (lines 65-67) |
| MyElements.php | yes | no | yes | yes | requires UserStudentID() (MyElements.php); read-only |

**Finding (not in scope):** teacher menu lists CategoryBreakdown (Menu.php:22-23) but the program blocks non-admin (CategoryBreakdown.php:15-21). Flagged to user; not fixed in this ticket.

## Delegation

- **Owner:** Feature Division (`feature-division-council`) — content drafting + implementation.
- **Specialists:** `feature-planner` (per-user-type help spec), `fullstack-engineer` (Help_en.php implementation), `feature-tester` (G1 peer), `ui-ux-engineer` (consulted for guidance clarity).
- **Consulted:** Documentation Guild (`docs-lead`) for the cross-module convention; Knowledge Commons (`knowledge-curator`) for KB entry.
- **Gates:** G1 peer → G2 division → G3 arch (N/A: no structural change) → G4 security (N/A: static text, no runtime surface) → G5 quality (lint + live verification) → G6 release (zip + convention).

## Gate ledger

| Gate | Verdict | Evidence |
|---|---|---|
| G0 triage | PASS | ticket opened; type/priority/AC defined; role model verified in source (see table) |
| G1 | PASS | peer review (feature-tester): all 8 program entries follow the per-user-type structure; content matches the code-verified role table (no invented capabilities); style clean (apostrophe escapes, `_help()` wrapper, `<p>` blocks) |
| G2 | PASS | division council: AC1–AC5 scope complete; no scope creep; spec §1 evidence table matches ledger role table |
| G3 | N/A | static text only (`Help_en.php` rewrite); no structural change, no core code touched — architecture review not applicable |
| G4 | N/A | static text only; no runtime surface, no input handling, no SQL, no secrets — security review not applicable |
| G5 | PASS | quality division: `php -l` clean; live school4 verified 8/8 `?bottomfunc=help&modname=Billing_Elements/<Program>.php` endpoints return the new texts as admin with per-user-type markers |
| G6 | PASS | release-custodian: zip rebuilt (17 files, 32,871 B, sha256 `ef5c8182bd6fb6e3e0f7123d45b937775c2e40a16fbb87b988978a83383a6540`); zipverify — Help_en.php entry 11,872 B sha1 `73f982251c0620261f955f331cbafcbb7a399280` == live; zipdetails 0 warnings; single `Billing_Elements/` root; convention KB-0018 recorded; KB-0014 Rev R4 line + MEMORY_INDEX updated |

## Outcome

The Help menu of the Billing Elements module (M1) now explains each of the 8 programs per user type: a one-line "what it is", a "Who uses it:" line that also states explicitly which roles do NOT have access, and a bold-labeled step-by-step subsection for every supported role (Administrator / Teacher / Students & Parents) with verbatim button and field names. The same structure is now the mandatory convention (KB-0018) for every future module. Live school4 already serves the new texts on all 8 help endpoints; the rebuilt `Billing_Elements.zip` (17 files, sha256 `ef5c8182…`) is ready for the user to re-download → delete → upload → activate. Known finding F1 (teacher menu lists CategoryBreakdown but code blocks it) is flagged for a follow-up, not fixed here.
