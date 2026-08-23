#!/usr/bin/env bash
# post-failover-verify.sh — Verify claims from a previous model after failover
# Reads CURRENT_STATE.json, checks claimed completions, outputs JSON report.
#
# Usage: bash _tools/post-failover-verify.sh [project_root]
#   project_root defaults to the repository root (two levels up from _tools/)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${1:-$(cd "$SCRIPT_DIR/.." && pwd)}"
CURRENT_STATE="$PROJECT_ROOT/AGENTVERSE/CURRENT_STATE.json"
TIMESTAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# --- Helpers ---

json_array_start() { echo -n "["; }
json_array_end()   { echo -n "]"; }
json_comma()       { echo -n ","; }

# --- Validate prerequisites ---

if [ ! -f "$CURRENT_STATE" ]; then
  cat <<EOF
{
  "failover_timestamp": "$TIMESTAMP",
  "error": "CURRENT_STATE.json not found at $CURRENT_STATE",
  "recommendation": "HALT_AND_VERIFY"
}
EOF
  exit 1
fi

if ! command -v jq &>/dev/null; then
  cat <<EOF
{
  "failover_timestamp": "$TIMESTAMP",
  "error": "jq is required but not installed",
  "recommendation": "HALT_AND_VERIFY"
}
EOF
  exit 1
fi

# --- Extract claimed completions from CURRENT_STATE.json ---

# CURRENT_STATE.json may have completed_tickets or active_tickets with claims.
# We look for any ticket where claimed_complete or status indicates done.

CLAIMS_JSON="[]"

