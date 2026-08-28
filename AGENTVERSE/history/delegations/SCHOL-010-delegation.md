# SCHOL-010 Delegation — Billing Elements Exact Clone

**Delegated by:** feature-division-council  
**Date:** 2026-08-16  
**Priority:** CRITICAL

---

## Assignment

| Specialist | Task | Deliverable | Deadline |
|------------|------|-------------|----------|
| **fullstack-engineer** | Fix implementation to match demo exactly | `/home/coder/premium-modules/Billing_Elements/` (corrected) | Immediate |
| **feature-tester** | G1 Peer Review | Code review notes, php -l clean, conventions check | After implementation |
| **feature-division-council** | G2 Division Review | All ACs met, scope contained | After G1 |
| **system-architect** | G3 Architecture | Design integrity, interface contracts, schema review | After G2 |
| **security-division-council** | G4 Security | XSS, injection, secrets, authz scan | After G3 |
| **quality-division-council** | G5 Quality | Live validation on school4 (KB-0016) | After G4 |
| **release-custodian** | G6 Release | Zip package, KB entry, MEMORY_INDEX update | After G5 |

---

## Required Fixes (fullstack-engineer)

### 1. Remove Extra Files
```bash
rm /home/coder/premium-modules/Billing_Elements/MyElements.php
rm /home/coder/premium-modules/Billing_Elements/Store.php
```

### 2. Fix Menu.php — Admin Only (match demo lines 384-397)
```php
// Admin only — no teacher/parent menus
$menu['Billing_Elements']['admin'] = [
    'title' => _('Billing Elements'),
    'default' => 'Billing_Elements/Elements.php',
    'Billing_Elements/Elements.php' => _('Elements'),
    'Billing_Elements/MonthlyElements.php' => _('Monthly Elements'),
    'Billing_Elements/MassAssignElements.php' => _('Mass Assign Elements'),
    'Billing_Elements/StudentElements.php' => _('Student Elements'),
    1 => _('Reports'),
    'Billing_Elements/CategoryBreakdown.php' => _('Category Breakdown'),
    'Billing_Elements/DailyTransactions.php' => _('Daily Transactions'),
];
// No teacher, no parent entries
$exceptions['Billing_Elements'] = [];
```

### 3. Fix Help_en.php
- Remove `Store.php` and `MyElements.php` entries
- Update 6 program helps to match **spec §3 exactly** (demo content)

### 4. Fix README.md
- Replace with **spec §6 exact content** (demo match)

### 5. Fix Database Schema (install.sql, install_mysql.sql, delete.sql)
Align with **spec §4** demo tables:
- `billing_elements` (categories) — NOT `billing_elements_categories`
- `billing_elements_items` (elements) — NOT `billing_elements`
- `student_billing_elements` (links)
- `billing_element_transactions` (transactions)
- Remove `billing_monthly_elements`, `billing_element_purchases`

### 6. Verify icon.png
- 64×64 RGBA, custom receipt/coin glyph

---

## Acceptance Criteria (from spec §8)
- [ ] AC1: Exact program file names match demo (6 files)
- [ ] AC2: Menu.php creates own "Billing Elements" top-level menu with icon.png (Admin only)
- [ ] AC3: Help_en.php matches demo help content exactly (6 programs)
- [ ] AC4: icon.png = 64×64 RGBA, custom glyph (receipt/coin)
- [ ] AC5: All 6 programs functional per demo
- [ ] AC6: Zip package valid (single root, zipdetails 0 warnings)
- [ ] AC7: Live validation on school4 passes KB-0016 guardrail

---

## Evidence Required at Each Gate
- **G1:** Code review notes, `php -l` clean on all files, conventions check
- **G2:** All ACs met, scope contained
- **G3:** Design integrity, interface contracts, schema review
- **G4:** Security scan (XSS, injection, secrets, authz)
- **G5:** Live validation on school4 per KB-0016 guardrail
- **G6:** Zip package valid (zipdetails 0 warnings), KB entry, MEMORY_INDEX update

---

## References
- **Spec:** `/home/coder/project/tmp/opencode/spec_schol010.md`
- **Demo evidence:** `/tmp/demo_modules_full.html` (lines 384-397, 428-462), `/tmp/demo_readmes.json["Billing_Elements"]`
- **KB-0016:** Live validation guardrail
- **KB-0020:** Premium module structural reference
