# SCHOL-005 — school4: clean unroll of uploaded modules (idempotent install, always-visible Delete)

**Status:** RESOLVED (fixed on live prod + repo fork) — 2026-08-14
**Type:** bug / UX (module manager) · **Priority:** high · **Product:** school4 prod (stock RosarioSIS 12.4.2, Docker) + `scholapro` fork
**Files (prod, via SSH+docker):** `/var/www/html/modules/School_Setup/includes/Modules.inc.php`, `/var/www/html/modules/Student_Billing_Premium/{install.sql,install_mysql.sql}` (live + local source `/home/coder/premium-modules/Student_Billing_Premium/`), repo `scholapro/modules/School_Setup/includes/Modules.inc.php`
**Platform:** host `extravus-prod` (161.153.35.43), containers `school4` (php:8.1-apache) + `db-school4` (mariadb)

## Request
User (report after SCHOL-004): re-activating the re-uploaded "Student Billing Premium" module fails with `DB Execute Failed. 1050 Table 'billing_monthly_fees' already exists`. Deactivation should "unroll the module cleanly"; the Delete button should exist even while the module is deactivated.

## Investigation (evidence-backed)
1. **1050 on re-activate:** activation runs `install_mysql.sql` only when the module key is absent (`Modules.inc.php` activate branch). Leftover tables from the pre-SCHOL-004 install (`billing_monthly_fees`, `billing_monnify_transactions`, `sbp_webhook_log`) made `CREATE TABLE` fatal → `DBQuery` aborts the script → key never set, module left "uninstalled". `delete.sql` correctly drops exactly those 3 module tables and NOT core `billing_fees`/`billing_payments`.
2. **Delete button hidden for uninstalled/deactivated modules:** `_makeDelete()` (render) and the `modfunc=delete` action both required `in_array($module, array_keys($RosarioModules)) && $RosarioModules[$module] == false`, so an uninstalled folder (key absent) could be activated but **not** deleted, and an active module had no Delete at all.
3. **"Menu still shows after deactivate" — mechanism, not a core bug:** RosarioSIS is a single-page app; `Warehouse('header')` outputs the page shell before module code runs, so `header()` redirects are impossible. `RedirectURL()` is **JS-based** (`var XRedirectUrl=...`, PreparePHP_SELF.fnc.php:189-199) and the menu (`<aside id="menu">`) is refreshed by `_reloadMenu()`'s `<script>ajaxLink('Side.php');</script>` (warehouse.js:441 → target `#menu`). Verified both are emitted on every activate/deactivate/delete and Side.php re-renders from `$RosarioModules` only (Menu.php:27), so the menu updates immediately once those scripts run. Deactivate additionally requires a `delete_ok=1` POST confirm (DeletePrompt) — a plain link shows the confirm box and returns false (this is why an earlier test showed the menu unchanged).
4. `AddonInstallationStatisticsPost()` also echoes output before the JS redirect; harmless (redirect is JS).

## Fix (applied + validated end-to-end via live UI)
1. **Idempotent install SQL** — `CREATE TABLE IF NOT EXISTS` for the 3 module tables (MySQL `install_mysql.sql` + PostgreSQL `install.sql`); applied live AND to local upload source `/home/coder/premium-modules/Student_Billing_Premium/`. Re-activation now skips existing tables (no 1050).
2. **Delete allowed in any state for non-core modules** (`Modules.inc.php`):
   - action: `$can_delete = !always_activated && !RosarioCoreModules` (dropped key-present/value-false requirements);
   - render `_makeDelete()`: Delete button emitted for every non-core module (active, deactivated, or uninstalled folder) — `Activate/Deactivate + Delete`.
3. **Menu refresh on delete:** added `_reloadMenu()` inside the delete block (previously only activate/deactivate refreshed the menu).
4. **Repo fork synced:** identical changes applied to `scholapro/modules/School_Setup/includes/Modules.inc.php` (diff vs live = only the "ScholaPro"/"RosarioSIS" branding comment).

## Validation (live, curl UI session)
- Login admin/[REDACTED_ADMIN_PASSWORD] → Modules list: uninstalled module shows **Activate + Delete**; active shows **Deactivate + Delete**.
- Activate (confirm POST) → 200, `ajaxLink('Side.php')` + `XRedirectUrl` emitted, **no "DB Execute Failed"**; Side.php now lists module (5 hits).
- Deactivate (confirm POST) → Side.php drops module (0 hits); list shows Activate + Delete.
- Re-activate → no 1050; menu back.
- Delete (confirm POST) → folder removed (`FOLDER_REMOVED`), config key gone (13 core modules intact), tables left = only core `billing_fees`/`billing_payments`; Side.php 0 hits; no PHP fatals/warnings on any response.
- Backups live: `/tmp/Modules.inc.php.bak` (original core file), `/tmp/install_mysql.sql.bak`, `/tmp/install.sql.bak` (container).

## Root cause (one line)
Module install SQL was non-idempotent (fatal 1050 when tables persisted after a failed/partial previous install) and the module manager's Delete path was over-restricted to "installed-and-deactivated", leaving no UI path to uninstall leftover modules.

## Recommendations (open, not applied)
- Module is currently **fully removed** from live. Re-upload from `/home/coder/premium-modules/Student_Billing_Premium` (zip) when the school wants it back — install is now idempotent and Delete is available in every state. Keep module files `www-data`-owned (KB-0008) so deletion always succeeds.
- Deactivate/delete confirm dialogs are a deliberate safety step (DeletePrompt); keep them.

## Gate ledger
Ops + core-file fix on live prod, validated end-to-end via HTTP UI; mirrored to repo fork.

| Gate | Verdict | Evidence | Sign-off |
|------|---------|----------|----------|
| G0 triage | PASS | live repro of 1050 + config/tables/folder state; read Modules.inc.php action+render logic | platform-division-council |
| G1 design | PASS | idempotent SQL + relaxed can_delete + _reloadMenu on delete; JS-redirect mechanism confirmed (no core rewiring) | platform-division-council |
| G2 impl | PASS | applied to live + local source + repo fork; `php -l` clean (live) | platform-division-council |
| G3 test | PASS | full lifecycle via live curl UI: activate→deactivate→reactivate→delete; menu + folder + config + tables verified | platform-division-council |
| G4 docs | PASS | SCHOL-005 ledger, KB-0010, MEMORY_INDEX updated | knowledge-curator / memory-steward |
