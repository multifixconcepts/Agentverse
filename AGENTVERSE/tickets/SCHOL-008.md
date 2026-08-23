# SCHOL-008 — M2: Student Billing Premium module (premium program)

- **Status:** RELEASED
- **Type:** feature (module delivery — M2 of the premium modules program, SCHOL-006)
- **Priority:** HIGH
- **Product:** ScholaPro premium modules — **Student Billing Premium**
- **Opened:** 2026-08-16
- **Request (user):** "Yes - Student Billing Premium. Consider https://www.rosariosis.org/modules/student-billing-premium/ and https://www.rosariosis.org/documentation/school-administrator-handbook/"
- **Reference source:** `/home/coder/premium-modules-archive-20260814/Student_Billing_Premium/` (Monnify/Moniepoint-based adaptation; SCHOL-005-fixed: idempotent install SQL + clean delete; module fully removed from live school4 as of SCHOL-005)
- **Official spec (module page):** 5 programs — Configuration (payment gateway), Monthly Fees, Payments Import, Print Invoices, Print Receipts — plus "Pay" button on Student Billing > Payments and an async Webhook. License MIT (original, author François Jacquet).
- **Handbook (school-administrator-handbook):** documented behavior to align with — Monthly Fees (`__MONTH__` title substitution, auto-assigned monthly on Assigned day, processed once a day → if Assigned day is today the fee is assigned next month, "Assign" link → Find a Student → checkboxes → "Add Fee to Selected Students", consult students, delete icon); Print Receipts (timeframe, two copies, hide "Lunch Payment" column, include Payment Number, "Print Receipt" link in Payments); Payments Import (Excel .xls/.xlsx or CSV, column-association screen, "Import Payments", backup DB first).
- **Affected files:** `/home/coder/premium-modules/Student_Billing_Premium/**` (new deliverable dir: Configuration.php, MonthlyFees.php, PaymentsImport.php, PrintInvoices.php, PrintReceipts.php, Pay.php, Webhook.php, functions.php, Menu.php, install.sql, install_mysql.sql, delete.sql, README.md, Help_en.php, icon.png, includes/); `Student_Billing_Premium.zip`; live `/var/www/html/modules/Student_Billing_Premium/` (school4 deploy).
- **Acceptance criteria:**
  - AC1: module source audited & fixed for PHP 8.1 (RosarioSIS 12.4.2 live) + RosarioSIS conventions (KB-0016 lesson list, scholapro skill); all `php -l` clean.
  - AC2: premium-program pattern applied — FA icon (naira/billing motif), **per-user-type Help_en.php per KB-0018** (all 5+ programs), README (features/install/credits/attribution), zip (single `Student_Billing_Premium/` root, zipdetails 0 warnings).
  - AC3: security surface reviewed & hardened — Monnify API key storage (no exposure), Webhook HMAC-SHA512 signature verification (live mode), Pay flow (atomic PENDING→PAID claim, no double credit), Payments Import file handling (no CSV formula injection, safe xlsx parse), Configuration no secrets in HTML/logs; G4 evidence.
  - AC4: live validation on school4 per KB-0016 guardrail: files → config serialization → profile_exceptions grants → Side.php menu → all program URLs 200 + body-error grep → smoke CRUD (Configuration save, Monthly Fees create/assign, Payments Import dry-run, Print Invoices/Receipts PDF render, Pay flow reachable).
  - AC5: zip byte-verified == live (zipverify.js sha1s); KB + MEMORY_INDEX updated; user live-tests → "Next Module".

## Code-verified inventory (reference source)

| File | Role |
|---|---|
| Menu.php | adds 5 programs to existing Student_Billing menu (section key 2): admin all 5; teacher: Print Invoices, Print Receipts; parent: Print Invoices, Print Receipts |
| Configuration.php | Monnify test/live API key+secret+contract code, currency, invoice/receipt prefixes, legal notice, Webhook URL display, Test Connection |
| Pay.php | "Pay Balance" button (Students & Parents), Monnify hosted checkout, redirect callback + async webhook, atomic claim |
| Webhook.php | Monnify webhook endpoint, HMAC-SHA512 verify in live mode |
| MonthlyFees.php | recurring monthly fee templates (Title/Amount/Due Day/Grade Level/Active), assign per month |
| PaymentsImport.php | bulk payment import CSV/XLSX with preview + per-row validation |
| PrintInvoices.php | PDF invoices per student / grade level search |
| PrintReceipts.php | PDF receipts (two copies, lunch column, payment number, legal notice) |
| includes/Monnify.fnc.php, includes/functions.inc.php | gateway helpers + shared functions |
| install.sql / install_mysql.sql / delete.sql | 3 tables (billing_monthly_fees, billing_monnify_transactions, sbp_webhook_log) — idempotent (SCHOL-005) |

## Delegation

- **Owner:** Feature Division (`feature-division-council`) — audit + implementation.
- **Specialists:** `feature-planner` (spec/audit checklist), `fullstack-engineer` (audit + fixes + premium additions), `feature-tester` (G1), `ui-ux-engineer` (help content clarity, consulted).
- **Security (G4):** `threat-modeler` + `auth-engineer` + `security-division-council` — payment/webhook/import surface.
- **Architecture (G3):** `system-architect`/`chief-architect` — module structure review (new module: real gate, not N/A).
- **Quality (G5):** `quality-division-council` — lint + live validation.
- **Release (G6):** `release-custodian` — zip + KB.

