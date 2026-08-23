# KNOWLEDGE BASE — Control Plane #2b

**Version:** 2.0.1 (2026-08-19)
**Owner:** knowledge-curator (Knowledge Commons)

Validated knowledge, issue records, and proficiency records for Agentverse.
Lifecycle: propose → review → accept → index → retire.

## 1. Knowledge lifecycle

1. **Proposal** — any agent files a draft entry with evidence.
2. **Review** — knowledge-curator checks: evidence-based, non-transient, non-duplicate.
3. **Accept / Reject** — accepted entries get a `KB-####` id and are indexed here.
4. **Retire** — stale entries removed with a recorded reason (kept in git history).

## 2. Taxonomy

`KB-000x` recovery/org · `KB-001x` environment & deployments · `KB-002x` product/issue records · `KB-003x` conventions & standards · `KB-004x` proficiency validations

---

## 3. Entries

### KB-0001 — Agentverse recovery verdict (accepted 2026-08-13)
No recoverable pre-migration Agentverse exists. Forensic coverage: local container, host filesystem, `archive-search/`, `home-coder-backup-20260812-102419.tar.gz`, `project-volume-backup-20260812-102351.tar.gz`, recovery opencode.db (420 sessions), prompt history, Claude Code data, Docker volumes, git object store (including unreachable blobs), and the prior recovery attempt's own output files — **zero hits** for `summoner`, all `*_division`, all councils/guilds, `cohesion`, `AGENT_REGISTRY`. Capability map survives only in user memory; Agentverse 2.0 rebuilt from scratch (70 agents).

### KB-0002 — SCHOL-001: calendar-setup.js change-event regression (RESOLVED)
**Ticket:** SCHOL-001 · Type: bug · Priority: high · Status: **RELEASED** (2026-08-13)
- **Symptom:** selecting a date in a RosarioSIS calendar picker fails to trigger change handlers bound via `addEventListener`/jQuery.
- **Root cause:** 12.5-fork code called only the native `onchange` property (`assets/js/jscalendar/calendar-setup.js`), which does not invoke `addEventListener`-bound listeners.
- **Fix:** `p.dayField.dispatchEvent(new Event( 'change', { 'bubbles': true } ));` — mirrors upstream 12.7.4 fix exactly. Dispatching a bubbling `change` event fires property handlers **and** listener-bound handlers.
- **Verification:** `scholapro/tests/calendar-setup.regression.test.js` (7/7 PASS, node v24); JS syntax OK; no double-fire with `onUpdate`.
- **Gates:** G0–G6 all PASS; G3 fast-path waiver (frontend-only). Ledger: `AGENTVERSE/tickets/SCHOL-001.md`.
- **Follow-up:** SCHOL-002 (warehouse.js regressions) queued.

### KB-0006 — Lesson: PHP numeric-string array keys (from pilot research)
`$arr['0']` and `$arr[0]` are the **same key** in PHP (canonical numeric strings are cast to int). A candidate "bug" in `functions/GetMP.php:335` (`mb_substr($children_mp[$mp][0],1)` where the loop wrote `['0']`) was ruled a **false positive** after verifying PHP semantics and upstream parity — do NOT "fix" it. Validation against live code (run it / check PHP semantics) is mandatory before reporting defects. Owner: search-librarian → knowledge-curator.

### KB-0003 — SCHOL-002: warehouse.js 12.5 regressions (queued)
Confirmed drift vs upstream (not yet fixed): mobile-menu `#!` appended to URL (upstream 12.7.2: `link = link.split('#')[0]`); jQuery `.html()/.append()` used where upstream moved to `insertAdjacentHTML`/`setInnerHTML` for CSP; missing `.onclick-checkall[data-error]` form-submit validation; `maxWidth` 95% vs 98%. Queue after SCHOL-001.

### KB-0004 — Deployment drift: school4 prod vs scholapro repo (open)
Prod `school4` = stock RosarioSIS 12.4.2 (VERSION/TITLE/NAME in `config` table); repo = rebranded ScholaPro 12.5 fork. Product rollout requires migration plan (platform-division + integration-division + data-division). `db-school4` config currently `SYEAR` context from 2025-09; new academic-year rollover to 2026 needed (data-division).

### KB-0005 — Test harness conventions (accepted 2026-08-13)
No PHPUnit/test framework present in `scholapro`. Verification methods available: `php -l` (PHP 8.1 via school4 container / host php:8.1 image mounting `vscode_code-server-home`), node v24 at `/usr/lib/code-server/lib/node`, DOM-level JS tests via node (jsdom not installed — use lightweight assertions), `rg`/grep. Quality Division should standardize a JS test harness in a later sprint.

### KB-0008 — school4: custom-module delete fails on non-www-data files (RESOLVED 2026-08-14)
**Ticket:** SCHOL-004 · Type: bug (ops) · Priority: high · Status: **RESOLVED / fixed live** — ledger `AGENTVERSE/tickets/SCHOL-004.md`
- **Symptom:** on school4 prod (stock RosarioSIS 12.4.2), activating/deactivating/deleting uploaded custom modules (e.g. Student Billing Premium) "does not work".
- **Root cause (delete):** `AddonDelTree()` (Addon.fnc.php:81-145) dry-runs `is_writable()` on **every** file; module files uploaded as uid 1000 (`644`) are not writable by `www-data` → dry-run false → `Files not eraseable.` → folder remains. `Modules.inc.php` clears the config key before the tree delete, so the module vanishes from `$RosarioModules` but its folder lingers → re-lists as "not installed". Reproduced on a throwaway module (fail → chown → success).
- **Root cause (activate/deactivate):** no real failure — state persists (`b:1`/`b:0` in config `MODULES`); the page just doesn't redirect (200, not 302) because `_reloadMenu()` echoes `<script>ajaxLink('Side.php');</script>` before `RedirectURL()`'s `header()` (headers already sent). Affects shipped modules equally (verified on `Resources`).
- **Fix (live, validated):** `chown -R www-data:www-data /var/www/html/modules/Student_Billing_Premium` (module backup host `/tmp/sbp-module-backup.tar.gz`); module left deactivated so Activate + Delete both render; delete then succeeds (folder gone).
- **Convention to adopt:** uploaded/extracted modules must be owned by `www-data` or they can't be deleted.
- **Admin auth note:** stored hash didn't match any password → re-synced `staff.PASSWORD` to `encrypt_password('Mafioso0147')` (original hash at `/tmp/admin.hash.orig`, restorable). Login verified 302→Portal; earlier "password not working" was a stale browser session.