if jq -e '.completed_tickets // empty' "$CURRENT_STATE" &>/dev/null; then
  CLAIMS_JSON=$(jq '[.completed_tickets[]? | {
    ticket_id: (.ticket_id // .id // "UNKNOWN"),
    claimed_by: (.claimed_by // .model // "unknown"),
    claimed_at: (.claimed_at // .timestamp // "unknown"),
    claim_type: (.claim_type // "completion"),
    artifacts: (.artifacts // []),
    verdict_file: (.verdict_file // null),
    test_command: (.test_command // null)
  }]' "$CURRENT_STATE")
fi

# Also check active tickets that may have completion claims
if jq -e '.active_tickets // empty' "$CURRENT_STATE" &>/dev/null; then
  ACTIVE_CLAIMS=$(jq '[.active_tickets[]? | select(.status == "complete" or .claimed_complete == true) | {
    ticket_id: (.ticket_id // .id // "UNKNOWN"),
    claimed_by: (.claimed_by // .model // "unknown"),
    claimed_at: (.claimed_at // .timestamp // "unknown"),
    claim_type: (.claim_type // "completion"),
    artifacts: (.artifacts // []),
    verdict_file: (.verdict_file // null),
    test_command: (.test_command // null)
  }]' "$CURRENT_STATE")
  CLAIMS_JSON=$(echo "$CLAIMS_JSON" "$ACTIVE_CLAIMS" | jq -s '.[0] + .[1]')
fi

CLAIM_COUNT=$(echo "$CLAIMS_JSON" | jq 'length')

# --- Verify each claim ---

RESULTS="[]"
TRUSTED="[]"
UNTRUSTED="[]"
HAS_UNTRUSTED=false

if [ "$CLAIM_COUNT" -gt 0 ]; then
  for i in $(seq 0 $((CLAIM_COUNT - 1))); do
    TICKET_ID=$(echo "$CLAIMS_JSON" | jq -r ".[$i].ticket_id")
    VERDICT_FILE=$(echo "$CLAIMS_JSON" | jq -r ".[$i].verdict_file // empty")
    ARTIFACTS=$(echo "$CLAIMS_JSON" | jq -r ".[$i].artifacts[]? // empty")
    TEST_CMD=$(echo "$CLAIMS_JSON" | jq -r ".[$i].test_command // empty")

    VERIFIED=false
    VERIFY_METHOD="none"
    VERIFY_DETAIL=""

    # 1. Check verdict file if specified
    if [ -n "$VERDICT_FILE" ] && [ -f "$PROJECT_ROOT/$VERDICT_FILE" ]; then
      VERDICT=$(jq -r '.verdict // .status // "unknown"' "$PROJECT_ROOT/$VERDICT_FILE" 2>/dev/null || echo "unreadable")
      if [ "$VERDICT" = "PASS" ] || [ "$VERDICT" = "passed" ]; then
        VERIFIED=true
        VERIFY_METHOD="verdict_file"
        VERIFY_DETAIL="Verdict file $VERDICT_FILE reports PASS"
      else
        VERIFY_DETAIL="Verdict file $VERDICT_FILE reports $VERDICT"
      fi
    elif [ -n "$VERDICT_FILE" ]; then
      VERIFY_DETAIL="Verdict file $VERDICT_FILE does not exist"
    fi

    # 2. Check artifact files exist
    if [ "$VERIFIED" = false ] && [ -n "$ARTIFACTS" ]; then
      ALL_EXIST=true
      for artifact in $ARTIFACTS; do
        if [ ! -f "$PROJECT_ROOT/$artifact" ]; then
          ALL_EXIST=false
          VERIFY_DETAIL="Artifact $artifact not found"
          break
        fi
      done
      if [ "$ALL_EXIST" = true ]; then
        VERIFY_METHOD="artifact_check"
        VERIFY_DETAIL="All artifact files exist"
        # Artifacts existing is a weak signal — mark as claim, not proof
        # Only verdict files and test runs constitute proof
      fi
    fi

    # 3. Run test command if specified
    if [ "$VERIFIED" = false ] && [ -n "$TEST_CMD" ]; then
      if eval "$TEST_CMD" &>/dev/null; then
        VERIFIED=true
        VERIFY_METHOD="test_execution"
        VERIFY_DETAIL="Test command passed: $TEST_CMD"
      else
        VERIFY_DETAIL="Test command failed: $TEST_CMD"
      fi
    fi

    # 4. If no verification method available, mark unverified
    if [ "$VERIFIED" = false ] && [ -z "$VERIFY_METHOD" ] || [ "$VERIFY_METHOD" = "none" ]; then
      VERIFY_DETAIL="No verification method available for ticket $TICKET_ID"
    fi

    # Build result entry
    RESULT=$(jq -n \
      --arg tid "$TICKET_ID" \
      --arg vm "$VERIFY_METHOD" \
      --arg vd "$VERIFY_DETAIL" \
      --argjson v "$VERIFY_VERIFIED" \
      '{ticket_id: $tid, verified: $v, verification_method: $vm, detail: $vd}')

    # Actually set verified properly
    if [ "$VERIFIED" = true ]; then
      RESULT=$(echo "$RESULT" | jq '.verified = true')
      TRUSTED=$(echo "$TRUSTED" | jq --arg tid "$TICKET_ID" '. + [$tid]')
    else
      RESULT=$(echo "$RESULT" | jq '.verified = false')
      UNTRUSTED=$(echo "$UNTRUSTED" | jq --arg tid "$TICKET_ID" '. + [$tid]')
      HAS_UNTRUSTED=true
    fi

    RESULTS=$(echo "$RESULTS" | jq --argjson r "$RESULT" '. + [$r]')
  done
fi

# --- Determine recommendation ---

if [ "$HAS_UNTRUSTED" = true ]; then
  RECOMMENDATION="HALT_AND_VERIFY"
else
  RECOMMENDATION="RESUME"
fi

# --- Output final JSON ---

jq -n \
  --arg ts "$TIMESTAMP" \
  --argjson claims "$CLAIMS_JSON" \
  --argjson results "$RESULTS" \
  --argjson trusted "$TRUSTED" \
  --argjson untrusted "$UNTRUSTED" \
  --arg rec "$RECOMMENDATION" \
  '{
    failover_timestamp: $ts,
    previous_model_claims: $claims,
    verification_results: $results,
    trusted_claims: $trusted,
    untrusted_claims: $untrusted,
    recommendation: $rec
  }'
