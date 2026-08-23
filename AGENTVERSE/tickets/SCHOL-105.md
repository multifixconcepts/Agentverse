# SCHOL-105 — Table Width / Content Shrinking in Student Billing Premium

- **Status:** OPEN
- **Type:** bug (UI/layout)
- **Priority:** HIGH
- **Product:** Student_Billing_Premium module
- **Opened:** 2026-08-17
- **Request:** User reports contents shrinking on pages — tables appear narrower than the demo site. Wants wider tables matching demo without breaking mobile responsiveness.

## Acceptance Criteria

1. Visual width of Invoices.php and Receipts.php search forms + results tables matches demo (rosariosis.org/demonstration)
2. Mobile responsiveness preserved (stacking at ≤479px, padding adjustments at ≤1023px)
3. No PHP deprecations or errors introduced
4. Both programs verified on school4 live

## Investigation Status

- CSS comparison: demo + school4 FlatSIS stylesheets are byte-identical (47,666 bytes)
- Core CSS rules: `#search input,#search select{max-width:150px}` — identical on both sites
- Responsive: `tr.st>td{float:left;width:100%}` at ≤479px — identical
- Pending: visual Playwright comparison to identify actual HTML/structural difference

## Gate Ledger

| Gate | Owner | Status | Evidence | Sign-off |
|------|-------|--------|----------|----------|
| G0 Triage | summoner | PASS | Classified as UI/layout bug, HIGH priority | summoner |