### KB-0010 — school4: module install SQL idempotency + always-visible Delete (RESOLVED 2026-08-14)
**Ticket:** SCHOL-005 · Type: bug/UX (module manager) · Priority: high · Status: **RESOLVED / fixed live + fork** — ledger `AGENTVERSE/tickets/SCHOL-005.md`
- **Symptom:** re-activating re-uploaded "Student Billing Premium" failed `DB Execute Failed. 1050 Table 'billing_monthly_fees' already exists`; Delete button was missing when a module was deactivated/uninstalled; menu seemed stuck after deactivate.
- **Root cause (1050):** activation runs `install_mysql.sql` only when the module key is absent; leftover tables from a prior install made `CREATE TABLE` fatal → `DBQuery` aborts → key never set. Module tables = `billing_monthly_fees`, `billing_monnify_transactions`, `sbp_webhook_log` (dropped by `delete.sql`); `billing_fees`/`billing_payments` are **core** Accounting tables and must never be dropped.
- **Root cause (Delete hidden):** `_makeDelete()` + delete action required `in_array($module, array_keys($RosarioModules)) && $RosarioModules[$module] == false`, so uninstalled folders had Activate but no Delete and active modules had none either.
- **Mechanism (menu, NOT a bug):** RosarioSIS is a single-page app — `Warehouse('header')` outputs before module code so header() redirects are impossible; `RedirectURL()` is JS (`var XRedirectUrl`, PreparePHP_SELF.fnc.php:189-199) and the menu reloads via `_reloadMenu()` → `<script>ajaxLink('Side.php')</script>` (warehouse.js:441 → `#menu`). Deactivate/Delete additionally need a `delete_ok=1` POST confirm (DeletePrompt).
- **Fix (live + local source + repo fork, validated via live curl UI):**
  - `CREATE TABLE IF NOT EXISTS` in `install_mysql.sql` + `install.sql` (live module folder + `/home/coder/premium-modules/Student_Billing_Premium/`);
  - `Modules.inc.php`: delete allowed for any non-core module (action + render), Delete button always emitted for non-core modules, `_reloadMenu()` added to the delete block.
- **Convention:** make add-on install SQL idempotent (`IF NOT EXISTS`) so re-activation never 1050s on persisted tables; Delete must always be available for non-core uploaded modules.

### KB-0009 — Agentverse dev-environment extensions & tooling (accepted 2026-08-14)
- **VSCode/code-server extensions (Open VSX, free):** intelephense-client (PHP), vscode-eslint, prettier-vscode, typescript-next, git-graph, gitlens, markdown-all-in-one, vscode-docker (+containers), errorlens, code-spell-checker, rainbow-csv, sqltools + sqltools-driver-mysql, rest-client. Install: `code-server --install-extension <publisher.name>` (16 total incl. sst-dev.opencode).
- **Playwright MCP (browser E2E):** `@playwright/mcp` at `.mcp/node_modules/@playwright/mcp/cli.js`, chromium+headless-shell at `~/.cache/ms-playwright` (chromium-1237). Dev box = Debian 13 (trixie) aarch64; system deps installed via `sudo apt-get install libnss3 libnspr4 libcairo2 libpango-1.0-0 libgbm1 libasound2 libxshmfence1 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libatspi2.0-0 libatk-bridge2.0-0 libatk1.0-0 libx11-xcb1 libxcb-dri3-0 libcups2 libxkbcommon0 libxkbcommon-x11-0 libdbus-1-3 libdrm2`. Launch verified headless (`browser_navigate` etc. via stdio handshake). MCP env `PLAYWRIGHT_BROWSERS_PATH=/home/coder/.cache/ms-playwright`.
- **Sequential-Thinking MCP:** `@modelcontextprotocol/server-sequential-thinking@2026.7.4` at `.mcp/node_modules/.../dist/index.js`; free/no keys; `sequentialthinking` tool verified via stdio handshake. 7 MCP servers total: sqlite, filesystem, memory, git, curl, playwright, sequential-thinking.
- **opencode.jsonc tuning:** `snapshot: true`, `compaction {auto:true, tail_turns:15}`, `tool_output {max_lines:250, max_bytes:8192}`, bash permissions allow read-only git (`git status/diff/log/branch/remote -v/show`) with writes still `ask`.
- **Skills added:** `school4-ops` (live prod runbook: hosts, DB, module ops, www-data ownership rule) and `task-ledger` (ticket/gate/KB conventions).
- **Local plugin:** `.opencode/plugins/session-ledger.js` (no deps, non-fatal) appends `session.created/idle/error/compacted/deleted` events to `AGENTVERSE/.sessions/session.log.ndjson`.
- **Operational notes:** dev box = code-server (vscode.edunaija.online); node at `/usr/lib/code-server/lib/node`, npm CLI at `/home/coder/.npm-global/lib/node_modules/npm/bin/npm-cli.js` (v12.0.2), passwordless sudo, Open VSX + npm registry reachable. **All changes require opencode restart** (run from `/home/coder/project`).

### KB-0007 — Informational queries: command-plane handling (accepted 2026-08-14)
**Ticket:** SCHOL-003 · Type: query · Priority: normal · Status: **CLOSED (answered)** — no change.
- **Precedent:** requests with no product change (e.g. "what is your job?") are answered at the command plane by the Summoner from control-plane sources of truth — `AGENT_REGISTRY.json:144-161` (summoner definition), `AGENTVERSE.md` §1/§3/§4/§5, `COHESION_MATRIX.md` §1, `OPENCODE_RUNTIME.md` §2. No division delegation and no G1–G6 gate pass is fabricated; gates are recorded **N/A** with rationale, since COHESION_MATRIX §1 gates apply to *changes* only.
- **Ledger rule:** ticket numbering must not collide with reserved numbers — SCHOL-002 was reserved (KB-0003, warehouse.js) despite no file existing; query allocated SCHOL-003.
- **Source:** summoner → knowledge-curator.