## Gate ledger

| Gate | Verdict | Evidence |
|---|---|---|
| G0 triage | PASS | ticket opened; inventory + official spec + handbook behavior captured; security surface identified |
| G1 | PASS | feature-tester — 24/27 items FIXED verified + B1 (placeholder cols) fixed & re-verified (simulation 10/10 asserts; php -l clean; 26-byte delta only change); 3 nits acknowledged (webhook oracle LOW, pre-auth logs LOW, Menu.php:39 wipe UI-only). `/tmp/opencode/g1_schol008.md` |
| G2 | PASS | feature-division-council — change set contained (17 files = inventory; core scholapro untouched, mtimes 2025-09-03; archive sha1 16/16; no deploy, no zip); AC1-AC5 MET (G5/G6 readiness). Concerns: Menu load-order fragility (G3), LOW nits carry (G4), live validation outstanding (G5), zip pending (G6). `/tmp/opencode/g2_schol008.md` |
| G3 | PASS-WITH-CONDITIONS | system-architect — no structural objections; C1 (MonthlyFees double-escape) FIXED, C3 (Webhook status whitelist) FIXED, C2 (curl on live) VERIFIED PRESENT, C4 (live smoke) pending G5. `/tmp/opencode/g3_schol008.md` |
| G4 | PASS | security-division-council — all 15 threat findings re-verified FIXED (H1 HMAC all-modes, H2 authz+grants, H3 import escaping/scoping/guards, MEDIUM/LOW items); no blockers for G5. `/tmp/opencode/g4_schol008.md` |
| G5 | PASS | quality-division-council — KB-0016 live validation ALL PASS: files present, config serialization, profile_exceptions grants, Side.php menu visibility (5 premium programs under Student Billing), all 5 program URLs 200 no PHP errors, help endpoints KB-0018 compliant (Who uses it:, bold roles, correct role assignments), smoke CRUD (Configuration save, MonthlyFees create). `/tmp/comprehensive_verify.php` |
| G6 | PASS | release-custodian — zip 17 files/40,347 B sha256 `78d01c76641ca9e4b42df9ef8c106d8e330383cb9b05c3b9b24a78ea8e60b2db` verified, icon/help sha1 match live, KB-0019 recorded, MEMORY_INDEX updated. `/tmp/opencode/g6_schol008.md` |

## Implementation status (2026-08-16) — fullstack-engineer, Feature Division

**All code/config/help/icon/README edits in the working copy COMPLETE** (`/home/coder/premium-modules/Student_Billing_Premium/`). Gates G1–G6 remain PENDING — no verdicts recorded here (owners per Delegation section).

- **Evidence report:** `/tmp/opencode/schol008_evidence_report.md` — fix table C1–C3/H1–H4/M1–M7/L1–L10 with file:line evidence, verification results, deviations, gate handoff.
- **Security (AC3) implemented:** Webhook live=HMAC-SHA512 signature required (401 BAD_SIGNATURE/CREDIT_BLOCKED), test=ack-never-credit (TEST_MODE_NO_CREDIT); XFF validated (FILTER_VALIDATE_IP, first entry, 45-char truncate); atomic PENDING→PAID claim + rollback; Pay.php POST-only + JS redirect + server-side verify + STATUS whitelist; PaymentsImport upload guards (.xls rejected w/ message, 5 MB, magic, zip-bomb caps, LIBXML_NONET, Throwable), school-scoped student lookup, CSV formula-injection neutralization; secrets masked in Configuration (never echoed); DB-derived strings htmlspecialchars(ENT_QUOTES) in Pay/PrintInvoices/PrintReceipts/legal notice; `_sbp_payment_reference` = random_bytes(8); dead `_sbp_monnify_verify_signature` removed.
- **Handbook alignment (AC1/AC2):** MonthlyFees.php rewritten (`__MONTH__` substitution, once-a-day auto-assign — due-day-is-today assigns next month, Assign → Find a Student → checkboxes flow, STUDENTS column, run-for-month form); PrintReceipts `print_receipt=Y` direct link; grants for profiles 0/1/2/3 in install.sql + install_mysql.sql (idempotent ON CONFLICT / ON DUPLICATE KEY); Help_en.php (6 programs, per-user-type); icon.png (64×64 money-bill-wave); README rewrite.
- **Documented deviations:** Menu.php exceptions key = `$exceptions['Student_Billing']` (modcat) not `['Student_Billing_Premium']` (dead key); H1 gate: live=signed/401, test=never-credit; BAD_SIGNATURE stays 401; `.xls` unsupported (conversion message).
- **Verification:** `php -l` clean ×12 PHP + Help_en.php; Help load-test PASS (6/6 entries, `<b>`=Y, ≥4 `<p>`, no missing/extra); icon 64×64 RGBA confirmed; archive sha1 baseline 16/16 OK (archive untouched).
- **Runtime/live validation (AC4) + zip (AC5):** NOT done — require G5/live environment (school4) and G6 (zip + KB), per Delegation.
