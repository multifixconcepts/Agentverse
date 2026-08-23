# SCHOL-061 — Free Module: TTHotel_Smart_Locks (Exact Clone)

- **Status:** OPEN
- **Type:** feature (exact clone)
- **Priority:** MEDIUM
- **Product:** ScholaPro modules — TTHotel_Smart_Locks
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Reference:** Demo site TTHotel_Smart_Locks v2.1 (activated), rosariosis.org/modules/tthotel-smart-locks/

## Demo Specification
- **Version:** 2.1 (Apr 2026)
- **Type:** Free module (activated on demo)
- **Programs:** Configuration, Smart Locks, Accounts, Access (4 programs added to Entry_Exit menu)
- **Menu:** Adds to Entry_Exit menu
- **License:** GNU/GPLv2 or later
- **Author:** François Jacquet
- **Sponsored by:** AT group, Slovenia
- **Requires:** Entry_Exit module
- **Features:** NFC card, eKey (bluetooth), password access; manage student/user access, consult records

## Acceptance Criteria
- **AC1:** Adds 4 programs to Entry_Exit menu (Configuration, Smart Locks, Accounts, Access)
- **AC2:** Requires Entry_Exit module (dependency check)
- **AC3:** NFC card, eKey (bluetooth), password access types
- **AC4:** Help_en.php per KB-0018
- **AC5:** icon.png, Zip valid, live validation passes

## Delegation
- **feature-planner** → create spec at `/tmp/opencode/spec_schol061.md` from demo evidence
- **fullstack-engineer** → implement at `/home/coder/project/premium-modules/TTHotel_Smart_Locks/`
- **feature-tester** → G1 peer review
- **feature-division-council** → G2 division review
- **system-architect** → G3 architecture
- **security-division-council** → G4 security
- **quality-division-council** → G5 live validation (KB-0016)
- **release-custodian** → G6 release

## Evidence
- `/tmp/demo_readmes.json` ["TTHotel_Smart_Locks"]