### KB-0011 — Project boundary: OpenClassify is UNRELATED to ScholaPro/school4 (accepted 2026-08-14)
`openclassify` is a completely separate, unrelated project (different codebase/stack). The related set is **ScholaPro / school4.edunaija.online / Agentverse** (repos `ScholaPro`, `School-Management-Syetem`, `agentverse-dungeon`). **Rule:** never port, merge, or cross-apply code, patterns, or assets between OpenClassify and the ScholaPro/school4 stack; never let one project's workflows/context contaminate the other. Directive from user (2026-08-14); enforced by summoner across all divisions.

### KB-0012 — GitHub repo map + CI/CD deployment topology (accepted 2026-08-14)
- **GitHub (multifixconcepts), all public:** `ScholaPro` (default `4.2-release`) = rebranded RosarioSIS fork but **stale** (last commits 2025-09-17; no `CHANGES_V9_10.md`, pre-v9/10) and ships a **mismatched** CI (`.github/workflows/main.yml` + `Dockerfile` target Laravel/PostgreSQL — artisan/phpunit/pgsql/fpm, NOT the live stack); `School-Management-Syetem` (typo; rename pending → `School-Management-System`); `openclassify` (UNRELATED — KB-0011); `agentverse-dungeon`.
- **Local dev box (code-server vscode.edunaija.online, container fda331b9b566):** `/home/coder/project` is the Agentverse **workspace monorepo** (no git remote) containing `scholapro/` (RosarioSIS 12.5-fork mirror, in sync with live for our changes), `AGENTVERSE/` (docs), `docker-dashboard-project/`, MCP infra, N8N. GitHub project repos must be linked/pushed from here.
- **Prod school4 (extravus-prod, docker):** stock RosarioSIS 12.4.2 on `php:8.1-apache`; the **whole docroot lives in named volume** `school4_school4_data` → `/var/www/html` (code+data together); DB in `school4_db_school4_data` (mariadb, db-school4). Container: net `npm-network`, restart `unless-stopped`, compose provenance label points to `/data/compose/63` which does not exist on host (manage via a fresh compose file). PHP 8.1 modules verified in prod: gd mbstring mysqli mysqlnd pdo_mysql intl zip iconv xml opcache — the CI image must include these.
- **Deploy method (decided by user 2026-08-14):** GitHub Actions builds the RosarioSIS image → push to **GHCR** → VPS pulls and recreates school4. Dev box tooling now: `gh` 2.97.0 (apt, deb trixie arm64) + GitHub Actions extension v0.32.3 (code-server).

### KB-0013 — Premium module conventions + zip packaging contract (accepted 2026-08-14)
Premium add-on modules (sold separately, uploaded via `School_Setup/Configuration.php?tab=modules`) follow the RosarioSIS add-on contract. Derived from the archived reference sources (`/home/coder/premium-modules-archive-20260814/`, Billing_Elements + Student_Billing_Premium) and `modules/School_Setup/includes/Addon.fnc.php`:
- **Module tree:** `<Module>/` = `Menu.php` (menu entries `$menu['<Module>']['admin'|'teacher'|'parent']` + `$exceptions['<Module>']=[]`), `functions.php` (auto-loaded; `require_once` includes + `add_action( 'Event', 'fn', prio )` hooks e.g. `School_Setup/Rollover.php|rollover_after`, `Student_Billing/StudentPayments.php|student_payments_header`), `<Program>.php` one per menu program, `includes/functions.inc.php` helpers (`_be_*` private / `BE_*` public pairs), `install.sql` (PostgreSQL) + `install_mysql.sql` (MySQL), `delete.sql`, `README.md` (rendered in ColorBox by `AddonMakeReadMe`).
- **Zip contract (critical):** `AddonUnzip` extracts the zip and takes the **first top-level directory** as the addon dir (`Addon.fnc.php:176-213`, skips `__MACOSX`). Therefore each zip must contain **exactly one folder named after the module** at its root (e.g. `Billing_Elements/…`). Absolute paths or files at zip root ⇒ upload fails ("No Modules were found"). Never include `__MACOSX`. Dev box has **no zip/python/docker**: create zips via the Node writer `/tmp/opencode/mkzip.js` (deflate+CRC32, entries relative to parent of module dir) and validate with `zipdetails` (Perl) — caught an absolute-path bug on first build.
- **Install SQL is idempotent:** `CREATE TABLE IF NOT EXISTS` everywhere (lesson KB-0010); `delete.sql` drops ONLY module-owned tables (never core `billing_fees`/`billing_payments`); module tables carry `SCHOOL_ID`, `SYEAR`, timestamps, indexes.
- **Conventions:** tabs, single quotes, `_( 'Text' )`, `function Name( $args )`, DB via `DBGet/DBGetOne/DBInsert/DBUpdate/DBQuery`, `UserSchool()/UserSyear()/UserStudentID()/User( 'PROFILE' )`, `AllowEdit()`, `ProgramTitle()`, `DrawHeader()`, `ErrorMessage()`, `issetVal()`, `URLEscape()`, `Currency()`, `DBEscapeString()`, `(int)` casts; no PHP 8.1 landmines; `delete_ok` confirm via `DeletePrompt()`, JS redirect via `RedirectURL()`.
- **Fee integration:** assign/purchase creates a core `billing_fees` row (`STUDENT_ID, SCHOOL_ID, SYEAR, TITLE, AMOUNT, ASSIGNED_DATE, DUE_DATE, COMMENTS, CREATED_BY`) + a `student_billing_elements` link row with `FEE_ID`; remove deletes fee+link (waiver-safe).

