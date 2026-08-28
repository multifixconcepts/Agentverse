# SCHOL-011 Delegation — Student Billing Premium Exact Clone

**Delegated by:** feature-division-council  
**Date:** 2026-08-16  
**Priority:** CRITICAL

---

## Assignment

| Specialist | Task | Deliverable | Deadline |
|------------|------|-------------|----------|
| **fullstack-engineer** | Fix implementation to match demo exactly | `/home/coder/premium-modules/Student_Billing_Premium/` (corrected) | Immediate |
| **feature-tester** | G1 Peer Review | Code review notes, php -l clean, conventions check | After implementation |
| **feature-division-council** | G2 Division Review | All ACs met, scope contained | After G1 |
| **system-architect** | G3 Architecture | Menu merge, hook integration, schema review | After G2 |
| **security-division-council** | G4 Security | Payment gateway, webhooks, XSS/injection | After G3 |
| **quality-division-council** | G5 Quality | Live validation on school4 (KB-0016) | After G4 |
| **release-custodian** | G6 Release | Zip package, KB entry, MEMORY_INDEX update | After G5 |

---

## Required Fixes (fullstack-engineer)

### 1. Rename Program Files (match demo exactly)
```bash
cd /home/coder/premium-modules/Student_Billing_Premium/
mv Configuration.php PaypalConfiguration.php
mv MonthlyFees.php StudentFeesMonthly.php
mv PrintInvoices.php Invoices.php
mv PrintReceipts.php Receipts.php
```

### 2. Remove Extra Files
```bash
rm Pay.php          # Demo uses hook on core StudentPayments.php
rm Webhook.php      # Not in demo structure
```

### 3. Fix Menu.php — Update Program Names
```php
// Admin
$menu['Student_Billing']['admin'] = issetVal($menu['Student_Billing']['admin'], []) + [
    2 => _('Student Billing'),
    'Student_Billing_Premium/PaypalConfiguration.php' => _('Configuration'),
    'Student_Billing_Premium/StudentFeesMonthly.php' => _('Monthly Fees'),
    'Student_Billing_Premium/PaymentsImport.php' => _('Payments Import'),
    'Student_Billing_Premium/Invoices.php' => _('Print Invoices'),
    'Student_Billing_Premium/Receipts.php' => _('Print Receipts'),
];

// Teacher
$menu['Student_Billing']['teacher'] = issetVal($menu['Student_Billing']['teacher'], []) + [
    2 => _('Student Billing'),
    'Student_Billing_Premium/Invoices.php' => _('Print Invoices'),
    'Student_Billing_Premium/Receipts.php' => _('Print Receipts'),
];

// Parent
$menu['Student_Billing']['parent'] = issetVal($menu['Student_Billing']['parent'], []) + [
    2 => _('Student Billing'),
    'Student_Billing_Premium/Invoices.php' => _('Print Invoices'),
    'Student_Billing_Premium/Receipts.php' => _('Print Receipts'),
];

// Exceptions
$exceptions['Student_Billing'] = issetVal($exceptions['Student_Billing'], []) + [
    'Student_Billing_Premium/PaypalConfiguration.php' => true,
];
```

### 4. Fix Help_en.php
- Update all keys to match renamed files
- Remove `Pay.php` entry
- Match **spec §6 demo help content exactly** for 5 programs

### 5. Fix README.md
- Replace with **spec §9 exact demo content** + Gateway Difference note (spec §9)

### 6. Fix Database Schema (install.sql, install_mysql.sql, delete.sql)
Align with **spec §7** demo tables:
- `billing_monthly_fees` ✓ (but check columns match)
- `billing_paypal_transactions` — **rename from** `billing_monnify_transactions` (keep table name for demo parity, implement Monnify inside)
- `sbp_webhook_log` ✓
- **ADD:** `billing_invoice_numbers` (auto-increment invoice/receipt numbers)
- **ADD:** `billing_payment_reminders` (payment reminder feature)
- Update `profile_exceptions` inserts to use new program names

### 7. Fix functions.php — Add Pay Button Hook
```php
// In functions.php
add_action('student_payments_header', 'sbp_add_pay_button');

function sbp_add_pay_button() {
    // Render Pay button that opens payment modal/gateway
    // Only show if PayPal/Stripe (Monnify) is configured
}
```

### 8. Verify icon.png
- 64×64 RGBA, custom payment glyph

---

## Acceptance Criteria (from spec §10)
- [ ] AC1: Exact program file names match demo (5 files)
- [ ] AC2: Menu.php merges into Student_Billing (modcat "Student_Billing") with correct role access
- [ ] AC3: Pay button on core Student Billing > Payments via hook (student_payments_header)
- [ ] AC4: Help_en.php matches demo help content exactly (5 programs)
- [ ] AC5: icon.png = 64×64 RGBA, custom glyph
- [ ] AC6: Zip package valid (single root, zipdetails 0 warnings)
- [ ] AC7: Live validation on school4 passes KB-0016 guardrail
- [ ] AC8: Gateway difference (Monnify vs PayPal/Stripe) documented in README

---

## Evidence Required at Each Gate
- **G1:** Code review notes, `php -l` clean on all files, conventions check
- **G2:** All ACs met, scope contained
- **G3:** Design integrity, interface contracts, schema review
- **G4:** Security scan (XSS, injection, secrets, authz, payment gateway)
- **G5:** Live validation on school4 per KB-0016 guardrail
- **G6:** Zip package valid (zipdetails 0 warnings), KB entry, MEMORY_INDEX update

---

## References
- **Spec:** `/home/coder/project/tmp/opencode/spec_schol011.md`
- **Demo evidence:** `/tmp/demo_modules_full.html` (lines 337-341, 462-511), `/tmp/demo_readmes.json["Student_Billing_Premium"]`
- **KB-0016:** Live validation guardrail
- **KB-0020:** Premium module structural reference
