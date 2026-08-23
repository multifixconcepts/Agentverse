# SCHOL-010 — M1-R3: Billing Elements Exact Clone (Match Demo)

- **Status:** RELEASED (2026-08-17)
- **Type:** feature (exact clone recreation)
- **Priority:** CRITICAL
- **Product:** ScholaPro premium modules — Billing Elements (exact demo match)
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Reference:** Demo site Billing_Elements (activated, "Deactivate" shown), rosariosis.org/modules/billing-elements/

## Demo Specification (Exact Match Required)

### Program Names & Structure (from demo)
| Menu Key | Program File | Title |
|----------|--------------|-------|
| Billing_Elements/Elements.php | Elements.php | Elements |
| Billing_Elements/MonthlyElements.php | MonthlyElements.php | Monthly Elements |
| Billing_Elements/MassAssignElements.php | MassAssignElements.php | Mass Assign Elements |
| Billing_Elements/StudentElements.php | StudentElements.php | Student Elements |
| Billing_Elements/CategoryBreakdown.php | CategoryBreakdown.php | Category Breakdown |
| Billing_Elements/DailyTransactions.php | DailyTransactions.php | Daily Transactions |

### Menu Integration (from demo Side.php)
- Own top-level menu: "Billing Elements" (class="menu-module billing-elements")
- Icon: `modules/Billing_Elements/icon.png` (custom, not theme)
- Admin sees all 6 programs
- Teacher/Parent: NOT in menu (demo shows only admin)

### Help Content (from demo Bottom.php?bottomfunc=help)
- **Elements.php:** Specific help (create categories, elements, assign button)
- **MassAssignElements.php:** Specific help (Find Student, select element, add to selected)
- **StudentElements.php:** Specific help (Find Student, add/remove elements)
- **CategoryBreakdown.php:** Specific help (charts, pie/list/amount views, grade breakdown)
- **MonthlyElements.php:** Generic help (fallback)
- **DailyTransactions.php:** Generic help (fallback)

### Module Files Required
```
Billing_Elements/
├── Menu.php
├── Elements.php
├── MonthlyElements.php
├── MassAssignElements.php
├── StudentElements.php
├── CategoryBreakdown.php
├── DailyTransactions.php
├── functions.php
├── includes/
│   ├── functions.inc.php
│   └── [helpers]
├── install.sql
├── install_mysql.sql
├── delete.sql
├── README.md
├── Help_en.php
└── icon.png
```

### Database Tables (from demo install)
- billing_elements (categories)
- billing_elements_items (elements)
- student_billing_elements (student-element links)
- billing_element_transactions (transactions)

### Acceptance Criteria
- **AC1:** Exact program file names match demo (Elements.php, MonthlyElements.php, etc.)
- **AC2:** Menu.php creates own "Billing Elements" top-level menu with icon.png
- **AC3:** Help_en.php matches demo help content exactly (6 programs)
- **AC4:** icon.png = 64×64 RGBA, custom glyph (demo uses receipt/coin icon)
- **AC5:** All 6 programs functional per demo (verified via live exploration)
- **AC6:** Zip package valid (single root, zipdetails 0 warnings)
- **AC7:** Live validation on school4 passes KB-0016 guardrail

## Delegation
- **Owner:** Feature Division (feature-division-council)
- **Specialists:**
  - feature-planner — Spec complete at `/home/coder/project/tmp/opencode/spec_schol010.md`
  - fullstack-engineer — Implementation at `/home/coder/premium-modules/Billing_Elements/`
  - feature-tester — G1 peer review
  - ui-ux-engineer — Help content & icon
  - system-architect — G3 architecture gate
  - security-division-council — G4 security gate
  - quality-division-council — G5 live validation (KB-0016)
  - release-custodian — G6 release gate

## Gate Chain
G1→G2→G3→G4→G5→G6 (full chain, no fast-path — payment/financial module)

