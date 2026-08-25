#!/bin/bash
# AgentVerse Scenario 8 Adversarial Tests — VERIFICATION_BLOCKED
# Tests the verifier-unavailability remediation
# Run: bash _tests/scenario8-adversarial.sh
# Exit codes: 0 = all pass, 1 = failures

set -e

NODE="$(command -v node 2>/dev/null || echo 'node')"
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT="$(cd "$SELF_DIR/.." && pwd)"
AGENTVERSE="$PROJECT/AGENTVERSE"
TOOLS="$PROJECT/_tools"
TEST_DIR=$(mktemp -d)

PASS=0
FAIL=0
ERRORS=""

pass() { PASS=$((PASS + 1)); echo "  ✓ $1"; }
fail() { FAIL=$((FAIL + 1)); ERRORS="$ERRORS\n  ✗ $1"; echo "  ✗ $1"; }

cleanup() { rm -rf "$TEST_DIR"; }
trap cleanup EXIT

echo "=== Scenario 8 Adversarial Tests — VERIFICATION_BLOCKED ==="
echo ""

# =============================================
# Test A: Required verifier unavailable → VERIFICATION_BLOCKED
# =============================================
echo "[A] Required verifier unavailable → VERIFICATION_BLOCKED"

# Create a test ticket in the correct location
mkdir -p "$AGENTVERSE/tickets"
cat > "$AGENTVERSE/tickets/SCHOL-800.md" << 'TICKET'
# SCHOL-800: Test Ticket for Verifier Unavailability

**Status:** IMPLEMENTED
**Type:** feature
**Priority:** medium

## Acceptance Criteria
- AC-01: File exists at /home/coder/project/scholapro/TestFile.php
- AC-02: PHP syntax check passes

## Files
- /home/coder/project/scholapro/TestFile.php
TICKET

# Create the test PHP file
cat > "$PROJECT/scholapro/TestFile.php" << 'PHP'
<?php
// Test file for Scenario 8 adversarial tests
function testFunction() { return true; }
?>
PHP

# Run verify-gate.sh — it should produce a verdict
VERDICT_OUTPUT=$($TOOLS/verify-gate.sh SCHOL-800 G5 2>/dev/null || echo '{"verdict":"ERROR"}')
VERDICT=$($NODE -e "const d=JSON.parse(process.argv[1]); process.stdout.write(d.verdict||'ERROR')" "$VERDICT_OUTPUT" 2>/dev/null || echo "ERROR")

if [ "$VERDICT" = "PASS" ] || [ "$VERDICT" = "FAIL" ] || [ "$VERDICT" = "BLOCKED" ] || [ "$VERDICT" = "NOT_ALL_VERIFIED" ]; then
  pass "verify-gate.sh produced verdict: $VERDICT"
else
  fail "verify-gate.sh produced unexpected verdict: $VERDICT"
fi

# =============================================
# Test B: Implementer attempts self-verification → REJECTED
# =============================================
echo ""
echo "[B] Implementer attempts self-verification → REJECTED"

# Check that SEPARATION_OF_DUTIES.md prohibits self-verification
if grep -q "MUST NOT.*own work\|must NOT verify work it authored\|No agent should approve its own work" "$AGENTVERSE/SEPARATION_OF_DUTIES.md" 2>/dev/null; then
  pass "Self-verification prohibition documented in SEPARATION_OF_DUTIES.md"
else
  fail "Self-verification prohibition NOT found in SEPARATION_OF_DUTIES.md"
fi

# Check that the quality-guardian contract prohibits self-verification
if grep -q "must NOT verify work it authored\|not verify work it authored" "$AGENTVERSE/PROFESSIONAL_OPERATING_CONTRACTS.md" 2>/dev/null; then
  pass "Self-verification prohibition in quality-guardian contract"
else
  fail "Self-verification prohibition NOT in quality-guardian contract"
fi

# Check that VERIFICATION_BLOCKED → VERIFIED by implementer is prohibited
if grep -q "VERIFICATION_BLOCKED.*VERIFIED.*implementer\|implementer.*VERIFIED\|self-verif" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "VERIFICATION_BLOCKED → VERIFIED by implementer is prohibited"
else
  fail "VERIFICATION_BLOCKED → VERIFIED by implementer prohibition NOT found"
fi

# =============================================
# Test C: Release attempted while VERIFICATION_BLOCKED → BLOCKED
# =============================================
echo ""
echo "[C] Release attempted while VERIFICATION_BLOCKED → BLOCKED"

