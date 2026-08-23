---
name: addon-live-validation
description: Automated post-activation live validation of RosarioSIS add-on modules on school4 (school4.edunaija.online). Use AFTER a module zip is uploaded/activated to verify menu visibility, profile_exceptions grants, program render (no PHP fatals), list/data rendering, and add/edit/delete flows — the "Agentverse guardrail" that catches regressions like the Billing Elements menu-missing incident. Uses curl (authenticated admin session) + docker (DB checks) against host extravus-prod.
---

# Addon Live Validation Guardrail (school4)

Purpose: after any module is activated on live school4, prove it renders AND is usable before declaring done. This encodes the checks that caught the Billing Elements incident (KB-0016): menu invisible despite active config (missing `profile_exceptions`), PHP 8.1 `ListOutput` TypeError from keyed `DBGet`, `DisplayNameSQL()` in double-quoted strings, and `g.SYEAR` on a table with no SYEAR column.

## Access (same as school4-ops)

- Host: `ssh -o BatchMode=yes extravus-prod` (161.153.35.43).
- Containers: `school4` (php:8.1-apache, volume `school4_school4_data` → `/var/www/html`), `db-school4` (mariadb).
- DB: `docker exec db-school4 mariadb -uadmin -pREDACTED_DB_PASSWORD -h127.0.0.1 school4_db`.
- Admin session: cookie jar `/tmp/opencode/adm.cookies` (regenerate per task: GET index.php then POST USERNAME/PASSWORD, expect 302).

## Guardrail checklist (run in order)

1. **Files present in live volume:**
   `ssh extravus-prod "docker exec school4 sh -c 'ls /var/www/html/modules/<Module>/'"` — every shipped file must be there. (Note: module may be removed/deactivated during fixes; the ops cycle is config-driver, see KB-0016.)

2. **Activated in config (serialized PHP, must be valid):**
   `SELECT config_value FROM config WHERE title='MODULES'` → expect `s:N:"<Module>";b:1;`. Verify by `php -r 'var_dump(unserialize("<value>"))'` (or `php -r 'print_r(unserialize(file_get_contents("php://stdin")));'` with the value piped). A mangled serialized string (e.g. missing `"` quotes around the key) silently breaks the whole menu — check quotes in the stored value, not just presence of the key name.

3. **profile_exceptions grants (root cause of "menu missing"):**
   Activation alone does NOT grant menu visibility. Each menu program needs a row for each profile in `profile_exceptions` (`MODNAME='<Module>/<Program>.php'`, PROFILE_ID, CAN_USE='Y', CAN_EDIT per role). Query:
   `SELECT MODNAME,PROFILE_ID,CAN_USE,CAN_EDIT FROM profile_exceptions WHERE MODNAME LIKE '<Module>/%'`
   Admin (PROFILE_ID=1) needs every program with CAN_USE/CAN_EDIT='Y'; teacher/parent/student profiles only what their menus expose (store/myelements). `AllowUse()` (functions/AllowEdit.fnc.php) false → no menu entries. Fix SQL pattern: `/tmp/opencode/grant_billing_elements.sql`.

4. **Menu shows the module + programs:**
   `curl -s -b adm.cookies https://school4.edunaija.online/Side.php | grep -c '<li class="menu-module <module>-slug">'` AND grep each `<Module>/<Program>.php` link. Note: `Side.php` renders the menu for the logged-in session only; menu links use the module dir name slugged.

5. **Every program URL renders clean** (this is where PHP 8.1 fatals hide — pages still return HTTP 200 after a fatal, so grep the BODY):
   For each `<Program>.php`: `curl -s -b adm.cookies "https://school4.edunaija.online/Modules.php?modname=<Module>/<Program>.php"` and assert `http=200` AND **0 matches** of `Fatal error|Failed opening required|DB Execute Failed|TypeError|Warning:`. A fatal mid-page truncates the render (list forms below the fatal disappear) — always compare form count / key sections, not just status code.

6. **List/data rendering with a real row (smoke add → render → edit → delete):**
   - POST a new row: `curl -s -b adm.cookies -L "<URL>" --data-urlencode "modname=..." --data-urlencode "modfunc=update" --data-urlencode "values[new][...]=..."` → expect "Changes saved" AND the new value rendered in the list (follow `-L` for the redirect response).
   - Existing rows must render as EDITABLE inputs named `values[<ID>][<COL>]` (values from `$THIS_RET['ID']`), not plain text — if plain text, the row functions aren't applied (either DBGet `$functions` arg missing or mis-placed as ListOutput's `$group`).
   - Edit an existing row via `values[<ID>][...]` POST → confirm DB changed.
   - Delete via `&modfunc=<remove_xxx>&id=<ID>&delete_ok=1` (plain link only shows the Confirm box; DeletePrompt needs `delete_ok=1`).
   - Confirm via DB after each step (`SELECT` count/row). Clean up the smoke rows afterward.

## PHP 8.1 + ListOutput trap (caught on Billing Elements)

`DBGet( $sql, $functions, [ 'ID' ] )` (keyed) passed straight to `ListOutput()` triggers a fatal in stock 12.4.2:
`TypeError: strpos(): Argument #1 ($haystack) must be of type string, array given in functions/ListOutput.fnc.php:523`
Keyed results are `[ ID => [1 => row] ]`; ListOutput's `$list_has_input` probe iterates the first inner value (an array) and `strpos` it. Only fires when the list is NON-empty. Fix: drop the key (`DBGet($sql, $functions)`); input names still map to real DB IDs via the global `$THIS_RET['ID']`, so `DBUpdate(..., ['ID'=>(int)$id])` keeps working. Core modules (e.g. School_Setup/GradeLevels.php) use non-keyed DBGet for the same reason. Never pass row functions as ListOutput's 6th arg (`$group`) — that's the grouping/flatten config, not functions.

## Other live-verified traps (Billing Elements)

- `DisplayNameSQL( 's' )` written INSIDE a double-quoted PHP string is sent literally to MySQL → error 1305. Concatenate: `"COALESCE(" . DisplayNameSQL('s') . ",'')"`. Also present in the archived reference module.
- `g.SYEAR` on `school_gradelevels` (no SYEAR column, stock AND fork) → error 1054. Drop the condition.
- MariaDB upgrade on db-school4 (11.8.2→12.3.2) left stale `mysql.proc` → error 1558 on `DisplayName`-related queries; fixed with `mariadb-upgrade -uroot -pschool4_root_pass --force`.
- Config MODULES edits via SQL must preserve serialization: use a full-value UPDATE (or CONCAT with the exact `s:16:"Module";b:1;` literal INCLUDING the double quotes). `docker exec` needs `sh -c '...'` around stdin redirection (`docker exec <c> mariadb ... < file` without `-i` silently does nothing).
