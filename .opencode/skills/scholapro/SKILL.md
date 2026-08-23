---
name: scholapro
description: ScholaPro (RosarioSIS 12.5 fork) codebase conventions. Use ONLY when working on code under /home/coder/project/scholapro — PHP/JS/CSS conventions, module layout, and how to verify changes against the PHP 8.1 runtime.
---

# ScholaPro Product Conventions

Product: `/home/coder/project/scholapro` — ScholaPro Educational Management Platform, a RosarioSIS 12.5 mobile-branch fork, fully rebranded (0 upstream brand refs outside `locale/` and `CHANGES*`).

## Layout

- `functions/` shared PHP functions (e.g. `Search.fnc.php`, `GetMP.php`)
- `ProgramFunctions/` report/substitution/notification helpers
- `modules/<Module>/` module code; `modules/<Module>/includes/` partials
- `assets/js/` JS (note: `jscalendar/`, `warehouse.js`, minified `plugins.min.js`)
- `config.inc.php` site config (`ROSARIO_DEBUG` default false)
- `CHANGES.md` changelog — every change adds an entry at the top

## Conventions

- PHP: tabs for indentation, `function Name( $args )`, single quotes, `_( 'Text' )` for i18n, snake_case vars; DB access via `DBGet/DBGetOne/DBQuery`; escape output.
- JS: jQuery heavily used; change handlers may be bound via `addEventListener` or jQuery `.on('change')`.
- This fork is clean of PHP 8.1 landmines (no `strftime`/`create_function`/reversed `implode`/`FILTER_SANITIZE_STRING`) — keep it that way.

## Verification

- **No test framework installed.** Primary checks:
  - `php -l` every touched `.php` file. Runtime PHP 8.1.34: run via host docker: `docker run --rm -v vscode_code-server-home:/src:ro php:8.1-apache sh -c 'find /src/project/scholapro -name "*.php" -print0 | xargs -0 -n1 php -l'`
  - Node v24 at `/usr/lib/code-server/lib/node` for JS checks (jsdom not installed; use lightweight assertions or a minimal DOM stub).
- DB is MySQL/MariaDB (`db-school4`, mariadb client) — never touch production data during a pilot unless the ticket says so.
- Deployed prod `school4` is stock RosarioSIS 12.4.2 — **do not** treat deployed code as the repo; compare against upstream GitHub `francoisjacquet/rosariosis` `mobile` branch when checking regressions.

## Release hygiene

- Update `CHANGES.md` (top entry) per change.
- Record issue + proficiency records in `AGENTVERSE/KNOWLEDGE_BASE.md`.
