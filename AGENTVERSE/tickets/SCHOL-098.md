# SCHOL-098 — Execute Module/Plugin Cloning Program (All 54+ Modules from SCHOL-009)

- **Status:** IN PROGRESS
- **Type:** feature (massive implementation)
- **Priority:** CRITICAL
- **Product:** ScholaPro modules & plugins — Complete module catalog matching rosariosis.org demo
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Request:** Execute the actual implementation of all 54+ modules/plugins that were delegated in tickets SCHOL-010 through SCHOL-096. The historical session `ses_ffeb3c23fffeGmhYMRQxpNvGMS` created the tickets and delegation chains but the implementation work was never done. No zip files exist yet.

## Progress Summary (2026-08-16)

### ✅ Phase 1: Core Premium (CRITICAL) — IMPLEMENTATION COMPLETE, READY FOR G1

| Ticket | Module | Status | Zip Package | Gate |
|--------|--------|--------|-------------|------|
| SCHOL-010 | Billing_Elements | ✅ Implementation complete | `/home/coder/premium-modules/Billing_Elements.zip` (15 files, 27924 bytes, zipdetails 0 warnings) | G1 READY |
| SCHOL-011 | Student_Billing_Premium | ✅ Implementation complete | `/home/coder/premium-modules/Student_Billing_Premium.zip` (15 files, 31881 bytes, zipdetails 0 warnings) | G1 READY |

**Both critical modules:**
- Exact program file names match demo
- Menu.php integration matches demo (Admin only for Billing_Elements; merges into Student_Billing for SBP)
- Help_en.php matches demo content exactly
- README.md matches demo exactly (+ Gateway Difference note for SBP)
- Database schema aligned with demo (idempotent install.sql/install_mysql.sql/delete.sql)
- icon.png: 64×64 RGBA, custom glyphs
- php -l clean on all files
- Zip packages validated with zipdetails (0 warnings)

### Phase 2: Other Premium Modules (12) — PENDING
### Phase 3: Free Modules (40) — PENDING
### Phase 4: Plugins (6) — PENDING

### Phase 2: Other Premium Modules (12)
| Ticket | Module | Type | Priority | Target Path |
|--------|--------|------|----------|-------------|
| SCHOL-012 | Accounting_Premium | Premium | HIGH | `/home/coder/premium-modules/Accounting_Premium/` |
| SCHOL-013 | Food_Service_Premium | Premium | HIGH | `/home/coder/premium-modules/Food_Service_Premium/` |
| SCHOL-014 | Class_Diary_Premium | Premium | HIGH | `/home/coder/premium-modules/Class_Diary_Premium/` |
| SCHOL-015 | Entry_Exit_Premium | Premium | HIGH | `/home/coder/premium-modules/Entry_Exit_Premium/` |
| SCHOL-016 | Hostel_Premium | Premium | HIGH | `/home/coder/premium-modules/Hostel_Premium/` |
| SCHOL-017 | Lesson_Plan_Premium | Premium | HIGH | `/home/coder/premium-modules/Lesson_Plan_Premium/` |
| SCHOL-018 | Library_Premium | Premium | HIGH | `/home/coder/premium-modules/Library_Premium/` |
| SCHOL-019 | Meeting_Premium | Premium | HIGH | `/home/coder/premium-modules/Meeting_Premium/` |
| SCHOL-020 | Messaging_Premium | Premium | HIGH | `/home/coder/premium-modules/Messaging_Premium/` |
| SCHOL-021 | Quiz_Premium | Premium | HIGH | `/home/coder/premium-modules/Quiz_Premium/` |
| SCHOL-022 | SMS_Premium | Premium | HIGH | `/home/coder/premium-modules/SMS_Premium/` |
| SCHOL-023 | Students_Import (Student_Import_Premium) | Premium | HIGH | `/home/coder/premium-modules/Students_Import/` |

