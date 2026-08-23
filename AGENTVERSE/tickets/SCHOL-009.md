# SCHOL-009 — Complete RosarioSIS Module Replication Program (All 54 Non-Slovenian Modules)

- **Status:** OPEN
- **Type:** feature (massive module replication program)
- **Priority:** CRITICAL
- **Product:** ScholaPro premium modules — Complete module catalog matching rosariosis.org demo
- **Opened:** 2026-08-16
- **Request:** Using rosariosis.org demo credentials, generate exact clones of ALL modules (except Slovenian) for zip files. Recreate Billing Elements and Student Billing Premium to match exactly with developer's demo. Include Certificate, Attendance Excel Sheet, Email, and all others. No truncation - keep count from top to bottom of entire list.

## Source Reference
- **Demo site:** https://www.rosariosis.org/demonstration/ (admin/admin, teacher/teacher, student/student, parent/parent)
- **Module pages:** https://www.rosariosis.org/modules/, https://www.rosariosis.org/add-ons/
- **Demo state:** Latest RosarioSIS development (Student Billing Premium v16.5, Aug 2026)
- **Evidence captured:** `/tmp/demo_modules_full.html`, `/tmp/demo_readmes.json`, `/tmp/explore_demo.js`, `/tmp/get_help.js`

## Module Inventory (54 modules, excluding 4 Slovenian)

### Premium Modules (14) — Activated on Demo
| # | Module | Version | Type | Programs | Menu Integration |
|---|--------|---------|------|----------|------------------|
| 1 | **Billing_Elements** | — | Premium | 6 (Elements, MonthlyElements, MassAssignElements, StudentElements, CategoryBreakdown, DailyTransactions) | Own menu "Billing Elements" |
| 2 | **Student_Billing_Premium** | 16.5 (Aug 2026) | Premium | 5 + Pay button (StudentFeesMonthly, Invoices, Receipts, PaymentsImport, PaypalConfiguration) | Merges into Student_Billing |
| 3 | **Accounting_Premium** | 6.1 (Jul 2026) | Premium | 3 additional | Merges into Accounting |
| 4 | **Food_Service_Premium** | — | Premium | 7 (Reservations, etc.) | Merges into Food_Service (requires free) |
| 5 | **Class_Diary_Premium** | — | Premium | Extends free Class_Diary | Merges into Class_Diary (requires free) |
| 6 | **Entry_Exit_Premium** | — | Premium | Extends free Entry_Exit | Merges into Entry_Exit (requires free) |
| 7 | **Hostel_Premium** | — | Premium | Extends free Hostel | Merges into Hostel (requires free) |
| 8 | **Lesson_Plan_Premium** | — | Premium | Extends free Lesson_Plan | Merges into Lesson_Plan |
| 9 | **Library_Premium** | — | Premium | Loans (student/staff) | Merges into Library (requires free) |
| 10 | **Meeting_Premium** | — | Premium | Extends free Meeting | Merges into Meeting |
| 11 | **Messaging_Premium** | — | Premium | Extends free Messaging | Merges into Messaging |
| 12 | **Quiz_Premium** | — | Premium | Extends free Quiz | Merges into Quiz (requires free) |
| 13 | **SMS_Premium** | — | Premium | Premium gateways | Merges into SMS |
| 14 | **Students_Import** (Student_Import_Premium) | 14.2 (Aug 2026) | Premium | Import Students (CSV/Excel) | Adds to Students menu |