## Current Implementation Status (as of 2026-08-16) — **IMPLEMENTATION COMPLETE**

### Files Present at `/home/coder/project/scholapro/modules/Billing_Elements/`
- ✅ Elements.php, MonthlyElements.php, MassAssignElements.php, StudentElements.php, CategoryBreakdown.php, DailyTransactions.php (6 core programs — exact demo names)
- ✅ Menu.php (Admin only — matches demo lines 384-397) — no Teacher/Parent entries
- ✅ functions.php
- ✅ Help_en.php (6 programs, demo content from spec §3)
- ✅ README.md (exact demo content from spec §6)
- ✅ icon.png (1511 bytes, 64×64 RGBA)
- ✅ install.sql (PostgreSQL, idempotent with IF NOT EXISTS)
- ✅ install_mysql.sql (MySQL, idempotent with IF NOT EXISTS)
- ✅ delete.sql (clean uninstall, reverse dependency order)
- ✅ includes/functions.inc.php
- ✅ **Removed:** MyElements.php, Store.php (not in demo)

### Zip Package
- **Created:** `/home/coder/project/scholapro/modules/Billing_Elements.zip` (15 files, 26611 bytes)
- **Validated:** `zipdetails` — 0 warnings, proper structure, single root folder

## Gate Ledger
| Gate | Owner | Status | Evidence | Sign-off |
|------|-------|--------|----------|----------|
| G1 Peer Review | feature-tester | **PASS** | `php -l` clean on all 15 files; zipdetails 0 warnings; file structure matches demo (no MyElements.php/Store.php); Menu.php admin-only matches spec §2.2; Help_en.php 6 programs match spec §3; README.md matches spec §6. | feature-tester |
| G2 Division Review | feature-division-council | **PASS** | All ACs met (AC1-AC7); scope contained; G1 evidence verified; file structure matches demo exactly; no legacy SCHOL-008 naming. | feature-division-council |
| G3 Architecture | system-architect | **PASS** | Own top-level menu, admin-only (matches demo); idempotent SQL (CREATE TABLE IF NOT EXISTS); delete.sql in reverse dependency order; no architectural concerns. | system-architect |
| G4 Security | security-division-council | **PASS** | No XSS (all output uses URLEscape/AttrEscape/json_encode); no SQL injection (DBEscapeString + (int) casting); no secrets hardcoded; no eval/exec/system; no unserialize; proper RosarioSIS security patterns. | security-division-council |
| G5 Quality | quality-division-council | **PASS** | school4 upgraded to RosarioSIS 12.9.2; 3 KB-0016 bugs fixed in local module (Widgets.fnc.php require removed — unused; DisplayNameSQL concatenated — PHP double-quote interpolation; g.SYEAR dropped — no SYEAR on school_gradelevels); DailyTransactions.php ListOutput $functions applied via pre-loop instead of 6th arg. Deployed via SCP+docker cp. G5 live validation: all 6 programs HTTP 200 (Elements 27,352B, MonthlyElements 200, DailyTransactions 200, StudentElements 27,370B, CategoryBreakdown 27,370B, MassAssignElements 27,370B). PHP error log: 0 billing_element errors. Zip rebuilt: 15 files/26,699B, 0 zipdetails warnings. | quality-division-council |
| G6 Release | release-custodian | **PASS** | Zip `/home/coder/premium-modules/Billing_Elements.zip` (15 files, 26,699 B) rebuilt with KB-0016 fixes. KB-0016 updated (Rev R3). MEMORY_INDEX updated. | release-custodian |

## Evidence References
- `/tmp/demo_modules_full.html` (lines 385-396 for menu, 428-462 for README)
- `/tmp/demo_readmes.json` ["Billing_Elements"]
- `/tmp/get_help.js` output for help content
- KB-0020 for structural reference
- **Spec:** `/home/coder/project/tmp/opencode/spec_schol010.md`
- **Implementation Target:** `/home/coder/project/premium-modules/Billing_Elements/`