### Phase 3: Free Modules (40)
| Ticket | Module | Type | Priority | Target Path |
|--------|--------|------|----------|-------------|
| SCHOL-024 | Certificate | Free | HIGH | `/home/coder/premium-modules/Certificate/` |
| SCHOL-025 | Attendance_Excel_Sheet | Free | HIGH | `/home/coder/premium-modules/Attendance_Excel_Sheet/` |
| SCHOL-026 | Email | Free | HIGH | `/home/coder/premium-modules/Email/` |
| SCHOL-027 | Email_Alerts | Free | HIGH | `/home/coder/premium-modules/Email_Alerts/` |
| SCHOL-028 | Email_Log | Free | HIGH | `/home/coder/premium-modules/Email_Log/` |
| SCHOL-029 | Email_Parents | Free | HIGH | `/home/coder/premium-modules/Email_Parents/` |
| SCHOL-030 | Email_Students | Free | HIGH | `/home/coder/premium-modules/Email_Students/` |
| SCHOL-031 | Entry_Exit | Free | HIGH | `/home/coder/premium-modules/Entry_Exit/` |
| SCHOL-032 | Hostel | Free | HIGH | `/home/coder/premium-modules/Hostel/` |
| SCHOL-033 | Student_ID_Card | Free | HIGH | `/home/coder/premium-modules/Student_ID_Card/` |
| SCHOL-034 | Student_Pickup | Free | HIGH | `/home/coder/premium-modules/Student_Pickup/` |
| SCHOL-035 | Timetable_Import | Free | HIGH | `/home/coder/premium-modules/Timetable_Import/` |
| SCHOL-036 | Staff_Absences | Free | HIGH | `/home/coder/premium-modules/Staff_Absences/` |
| SCHOL-037 | Staff_Parents_Import | Free | HIGH | `/home/coder/premium-modules/Staff_Parents_Import/` |
| SCHOL-038 | Audit | Free | MEDIUM | `/home/coder/premium-modules/Audit/` |
| SCHOL-039 | Backup | Free | MEDIUM | `/home/coder/premium-modules/Backup/` |
| SCHOL-040 | Class_Diary | Free | MEDIUM | `/home/coder/premium-modules/Class_Diary/` |
| SCHOL-041 | Dashboards | Free | MEDIUM | `/home/coder/premium-modules/Dashboards/` |
| SCHOL-042 | Ecuador_Retake_Exam | Free | MEDIUM | `/home/coder/premium-modules/Ecuador_Retake_Exam/` |
| SCHOL-043 | Embedded_Resources | Free | MEDIUM | `/home/coder/premium-modules/Embedded_Resources/` |
| SCHOL-044 | Grades_Import | Free | MEDIUM | `/home/coder/premium-modules/Grades_Import/` |
| SCHOL-045 | Graduation_Paths | Free | MEDIUM | `/home/coder/premium-modules/Graduation_Paths/` |
| SCHOL-046 | Human_Resources | Free | MEDIUM | `/home/coder/premium-modules/Human_Resources/` |
| SCHOL-047 | Jitsi_Meet | Free | MEDIUM | `/home/coder/premium-modules/Jitsi_Meet/` |
| SCHOL-048 | Lesson_Plan | Free | MEDIUM | `/home/coder/premium-modules/Lesson_Plan/` |
| SCHOL-049 | Library | Free | MEDIUM | `/home/coder/premium-modules/Library/` |
| SCHOL-050 | Marking_Period_Groups | Free | MEDIUM | `/home/coder/premium-modules/Marking_Period_Groups/` |
| SCHOL-051 | Medical_Report | Free | MEDIUM | `/home/coder/premium-modules/Medical_Report/` |
| SCHOL-052 | Meeting | Free | MEDIUM | `/home/coder/premium-modules/Meeting/` |
| SCHOL-053 | NFC_QR_Actions | Free | MEDIUM | `/home/coder/premium-modules/NFC_QR_Actions/` |
| SCHOL-054 | PDF_Archive | Free | MEDIUM | `/home/coder/premium-modules/PDF_Archive/` |
| SCHOL-055 | Pedagogical_Plan | Free | MEDIUM | `/home/coder/premium-modules/Pedagogical_Plan/` |
| SCHOL-056 | Quiz | Free | MEDIUM | `/home/coder/premium-modules/Quiz/` |
| SCHOL-057 | Reports | Free | MEDIUM | `/home/coder/premium-modules/Reports/` |
| SCHOL-058 | School_Inventory | Free | MEDIUM | `/home/coder/premium-modules/School_Inventory/` |
| SCHOL-059 | Semester_Rollover | Free | MEDIUM | `/home/coder/premium-modules/Semester_Rollover/` |
| SCHOL-060 | SMS | Free | MEDIUM | `/home/coder/premium-modules/SMS/` |
| SCHOL-061 | TTHotel_Smart_Locks | Free | MEDIUM | `/home/coder/premium-modules/TTHotel_Smart_Locks/` |
| SCHOL-062 | VLaby | Free | MEDIUM | `/home/coder/premium-modules/VLaby/` |