### Free Modules on Demo (40)
| # | Module | Version | Type | Programs | Menu Integration |
|---|--------|---------|------|----------|------------------|
| 15 | **Certificate** | — | Free | Print certificates | Own menu |
| 16 | **Attendance_Excel_Sheet** | 2.6 (Sep 2025) | Free | 2 (Print Attendance Sheets week/month) | Adds to Attendance |
| 17 | **Audit** | — | Free | Audit trails | Own menu |
| 18 | **Backup** | — | Free | Database backup | Own menu |
| 19 | **Class_Diary** | 7.7 (Sep 2025) | Free | Realizations, Meetings, Cooperation, PDF, Config, Abbreviations, On Call Log | Own menu "Slovenian Class Diary" |
| 20 | **Dashboards** | — | Free | Dashboards | Own menu |
| 21 | **Ecuador_Retake_Exam** | — | Free | Retake exams | Own menu |
| 22 | **Email** | — | Free | Email system | Own menu |
| 23 | **Email_Alerts** | — | Free | Email alerts | Own menu |
| 24 | **Email_Log** | — | Free | Email logging | Own menu |
| 25 | **Email_Parents** | — | Free | Email parents | Own menu |
| 26 | **Email_Students** | — | Free | Email students | Own menu |
| 27 | **Embedded_Resources** | — | Free | Embedded resources | Own menu |
| 28 | **Entry_Exit** | — | Free | Records, Checkpoints, Configuration | Own menu "Entry and Exit" |
| 29 | **Grades_Import** | — | Free | Import grades | Adds to Grades |
| 30 | **Graduation_Paths** | — | Free | Graduation paths | Own menu |
| 31 | **Hostel** | — | Free | Hostel management | Own menu "Hostel" |
| 32 | **Human_Resources** | — | Free | HR management | Own menu |
| 33 | **Jitsi_Meet** | — | Free | Video conferencing | Own menu |
| 34 | **Lesson_Plan** | — | Free | Lesson plans | Adds to Scheduling |
| 35 | **Library** | — | Free | Library management | Own menu "Library" |
| 36 | **Marking_Period_Groups** | — | Free | Marking period groups | Own menu |
| 37 | **Medical_Report** | — | Free | Medical reports | Own menu |
| 38 | **Meeting** | — | Free | Meetings | Own menu "Meeting" |
| 39 | **NFC_QR_Actions** | — | Free | NFC/QR actions | Own menu |
| 40 | **PDF_Archive** | — | Free | PDF archiving | Own menu |
| 41 | **Pedagogical_Plan** | — | Free | Pedagogical plans | Own menu |
| 42 | **Quiz** | — | Free | Quizzes | Own menu "Quiz" |
| 43 | **Reports** | — | Free | Reports | Own menu |
| 44 | **School_Inventory** | — | Free | School inventory | Own menu |
| 45 | **Semester_Rollover** | 10.3 (Apr 2026) | Free | Semester rollover | Own menu |
| 46 | **SMS** | — | Free | SMS messaging | Own menu |
| 47 | **Staff_Absences** | 12.5 (Aug 2026) | Free | Staff absences | Own menu |
| 48 | **Staff_Parents_Import** | 13.0 (Aug 2026) | Free | Import staff/parents | Adds to Users |
| 49 | **Student_ID_Card** | 3.0 (Aug 2026) | Free | Print ID cards | Own menu |
| 50 | **Student_Pickup** | 1.4 (Aug 2025) | Free | QR pickup/dropoff | Adds to Students |
| 51 | **Timetable_Import** | 12.4 (Apr 2026) | Free | Import timetable (FET) | Adds to Scheduling |
| 52 | **TTHotel_Smart_Locks** | 2.1 (Apr 2026) | Free | Smart locks (requires Entry_Exit) | Adds to Entry_Exit |
| 53 | **VLaby** | 1.2 (Aug 2025) | Free | Virtual lab experiments | Adds to Resources |
| 54 | **Reports** | — | Free | Reports | Own menu |

### Excluded (4 Slovenian Modules)
- Slovenian_Attendance_Excel_Sheet
- Slovenian_Class_Diary
- Slovenian_Discipline
- Slovenian_Grades_2

