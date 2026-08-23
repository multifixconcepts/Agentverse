# SCHOL-063 — Plugin: Content_Security_Policy (Exact Clone)

- **Status:** DELEGATED
- **Type:** feature (exact clone)
- **Priority:** HIGH
- **Product:** ScholaPro plugins — Content_Security_Policy
- **Opened:** 2026-08-16
- **Parent:** SCHOL-009
- **Reference:** Demo site Content_Security_Policy (ACTIVATED)

## Demo Specification
- **Status:** ACTIVATED on demo (shows "Deactivate" button)
- **Type:** Core plugin (always present)
- **Purpose:** Content Security Policy headers for security

## Acceptance Criteria
- **AC1:** Plugin structure matches demo exactly
- **AC2:** CSP header configuration
- **AC3:** Help content per KB-0018
- **AC4:** Zip package valid (single root, zipdetails 0 warnings)
- **AC5:** Live validation on school4

## Delegation Plan
| Step | Role | Task | Deliverable |
|------|------|------|-------------|
| 1 | **feature-planner** | Create spec from demo evidence | `/home/coder/project/tmp/opencode/spec_schol063.md` ✓ |
| 2 | **fullstack-engineer** | Implement exact clone | `/home/coder/project/plugins/Content_Security_Policy/` + zip |
| 3 | **feature-tester** | G1 Peer Review | Review notes, no unresolved issues |
| 4 | **integration-division-council** | G2 Division Review | All AC met, scope contained |
| 5 | **system-architect** | G3 Architecture | Design integrity, interface contracts |
| 6 | **security-division-council** | G4 Security | CSP headers, XSS/injection, secrets |
| 7 | **quality-division-council** | G5 Quality | Live validation on school4 |
| 8 | **release-custodian** | G6 Release | DoD complete, changelog updated |

## Evidence
- `/tmp/demo_plugin_readmes.json` ["Content_Security_Policy"]
- `/tmp/demo_plugins_full.html` (plugin row with Deactivate button)
- Spec: `/home/coder/project/tmp/opencode/spec_schol063.md` (created)

## Gate Chain (COHESION_MATRIX.md §1)
| Gate | Owner | Status | Evidence Required |
|------|-------|--------|-------------------|
| G1 Peer Review | feature-tester | PENDING | Code review notes |
| G2 Division Review | integration-division-council | PENDING | All AC met |
| G3 Architecture | system-architect | PENDING | Design integrity |
| G4 Security | security-division-council | PENDING | CSP headers, no XSS/injection |
| G5 Quality | quality-division-council | PENDING | Live validation on school4 |
| G6 Release | release-custodian | PENDING | DoD complete |

## Notes
- Plugin is CORE (always present on demo, cannot be deleted)
- Must implement Report-Only mode CSP header per demo
- SaveReport.php endpoint for violation reporting
- Configuration: Reports tab + Domains tab (4 directives)
- Help per KB-0018 (Administrator only access)
- Languages: EN, FR, ES minimum
- Zip via `/tmp/opencode/mkzip.js` → validate with zipdetails
