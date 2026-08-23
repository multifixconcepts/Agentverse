# SCHOL-040 — Free Module: Class_Diary (Exact Clone)

- **Status:** OPEN
- **Type:** feature (exact clone)
- **Priority:** MEDIUM
- **Product:** ScholaPro modules — Class_Diary
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Reference:** Demo site Class_Diary v7.7 (activated), rosariosis.org/modules/slovenian-class-diary/

## Demo Specification
- **Version:** 7.7 (Sep 2025)
- **Type:** Free module (activated on demo)
- **Programs:** Realizations, Meetings, Cooperation, PDF, Configuration, Abbreviations, First Page, Last Page, On Call Log (Read/Write Daytime/Nighttime)
- **Menu:** Own top-level menu "Slovenian Class Diary"
- **License:** GNU/GPLv2 or later
- **Author:** François Jacquet
- **Sponsored by:** AT group, Slovenia
- **Required by:** Class_Diary_Premium
- **Features:** Complete class diary for dormitories, realizations, meetings, cooperation, PDF generation, email reminders

## Acceptance Criteria
- **AC1:** Own top-level menu "Slovenian Class Diary" (or "Class Diary")
- **AC2:** All programs: Realizations, Meetings, Cooperation, PDF, Configuration, Abbreviations, On Call Log
- **AC3:** Email reminders for teachers
- **AC4:** Help_en.php per KB-0018
- **AC5:** icon.png, Zip valid, live validation passes
- **AC6:** Serves as dependency for Class_Diary_Premium

## Delegation
- **feature-planner** → create spec at `/tmp/opencode/spec_schol040.md` from demo evidence
- **fullstack-engineer** → implement at `/home/coder/project/premium-modules/Class_Diary/`
- **feature-tester** → G1 peer review
- **feature-division-council** → G2 division review
- **system-architect** → G3 architecture
- **security-division-council** → G4 security
- **quality-division-council** → G5 live validation (KB-0016)
- **release-custodian** → G6 release

## Evidence
- `/tmp/demo_readmes.json` ["Class_Diary"]