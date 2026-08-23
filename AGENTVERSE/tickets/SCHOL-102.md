# SCHOL-102 — StudentFeesMonthly.php Undefined array key "theme" Warning

- **Status:** RELEASED
- **Type:** bug (PHP warning)
- **Priority:** HIGH
- **Product:** Student_Billing_Premium module
- **Parent:** SCHOL-101
- **Opened:** 2026-08-17
- **Request:** PHP Warning on StudentFeesMonthly.php:355 — `Undefined array key "theme"` in `$_SESSION['theme']`. Occurs when rendering the add-button image path. Session key may not be set in all contexts.

## Root Cause

Line 355 used bare `$_SESSION['theme']` which doesn't exist in RosarioSIS 12.9.2. The `theme` key is never set in `$_SESSION` — the correct API is `Preferences('THEME')`, used by all other modules (Scheduling/Schedule.php:271, StudentAssignments.fnc.php:809). My initial fix used `'roboto'` as fallback, but the actual theme is `FlatSIS`.

## Fix

Replace `$_SESSION['theme'] ?? 'roboto'` with `Preferences('THEME')` — the standard RosarioSIS theme resolution function.

**File:** `Student_Billing_Premium/StudentFeesMonthly.php:355`

## Acceptance Criteria

- **AC1:** No PHP warning on StudentFeesMonthly page load
- **AC2:** Add button icon renders correctly (or graceful fallback if theme missing)
- **AC3:** Deploy to school4, G5 validation passes

## Gate Chain

Fast-path: G1+G5 only (one-line null-coalescing, no security/architecture impact).

## Gate Ledger

| Gate | Owner | Status | Evidence | Sign-off |
|------|-------|--------|----------|----------|
| G1 Peer Review | feature-tester | PASS | `php -l` 0 errors; fix is `Preferences('THEME')` — standard RosarioSIS API used by Schedule.php:271, StudentAssignments.fnc.php:809. Initial fix `$_SESSION['theme'] ?? 'roboto'` corrected after finding `$_SESSION['theme']` never set in 12.9.2 and `roboto` theme doesn't exist (available: FlatSIS, WPadmin). | summoner |
| G5 Quality | quality-division-council | PASS | G5 live: `assets/themes/FlatSIS/btn/add_button.png` renders HTTP 200. Page 25,084 bytes, 0 warnings. Full suite 11/11 programs HTTP 200. Deployed via SCP+docker cp+chown www-data. | summoner |
| G6 Release | release-custodian | PASS | Student_Billing_Premium.zip rebuilt (20 files/35,680B) | summoner |