## Acceptance Criteria (per module)
- **AC1:** Module structure matches demo exactly (file tree, naming, Menu.php integration)
- **AC2:** All programs functional per demo (verified via demo site exploration)
- **AC3:** Help_en.php matches demo help content (KB-0018 per-user-type standard)
- **AC4:** icon.png matches demo (64×64 RGBA, module-specific glyph)
- **AC5:** install.sql / install_mysql.sql / delete.sql idempotent (KB-0010, KB-0013)
- **AC6:** profile_exceptions grants for all programs per role (KB-0016)
- **AC7:** Zip package valid (single root folder, zipdetails 0 warnings, KB-0013)
- **AC8:** Live validation on school4 (KB-0016 guardrail: files→config→grants→menu→programs→help→CRUD)
- **AC9:** README.md matches demo (features, install, credits, license, test files)
- **AC10:** Gateway/localization differences documented (e.g., Monnify vs PayPal/Stripe)

## Special Requirements
- **Billing Elements & Student Billing Premium:** Must be RECREATED from scratch to match demo EXACTLY (program names, structure, help content)
- **Student Billing Premium:** Use demo program names (StudentFeesMonthly.php, Invoices.php, Receipts.php, PaymentsImport.php, PaypalConfiguration.php) — gateway difference (PayPal/Stripe vs Monnify) documented
- **Pay Button:** Integrate into core StudentPayments.php via hook (student_payments_header) per demo
- **All modules:** Exact clone — no truncation, full feature parity

## Delegation Strategy
- **Phase 1 (Core Premium):** Billing_Elements, Student_Billing_Premium (recreate exact)
- **Phase 2 (Other Premium):** Accounting_Premium, Food_Service_Premium, Class_Diary_Premium, Entry_Exit_Premium, Hostel_Premium, Lesson_Plan_Premium, Library_Premium, Meeting_Premium, Messaging_Premium, Quiz_Premium, SMS_Premium, Students_Import
- **Phase 3 (Free Modules - High Priority):** Certificate, Attendance_Excel_Sheet, Email, Email_Alerts, Email_Log, Email_Parents, Email_Students, Entry_Exit, Hostel, Student_ID_Card, Student_Pickup, Timetable_Import, Staff_Absences, Staff_Parents_Import
- **Phase 4 (Free Modules - Standard):** Audit, Backup, Class_Diary, Dashboards, Ecuador_Retake_Exam, Embedded_Resources, Grades_Import, Graduation_Paths, Human_Resources, Jitsi_Meet, Lesson_Plan, Library, Marking_Period_Groups, Medical_Report, Meeting, NFC_QR_Actions, PDF_Archive, Pedagogical_Plan, Quiz, Reports, School_Inventory, Semester_Rollover, SMS, Staff_Absences, Staff_Parents_Import, TTHotel_Smart_Locks, VLaby

## Gate Chain (per module)
G1: Peer review (feature-tester) → G2: Division review (feature-division-council) → G3: Architecture (system-architect) → G4: Security (security-division-council) → G5: Quality live validation (quality-division-council) → G6: Release (release-custodian)

## Evidence Files
- `/tmp/demo_modules_full.html` — Full modules configuration page
- `/tmp/demo_readmes.json` — All 58 README contents
- `/tmp/explore_demo.js` — Playwright exploration script
- `/tmp/get_help.js` — Playwright help extraction script
- KB-0020 — Demo site inventory reference

