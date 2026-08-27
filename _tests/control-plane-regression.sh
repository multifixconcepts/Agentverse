#!/bin/bash
# AgentVerse Control Plane Regression Tests
# Run: bash _tests/control-plane-regression.sh
# Exit codes: 0 = all pass, 1 = failures

set -e

# Resolve project root and node from environment
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT="$(cd "$SELF_DIR/.." && pwd)"
NODE="$(command -v node 2>/dev/null || echo 'node')"

PASS=0
FAIL=0
ERRORS=""

pass() { PASS=$((PASS + 1)); echo "  ✓ $1"; }
fail() { FAIL=$((FAIL + 1)); ERRORS="$ERRORS\n  ✗ $1"; echo "  ✗ $1"; }

echo "=== AgentVerse Control Plane Regression Tests ==="
echo ""

# Test 1: ORG_CHECKSUM.json exists and is valid JSON
echo "[1] ORG_CHECKSUM.json integrity"
if [ -f "AGENTVERSE/ORG_CHECKSUM.json" ]; then
  if $NODE -e "JSON.parse(require('fs').readFileSync('AGENTVERSE/ORG_CHECKSUM.json','utf8'))" 2>/dev/null; then
    pass "ORG_CHECKSUM.json is valid JSON"
  else
    fail "ORG_CHECKSUM.json is not valid JSON"
  fi
else
  fail "ORG_CHECKSUM.json does not exist"
fi

# Test 2: All control-plane documents exist
echo "[2] Control-plane documents exist"
for doc in AGENTVERSE.md COHESION_MATRIX.md AGENT_REGISTRY.json MEMORY_INDEX.md KNOWLEDGE_BASE.md VERIFICATION_CONTRACT.md HANDOFF_TEMPLATE.md TRUTH_HIERARCHY.md MODEL_FAILOVER_PROTOCOL.md FAILURE_LOG.md ORG_CHECKSUM.json; do
  if [ -f "AGENTVERSE/$doc" ]; then
    pass "$doc exists"
  else
    fail "$doc missing"
  fi
done