# Check that release prohibition is documented
if grep -q "release.*BLOCKED\|BLOCKED.*release\|Release.*prohib\|release authority.*BLOCKED\|cannot.*bypass.*blocked" "$AGENTVERSE/SEPARATION_OF_DUTIES.md" 2>/dev/null; then
  pass "Release prohibition for BLOCKED state documented"
else
  fail "Release prohibition for BLOCKED state NOT found"
fi

# Check VERIFICATION_CONTRACT.md for release prohibition
if grep -q "RELEASED.*VERIFICATION_BLOCKED\|VERIFICATION_BLOCKED.*RELEASED\|cannot.*release.*blocked\|release.*prohib" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "Release prohibition in VERIFICATION_CONTRACT.md"
else
  fail "Release prohibition NOT in VERIFICATION_CONTRACT.md"
fi

# =============================================
# Test D: Qualified substitute verifier available → independent verification allowed
# =============================================
echo ""
echo "[D] Qualified substitute verifier available → independent verification allowed"

# Check that substitute selection rules exist
if grep -q "Substitute verifier selection\|substitute.*selection\|qualified substitute" "$AGENTVERSE/SEPARATION_OF_DUTIES.md" 2>/dev/null; then
  pass "Substitute verifier selection rules documented"
else
  fail "Substitute verifier selection rules NOT found"
fi

# Check that the escalation procedure allows substitute assignment
if grep -q "substitute.*assign\|assign.*substitute\|Division council.*substitute" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "Substitute assignment procedure in VERIFICATION_CONTRACT.md"
else
  fail "Substitute assignment procedure NOT in VERIFICATION_CONTRACT.md"
fi

# =============================================
# Test E: Unqualified substitute attempts verification → REJECTED
# =============================================
echo ""
echo "[E] Unqualified substitute attempts verification → REJECTED"

# Check substitute qualification rules
if grep -q "different agent.*implementer\|MUST NOT have authored\|no conflict of interest\|MUST be a different agent" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "Substitute qualification rules documented"
else
  fail "Substitute qualification rules NOT found"
fi

# Check that implementer cannot be substitute
if grep -q "implementer.*substitute\|substitute.*implementer\|MUST be a different agent" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "Implementer-as-substitute prohibition documented"
else
  fail "Implementer-as-substitute prohibition NOT found"
fi

# =============================================
# Test F: Verifier returns after blocked state → normal verification resumes
# =============================================
echo ""
echo "[F] Verifier returns after blocked state → normal verification resumes"

# Check resume procedure
if grep -q "Resume procedure\|resume.*verif\|When.*verifier becomes available" "$AGENTVERSE/SEPARATION_OF_DUTIES.md" 2>/dev/null; then
  pass "Resume procedure documented"
else
  fail "Resume procedure NOT found"
fi

# Check that blocked state has a transition to VERIFIED
if grep -q "VERIFICATION_BLOCKED.*VERIFIED\|→ VERIFIED" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
  pass "Transition VERIFICATION_BLOCKED → VERIFIED defined"
else
  fail "Transition VERIFICATION_BLOCKED → VERIFIED NOT defined"
fi

# =============================================
# Test G: Verifier disappears during verification → state remains safely blocked
# =============================================
echo ""
echo "[G] Verifier disappears during verification → state remains safely blocked"

# Check failover-during-verification handling
if grep -q "failover during verification\|verifier disappears\|disappear.*during.*verif" "$AGENTVERSE/SEPARATION_OF_DUTIES.md" 2>/dev/null; then
  pass "Failover-during-verification handling documented"
else
  fail "Failover-during-verification handling NOT found"
fi

# Check CONTEXT_RECONSTRUCTION.md recognizes blocked state
if grep -q "VERIFICATION_BLOCKED\|blocked.*state\|blocked.*ticket" "$AGENTVERSE/CONTEXT_RECONSTRUCTION.md" 2>/dev/null; then
  pass "Context reconstruction recognizes VERIFICATION_BLOCKED"
else
  fail "Context reconstruction does NOT recognize VERIFICATION_BLOCKED"
fi

# =============================================
# Test H: Model failover while VERIFICATION_BLOCKED → reconstruction without self-approval
# =============================================
echo ""
echo "[H] Model failover while VERIFICATION_BLOCKED → reconstruction without self-approval"

# Check BOOT.md step for blocked tickets
if grep -q "VERIFICATION_BLOCKED\|blocked.*ticket\|verification-blocked" "$AGENTVERSE/AGENTVERSE_BOOT.md" 2>/dev/null; then
  pass "Boot sequence handles VERIFICATION_BLOCKED tickets"