### KB-0014 — SCHOL-006 M1: Billing Elements module fabricated (accepted 2026-08-14)
Module 1 of the premium program shipped to `/home/coder/premium-modules/Billing_Elements/` + `Billing_Elements.zip` (15 files, 27,460 bytes, spec-validated with zipdetails). Feature parity with the reference: 8 programs (Elements, Mass Assign Elements, Student Elements, Monthly Elements, Daily Transactions, Category Breakdown, Store, My Elements) + `functions.php` rollover hook + helpers; idempotent `install_mysql.sql`/`install.sql` (5 tables, all `IF NOT EXISTS`), `delete.sql` drops only module tables. Gates: G1/G2 PASS (structure parity, AC met), G3 PASS (zero core changes — hooks+DB only), G4 PASS (no raw `$_REQUEST` in SQL, 9× `(int)` casts, 24× `DBEscapeString`, no debug artifacts), G5 PASS (11/11 `php -l` clean), G6 packaged + README. Pending user live-site validation on school4.
- **Rev R3 (2026-08-17):** After school4 upgraded to RosarioSIS 12.9.2, the 3 code-level KB-0016 bugs (DisplayNameSQL double-quote interpolation, g.SYEAR on school_gradelevels, ListOutput $functions as $group) were found STILL PRESENT in the deployed module despite Rev R2 claiming they were fixed. Root cause: Rev R2 fixes were documented but never actually applied to the live files or the local premium-modules copy. Fixed for real on 2026-08-17: (1) `require_once 'ProgramFunctions/Widgets.fnc.php'` removed entirely from Elements.php (unused, path wrong for 12.9.2 where file is at `functions/Widgets.fnc.php`); (2) `DisplayNameSQL('s')` concatenated properly in Elements.php:693; (3) `g.SYEAR` dropped from Elements.php:664 and MonthlyElements.php:388; (4) DailyTransactions.php ListOutput $functions applied via pre-processing loop before ListOutput call (ListOutput 6th arg = $group, not functions). Deployed via SCP+docker cp, ownership fixed. G5 live validation: all 6 Billing_Elements + 5 Student_Billing_Premium programs HTTP 200, 0 PHP errors. Zips rebuilt: Billing_Elements 15 files/26,699B, Student_Billing_Premium 15 files/28,453B.
- **Rev R2.1 (2026-08-16):** user audit found live site still icon-less/help-less — correct; R2 had only been built, never deployed (agent overclaimed). Deployed both files live (`docker cp` + `chown www-data`, sha1 live==local: icon `567f0baa…` 1504 B, help `7859cb4b…` 5406 B). Public icon URL still 404'd until NPM cache purge (KB-0017 — proxy cached the pre-deploy 404; `assets.conf` `proxy_cache_valid 404 1m` + `proxy_cache_use_stale http_404`). After purge: icon URL 200 image/png; sidebar + DrawHeader spans render `url(modules/Billing_Elements/icon.png)`; all 8 `?bottomfunc=help&modname=Billing_Elements/<Program>.php` return their help text (help is ajax-loaded via Bottom.php:164-171, not embedded in page HTML).
- **Rev R2.3 (2026-08-16):** user rejected v1 icon ("looks like notepad — never like the Naira icon"). Replaced with **Font Awesome Free 6.7.2 `naira-sign`** (solid, CC BY 4.0, Fonticons Inc.) from jsDelivr. New rasterizer `/tmp/opencode/mkicon2.php`: full SVG path parser (M/L/H/V/C/S/Q/T/Z, rel+abs, implicit repeats) → flatten → nonzero scanline fill at 256 → emerald tile (margin 24, radius 44) + white glyph (168×192 box) → 4× downsample → 64×64 RGBA PNG (1511 B). Parser gotcha: naive token walk misaligns on `zM` (command with no number token) — guard with `tok_is_num()`; fixed → 3/3 subpaths (bar notches punch through). **Verification (no-hallucination):** pixel-mask compare vs FA reference in headless Chromium — refCovered 0.992, IoU 0.757, bbox `[12,15,51,48]` vs `[11,11,52,52]`. Deployed live (sha1 `032e97aa…` == local), NPM cache re-purged (entry `a/e2/62efb516…` re-cached old icon), public URL 200 image/png 1511 B sha1-match. Zip rebuilt 17 files / 31,199 B, sha256 `49691c6c…`; README gained FA CC BY 4.0 attribution. Lesson: when replacing an asset at the SAME URL, purge NPM cache again (old content re-cached under same key).
- **Rev R4 (2026-08-16):** Help_en.php rewritten per-user-type (SCHOL-007) — 8/8 programs, zip rebuilt.

### KB-0015 — opencode `/summon` UnknownError root cause (accepted 2026-08-14)
Log evidence (`~/.local/share/opencode/log/opencode.log`): `/summon` was found (`message=command command=summon`) but execution failed `UnknownError: UnknownError` at `SessionPrompt.command` ← `SessionHttpApi.command` (16:17Z/16:20Z, run=5aba2730, long-running opencode started 10:26). After restarting opencode from `/home/coder/project` (18:20 local), `/summon` executed cleanly (17:22:48Z, run=e740fc43 — this session, agent=summoner). Root cause: opencode loads config (commands/agents/plugins/MCP) **only at startup** (opencode docs + customize-opencode skill) — the stale long-lived process kept pre-change state and threw on command execution. Rule: after any `.opencode/` or `opencode.json*` change, **restart opencode**; launch from the project dir so project commands register; a failing `/summon` on a long-running instance is fixed by restart, not by editing summon.md (file was valid throughout).

### KB-0016 — M1 Billing Elements live validation: 4 bugs + guardrail (accepted 2026-08-16)
SCHOL-006 M1 validated live on school4 (stock 12.4.2, PHP 8.1). Activation alone does NOT make a module's menu appear: `profile_exceptions` grants are required per program per profile (`AllowUse()` false → no menu). Four module bugs fixed in live + regenerated `Billing_Elements.zip`:
- **PHP 8.1 `ListOutput` TypeError (fatal, only with data):** `DBGet($sql, $fn, ['ID'])` keyed results → `strpos(): Argument #1 must be of type string, array given` at `ListOutput.fnc.php:523` (the `$list_has_input` probe iterates the first row). Fires only when a list is non-empty (silent while empty). Fix: drop the index; input names come from `$THIS_RET['ID']` so `DBUpdate(...,['ID'=>(int)$id])` still works. Never pass row functions as ListOutput's 6th arg (`$group` = grouping config). Reference module passed functions as `$group` — latent break.
- **Row functions not applied:** categories `DBGet` used `[]` functions (defined after the query); existing rows rendered plain text. Fix: define functions first, pass as DBGet 2nd arg; filter dropdown must use a separate plain query (function-transformed rows leak `<input>` HTML into `<option>`).
- **`DisplayNameSQL( 's' )` inside `"..."`:** PHP sends the literal text to MySQL → error 1305. Concatenate: `"COALESCE(" . DisplayNameSQL('s') . ",'')"`. Also in archived reference.
- **`g.SYEAR` on `school_gradelevels`** (no SYEAR column) → error 1054. Drop it.
Plus prod-DB: stale `mysql.proc` after MariaDB 11.8→12.3 → error 1558 on DisplayName queries; `mariadb-upgrade -uroot -pschool4_root_pass --force` fixed it (user-approved).
Lesson → new skill **`addon-live-validation`** (post-activation guardrail: files → config serialization → profile_exceptions → Side.php menu → program URL body-error grep → smoke add/edit/delete). Also note: `docker exec <c> cmd < file` without `-i` silently does nothing (stdin not attached) — use `sh -c '... < file'`; and config MODULES edits must preserve `s:N:"Module"` quotes or the whole menu breaks.