### Phase 4: Plugins (6)
| Ticket | Plugin | Type | Priority | Target Path |
|--------|--------|------|----------|-------------|
| SCHOL-063 | Content_Security_Policy | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Content_Security_Policy/` |
| SCHOL-064 | Moodle | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Moodle/` |
| SCHOL-065 | Paypal_Registration | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Paypal_Registration/` |
| SCHOL-066 | Stripe_Registration | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Stripe_Registration/` |
| SCHOL-067 | Public_Pages | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Public_Pages/` |
| SCHOL-068 | Templates | Plugin | HIGH | `/home/coder/project/scholapro/plugins/Templates/` |
| SCHOL-069 | Absent_for_the_Day_on_First_Absence | Plugin | MEDIUM | `/home/coder/project/scholapro/plugins/Absent_for_the_Day_on_First_Absence/` |
| SCHOL-072 | Automatic_Attendance | Plugin | MEDIUM | `/home/coder/project/scholapro/plugins/Automatic_Attendance/` |

## Acceptance Criteria (per module/plugin)
- **AC1:** Module/plugin structure matches demo exactly (file tree, naming, Menu.php integration)
- **AC2:** All programs functional per demo (verified via demo site exploration evidence)
- **AC3:** Help_en.php matches demo help content (KB-0018 per-user-type standard)
- **AC4:** icon.png matches demo (64×64 RGBA, module-specific glyph)
- **AC5:** install.sql / install_mysql.sql / delete.sql idempotent (KB-0010, KB-0013)
- **AC6:** profile_exceptions grants for all programs per role (KB-0016)
- **AC7:** Zip package valid (single root folder, zipdetails 0 warnings, KB-0013)
- **AC8:** Live validation on school4 passes KB-0016 guardrail
- **AC9:** README.md matches demo (features, install, credits, license, test files)
- **AC10:** Gateway/localization differences documented (e.g., Monnify vs PayPal/Stripe)

## Special Requirements
- **Billing Elements & Student Billing Premium:** Must be RECREATED from scratch to match demo EXACTLY (program names, structure, help content) — specs at `/home/coder/project/tmp/opencode/spec_schol010.md` and `spec_schol011.md`
- **Student Billing Premium:** Use demo program names (StudentFeesMonthly.php, Invoices.php, Receipts.php, PaymentsImport.php, PaypalConfiguration.php) — gateway difference (PayPal/Stripe vs Monnify) documented
- **Pay Button:** Integrate into core StudentPayments.php via hook (student_payments_header) per demo
- **All modules:** Exact clone — no truncation, full feature parity

## Delegation Strategy
- **Owner:** Feature Division (feature-division-council) for modules; Integration Division (integration-division-council) for plugins
- **Specialists per module:**
  - **feature-planner** → Create/update spec from demo evidence (if not already done)
  - **fullstack-engineer** → Implement exact clone at target path
  - **feature-tester** → G1 peer review
  - **feature-division-council** / **integration-division-council** → G2 division review
  - **system-architect** → G3 architecture
  - **security-division-council** → G4 security
  - **quality-division-council** → G5 live validation on school4 (KB-0016)
  - **release-custodian** → G6 release (zip, KB, MEMORY_INDEX)

## Gate Chain
G1→G2→G3→G4→G5→G6 (full chain for all — payment/financial modules no fast-path)

## Evidence References
- Demo evidence: `/tmp/demo_modules_full.html`, `/tmp/demo_readmes.json`, `/tmp/explore_demo.js`, `/tmp/get_help.js`
- Specs: `/home/coder/project/tmp/opencode/spec_schol010.md`, `spec_schol011.md`, `/home/coder/project/.opencode/spec_schol064.md`
- Individual tickets: SCHOL-010 through SCHOL-096 (all OPEN with delegation chains)
- KB-0016 (live validation guardrail), KB-0018 (help content standard), KB-0020 (premium module structure)