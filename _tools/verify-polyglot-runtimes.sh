#!/bin/bash
# AGENTVERSE 2.0.2 — Polyglot Runtime Verification
# Verifies all Tier 1 runtimes are available and functional

PASS=0
FAIL=0

check() {
  local name="$1" cmd="$2"
  if eval "$cmd" >/dev/null 2>&1; then
    echo "  ✓ $name: $(eval "$cmd" 2>&1)"
    PASS=$((PASS + 1))
  else
    echo "  ✗ $name: NOT FOUND"
    FAIL=$((FAIL + 1))
  fi
}

echo "=== AGENTVERSE 2.0.2 — Polyglot Runtime Verification ==="
echo ""
echo "Tier 1 Runtimes:"
check "Node.js" "node --version"
check "Python" "python3 --version"
check "Go" "go version | awk '{print \$3}'"
check "Rust" "rustc --version | awk '{print \$2}'"
check "Java" "java --version 2>&1 | head -1 | awk '{print \$2}'"
check "C#/.NET" "DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1 dotnet --version"

echo ""
echo "Toolchains:"
check "Git" "git --version | awk '{print \$3}'"
check "npm" "npm --version"
check "pip" "pip3 --version | awk '{print \$2}'"
check "Cargo" "cargo --version | awk '{print \$2}'"
check "javac" "javac --version 2>&1 | awk '{print \$2}'"

echo ""
echo "Results: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && echo "STATUS: ALL RUNTIMES VERIFIED" || echo "STATUS: SOME RUNTIMES MISSING"
