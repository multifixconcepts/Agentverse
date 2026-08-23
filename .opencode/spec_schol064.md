# SCHOL-064 Specification — Moodle Plugin (Exact Clone)

**Ticket:** SCHOL-064
**Parent:** SCHOL-009
**Status:** OPEN
**Type:** Feature (exact clone from demo)
**Priority:** HIGH
**Owner:** Integration Division (integration-division-council)

---

## 1. Demo Evidence

### 1.1 Plugin Row (from /tmp/demo_plugins_full.html)
- **Status:** ACTIVATED (shows "Deactivate" button)
- **Type:** Core plugin (always present in demo)
- **Title:** Moodle
- **Configuration:** Available (link to modfunc=config&plugin=Moodle)

### 1.2 README Content (from demo)
```
# Moodle Integrator Plugin

This plugin integrates RosarioSIS with Moodle LMS. It lets you import Moodle users.

- Configure Moodle API and Test
- Create student in Moodle if "Automatic Student Account Activation" configuration option set (Moodle creates a password and sends an email to user).
- Create, update & delete students in Moodle
- Create, update & delete teachers, parents, admins in Moodle
- Subjects, courses & course periods are automatically created, updated & deleted in Moodle
- Teacher users are automatically assigned the "Teacher" role for their courses in Moodle.
- Automatically (mass) schedule or drop students from a course period in Moodle
- Calendar events are automatically added to & removed from the Moodle calendar
- Portal notes are automatically created, updated & deleted in Moodle
- Rollover: Moodle users (and students) and courses are associated to the next school year entities
- Assignments are automatically added to & removed from the Moodle calendar (provided a Due Date is set)

Please follow this tutorial: https://gitlab.com/francoisjacquet/rosariosis/wikis/Moodle-integrator-setup

Requires Moodle 3.1 or higher & PHP curl extension.
```

---

## 2. Acceptance Criteria

| AC | Description | Verification |
|----|-------------|--------------|
| AC1 | Plugin structure matches demo exactly | File tree diff vs demo structure |
| AC2 | Moodle integration functionality | Configuration page, API test, sync operations |
| AC3 | Help content per KB-0018 | Help_en.php with 6 programs, per-user-type content |
| AC4 | Zip package valid | mkzip.js produces valid zip, zipdetails passes |
| AC5 | Live validation on school4 | addon-live-validation skill: menu, grants, programs, CRUD |

---

## 3. Plugin Structure (RosarioSIS Module Convention)

```
/home/coder/project/plugins/Moodle/
├── Moodle.php                    # Main module class
├── Moodle.inc.php                # Module initialization
├── config.php                    # Configuration page
├── install_mysql.sql             # Database schema (idempotent)
├── delete.sql                    # Cleanup on uninstall
├── Help_en.php                   # Help content (KB-0018 standard)
├── icon.png                      # 64x64 RGBA (emerald tile + white FA glyph)
├── README.md                     # Features/install/credits
├── lang/
│   └── en_US/
│       └── Moodle.lang.php       # Language strings
├── lib/
│   ├── MoodleAPI.php             # Moodle REST API client
│   └── MoodleSync.php            # Sync operations
└── css/
    └── Moodle.css                # Module styles
```

---

## 4. Key Functional Requirements

### 4.1 Configuration Page (config.php)
- Moodle URL (e.g., https://moodle.example.com)
- Moodle Token (Web service token)
- Moodle Web Service Protocol (REST)
- "Automatic Student Account Activation" checkbox
- "Test Connection" button

### 4.2 Sync Operations
- **Users:** Students, Teachers, Parents, Admins (create/update/delete)
- **Courses:** Subjects, Courses, Course Periods (create/update/delete)
- **Enrollments:** Mass schedule/drop students from course periods
- **Calendar:** Events, Assignments (with Due Date)
- **Portal Notes:** Create/update/delete
- **Rollover:** Associate users/courses to next school year

### 4.3 Technical Requirements
- PHP curl extension required
- Moodle 3.1+ compatible
- Uses Moodle Web Services (REST)
- Idempotent SQL (CREATE TABLE IF NOT EXISTS)

---

## 5. Help Content (KB-0018 Standard)

Per KB-0018, Help_en.php must contain 6 programs with per-user-type content:
1. Configuration — Admin: API setup, test connection
2. User Sync — Admin: Student/Teacher/Parent/Admin sync
3. Course Sync — Admin: Subject/Course/Period sync
4. Enrollment — Teacher/Admin: Schedule/drop students
5. Calendar & Notes — Teacher/Admin: Events, assignments, portal notes
6. Rollover — Admin: Year-end rollover procedures

Each program: title, description, steps, screenshots (placeholders), per-role notes.

---

## 6. Implementation Notes

### 6.1 Existing Plugins Reference
Check /home/coder/project/plugins/ for existing module patterns (e.g., Resources, Accounting, Student_Billing_Premium).

### 6.2 Zip Creation
```bash
node /tmp/opencode/mkzip.js /home/coder/project/plugins/Moodle /home/coder/project/plugins/Moodle.zip
```

### 6.3 Validation
```bash
zipdetails /home/coder/project/plugins/Moodle.zip
```

---

## 7. Delegation Plan

| Step | Agent | Task | Output |
|------|-------|------|--------|
| 1 | feature-planner | Create detailed implementation spec | This document (finalized) |
| 2 | fullstack-engineer | Implement plugin + zip | /home/coder/project/plugins/Moodle/, Moodle.zip |
| 3 | feature-tester | G1 Peer Review | Code review notes |
| 4 | integration-division-council | G2 Division Review | Scope/AC verification |
| 5 | system-architect | G3 Architecture | Design integrity |
| 6 | security-division-council | G4 Security | XSS, injection, secrets, auth |
| 7 | quality-division-council | G5 Live Validation | addon-live-validation on school4 |
| 8 | release-custodian | G6 Release | DoD, changelog, version |

---

## 8. References

- Demo README: /tmp/demo_plugins_full.html (Moodle row)
- KB-0018: Module Help per-user-type content standard
- KB-0008: www-data ownership rule for modules
- KB-0010: SQL idempotency + Delete button always visible
- Tutorial: https://gitlab.com/francoisjacquet/rosariosis/wikis/Moodle-integrator-setup