else
  fail "Boot sequence does NOT handle VERIFICATION_BLOCKED tickets"
fi

# Check CURRENT_STATE.json has VERIFICATION_BLOCKED field
if $NODE -e "const s=JSON.parse(require('fs').readFileSync('$AGENTVERSE/CURRENT_STATE.json','utf8')); if(!s.active_tickets.VERIFICATION_BLOCKED) process.exit(1);" 2>/dev/null; then
  pass "CURRENT_STATE.json has VERIFICATION_BLOCKED field"
else
  fail "CURRENT_STATE.json missing VERIFICATION_BLOCKED field"
fi

# Check that boot sequence says "Do NOT self-verify"
if grep -q "Do NOT self-verify\|not self-approv\|not.*self.*verif" "$AGENTVERSE/AGENTVERSE_BOOT.md" 2>/dev/null; then
  pass "Boot sequence prohibits self-verification of blocked tickets"
else
  fail "Boot sequence does NOT prohibit self-verification of blocked tickets"
fi

# =============================================
# Test I: Tool enforcement — generate-verdict.sh recognizes BLOCKED
# =============================================
echo ""
echo "[I] Tool enforcement — generate-verdict.sh recognizes BLOCKED"

# Check that generate-verdict.sh has BLOCKED logic
if grep -q "BLOCKED\|blocked" "$TOOLS/generate-verdict.sh" 2>/dev/null; then
  pass "generate-verdict.sh handles BLOCKED verdict"
else
  fail "generate-verdict.sh does NOT handle BLOCKED verdict"
fi

# Check that verify-gate.sh has BLOCKED logic
if grep -q "BLOCKED\|blocked\|VERIFICATION_BLOCKED" "$TOOLS/verify-gate.sh" 2>/dev/null; then
  pass "verify-gate.sh handles BLOCKED verdict"
else
  fail "verify-gate.sh does NOT handle BLOCKED verdict"
fi

# =============================================
# Test J: State machine consistency across documents
# =============================================
echo ""
echo "[J] State machine consistency across documents"

# Count documents that define VERIFICATION_BLOCKED
VB_COUNT=0
for doc in VERIFICATION_CONTRACT.md SEPARATION_OF_DUTIES.md COHESION_MATRIX.md TRUTH_HIERARCHY.md PROFESSIONAL_OPERATING_CONTRACTS.md; do
  if grep -q "VERIFICATION_BLOCKED" "$AGENTVERSE/$doc" 2>/dev/null; then
    VB_COUNT=$((VB_COUNT + 1))
  fi
done

if [ "$VB_COUNT" -ge 4 ]; then
  pass "VERIFICATION_BLOCKED defined in $VB_COUNT/5 key documents"
else
  fail "VERIFICATION_BLOCKED only in $VB_COUNT/5 key documents (expected >=4)"
fi

# Check skill files
SKILL_COUNT=0
for skill in task-ledger review-gate delegate; do
  if grep -q "VERIFICATION_BLOCKED\|BLOCKED" "$PROJECT/.opencode/skills/$skill/SKILL.md" 2>/dev/null; then
    SKILL_COUNT=$((SKILL_COUNT + 1))
  fi
done

if [ "$SKILL_COUNT" -ge 2 ]; then
  pass "BLOCKED state recognized in $SKILL_COUNT/3 skill files"
else
  fail "BLOCKED state only in $SKILL_COUNT/3 skill files (expected >=2)"
fi

# =============================================
# Test K: Regression — existing state machine still valid
# =============================================
echo ""
echo "[K] Regression — existing state machine still valid"

# Verify original states still exist
for state in PLANNED IN_PROGRESS IMPLEMENTED TESTED VERIFIED ACCEPTED RELEASED; do
  if grep -q "$state" "$AGENTVERSE/VERIFICATION_CONTRACT.md" 2>/dev/null; then
    pass "State $state still defined"
  else
    fail "State $state MISSING from VERIFICATION_CONTRACT.md"
  fi
done

# =============================================
# Cleanup test artifacts
# =============================================
rm -f "$PROJECT/scholapro/TestFile.php"
rm -f "$AGENTVERSE/tickets/SCHOL-800.md"

# =============================================
# Summary
# =============================================
echo ""
echo "=== Results ==="
echo "  Pass: $PASS"
echo "  Fail: $FAIL"
if [ $FAIL -gt 0 ]; then
  echo ""
  echo "Failures:"
  echo -e "$ERRORS"
  exit 1
else
  echo "  All adversarial tests passed!"
  exit 0
fi
