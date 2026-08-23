# SCHOL-064 — Plugin: Moodle (Exact Clone)

- **Status:** IN_PROGRESS
- **Type:** feature (exact clone)
- **Priority:** HIGH
- **Product:** ScholaPro plugins — Moodle
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Reference:** Demo site Moodle (ACTIVATED)

## Demo Specification
- **Status:** ACTIVATED on demo (shows "Deactivate" button)
- **Type:** Core plugin (always present)
- **Purpose:** Moodle LMS integration

## Acceptance Criteria
- **AC1:** Plugin structure matches demo exactly
- **AC2:** Moodle integration functionality
- **AC3:** Help content per KB-0018
- **AC4:** Zip package valid
- **AC5:** Live validation on school4

## Delegation
- **Owner:** Integration Division (integration-division-council)
- **Specialists:** feature-planner (spec), fullstack-engineer (implementation)

## Evidence
- `/tmp/demo_plugin_readmes.json` ["Moodle"]
- Demo site: activated (deactivate=true)
- Spec: `/home/coder/project/.opencode/spec_schol064.md`

## Gate Ledger
| Gate | Owner | Status | Evidence |
|------|-------|--------|----------|
| G1 Peer Review | feature-tester | PENDING | |
| G2 Division Review | integration-division-council | PENDING | |
| G3 Architecture | system-architect | PENDING | |
| G4 Security | security-division-council | PENDING | |
| G5 Quality | quality-division-council | PENDING | |
| G6 Release | release-custodian | PENDING | |

## Delegation Log
- 2026-08-16: Spec created at `.opencode/spec_schol064.md` by integration-division-council
- 2026-08-16: Delegated to fullstack-engineer for implementation
