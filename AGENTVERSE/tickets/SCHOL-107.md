# SCHOL-107 — Postbox Width Mismatch & Missing Receipt Number Text Field

**Status:** RELEASED
**Type:** feature
**Priority:** high
**Product:** Student_Billing_Premium (ScholaPro)
**Affected files:**
- `premium-modules/Student_Billing_Premium/Invoices.php` (lines 114-115 removed, lines 198-200 updated)
- `premium-modules/Student_Billing_Premium/Receipts.php` (lines 123-124 removed, lines 88-92 updated, lines 226-229 updated)

## Request

Two issues need resolution to match the rosariosis.org demo site exactly:

1. **Postbox width mismatch:** The CSS override `#body .postbox{width:calc(100% - 2px);margin:0 auto}` (added in SCHOL-105) makes the postbox full-width (~766px on school4), but the demo site shows the postbox centered at ~408px width. This override must be removed so the postbox renders at the demo-matching width.

2. **Missing "Include Receipt Number" text field:** Receipts.php only has a checkbox for `spr_display_receipt_id`, but the demo site shows both a checkbox AND a text input field (matching Invoices.php's `spi_display_invoice_id` pattern). The text input must be added to Receipts.php.

## Root cause

1. SCHOL-105 added `echo '<style>#body .postbox{width:calc(100% - 2px);margin:0 auto}</style>';` to both Invoices.php and Receipts.php to widen the postbox. This was appropriate for SCHOL-105 but creates a width mismatch with the demo site in the current RosarioSIS version.

2. Receipts.php was missing the text input field for the receipt number. Invoices.php has both checkbox + text input (lines 78-83), but Receipts.php only had the checkbox (lines 88-92).

## Fix

1. **Removed the CSS override** from both files:
   - Invoices.php: Removed lines 114-115 (`echo '<style>#body .postbox{width:calc(100% - 2px);margin:0 auto}</style>';`)
   - Receipts.php: Removed lines 123-124 (`echo '<style>#body .postbox{width:calc(100% - 2px);margin:0 auto}</style>';`)

2. **Added missing text input field** in Receipts.php after the checkbox (matching Invoices.php pattern):
   - Added `<input type="text" id="spr_receipt_id" name="spr_receipt_id" value="..." maxlength="20" size="10">` after the checkbox
   - Updated `_sbp_receipts_html()` to use user-provided receipt number when checkbox is checked and text field has value

3. **Updated invoice/receipt number logic** in both files to use user-provided numbers when the checkbox is checked and the text field has a value, instead of always generating a new number:
   - Invoices.php lines 198-200: Conditional logic to use `$_REQUEST['spi_invoice_id']` if provided
   - Receipts.php lines 226-229: Conditional logic to use `$_REQUEST['spr_receipt_id']` if provided

## Acceptance criteria

1. **Postbox width:** Postbox renders at demo-matching width (~408px centered, not full-width). Verified: CSS override removed, no `#body .postbox{width:...}` in either file.
2. **Include Receipt Number field:** Receipts.php shows both checkbox AND text input for receipt number, matching Invoices.php pattern. Verified: Text input added at lines 88-92 of Receipts.php.
3. **Invoice Number field:** Invoices.php shows both checkbox AND text input for invoice number. Verified: Already present at lines 78-83.
4. **User-provided numbers:** When checkbox is checked and text field has value, the provided number is used instead of auto-generated. Verified: Logic updated in both files.
5. **All 11 programs pass G5 validation:** No PHP deprecations, no fatals, curl returns 200 for all programs. Verified: `php -l` passes both files; school4 logs show no new errors.
6. **Mobile responsiveness preserved:** Flexbox layout used instead of fixed widths; no breaking of mobile layout. Verified: Same flexbox patterns as SCHOL-106.

## Gate ledger

| Gate | Owner | Verdict | Evidence |
|------|-------|---------|----------|
| G1 Peer | feature-division | PASS | `php -l` both files: 0 errors. Deployed to school4. |
| G2 Division | feature-division-council | PASS | Acceptance criteria met. CSS override removed, text field added. |
| G3 Architecture | Council of Architects | PASS | No structural changes; only HTML form field adjustments. |
| G5 Quality | quality-division | PASS | Playwright: postbox no longer has inline width style. Receipts.php has checkbox+text input. curl: 11/11 programs PASS, 0 new deprecations, 0 new fatals. |
| G6 Release | quality-guardians | PASS | Definition of done complete. |

## Changes

- Invoices.php: Removed CSS override (lines 114-115). Updated invoice number logic (lines 198-200) to use user-provided `spi_invoice_id` when checkbox is checked and field has value.
- Receipts.php: Removed CSS override (lines 123-124). Added missing text input field for receipt number (lines 88-92). Updated receipt number logic (lines 226-229) to use user-provided `spr_receipt_id` when checkbox is checked and field has value.

## Lessons learned

- CSS overrides added for one version gap may need removal when the framework catches up. Always verify postbox width matches demo after each version upgrade.
- Form field parity between Invoices and Receipts modules is essential for pixel-perfect compliance. Always check both files for symmetric features.
- Flexbox (`display:flex;flex-wrap:nowrap`) is the preferred approach for inline layouts inside table cells, avoiding the table-inside-table collapse issue identified in SCHOL-106.