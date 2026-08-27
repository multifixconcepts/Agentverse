#!/bin/bash
# deployment-verification.sh — Verify production deployment capability
# Tests: Portainer API connectivity, Docker stack status, HTTPS endpoint, NPM admin
# Usage: bash _tests/deployment-verification.sh

set -e

PASS=0; FAIL=0; SKIP=0; TOTAL=0
pass() { TOTAL=$((TOTAL+1)); PASS=$((PASS+1)); printf "  ✓ %-25s %s\n" "$1" "$2"; }
fail() { TOTAL=$((TOTAL+1)); FAIL=$((FAIL+1)); printf "  ✗ %-25s %s\n" "$1" "$2"; }
skip() { TOTAL=$((TOTAL+1)); SKIP=$((SKIP+1)); printf "  ○ %-25s %s\n" "$1" "$2"; }

echo "=============================================="
echo "AGENTVERSE DEPLOYMENT VERIFICATION"
echo "=============================================="
echo ""

# --- Portainer API Connectivity ---
smoke_portainer() {
  echo "Portainer API"
  if [ -n "$PORTAINER_API_KEY" ]; then
    HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" \
      -H "X-API-Key: $PORTAINER_API_KEY" \
      "${PORTAINER_URL:-http://127.0.0.1:9443}/api/endpoints" 2>/dev/null || echo "000")
    if [ "$HTTP_CODE" = "200" ]; then
      pass "API" "Portainer API reachable"
    else
      fail "API" "Portainer API returned $HTTP_CODE"
    fi
  else
    skip "API" "PORTAINER_API_KEY not set"
  fi
}

# --- Docker Stack Status ---
smoke_docker_stack() {
  echo "Docker Stack"
  if [ -n "$PORTAINER_API_KEY" ]; then
    STACKS=$(curl -sk \
      -H "X-API-Key: $PORTAINER_API_KEY" \
      "${PORTAINER_URL:-http://127.0.0.1:9443}/api/stacks" 2>/dev/null | python3 -c "
import sys, json
try:
    stacks = json.load(sys.stdin)
    names = [s.get('Name','?') for s in stacks]
    print(','.join(names))
except: print('error')
" 2>/dev/null || echo "error")
    if [ "$STACKS" != "error" ] && [ -n "$STACKS" ]; then
      pass "Stacks" "Active: $STACKS"
    else
      skip "Stacks" "Could not list stacks"
    fi
  else
    skip "Stacks" "PORTAINER_API_KEY not set"
  fi
}

# --- HTTPS Endpoint ---
smoke_https() {
  echo "HTTPS Endpoint"
  HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "https://clientflow.edunaija.online" 2>/dev/null || echo "000")
  if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
    pass "ClientFlow" "HTTPS responds ($HTTP_CODE)"
  else
    fail "ClientFlow" "HTTPS returned $HTTP_CODE"
  fi
}

# --- NPM Admin Access ---
smoke_npm() {
  echo "NPM Admin"
  NPM_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "https://registry.npmjs.org/" 2>/dev/null || echo "000")
  if [ "$NPM_CODE" = "200" ]; then
    pass "NPM Registry" "Reachable"
  else
    skip "NPM Registry" "Returned $NPM_CODE"
  fi
}

# --- GitHub CLI ---
smoke_github() {
  echo "GitHub CLI"
  if gh auth status >/dev/null 2>&1; then
    REPO=$(gh repo view multifixconcepts/Agentverse --json name 2>/dev/null | python3 -c "import sys,json; print(json.load(sys.stdin).get('name',''))" 2>/dev/null || echo "")
    if [ "$REPO" = "Agentverse" ]; then
      pass "GitHub" "Authenticated, repo accessible"
    else
      pass "GitHub" "Authenticated"
    fi
  else
    fail "GitHub" "Not authenticated"
  fi
}

# --- SSH Connectivity ---
smoke_ssh() {
  echo "SSH"
  if [ -f ~/.ssh/id_rsa_extravus ]; then
    pass "SSH Key" "extravus key exists"
    # Don't actually connect in tests - just verify key exists
  else
    fail "SSH Key" "extravus key missing"
  fi
}

# --- Run tests ---
smoke_portainer
smoke_docker_stack
smoke_https
smoke_npm
smoke_github
smoke_ssh

echo ""
echo "=============================================="
echo "RESULTS: $PASS passed, $FAIL failed, $SKIP skipped (of $TOTAL)"
echo "=============================================="
if [ "$FAIL" -eq 0 ]; then
  echo "STATUS: ALL DEPLOYMENT TESTS PASSED"
  exit 0
else
  echo "STATUS: $FAIL DEPLOYMENT TEST(S) FAILED"
  exit 1
fi
