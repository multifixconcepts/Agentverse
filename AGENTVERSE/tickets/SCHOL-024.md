# Ticket SCHOL-024: Certificate Module (Free)

**Status:** OPEN
**Type:** Feature
**Priority:** HIGH
**Product:** scholapro
**Affected Files:** /home/coder/premium-modules/Certificate/
**Request:** Implement Certificate free module (SCHOL-024) following the standard delegation chain.

## Acceptance Criteria
- Module installs and activates without PHP fatals
- Menu appears in navigation with correct profile_exceptions grants
- Certificate generation/rendering works
- List/data rendering functional
- Add/edit/delete flows operational
- Live validation passes (KB-0016)

## Gate Ledger
| Gate | Owner | Verdict | Evidence | Sign-off |
|------|-------|---------|----------|----------|
| G1 Peer Review | feature-tester | PENDING | | |
| G2 Division Review | feature-division-council | PENDING | | |
| G3 Architecture | system-architect | PENDING | | |
| G4 Security | security-division-council | PENDING | | |
| G5 Quality (Live Validation) | quality-division-council | PENDING | | |
| G6 Release | release-custodian | PENDING | | |

## Delegation Chain
1. **feature-planner** → Create spec at `/tmp/opencode/spec_schol024.md` from demo evidence
2. **fullstack-engineer** → Implement at `/home/coder/premium-modules/Certificate/`
3. **feature-tester** → G1 peer review
4. **feature-division-council** → G2 division review
5. **system-architect** → G3 architecture
6. **security-division-council** → G4 security
7. **quality-division-council** → G5 live validation (KB-0016)
8. **release-custodian** → G6 release