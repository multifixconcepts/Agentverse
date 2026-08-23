# SCHOL-104 — PHP 8.1 Deprecation Warnings in Invoices.php + Receipts.php

- **Status:** RELEASED
- **Type:** bug (PHP 8.1 deprecation)
- **Priority:** HIGH
- **Product:** Student_Billing_Premium module
- **Parent:** SCHOL-103
- **Opened:** 2026-08-17
- **Request:** PHP 8.1 deprecation warnings on Invoices.php when clicking "Print Invoices" — `htmlspecialchars(): Passing null to parameter #1 ($string)`. Also user requested thorough page-by-page audit vs demo site.

## Root Cause

`issetVal( $_REQUEST['key'] )` returns `null` when the key doesn't exist (no default provided). PHP 8.1 deprecates passing `null` to `htmlspecialchars()` — must be a string.

## Fix

Added `?? ''` null coalescing after every `issetVal()` call passed to `htmlspecialchars()`:

**Invoices.php:**
- Line 136: `htmlspecialchars( issetVal( $_REQUEST['spi_legal_notice'] ) ?? '', ENT_QUOTES )`
- Line 155: `htmlspecialchars( issetVal( $_REQUEST['balance_low'] ) ?? '', ENT_QUOTES )`
- Line 156: `htmlspecialchars( issetVal( $_REQUEST['balance_high'] ) ?? '', ENT_QUOTES )`
- Line 255: `htmlspecialchars( issetVal( $student['GRADE_ID'] ) ?? '', ENT_QUOTES )`

**Receipts.php:**
- Line 145: `htmlspecialchars( issetVal( $_REQUEST['spr_legal_notice'] ) ?? '', ENT_QUOTES )`
- Line 164: `htmlspecialchars( issetVal( $_REQUEST['balance_low'] ) ?? '', ENT_QUOTES )`
- Line 165: `htmlspecialchars( issetVal( $_REQUEST['balance_high'] ) ?? '', ENT_QUOTES )`

## Demo Audit Results

Full page-by-page comparison of all 13 programs (6 BE + 5 SBP + 2 advanced) against demo site:

| Program | Demo | School4 | Deprecations | Notes |
|---------|------|---------|-------------|-------|
| Invoices.php | 31,450B | 40,198B | 0 | Size diff = core framework (12.9.2 vs dev) |
| Invoices.php&advanced=Y | 77,296B | 87,183B | 0 | Core advanced search adds 5 extra tr rows |
| Receipts.php | 31,484B | 40,189B | 0 | Same pattern |
| Receipts.php&advanced=Y | 77,330B | 87,174B | 0 | Same pattern |
| PaymentsImport.php | 21,657B | 22,541B | 0 | |
| StudentFeesMonthly.php | 23,544B | 25,102B | 0 | |
| PaypalConfiguration.php | 22,182B | 24,021B | 0 | |
| Elements.php | 209B | 25,006B | 0 | Demo: module not activated |
| DailyTransactions.php | 209B | 29,136B | 0 | Demo: module not activated |
| CategoryBreakdown.php | 209B | 28,484B | 0 | Demo: module not activated |
| MonthlyElements.php | 209B | 26,707B | 0 | Demo: module not activated |
| MassAssignElements.php | 209B | 28,241B | 0 | Demo: module not activated |
| StudentElements.php | 209B | 23,276B | 0 | Demo: module not activated |

**Key findings:**
- Billing_Elements programs return 209 bytes on demo (module not activated) — cannot compare
- Size differences in SBP programs are from core framework version gap (12.9.2 vs dev)
- Advanced search table width difference is core CSS between versions — not our code
- All SBP-specific elements (calendar icons, SBPWidgets.js, Balance widget, Legal notice text input, Include Invoice # checkbox+text) match demo exactly

## Gate Chain

Fast-path G1+G5 (PHP 8.1 null-coalescing fix, no security impact)

## Gate Ledger

| Gate | Owner | Status | Evidence | Sign-off |
|------|-------|--------|----------|----------|
| G1 Peer Review | feature-tester | PASS | `php -l` 0 errors on Invoices.php + Receipts.php. All 7 `issetVal()` → `htmlspecialchars()` calls now use `?? ''` null coalescing. Pattern verified across both files. | summoner |
| G5 Quality | quality-division-council | PASS | G5 live validation: 11/11 programs HTTP 200, 0 deprecations, 0 warnings, 0 fatal errors. Invoices.php basic + advanced: "Deprecated:" grep = 0 matches. Receipts.php: same. Full page-by-page demo audit completed (13 programs). | summoner |
| G6 Release | release-custodian | PASS | Student_Billing_Premium.zip rebuilt (21 files/37,214B). Deployed via SCP+docker cp+chown www-data. | summoner |
