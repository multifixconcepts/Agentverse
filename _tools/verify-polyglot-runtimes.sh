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
check "Python" "/home/coder/python/bin/python3 --version"
check "Go" "/home/coder/go/bin/go version | awk '{print \$3}'"
check "Rust" "/home/coder/.cargo/bin/rustc --version | awk '{print \$2}'"
check "Java" "/home/coder/jdk-21.0.3+9/bin/java --version 2>&1 | head -1 | awk '{print \$2}'"
check "C#/.NET" "DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1 /home/coder/.dotnet/dotnet --version"

echo ""
echo "Toolchains:"
check "Git" "git --version | awk '{print \$3}'"
check "npm" "npm --version"
check "pip" "/home/coder/python/bin/pip3 --version | awk '{print \$2}'"
check "Cargo" "/home/coder/.cargo/bin/cargo --version | awk '{print \$2}'"
check "javac" "/home/coder/jdk-21.0.3+9/bin/javac --version 2>&1 | awk '{print \$2}'"

echo ""
echo "Results: $PASS passed, $FAIL failed"
[ $FAIL -eq 0 ] && echo "STATUS: ALL RUNTIMES VERIFIED" || echo "STATUS: SOME RUNTIMES MISSING"
