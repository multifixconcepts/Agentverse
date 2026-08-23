# SCHOL-001 — calendar-setup.js change-event regression

**Status:** RELEASED (resolved) — pilot complete
**Type:** bug · **Priority:** high · **Product:** `scholapro` (ScholaPro / RosarioSIS 12.5 fork)
**Files:** `scholapro/assets/js/jscalendar/calendar-setup.js` · `scholapro/tests/calendar-setup.regression.test.js` · `scholapro/CHANGES.md`
**Evidence basis:** diff vs upstream `francoisjacquet/rosariosis` mobile branch (12.7.4 changelog: "Fix regression since 12.5 trigger change event when selecting date from calendar").

## Problem
Selecting a date in the calendar picker only fires the native `onchange` **property** handler
(`calendar-setup.js:142-143`). Handlers bound via `addEventListener`/jQuery (RosarioSIS binds program-wide) never fire → date-driven UI (attendance, assignments, scheduling, MP filters) does not react.

## Acceptance criteria
1. Picker date selection triggers change listeners bound via `addEventListener`/jQuery.
2. Native `onchange` property handlers (if set) still fire (dispatched `change` covers them).
3. Regression test added and passing under node v24.
4. `CHANGES.md` updated (pre-release product — no version bump).

## Plan (feature-planner)
- **G1/G2 (Feature):** replace the two lines `if (typeof p.dayField.onchange == "function") p.dayField.onchange();` with `p.dayField.dispatchEvent(new Event('change', { 'bubbles': true }));` (mirrors upstream fix exactly).
- **Tests (Quality):** node test asserting (a) the shipped file dispatches a bubbling `change` event and no longer calls `.onchange()`; (b) the fix contract fires both an `addEventListener`-bound listener and an `onchange` property handler on a stubbed field.
- **G3 Architecture:** frontend-only, isolated → fast-path waiver (no structural impact).
- **G4 Security:** verify no XSS/CSP/injection surface (dispatchEvent of a synthetic event is inert).
- **G5 Quality:** run node test + JS syntax check.
- **G6 Release:** changelog + DoD complete.

## Gate ledger

| Gate | Verdict | Evidence | Sign-off |
|------|---------|----------|----------|
| G0 Triage | PASS | upstream 12.7.4 changelog + source diff confirm real regression in `calendar-setup.js:142-143` | summoner |
| G1 Peer | PASS | 2-line change mirrors upstream fix exactly (`dispatchEvent(new Event('change',{bubbles:true}))`); no dead code; comment references upstream 12.7.4 | backend-engineer (peer) |
| G2 Division | PASS | Scope complete: fix + regression test + changelog. AC1-4 all met (listener fires via addEventListener AND onchange property; test green; changelog updated) | feature-division-council |
| G3 Architecture | PASS (fast-path waiver) | frontend-only, isolated JS in one file; no structural/interface impact; waiver signed | chief-architect |
| G4 Security | PASS | synthetic `Event` dispatch — inert, no user input, no innerHTML of untrusted data, no eval; no CSP impact; no secrets in diff (scan clean) | security-division-council |
| G5 Quality | PASS | `node tests/calendar-setup.regression.test.js` → 7/7 PASS (exit 0); `new Function(src)` syntax OK; `onUpdate` callback separate — no double-fire | quality-division-council |
| G6 Release | PASS | CHANGES.md top entry added; DoD complete; KB-0002 updated; ticket ledger closed | release-custodian |

**Final verdict: RELEASED** — SCHOL-001 resolved. Related drift queued as SCHOL-002 (warehouse.js).