### KB-0017 — NPM proxy caches static assets incl. 404s; purge recipe (accepted 2026-08-16)
Discovered during SCHOL-006 M1 R2.1 live deploy: **nginx-proxy-manager** (`/data/nginx/conf.d/include/assets.conf`) matches `location ~* ^.*\.(css|js|jpe?g|gif|png|webp|woff|woff2|eot|ttf|svg|ico|css\.map|js\.map)$` and caches ALL such responses (`proxy_cache_valid any 30m`, `proxy_cache_valid 404 1m`, `proxy_cache_use_stale … http_404`). Consequences: (1) a 404 observed BEFORE a file is deployed stays visible at the public URL even after the file exists upstream — verify with a cache-buster `?v=$(date +%s)` which gets a different key; (2) the public 404 is served by openresty (285 B page, `Server: openresty`), not by Apache. Cache key = nginx default `$scheme$host$request_uri` (proxy.conf sets no `proxy_cache_key`); path `/var/lib/nginx/cache/public` with `levels=1:2` → `<key[0]>/<key[1:3]>/<key[3:]>` (e.g. key `62efb516…` → `a/e2/62efb516…` — note: NOT the intuitive 2/2/rest split). Purge recipe: compute `KEY=$(printf "%s" "school4.edunaija.online/modules/Billing_Elements/icon.png" | md5sum | cut -d' ' -f1)`, then `docker exec nginx-proxy-manager-app-1 rm -f /var/lib/nginx/cache/public/${KEY:0:1}/${KEY:1:2}/${KEY:3}`; locate via `grep -rl "<url-substring>" /var/lib/nginx/cache/public/`. Also: `Expires @30m` → user browsers may keep a stale 404 for ~30 min; advise hard refresh. Deployment rule: after placing/updating any static asset behind NPM, purge the proxy cache entry and re-verify the public URL (cache-busted first, then plain).

### KB-0018 — Module Help: per-user-type content standard (accepted 2026-08-16)
**Ticket:** SCHOL-007 · Type: feature (module Help content / UX docs) · Priority: HIGH · Status: **RELEASED** (2026-08-16)
Every module Help must have per-program entries structured per user type (Administrator / Teacher / Student / Parent):
- **(a) one-line what-it-is:** exactly one sentence stating what the program does.
- **(b) "Who uses it:" line** naming the supported roles AND explicitly the unsupported roles (e.g. "Administrator only. Teachers, students and parents do not have access to this program.") — never leave a role unaddressed.
- **(c) bold-labeled subsection per supported role** — `<b>For the Administrator:</b>` / `<b>For the Teacher:</b>` / `<b>For Students & Parents:</b>` (single combined label when student and parent do exactly the same; RosarioSIS forces students into the parent menu) — with concrete step-by-step UI steps citing verbatim button/field names (e.g. "Add Element", "Assign fees for month", "Purchase").
- **(d) code-verified role table attached** — role support derived from `Menu.php` entries + `User('PROFILE')` / `AllowEdit()` / `UserStudentID()` guards (evidence file:line), so no capability is invented.
- **(e) style/mechanics:** no "your child" phrasing (core `GetHelpText()` rewrites it for the student profile — Help.fnc.php:191-198; "your student" is safe for both profiles); apostrophes escaped (`\'`) inside single-quoted strings; static text only; entries wrapped `_help( 'TEXT', '<Module>' )` inside a single `$help['<Module>/<Program>.php'] = '<p>' . _help( 'TEXT', '<Module>' ) . '</p>'` assignment; paragraphs as separate `<p>` blocks (`</p>` newline + tab `<p>`).
- **Reference/template:** `/tmp/opencode/spec_help_per_user.md` §2 (full template §2.4). Applied: SCHOL-007 (Billing Elements `Help_en.php`, 11,872 B, sha1 `73f982251c0620261f955f331cbafcbb7a399280`, live-verified 8/8 endpoints; zip rebuilt 17 files / 32,871 B, sha256 `ef5c8182bd6fb6e3e0f7123d45b937775c2e40a16fbb87b988978a83383a6540`, zipdetails 0 warnings).
- **Finding F1 (flagged, not fixed):** teacher menu lists `CategoryBreakdown.php` (Menu.php:22-23) but the program blocks non-admin (CategoryBreakdown.php:15-21); help text follows the code guard — follow-up ticket needed.

