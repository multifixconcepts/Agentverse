# SCHOL-106 — Date Range Rows Jumbled on Invoices & Receipts Search Forms

**Status:** RELEASED
**Type:** bug
**Priority:** high
**Product:** Student_Billing_Premium (ScholaPro)
**Affected files:**
- `premium-modules/Student_Billing_Premium/Invoices.php` (lines 23-55)
- `premium-modules/Student_Billing_Premium/Receipts.php` (lines 36-55)

## Request

The "Assigned" date range row on Invoices.php and "Payments" date range row on Receipts.php render "jumbled-up and almost unreadable." The >= and <= selectors with Month/Day/Year selects are stacking vertically instead of displaying on clean rows.

## Root cause

A nested `<table class="cellspacing-0 valign-middle">` inside the widefat table's `<td>` auto-sizes to only ~148px wide (inner `<td>` for selects: 113px), but the three selects + calendar icon need ~194px. This forces the selects to wrap vertically within each row, creating a 137px-tall jumbled block.

The outer widefat table's `col1-align-right` class + `table-layout: auto` + `width: 100%` (postbox CSS fix from SCHOL-105) causes the nested table to collapse instead of expanding to fit its content. This is a table-inside-table layout issue in the core Search() rendering context.

## Fix

Replaced nested `<table>` with `<div style="display:flex;align-items:center;flex-wrap:nowrap">` containers. Flexbox with `flex-wrap: nowrap` forces the Month/Day/Year selects and calendar icon to stay on one line, regardless of the parent table cell's auto-sizing behavior.

**Before:** Each row height = 137px (selects stacking vertically). Total date range height = 260px.
**After:** Each row height = 29px (single line). Total date range height = 71px. 73% height reduction.

## Acceptance criteria

1. "Assigned" row on Invoices.php: >= and <= each on their own line with Month/Day/Year/Calendar inline -- no vertical wrapping. PASS (29px per row)
2. "Payments" row on Receipts.php: same layout. PASS (29px per row)
3. All 11 programs pass G5 validation (no deprecations, no fatals). PASS (11/11)
4. No changes to business logic (date range filtering still works). PASS (PHP code unchanged, only HTML structure).

## Gate ledger

| Gate | Owner | Verdict | Evidence |
|------|-------|---------|----------|
| G1 Peer | feature-division | PASS | `php -l` both files: 0 errors. Deployed to school4. |
| G2 Division | feature-division-council | PASS | Acceptance criteria met. No scope creep. |
| G5 Quality | quality-division | PASS | Playwright: Invoices td2Height=71px, row1H=29px, row2H=29px, flexWrap=nowrap. Receipts identical. curl: 11/11 programs PASS, 0 deprecations, 0 fatals. |
| G6 Release | quality-guardians | PASS | Definition of done complete. |

## Changes

- Invoices.php: Replaced nested `<table class="cellspacing-0 valign-middle">` with two `<div style="display:flex;align-items:center;flex-wrap:nowrap">` containers for >= and <= date range rows. Extracted shared `$month_names`, `$day_vals`, `$year_vals`, `$calendar_icon` variables.
- Receipts.php: Same change for Payments date range rows.

## Lessons learned

- Nested `<table>` inside a `table-layout: auto` cell can collapse to minimum content width instead of expanding. Use flexbox (`display:flex;flex-wrap:nowrap`) for inline layouts that must stay on one line.
- `white-space: nowrap` on `<span>` prevents text wrapping but does NOT prevent `<select>` (inline-block) elements from wrapping when the parent table cell constrains column width.
