---
name: school4-ops
description: Operating the live school4 production RosarioSIS deployment (school4.edunaija.online) via docker on host extravus-prod. Use when deploying, configuring, troubleshooting, inspecting DB, backup/restore, or managing modules on school4. Includes admin credentials, DB access, module-management gotchas (www-data ownership rule).
---

# school4 Production Ops

Source of truth: KB-0004 (deployment drift), KB-0008 (module ops fix), SCHOL-004 ledger, MEMORY_INDEX 2026-08-14.

## Access

- Host: `ssh -o BatchMode=yes extravus-prod` → 161.153.35.43 (ubuntu/aarch64).
- Containers: `school4` (php:8.1-apache, volume `school4_school4_data` → `/var/www/html`, port 8084:80) and `db-school4` (mariadb).
- Site: https://school4.edunaija.online — stock RosarioSIS **12.4.2** (config VERSION/TITLE), NOT the scholapro fork.
- DB: `docker exec db-school4 mariadb -uadmin -pREDACTED_DB_PASSWORD -h127.0.0.1 school4_db -N -e "..."`.
- Admin login: `admin` / `<REDACTED_ADMIN_PASSWORD>` (password supplied at deployment time, not stored in this file; hash re-synced 2026-08-14; original hash backed up host `/tmp/admin.hash.orig`).
- This machine (code-server / opencode host): node only at `/usr/lib/code-server/lib/node` (not on PATH); npm CLI at `/home/coder/.npm-global/lib/node_modules/npm/bin/npm-cli.js` (run with the node path).

## Module management (verified 2026-08-14)

- Module active state = serialized PHP array in `config` table, title `MODULES` (`$RosarioModules`). Loaded in Warehouse.php; menu ops in `modules/School_Setup/includes/Modules.inc.php`; delete logic `AddonDelTree` in `modules/School_Setup/includes/Addon.fnc.php`.
- **www-data ownership rule (KB-0008):** uploaded custom modules whose files are NOT owned by `www-data` can never be deleted — `AddonDelTree` dry-runs `is_writable()` on every file. Fix: `docker exec school4 chown -R www-data:www-data /var/www/html/modules/<Module>/`. Back up first: tar from volume `school4_school4_data` to host `/tmp/`.
- **Module ops cycle (KB-0010, SCHOL-005):** install SQL must be idempotent (`CREATE TABLE IF NOT EXISTS`) or re-activation dies with `1050 ... already exists`; module tables = `billing_monthly_fees`, `billing_monnify_transactions`, `sbp_webhook_log` (never drop core `billing_fees`/`billing_payments`). Delete is now allowed for ANY non-core module in any state (action + `_makeDelete`), and `_reloadMenu()` runs on delete too. Deactivate AND Delete require a `delete_ok=1` POST confirm (DeletePrompt) — a plain link only shows the confirm box.
- Activate/deactivate/delete return 200, not 302 — redirect is JS (`var XRedirectUrl`, PreparePHP_SELF.fnc.php:189-199) and the menu reloads via `_reloadMenu()` → `<script>ajaxLink('Side.php')</script>` (target `#menu`, warehouse.js:441). Confirm state by re-fetching the modules page or Side.php / checking the DB, never by HTTP status alone.
- Login flow via curl: two-step (GET index.php to set `RosarioSIS` cookie, then POST USERNAME/PASSWORD); expected success = 302 → `Modules.php?modname=misc/Portal.php`. A plain GET /Side.php or 199-byte logout redirect usually means the session cookie went stale.

## Common tasks

- Read config: `SELECT config_value FROM config WHERE title='MODULES'`.
- Logs: `docker logs --since 30m school4 2>&1 | grep -aE "Fatal|Parse error|Warning"`.
- Backup module folder: `docker run --rm -v school4_school4_data:/d -v /tmp:/h ubuntu:22.04 sh -c "tar -C /d -czf /h/<name>.tar.gz <path>"`.
- Session cookie for authenticated curl: `/tmp/adm.cookies` pattern; regenerate per task.