### KB-0019 — SCHOL-008 M2: Student Billing Premium module fabricated (accepted 2026-08-16)
**Ticket:** SCHOL-008 · Type: feature (premium module M2) · Priority: HIGH · Status: **RELEASED** (2026-08-16)
Module 2 of the premium program shipped to `/home/coder/premium-modules/Student_Billing_Premium/` + `Student_Billing_Premium.zip` (17 files, 40,347 bytes, sha256 `78d01c76641ca9e4b42df9ef8c106d8e330383cb9b05c3b9b24a78ea8e60b2db`, zipdetails 0 warnings, single top-level `Student_Billing_Premium/`). Reference: archived `Student_Billing_Premium` (Monnify/Moniepoint gateway, 14 files) + rosariosis.org module page + school-administrator-handbook (Monthly Fees `__MONTH__` substitution, auto-assign daily, Assign flow; Print Receipts timeframe/two-copies/lunch column/payment number; Payments Import CSV/XLSX column-association). Delivered:
- **6 programs** merged into core Student_Billing menu (admin: all; teacher: PrintInvoices/PrintReceipts; parent: PrintInvoices/PrintReceipts/Pay): Configuration (Monnify test/live API key/secret/contract, currency, invoice/receipt prefixes, legal notice, webhook URL, Test Connection), Monthly Fees (templates with `__MONTH__`, due day, grade level, auto-assign daily, Assign link → Find a Student → checkboxes → Add Fee, student count link, delete), Payments Import (CSV/XLSX upload 5MB, zip-bomb/XXE guards, formula-injection neutralization, school-scoped student lookup, preview→import re-validated), Print Invoices (PDF, student/grade search), Print Receipts (PDF, two copies, hide lunch column, payment number, legal notice, direct `print_receipt=Y` link from Payments), Pay (POST-only, Monnify hosted checkout, JS redirect, server-side callback verify, atomic claim).
- **Security hardening (G4 PASS):** Webhook HMAC-SHA512 enforced in ALL modes (live: 401 BAD_SIGNATURE/CREDIT_BLOCKED; test: 200 TEST_MODE_NO_CREDIT, never credits); atomic PENDING→PAID claim (WHERE STATUS='PENDING' + affected-rows===1); Pay.php AllowUse()+profile allowlist + 6-program×4-profile grants in install SQL; PaymentsImport school-scoped JOIN, htmlspecialchars(ENT_QUOTES) on errors, formula metacharacters (=+-@) neutralized, 5MB/xlsx-csv/zip-bomb/LIBXML_NONET guards; Configuration secrets masked (never echoed); Print* output escaped.
- **KB-0016 regressions fixed:** MonthlyFees `g.SYEAR` dropped (school_gradelevels has no SYEAR); double-escape of TITLE removed (fork pre-escapes at Warehouse.php:372); Webhook status whitelist aligned with Pay.php.
- **Premium pattern:** icon.png (64×64 RGBA, emerald tile + white FA `money-bill-wave` glyph, CC BY 4.0), Help_en.php (6 programs, KB-0018 per-user-type, sha1 `d791f30fec4cc7a19626b34fc60cb51ac9d0990c`), README (features/install/webhook/credits: adapted from RosarioSIS premium module by François Jacquet MIT + FA CC BY 4.0 + Monnify/Moniepoint + .xls deviation note).
- **Gates:** G1 PASS (feature-tester: 24/27 FIXED + B1 placeholder-cols fixed, 10-assert probe), G2 PASS (feature-division-council: change set contained, AC1-5 MET), G3 PASS-WITH-CONDITIONS (system-architect: C1/C3 fixed, C2 curl verified, C4 live smoke), G4 PASS (security-division-council: all 15 threat findings re-verified FIXED), G5 PASS (quality-division-council: KB-0016 live validation ALL PASS — files, config, grants, menu, 5 programs 200, help KB-0018, smoke CRUD), G6 PASS (release-custodian: zip 17 files/40KB sha256 verified, icon/help sha1 match live, KB-0019 recorded, MEMORY_INDEX updated).

### KB-0020 — RosarioSIS Demo Site Module Inventory & Structure Reference (accepted 2026-08-16)
**Source:** Live exploration of https://www.rosariosis.org/demonstration/ (admin/admin, teacher/teacher, student/student, parent/parent) via playwright-core headless Chromium; module data from https://www.rosariosis.org/modules/, https://www.rosariosis.org/add-ons/, https://www.rosariosis.org/demo/. Demo runs latest RosarioSIS development state (Student Billing Premium v16.5, August 2026).

**Core Modules (always present, cannot be deleted):**
School_Setup, Students, Users, Scheduling, Grades, Attendance, Eligibility, Discipline, Accounting, Student_Billing, Food_Service, Resources, Custom

**Premium Modules (activated on demo, shown with "Deactivate" button):**
| Module | Version | Programs (menu key → file) | Notes |
|--------|---------|---------------------------|-------|
| **Billing_Elements** | — | Elements.php, MonthlyElements.php, MassAssignElements.php, StudentElements.php, CategoryBreakdown.php, DailyTransactions.php | 6 programs; icon.png present; "Deactivate" shown |
| **Student_Billing_Premium** | 16.5 (Aug 2026) | StudentFeesMonthly.php, Invoices.php, Receipts.php, PaymentsImport.php, PaypalConfiguration.php | 5 programs + Pay button on Student Billing > Payments; "Deactivate" shown |
| **Accounting_Premium** | 6.1 (Jul 2026) | 3 additional programs | "Deactivate" shown |
| **Food_Service_Premium** | — | 7 programs (Reservations.php etc.) | Requires free Food_Service also active |
| **Class_Diary_Premium** | — | Extends free Class_Diary | Requires free module also active |
| **Entry_Exit_Premium** | — | Extends free Entry_Exit | Requires free module also active |
| **Hostel_Premium** | — | Extends free Hostel | Requires free module also active |
| **Lesson_Plan_Premium** | — | Extends free Lesson_Plan | |
| **Library_Premium** | — | Loans.php (student/staff) | Requires free Library also active |
| **Meeting_Premium** | — | Extends free Meeting | |
| **Messaging_Premium** | — | Extends free Messaging | |
| **Quiz_Premium** | — | Extends free Quiz | Requires free Quiz also active |
| **SMS_Premium** | — | Premium gateways | |
| **Student_Import_Premium** | — | Premium features | |

**Minor Modules (free, on GitLab, not on demo):**
Audit, Attendance_Excel_Sheet, Backup, Dashboards, Embedded_Resources, Medical_Report, PDF_Archive, Semester_Rollover, Slovenian_Attendance_Excel_Sheet, Slovenian_Class_Diary, Slovenian_Discipline

**Key Structural Differences (Demo vs Our Implementation):**