## Ticket Status
- **SCHOL-009** (this ticket): Master tracking
- **SCHOL-010:** Billing Elements Exact Clone (CRITICAL)
- **SCHOL-011:** Student Billing Premium Exact Clone (CRITICAL)
- **SCHOL-012:** Accounting_Premium
- **SCHOL-013:** Food_Service_Premium
- **SCHOL-014:** Class_Diary_Premium
- **SCHOL-015:** Entry_Exit_Premium
- **SCHOL-016:** Hostel_Premium
- **SCHOL-017:** Lesson_Plan_Premium
- **SCHOL-018:** Library_Premium
- **SCHOL-019:** Meeting_Premium
- **SCHOL-020:** Messaging_Premium
- **SCHOL-021:** Quiz_Premium
- **SCHOL-022:** SMS_Premium
- **SCHOL-023:** Students_Import (Student_Import_Premium)
- **SCHOL-024:** Certificate
- **SCHOL-025:** Attendance_Excel_Sheet
- **SCHOL-026:** Email
- **SCHOL-027:** Email_Alerts
- **SCHOL-028:** Email_Log
- **SCHOL-029:** Email_Parents
- **SCHOL-030:** Email_Students
- **SCHOL-031:** Entry_Exit
- **SCHOL-032:** Hostel
- **SCHOL-033:** Student_ID_Card
- **SCHOL-034:** Student_Pickup
- **SCHOL-035:** Timetable_Import
- **SCHOL-036:** Staff_Absences
- **SCHOL-037:** Staff_Parents_Import
- **SCHOL-038:** Audit
- **SCHOL-039:** Backup
- **SCHOL-040:** Class_Diary
- **SCHOL-041:** Dashboards
- **SCHOL-042:** Ecuador_Retake_Exam
- **SCHOL-043:** Embedded_Resources
- **SCHOL-044:** Grades_Import
- **SCHOL-045:** Graduation_Paths
- **SCHOL-046:** Human_Resources
- **SCHOL-047:** Jitsi_Meet
- **SCHOL-048:** Lesson_Plan
- **SCHOL-049:** Library
- **SCHOL-050:** Marking_Period_Groups
- **SCHOL-051:** Medical_Report
- **SCHOL-052:** Meeting
- **SCHOL-053:** NFC_QR_Actions
- **SCHOL-054:** PDF_Archive
- **SCHOL-055:** Pedagogical_Plan
- **SCHOL-056:** Quiz
- **SCHOL-057:** Reports
- **SCHOL-058:** School_Inventory
- **SCHOL-059:** Semester_Rollover
- **SCHOL-060:** SMS
- **SCHOL-061:** TTHotel_Smart_Locks
- **SCHOL-062:** VLaby
- **Total: 53 individual module tickets** (SCHOL-010 through SCHOL-062)

## Plugin Inventory (34 plugins from demo site)

### Activated on Demo (2 plugins)
| # | Plugin | Version | Type | Status |
|---|--------|---------|------|--------|
| 1 | **Content_Security_Policy** | — | Core | Activated |
| 2 | **Moodle** | — | Core | Activated |

### Not Activated on Demo (32 plugins available for replication)
| # | Plugin | Version | Type | Notes |
|---|--------|---------|------|-------|
| 3 | **Absent_for_the_Day_on_First_Absence** | — | Free | |
| 4 | **Append_Custom_Field_to_Grade_Level** | — | Free | |
| 5 | **Assignment_Max_Points** | — | Free | |
| 6 | **Automatic_Attendance** | — | Free | |
| 7 | **Calendar_Schedule_View** | — | Free | |
| 8 | **Convert_Names_To_Titlecase** | — | Free | |
| 9 | **Custom_Menu** | — | Free | |
| 10 | **Discipline_Score** | — | Free | |
| 11 | **Email_SMTP** | — | Free | |
| 12 | **Flexible_Schedule** | — | Free | |
| 13 | **Force_Password_Change** | — | Free | |
| 14 | **Grading_Scale_Generation** | — | Free | |
| 15 | **iCalendar** | 11.4 (Apr 2026) | Free | |
| 16 | **Instant_List_Search_Sorting** | — | Free | |
| 17 | **Iomad** | — | Free | |
| 18 | **LDAP** | — | Free | |
| 19 | **Microsoft_Social_Login** | — | Free | |
| 20 | **Parent_Agreement** | 10.5 (May 2026) | Free | |
| 20 | **Paypal_Registration** | 12.2 (Aug 2026) | Premium | |
| 21 | **PDF_Header_Footer** | — | Free | |
| 22 | **Prevent_Teachers_Adding_Deleting_Assignments** | 1.2 (Apr 2026) | Free | |
| 23 | **Previous_Next_Student** | 1.3 (Sep 2025) | Free | |
| 24 | **Public_Pages** | 12.4 (May 2026) | Premium | |
| 25 | **Relatives** | 10.4 (Apr 2026) | Free | |
| 26 | **Report_Cards_PDF_2_Copies_Landscape** | 10.1 (Apr 2026) | Free | |
| 27 | **Setup_Assistant** | 12.0 (Jun 2026) | Free | |
| 28 | **Stripe_Registration** | 2.6 (Aug 2026) | Premium | |
| 29 | **Templates** | 1.9 (Apr 2026) | Premium | |
| 30 | **TinyMCE_Formula** | 10.2 (Apr 2025) | Free | |
| 31 | **TinyMCE_Record_Audio_Video** | 10.8 (Apr 2026) | Free | |
| 32 | **Tutor_Report_Card_Comments** | 2.0 (May 2026) | Free | |
| 33 | **Unsaved_Changes_Warning** | 1.3 (Apr 2026) | Free | |