# Test 3: Agent count in registry matches actual agent files
echo "[3] Agent count consistency"
REGISTRY_COUNT=$($NODE -e "console.log(JSON.parse(require('fs').readFileSync('AGENTVERSE/AGENT_REGISTRY.json','utf8')).total_agents)" 2>/dev/null || echo "0")
FILE_COUNT=$(ls .opencode/agents/*.md 2>/dev/null | wc -l)
if [ "$REGISTRY_COUNT" = "70" ] && [ "$FILE_COUNT" = "70" ]; then
  pass "Agent count: registry=$REGISTRY_COUNT, files=$FILE_COUNT (expected 70)"
else
  fail "Agent count mismatch: registry=$REGISTRY_COUNT, files=$FILE_COUNT (expected 70)"
fi

# Test 4: No duplicate KB IDs in the knowledge base entries
echo "[4] KB ID uniqueness"
KB_IDS=$(grep -oP '### KB-\d{4}' AGENTVERSE/KNOWLEDGE_BASE.md | sed 's/### //' | sort)
KB_UNIQUE=$(echo "$KB_IDS" | sort -u | wc -l)
KB_TOTAL=$(echo "$KB_IDS" | wc -l)
if [ "$KB_TOTAL" -eq "$KB_UNIQUE" ]; then
  pass "KB IDs are unique ($KB_TOTAL entries)"
else
  fail "Duplicate KB IDs found ($KB_TOTAL total, $KB_UNIQUE unique)"
fi

# Test 5: Ticket naming convention (should all be zero-padded)
echo "[5] Ticket naming convention"
# Check for non-zero-padded tickets (SCHOL-XX.md where XX is not zero-padded)
BAD_TICKETS=$(ls AGENTVERSE/tickets/SCHOL-[0-9][0-9].md 2>/dev/null | grep -v "SCHOL-[0-9][0-9][0-9]" | wc -l)
if [ "$BAD_TICKETS" -eq "0" ]; then
  pass "All ticket files use zero-padded naming"
else
  fail "$BAD_TICKETS ticket files use non-zero-padded naming"
fi

# Test 6: Checksum hashes match actual files
echo "[6] Checksum verification"
if command -v $NODE &>/dev/null; then
  HASH_MISMATCH=$($NODE -e "
    const fs = require('fs');
    const crypto = require('crypto');
    const checksum = JSON.parse(fs.readFileSync('AGENTVERSE/ORG_CHECKSUM.json','utf8'));
    const hashes = checksum.sha256_hashes || checksum.control_planes || {};
    let mismatches = 0;
    for (const [file, info] of Object.entries(hashes)) {
      // Skip self-hash — inherently circular
      if (file === 'ORG_CHECKSUM.json') continue;
      const expectedHash = typeof info === 'string' ? info : (info.hash || 'MISSING');
      if (expectedHash === 'MISSING') continue;
      const actual = crypto.createHash('sha256').update(fs.readFileSync('AGENTVERSE/' + file)).digest('hex');
      if (actual !== expectedHash) {
        console.error('MISMATCH: ' + file + ' expected=' + expectedHash.slice(0,12) + ' actual=' + actual.slice(0,12));
        mismatches++;
      }
    }
    console.log(mismatches);
  " 2>&1)
  if [ "$HASH_MISMATCH" = "0" ]; then
    pass "All checksum hashes match"
  else
    fail "$HASH_MISMATCH checksum hash mismatches"
  fi
else
  echo "  ⚠ node not available, skipping hash verification"
fi

# Test 7: Version headers present in control-plane docs
echo "[7] Version headers"
for doc in AGENTVERSE.md COHESION_MATRIX.md MEMORY_INDEX.md KNOWLEDGE_BASE.md; do
  if grep -q "^\*\*Version:\*\*" "AGENTVERSE/$doc" 2>/dev/null; then
    pass "$doc has version header"
  else
    fail "$doc missing version header"
  fi
done

# Test 8: Memory entity count
echo "[8] Memory entities"
ENTITY_COUNT=$($NODE -e "console.log(JSON.parse(require('fs').readFileSync('.memory/memory.json','utf8')).length)" 2>/dev/null || echo "0")
if [ "$ENTITY_COUNT" -ge "5" ]; then
  pass "Memory has $ENTITY_COUNT entities (expected >=5)"
else
  fail "Memory has only $ENTITY_COUNT entities (expected >=5)"
fi

# Test 9: No credentials in memory
echo "[9] Credential scan in memory"
if grep -qi "password\|secret\|api_key\|token" .memory/memory.json 2>/dev/null; then
  fail "Possible credentials found in .memory/memory.json"
else
  pass "No credentials found in .memory/memory.json"
fi

# Test 10: Session ledger plugin exists and captures session IDs
echo "[10] Session ledger"
if grep -q "event.properties?.sessionID" .opencode/plugins/session-ledger.js 2>/dev/null; then
  pass "Session ledger captures session IDs"
else
  fail "Session ledger missing session ID capture"
fi

# Test 11: VERIFICATION_BLOCKED state is defined in state machine
echo "[11] VERIFICATION_BLOCKED state definition"
if grep -q "VERIFICATION_BLOCKED" AGENTVERSE/VERIFICATION_CONTRACT.md 2>/dev/null; then
  pass "VERIFICATION_BLOCKED defined in VERIFICATION_CONTRACT.md"
else
  fail "VERIFICATION_BLOCKED not defined in VERIFICATION_CONTRACT.md"
fi

if grep -q "VERIFICATION_BLOCKED" .opencode/skills/task-ledger/SKILL.md 2>/dev/null; then
  pass "VERIFICATION_BLOCKED defined in task-ledger SKILL.md"
else
  fail "VERIFICATION_BLOCKED not defined in task-ledger SKILL.md"
fi

if grep -q "Verifier Unavailability Procedure" AGENTVERSE/SEPARATION_OF_DUTIES.md 2>/dev/null; then
  pass "Verifier Unavailability Procedure defined in SEPARATION_OF_DUTIES.md"
else
  fail "Verifier Unavailability Procedure not defined in SEPARATION_OF_DUTIES.md"
fi

# Test 12: Self-verification prohibition is documented
echo "[12] Self-verification prohibition"
if grep -q "No agent should approve its own work\|self-verification\|self-verif\|MUST NOT.*own work\|implementer.*VERIFIED" AGENTVERSE/SEPARATION_OF_DUTIES.md 2>/dev/null; then
  pass "Self-verification prohibition documented"
else
  fail "Self-verification prohibition not found"
fi

# Test 13: BLOCKED verdict is recognized by review-gate skill
echo "[13] BLOCKED verdict vocabulary"
if grep -q "BLOCKED" .opencode/skills/review-gate/SKILL.md 2>/dev/null; then
  pass "BLOCKED verdict defined in review-gate SKILL.md"
else
  fail "BLOCKED verdict not defined in review-gate SKILL.md"
fi

# Test 14: Truth hierarchy includes verification-unavailability rule
echo "[14] Truth hierarchy rule"
if grep -q "Verification-unavailability blocks\|VERIFICATION_BLOCKED" AGENTVERSE/TRUTH_HIERARCHY.md 2>/dev/null; then
  pass "Verification-unavailability rule in TRUTH_HIERARCHY.md"
else
  fail "Verification-unavailability rule missing from TRUTH_HIERARCHY.md"
fi

# Test 15: FAILURE_LOG includes FAIL-006
echo "[15] FAILURE_LOG completeness"
if grep -q "FAIL-006" AGENTVERSE/FAILURE_LOG.md 2>/dev/null; then
  pass "FAIL-006 (verifier-unavailability gap) recorded"
else
  fail "FAIL-006 not recorded in FAILURE_LOG.md"
fi

# Summary
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
  echo "  All tests passed!"
  exit 0
fi