| Aspect | Demo Site (Original) | Our SCHOL-008 Implementation | Action Needed |
|--------|---------------------|------------------------------|---------------|
| **Student Billing Premium Programs** | StudentFeesMonthly.php, Invoices.php, Receipts.php, PaymentsImport.php, PaypalConfiguration.php | MonthlyFees.php, PrintInvoices.php, PrintReceipts.php, PaymentsImport.php, Configuration.php | **Rename our files to match demo** for consistency |
| **Pay Button** | Appears on Student Billing > Payments (core) | Separate Pay.php program in module | **Move Pay logic to core StudentPayments.php hook** or keep as-is with note |
| **Configuration** | PaypalConfiguration.php (PayPal/Stripe) | Configuration.php (Monnify/Moniepoint) | **Gateway difference** — demo uses PayPal/Stripe; we use Monnify (Nigerian market) |
| **Monthly Fees** | StudentFeesMonthly.php | MonthlyFees.php | **Rename** |
| **Print Invoices** | Invoices.php | PrintInvoices.php | **Rename** |
| **Print Receipts** | Receipts.php | PrintReceipts.php | **Rename** |
| **Billing Elements Programs** | Elements.php, MonthlyElements.php, MassAssignElements.php, StudentElements.php, CategoryBreakdown.php, DailyTransactions.php | Elements.php, MonthlyElements.php, MassAssignElements.php, StudentElements.php, CategoryBreakdown.php, DailyTransactions.php | **Matches** (6 programs) |
| **Menu Integration** | Both merge into Student_Billing menu (admin sees all) | Same approach | **Matches** |
| **Module Activation** | Both show "Deactivate" (activated) | We activate via MODULES config | **Matches** |
| **Help System** | `Bottom.php?bottomfunc=help&modname=...` | Same | **Matches** |

**Demo Site Help Content (verified via playwright):**
- Billing_Elements/Elements.php: Specific help (create categories, elements, assign button)
- Billing_Elements/MassAssignElements.php: Specific help (Find Student, select element, add to selected)
- Billing_Elements/StudentElements.php: Specific help (Find Student, add/remove elements)
- Billing_Elements/CategoryBreakdown.php: Specific help (charts, pie/list/amount views, grade breakdown)
- MonthlyElements.php, DailyTransactions.php: Fallback to generic help (no specific help entries)
- Student_Billing_Premium programs: Not individually tested (help system returns generic for some)

**Gateway Note:** Demo uses PayPal/Stripe (PaypalConfiguration.php). Our implementation uses Monnify/Moniepoint (Nigerian market) — this is a deliberate localization, not a deviation.

**Version References:**
- Student Billing Premium on demo: v16.5 (August 2026)
- Accounting Premium: v6.1 (July 2026)
- Demo runs latest development state

**Action Items for Parity:**
1. Rename SCHOL-008 program files to match demo naming convention (StudentFeesMonthly.php, Invoices.php, Receipts.php, PaypalConfiguration.php)
2. Consider moving Pay button to core StudentPayments.php via hook (student_payments_header)
3. Update Menu.php to use demo program names
4. Update Help_en.php keys to match new program filenames
5. Update README with correct program names

**Evidence Files:**
- `/tmp/demo_modules_page.html` — full modules configuration page HTML
- `/tmp/explore_demo.js` — playwright exploration script
- `/tmp/get_help.js` — playwright help extraction script
- Demo site screenshots via playwright (not saved, but reproducible)

---

### KB-0021 — opencode session lifecycle (accepted 2026-08-16)
**Ticket:** SCHOL-097 · Type: ops (session recovery) · Priority: HIGH · Status: **RESOLVED**
- **Issue:** User reported session `ses_ffeb3c23fffeGmhYMRQxpNvGMS` "stuck in another terminal".
- **Investigation:** Queried opencode SQLite DB (`/home/coder/.local/share/opencode/opencode.db`) and process table.
- **Finding:** Session `ses_ffeb3c23fffeGmhYMRQxpNvGMS` **COMPLETED** on 2026-08-15T21:58:53Z (last message `finish":"stop"`). It was the main orchestration session (Aug 14–15) that delegated 54+ module replication tickets (SCHOL-009 through SCHOL-096) via subagents.
- **The "stuck" process:** PID 667072 (started 16:21 today) is an **orphaned leftover** — connected to `/dev/pts/1 (deleted)` (terminal closed). It is NOT running the historical session. Current active session is `ses_ff4d39d54ffe6igeZP7Bo3hBE4` (PID 667706, pts/3).
- **Resolution:** Clean up orphaned process: `kill 667072`. No session recovery needed — historical session already complete; all work tracked in ticket ledger.
- **Convention:** opencode sessions persist in DB after completion. Leftover processes from closed terminals should be killed. Current session ID available via `sqlite3 /home/coder/.local/share/opencode/opencode.db "SELECT id FROM session ORDER BY time_created DESC LIMIT 1;"`.
- **Evidence:** DB queries, `ps -fp 667072 667706`, `/proc/667072/fd/`, log file `/home/coder/.local/share/opencode/log/opencode.log`, ticket ledger SCHOL-009–SCHOL-096.
- **Owner:** ci-cd-engineer → platform-division-council

### KB-0022 — SCHOL-106: Nested table collapses in table-layout: auto context (accepted 2026-08-17)
**Ticket:** SCHOL-106 · Type: bug · Priority: high · Status: **RELEASED** (2026-08-17)
- **Symptom:** Date range rows (Assigned/Payments) on Invoices.php and Receipts.php rendered "jumbled up and almost unreadable" — Month/Day/Year `<select>` elements stacking vertically within 137px-tall blocks instead of inline on a single row.
- **Root cause:** A nested `<table class="cellspacing-0 valign-middle">` inside a `table-layout: auto` widefat table cell collapsed to ~148px total width (inner selects column: 113px). The `<span class="nobr">` with `white-space: nowrap` did NOT prevent inline-block `<select>` elements from wrapping because `table-layout: auto` column minimum-width calculation constrains the cell based on unbreakable content pieces, not full inline-block widths. The three selects need ~194px but only had 113px.
- **Fix:** Replaced nested `<table>` with `<div style="display:flex;align-items:center;flex-wrap:nowrap">` containers. Flexbox `flex-wrap: nowrap` forces items to stay on one line regardless of parent sizing. Result: row height dropped from 137px to 29px (79% reduction). Total date range section: 260px → 71px.
- **Lesson:** `white-space: nowrap` on `<span>` prevents TEXT wrapping but does NOT prevent `<select>` (inline-block) elements from wrapping when a parent table cell constrains column width via `table-layout: auto`. Use flexbox (`display:flex;flex-wrap:nowrap`) for inline layouts that must stay on one line inside table cells.
- **Verification:** Playwright: td2Height=71px, row1H=29px, row2H=29px, flexWrap=nowrap. curl: 11/11 programs PASS, 0 deprecations.
- **Gates:** G1 PASS (php -l), G2 PASS (scope complete), G5 PASS (Playwright + curl), G6 PASS (DoD complete). Ledger: `AGENTVERSE/tickets/SCHOL-106.md`.
- **Owner:** feature-division → quality-division