### Plugin Folder Structure
- **Location:** `/home/coder/project/plugins/` (parallel to `/home/coder/project/premium-modules/`)
- **Each plugin:** `/home/coder/project/plugins/<Plugin_Name>/` with zip at `/home/coder/project/plugins/<Plugin_Name>.zip`
- **Zip contract:** Same as modules (single top-level folder, zipdetails 0 warnings)

### Plugin Tickets to Create
- **SCHOL-063:** Content_Security_Policy (ACTIVATED)
- **SCHOL-064:** Moodle (ACTIVATED)
- **SCHOL-065:** Paypal_Registration (Premium)
- **SCHOL-066:** Stripe_Registration (Premium)
- **SCHOL-067:** Public_Pages (Premium)
- **SCHOL-068:** Templates (Premium)
- **SCHOL-069:** Absent_for_the_Day_on_First_Absence
- **SCHOL-070:** Append_Custom_Field_to_Grade_Level
- **SCHOL-071:** Assignment_Max_Points
- **SCHOL-072:** Automatic_Attendance
- **SCHOL-073:** Calendar_Schedule_View
- **SCHOL-074:** Convert_Names_To_Titlecase
- **SCHOL-075:** Custom_Menu
- **SCHOL-076:** Discipline_Score
- **SCHOL-077:** Email_SMTP
- **SCHOL-078:** Flexible_Schedule
- **SCHOL-079:** Force_Password_Change
- **SCHOL-080:** Grading_Scale_Generation
- **SCHOL-081:** iCalendar
- **SCHOL-082:** Instant_List_Search_Sorting
- **SCHOL-083:** Iomad
- **SCHOL-084:** LDAP
- **SCHOL-085:** Microsoft_Social_Login
- **SCHOL-086:** Parent_Agreement
- **SCHOL-087:** PDF_Header_Footer
- **SCHOL-088:** Prevent_Teachers_Adding_Deleting_Assignments
- **SCHOL-089:** Previous_Next_Student
- **SCHOL-090:** Public_Pages (Premium)
- **SCHOL-091:** Relatives
- **SCHOL-092:** Report_Cards_PDF_2_Copies_Landscape
- **SCHOL-093:** Setup_Assistant
- **SCHOL-094:** Stripe_Registration (Premium)
- **SCHOL-094:** Templates (Premium)
- **SCHOL-095:** TinyMCE_Formula
- **SCHOL-096:** TinyMCE_Record_Audio_Video
- **SCHOL-097:** Tutor_Report_Card_Comments
- **SCHOL-098:** Unsaved_Changes_Warning
- **Total: 36 plugin tickets** (SCHOL-063 through SCHOL-098)