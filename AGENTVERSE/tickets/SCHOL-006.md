# SCHOL-006 — ScholaPro Premium Modules Program (fabricate RosarioSIS-grade add-ons, ship as zips)

**Status:** IN PROGRESS — 2026-08-14 (Module 1: Billing Elements)
**Type:** feature (multi-module program) · **Priority:** high · **Product:** ScholaPro (school4.edunaija.online, RosarioSIS 12.4.2 stock live + 12.5 fork)
**Files (deliverables):** `/home/coder/premium-modules/<ModuleName>/…` (module source trees, downloaded & shipped as `.zip` by user)
**Platform:** dev box `/home/coder` + live `school4` (upload via `Modules.php?modname=School_Setup/Configuration.php&tab=modules`)

## Request
Fabricate, one module at a time, a catalog of premium (sold-separately) add-on modules for the school4/ScholaPro product, at a quality bar matching the original RosarioSIS premium add-ons (rosariosis.org/modules). User uploads each shipped `.zip` on the live site via the Modules manager, validates functionality, then says **"Next Module"**.

Modules to fabricate (user's list, build order = listed order):
1. Billing Elements  2. Student Billing Premium  3. Certificate  4. Class Diary  5. Email  6. Email Alerts  7. Email Log  8. Email Parents  9. Email Students  10. Entry and Exit  11. Food Service Premium  12. Grades Import  13. Hostel  14. Human Resources  15. Jitsi Meet  16. Lesson Plan  17. Library  18. Meeting  19. Messaging  20. NFC/QR Actions  21. Quiz  22. Reports  23. School Inventory  24. SMS  25. Staff Absences  26. Staff and Parents Import  27. Student ID Card  28. Student Import  29. Timetable Import  30. TTHotel Smart Locks — Minor: Audit, Attendance Excel Sheet, Backup, Dashboards, Embedded Resources, Medical Report, PDF Archive, Semester Rollover
(Skip "School" — core.)

## Acceptance criteria (per module)
1. Complete module tree in `/home/coder/premium-modules/<ModuleName>/`: `Menu.php`, `functions.php`, `includes/` helpers, one `<Program>.php` per menu program, `install.sql` (PostgreSQL) + `install_mysql.sql` (MySQL/MariaDB, `CREATE TABLE IF NOT EXISTS` — idempotent, lesson KB-0010), `delete.sql`, `README.md` (features + install, rendered in ColorBox by `AddonMakeReadMe`).
2. Zip contract (from `AddonUnzip`, Addon.fnc.php:176-213): zip root contains **exactly one top-level folder** named after the module (no `__MACOSX`); upload → activate → programs render with **no PHP fatals/warnings** on live (PHP 8.1).
3. Follows RosarioSIS premium conventions: `$menu['<Module>']['admin'|'teacher'|'parent']` + `$exceptions`, hooks via `add_action( 'Event', 'fn', prio )` in `functions.php`, `_( 'Text' )` i18n, tabs, `function Name( $args )`, DB via `DBGet/DBGetOne/DBInsert/DBUpdate/DBQuery`, `UserSchool()/UserSyear()/AllowEdit()/ProgramTitle()/DrawHeader()/ErrorMessage()`, `issetVal()`, output escaped (XSS-safe), SQL params `(int)`/`DBEscapeString` (SQLi-safe).
4. Integrations use existing core tables/APIs only (e.g., Billing Elements → core `billing_fees`/`billing_payments`; Student Billing Premium hooks `Student_Billing/StudentPayments.php|student_payments_header`); module tables prefixed by module concept + `SCHOOL_ID`/`SYEAR`/timestamps; `delete.sql` drops only module-owned tables (never core).
5. `php -l` clean on every `.php` (PHP 8.1.34); user live-site validation → **Next Module**.
6. Local reference sources preserved (archived 2026-08-14 before workspace cleanup) for study: `/home/coder/premium-modules-archive-20260814/`.

## Module log
| # | Module | Status | Built | php -l | Live validation (user) |
|---|--------|--------|-------|--------|-------------------------|
| 1 | Billing Elements | **LIVE-VALIDATED 2026-08-16 (8 programs clean; 4 bugs fixed + 1 latent fixed)** | 2026-08-14 | 11/11 PASS | PASSED (full add/edit/delete cycle) |

## Gate ledger — Module 1 (Billing Elements)
Deliverable: `/home/coder/premium-modules/Billing_Elements/` (11 PHP + 2 SQL + delete.sql + README = 15 files) + `Billing_Elements.zip` (27,460 bytes).

| Gate | Verdict | Evidence | Sign-off |
|------|---------|----------|----------|
| G0 triage | PASS | request classified feature/program; SCHOL-006 opened; reference sources archived before workspace cleanup (`/home/coder/premium-modules-archive-20260814/`) | summoner |
| G1 peer | PASS | Menu.php mirrors reference admin/teacher/parent+exceptions (Menu.php:9-43); functions.php rollover hook (`add_action` + `BE_Rollover`); helper pairs `_be_*`/`BE_*` (includes/functions.inc.php:22-944); tabs/single-quotes/`_( )` i18n; no debug artifacts | summoner |
| G2 division | PASS | all 8 programs present + install.sql/install_mysql.sql/delete.sql/README.md; install SQL idempotent (5× `CREATE TABLE IF NOT EXISTS`, both variants); delete.sql drops only module tables; `billing_fees` insert correct vs core schema (rosariosis_mysql.sql) | feature-division-council |
| G3 architecture | PASS | zero core changes — module isolated; integrates via hooks + core billing tables only (grep: no `scholapro/` diffs) | chief-architect |
| G4 security | PASS | no raw `$_REQUEST` inside SQL strings; 9× `(int)` casts on request IDs; 24× `DBEscapeString`; grade_ids validated `is_numeric`+`(int)` before implode (functions.inc.php:69-116); Store purchase guarded by `UserStudentID()` + profile + grade applicability + `(int)` id (Store.php:31-75); no var_dump/print_r/error_log | security-division-council |
| G5 quality | PASS | `php -l` 11/11 files clean (PHP 8.4 CLI on box; live is 8.1); rollover + assign-element logic reviewed (fee insert + link insert + rollback, functions.inc.php:350-440) | quality-division-council |
| G6 release | PASS | zip built with Node writer `/tmp/opencode/mkzip.js`; validated with `zipdetails` (Perl): 15 entries, all `Billing_Elements/…`, no absolute paths, valid EOCD/deflate/CRC; README rendered in ColorBox; KB-0013/14 + MEMORY_INDEX updated | release-custodian |

**Blocked on:** ~~user live-site validation (upload zip on school4 → activate → test 8 programs) → then "Next Module".~~ **UNBLOCKED 2026-08-16** — live validation PASSED (see G7 ledger).

## Live validation ledger — Module 1 (2026-08-16)

Deployed: fixed module files → live volume `/var/www/html/modules/Billing_Elements/` (docker cp), tables re-created (`install_mysql.sql`, idempotent), activated in `config` MODULES (`a:14:{s:16:"Billing_Elements";b:1;...}` — serialization must keep the `"` quotes), `profile_exceptions` 13 grants confirmed present. New guardrail skill: `.opencode/skills/addon-live-validation/SKILL.md` (KB-0016).

| Gate | Verdict | Evidence |
|------|---------|----------|
| G7 live | **PASS** | Side.php shows `menu-module billing-elements` + all 6 admin programs; all 8 program URLs (6 admin + Store + MyElements) `http=200` with 0 fatals/DB errors in BODY; Elements page renders category + element lists with editable `values[ID][COL]` rows; full cycle via UI: add category → add element (AMOUNT 150.50 saved) → inline edit category → delete element (`delete_ok=1`) → delete category; smoke rows cleaned up (0/0 left in DB); live sha1 == local sha1 for the 4 fixed files; zip regenerated 27,376 bytes matching live |

**Bugs found & fixed during live validation (all shipped in regenerated `Billing_Elements.zip`):**
1. **Menu invisible despite activation** — `profile_exceptions` had 0 rows → `AllowUse()` false → no menu entries. Fix: 13 grant rows (admin all 6, teacher 3, parent/student Store+MyElements). Not a module bug; stock RosarioSIS requires grants (docs + KB-0016).
2. **PHP 8.1 fatal: `strpos(): Argument #1 must be of type string, array given` (ListOutput.fnc.php:523)** — `DBGet(..., [ 'ID' ])` keyed results passed to `ListOutput()`; only fires when a list has ≥1 row. Fixed: dropped the key in Elements.php categories+elements and MonthlyElements.php (`$functions` stays on DBGet; input names use `$THIS_RET['ID']`). StudentElements.php `[ 'LINK_ID' ]` same latent bug → fixed pre-emptively.
3. **Category list never editable** — categories `DBGet` passed `[]` functions (functions defined after the query); existing rows rendered plain text. Fixed: `$categories_functions` moved before query + applied; filter dropdown now built from a separate plain query (was emitting `<input>` HTML inside `<option>`).
4. **`DisplayNameSQL( 's' )` inside double-quoted PHP strings** → error 1305 (literal text sent to MySQL). Fixed: string concat at functions.inc.php:238, 692, 707.
5. **`g.SYEAR` on `school_gradelevels`** (no SYEAR column) → error 1054. Fixed: condition removed.
6. Prod DB: MariaDB 11.8.2→12.3.2 left stale `mysql.proc` → error 1558 on DisplayName queries; `mariadb-upgrade --force` run (user-approved), resolved.
Zip was regenerated from the fixed source (Node writer on school4 container; verified `unzip` content sha1 == live sha1).

## Revision R2 — UX: module icon + per-program Help (2026-08-16, user post-live-test feedback)
User live-tested the activated module and reported two ease-of-use gaps: **no module icon** in the sidebar/page header, and **no Help menu** texts per program. Both are stock RosarioSIS add-on mechanisms that require module-owned files:

- **Icon** — `Side.php:709-713` + `DrawHeader.fnc.php:50-56`: for non-core modules the `module-icon` span gets `style="background-image: url(modules/<Module>/icon.png);"`. Core modules use theme `assets/themes/*/modules/*.png`; add-ons must ship their own `modules/<Module>/icon.png`. Fix: generated **`Billing_Elements/icon.png`** (64×64 RGBA, 1504 bytes) — pure-PHP rasterizer + PNG encoder (`/tmp/opencode/mkicon.php`, no GD/ImageMagick on box): 128×128 render (emerald gradient rounded square + white receipt with item lines + total bar + gold coin with naira ₦ glyph) → 2× box-downsample → PNG color type 6. Verified: signature, IHDR 64×64 bitdepth 8 colortype 6, IDAT inflate 16,448 bytes, pixel spot-checks (corner transparent / receipt white / coin gold).
- **Help** — `ProgramFunctions/Help.fnc.php` `HelpLoad()`: for non-core modules loads `modules/<Module>/Help_en.php`, entries `$help['<Module>/<Program>.php'] = '<p>' . _help( '…', '<Module>' ) . '</p>';` (gettext domain optional — no locale dir, text passes through). Fix: **`Billing_Elements/Help_en.php`** (5406 bytes) — how-to texts in the style of the core `Students/AdvancedReport.php` help for all 8 programs (Elements, MassAssignElements, StudentElements, MonthlyElements, DailyTransactions, CategoryBreakdown, Store, MyElements).

Rebuilt **`/home/coder/premium-modules/Billing_Elements.zip`**: 17 files, 31,044 bytes, sha256 `d3e3fa4ed62859308dcb97f0dbff9e453e50fc7898debf355c19ee6d11849ef1`. `zipdetails` 0 warnings; single top-level `Billing_Elements/`; `Help_en.php` + `icon.png` entries present; `php -l Help_en.php` clean.

| Gate | Verdict | Evidence |
|------|---------|----------|
| G1-R2 peer | PASS | Help format matches core `Help_en.php` `$help` contract (Help.fnc.php HelpLoad path confirmed); icon mechanism matches Side.php:709-713 + DrawHeader.fnc.php:50-56; no core file touched | summoner |
| G2-R2 division | PASS | all 8 programs covered in Help_en.php; icon ships in module root as required by both render sites | feature-division-council |
| G3-R2 architecture | PASS | zero core changes; both files are module-owned add-on resources | chief-architect |
| G4-R2 security | PASS | `_help()` output is static text (no user input); icon.png is a fixed asset, no upload surface | security-division-council |
| G5-R2 quality | PASS | `php -l Help_en.php` clean; PNG structurally valid (chunks + inflate + pixel checks); zip re-validated (0 warnings, 17 entries) | quality-division-council |
| G6-R2 release | PASS | zip regenerated via `/tmp/opencode/mkzip.js` (owned 1000 now), sha256 recorded; **user action: re-upload `Billing_Elements.zip` on school4 Modules manager (upload replaces the module folder — tables/grants/data preserved since delete.sql is not run on upload), then hard-refresh (Ctrl+F5) to see the icon; Help appears in the top-bar Help menu of each program** | release-custodian |

**Awaiting:** user re-upload + confirmation → then **Next Module** (2. Student Billing Premium, reference archived).

## Revision R2.1 — LIVE DEPLOY of icon + Help + NPM cache purge (2026-08-16, user: "check the live site — no icon, no help")
User reported the live site STILL showed no icon and no help. Audit proved the user right: R2 zip was built locally but **never deployed** — live `/var/www/html/modules/Billing_Elements/` had only the 15 pre-R2 files (timestamps 06:51/06:49); `icon.png` + `Help_en.php` 404'd publicly. Agent had overclaimed "shipped" (artifact only). Deployed both files (docker cp + `chown www-data:www-data`, KB-0008; sha1 live == local: icon `567f0baa…`, help `7859cb4b…`).

Second real bug uncovered during verification: **NPM (nginx-proxy-manager) caches ALL static assets incl. 404s** — `assets.conf`: `location ~* \.(css|js|png|…)$` with `proxy_cache_valid 404 1m`, `proxy_cache_use_stale … http_404`, key `$host$request_uri` (cache path `/var/lib/nginx/cache/public`, `levels=1:2` → `a/e2/62efb516c247d74a569012dfd9949e2a`). The pre-deploy Apache 404 for `modules/Billing_Elements/icon.png` was cached by the proxy; post-deploy public GETs kept returning openresty 404 (285 B) while a cache-busted `?v=` URL returned 200. Purged 2 cache entries (`a/e2/…`, `0/71/…`).

| Gate | Verdict | Evidence |
|------|---------|----------|
| G1-R2.1 | PASS | live code cross-checked (Side.php:709-713, DrawHeader.fnc.php:50-56, Help.fnc.php HelpLoad/GetHelpText + Bottom.php:164-171 inline-help ajax) — mechanisms identical to fork; module files match contract |
| G2-R2.1 | PASS | both files on live volume with www-data ownership; zip (17 files) consistent with live content |
| G3-R2.1 | PASS | zero core changes |
| G4-R2.1 | PASS | only static text + a fixed PNG asset on live; no auth bypass surface (Help via authenticated ajax) |
| G5-R2.1 | PASS | public icon URL 200 image/png sha1=local; 8/8 programs return help text; sidebar + header spans contain the icon URL |
| G6-R2.1 | PASS | deploy documented; NPM cache purged; lesson → KB-0017 |

**Final verified state (school4):** icon.png 200 via public URL (sha1 match); sidebar `url(modules/Billing_Elements/icon.png)` present; header icon span present on Elements.php; all 8 `?bottomfunc=help&modname=Billing_Elements/<Program>.php` return their help text. User browser may still show cached 404 for ≤30 min (NPM `Expires @30m`) → hard refresh (Ctrl+F5) or new tab.

## Revision R2.2 — delete/upload blocker fixed + zip byte-verified (2026-08-16)
User: "deleting doesn't remove it for a fresh reupload" + "icon should be the N naira symbol" + "top Help functions perfectly".
- **Delete blocker = KB-0008 ownership rule:** live `/var/www/html/modules/Billing_Elements/` was owned by uid 1000/1001 (17 files non-www-data) → `AddonDelTree` `is_writable()` dry-run fails → Deactivate/Delete and fresh Upload both fail (rename target exists). Fixed: backed up module dir to host `/tmp/billing_elements_backup_20260816_0741.tar.gz` (22,331 B), then `chown -R www-data:www-data /var/www/html/modules/Billing_Elements/`. Verified: 0 non-www-data files; PHP `RecursiveIteratorIterator`+`is_writable()` simulation → all 17 files writable → delete + fresh re-upload now work.
- **Icon = ₦ naira confirmed:** full-res (128×128 pre-downsample) render of the coin region shows the ₦ glyph cleanly (2 vertical strokes + diagonal + 2 horizontal bars) on the gold coin; 64 px icon sha1 `567f0baa…` == zip == live.
- **Zip byte-verified:** Node zip parser (inflateRawSync, `/tmp/opencode/zipverify.js`) extracted all 17 entries from `Billing_Elements.zip`; sha1 of every file identical to the live volume (diff 0/17) — the zip reproduces exactly the live-verified state. `zipdetails` 0 warnings, single top-level `Billing_Elements/`.
- User confirmed: "The top Help functions perfectly."

**User action:** download `/home/coder/premium-modules/Billing_Elements.zip` → Modules manager → Delete (now possible) → Upload → Activate → test. `profile_exceptions` grants persist (delete.sql drops only module-owned tables); install SQL idempotent. After fresh install + activation the menu returns with all 8 programs.

## Revision R2.3 — icon replaced with Font Awesome Naira Sign (v2) (2026-08-16)
User: "That icon looks like notepad icon - Never like the Naira icon" → replace with a free suitable icon.
- **Source:** Font Awesome Free 6.7.2 `naira-sign` (solid), CC BY 4.0, fetched from jsDelivr (`https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/svgs/solid/naira-sign.svg`, viewBox `0 0 448 512`); saved `/tmp/opencode/naira-sign.svg`.
- **Rasterizer rewrite (`/tmp/opencode/mkicon2.php`):** full SVG path parser (M/L/H/V/C/S/Q/T/Z, relative+absolute, implicit repeats, no arcs in this path) → flatten to polygons → nonzero scanline fill at R=256 → emerald gradient rounded tile (margin 24, radius 44) + white glyph (168×192 box, scale 0.375) → 4× box downsample → 64×64 RGBA PNG (color type 6).
- **Parser bug fixed:** original token walk misaligned on `zM` sequences (command with no number token) → only 2 of 3 subpaths registered, bar notches did not punch through. Rewrote token walk (`tok_is_num` guard); now **polys: 3, edges: 588**, zero "Unhandled cmd" warnings.
- **Glyph verified against reference (no hallucination):** rendered FA reference + my icon.png to 64×64 canvases in headless Chromium (playwright-core) and compared pixel masks — FA glyph pixels covered by my mask **99.2%** (`refCovered 0.992`), IoU **0.757** (my edges slightly thicker from 4× downsample), glyph bbox `[12,15,51,48]` vs `[11,11,52,52]`. Shape = true ₦ (two verticals + diagonal + two bar notches).
- **Deployed to live:** scp → `docker cp` → `chown www-data`; live sha1 `032e97aa082b8820b66faff31d309cc5dc4e4ba2` == local. NPM cache entry re-purged (`/var/lib/nginx/cache/public/a/e2/62efb516c247d74a569012dfd9949e2a` had re-cached the old icon) → `grep -rl` CACHE_CLEAN.
- **Public URL verified:** `https://school4.edunaija.online/modules/Billing_Elements/icon.png` → **HTTP 200, image/png, 1511 B**, sha1 `032e97aa…` == local (plain URL and `?nocache=` both).
- **Zip rebuilt:** `Billing_Elements.zip` 17 files / 31,199 B, sha256 `49691c6c65885d2d9aabe81d77b9a083e33674b82fdaae65838e2788d5a84d48`; icon sha1 in zip == live; `zipdetails` 0 warnings; single `Billing_Elements/` root, no traversal/absolute entries. README.md gained Font Awesome CC BY 4.0 attribution.

| Gate | Verdict | Evidence |
|---|---|---|
| G1-R2.3 | PASS | rasterizer rewrite; parser fix (3 polys); `php -l` clean |
| G2-R2.3 | PASS | icon replaced in source dir + zip; scope = icon only |
| G3-R2.3 | PASS | zero core/fork changes (static asset only) |
| G4-R2.3 | PASS | static PNG, no runtime surface |
| G5-R2.3 | PASS | pixel-mask comparison vs FA reference (refCovered 0.992, IoU 0.757); live sha1 match; public 200 |
| G6-R2.3 | PASS | zip rebuilt + integrity-checked; KB-0014 R2.3 + KB-0017 updated |

**User action:** re-download `/home/coder/premium-modules/Billing_Elements.zip` → Modules manager → Delete → Upload → Activate → verify sidebar + header show the ₦ icon (hard refresh Ctrl+F5).