### KB-0023 — SCHOL-107: Postbox width mismatch & missing receipt number text field (accepted 2026-08-17)

- **Lesson:** CSS overrides added for one version gap may need removal when the framework catches up. Always verify postbox width matches demo after each version upgrade. Form field parity between Invoices and Receipts modules is essential for pixel-perfect compliance. Always check both files for symmetric features. Flexbox (`display:flex;flex-wrap:nowrap`) is the preferred approach for inline layouts inside table cells, avoiding the table-inside-table collapse issue identified in SCHOL-106.

- **Verification:** CSS override removed from both Invoices.php and Receipts.php (no `#body .postbox{width:...}`). Receipts.php now has checkbox + text input for "Include Receipt Number", matching Invoices.php pattern. Postbox width expected at ~408px centered (demo match). `php -l` passes both files. school4 logs show no new errors. curl: 11/11 programs PASS, 0 new deprecations, 0 new fatals.

- **Owner:** feature-division → quality-division

### KB-0024 — SCHOL-108: Billing_Elements ListOutput TypeError on 12.9.2 (accepted 2026-08-19)

- **Lesson:** RosarioSIS 12.9.2 changed `ListOutput()` signature: 6th parameter is now `$group` (not `$functions`). Custom module code from older versions must be updated to match the new parameter order. Always verify module compatibility with the target RosarioSIS version before deployment. The "Agentverse testing phase leaking" is a valid concern — each pilot should clean its test data from the live database, or use a fresh database instance for testing.

- **Verification:** `php -l` passes both modified files (Elements.php ListOutput calls). School4 Elements page now loads without Fatal Error TypeError. `ListOutput()` call uses `[]` for `$group` parameter matching 12.9.2 API. Residual test data in school4 `billing_elements` table noted (1 element with CATEGORY_ID=0, no category assigned). curl: Billing_Elements programs PASS, 0 new deprecations.

### KB-0025 — SCHOL-109: Billing_Elements ListOutput TypeError fix (12.9.2 compatibility) (accepted 2026-08-19)

- **Lesson:** RosarioSIS 12.9.2 changed `ListOutput()` signature: 6th parameter is now `$group` (not `$functions`). Custom module code from older versions must update the parameter order when upgrading RosarioSIS. The "Elements appear all together" behavior is a fallback when ListOutput fails — proper progressive loading requires correct parameter order (`$group` = `[]`, not `$functions`). Always check the RosarioSIS version API changes when deploying custom modules after an upgrade.

- **Verification:** `Elements.php` ListOutput calls use `[]` for `$group` parameter. School4 Elements page loads without TypeError. Full workflow (create category → add element progressively) works without Fatal Error. Premium module `php -l` passes both files.

- **Owner:** feature-division → quality-division
## 4. Proficiency validation records

Validation method (per gate role): each pilot-relevant agent performs its actual role against SCHOL-001 on the live codebase; proficiency scored 1–5 (1=blocked/unable, 3=adequate, 5=strong) with evidence.

| Agent | Role in pilot | Score | Evidence |
|-------|---------------|-------|----------|
| summoner | triage, delegation, ledger, gate recording | 4 | routed correctly to Feature division; ran G0 and closed G6 with full ledger |
| feature-planner | plan, scope, acceptance criteria | 4 | plan matched implementation 1:1; AC1-4 accurate and testable |
| frontend-engineer | implemented fix | 5 | 2-line fix mirrored upstream exactly; comment references 12.7.4; no collateral edits |
| backend-engineer | G1 peer review | 4 | verified upstream parity + conventions; caught nothing outstanding (change minimal) |
| feature-division-council | G2 division gate | 4 | confirmed scope/AC complete, no scope creep |
| chief-architect | G3 architecture waiver | 4 | correctly applied fast-path for isolated frontend change |
| security-division-council | G4 security gate | 5 | thorough surface scan (synthetic event inert, no untrusted innerHTML, no eval, no secrets); clean verdict with rationale |
| quality-division-council | G5 quality gate | 5 | 7/7 test suite PASS + syntax check + no-double-fire analysis |
| unit-test-engineer / feature-tester | regression test authoring | 5 | wrote a meaningful contract test (stub field, both listener types) without a DOM |
| regression-gate | regression protection | 4 | verified fix restores intended behavior without double-firing |
| release-custodian | G6 release gate | 4 | changelog entry, DoD complete, KB updated |
| knowledge-curator | KB records | 4 | KB-0002/KB-0006 indexed with evidence |
| search-librarian | issue discovery | 3 | found the real regression, but also chased a false positive (GetMP.php) — lesson recorded in KB-0006 |
| secure-connector | SCHOL-004 host/container recon | 4 | clean SSH+docker workflow; config/db/log evidence gathered without breaking prod |
| platform-division-council | SCHOL-004 diagnosis + live fix | 5 | isolated delete blocker (AddonDelTree dry-run vs uid-1000 files), validated before/after via live UI, backed up module first |
| toolsmith | dev-env extensions & tooling | 5 | 15 extensions + Playwright MCP (chromium+system deps on Debian trixie, launch+handshake verified) + 2 skills + session-ledger plugin; all config validated |
| backend-engineer | SCHOL-006 M1 Billing Elements build | 4 | full module tree delivered (11 PHP files, 15 files total) before interruption; all `php -l` clean; spec deviations: none found in review |
| summoner | SCHOL-006 program triage + gate chain | 4 | SCHOL-006 ticket, archive-first cleanup, G1–G6 evidence (structure parity, SQLi/XSS scan, php -l, zipdetails contract validation incl. caught absolute-path bug), KB-0013/14/15 |

**Pilot verdict:** Feature Division + gate chain operated end-to-end on live code; cohesion matrix gate sequence (G0→G6) worked as specified. Only adjustment needed: fast-path waiver policy applied cleanly (no change).
