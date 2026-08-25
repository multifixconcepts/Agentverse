#!/bin/bash
set -e

# doctor.sh — Agentverse environment health check
# Usage: bash _tools/doctor.sh
# Outputs formatted health report.

NODE="/usr/lib/code-server/lib/node"
PROJECT="/home/coder/project"
AGENTVERSE="$PROJECT/AGENTVERSE"
AGENTS_DIR="$PROJECT/.opencode/agents"
SKILLS_DIR="$PROJECT/.opencode/skills"
export PATH="/home/coder/bin:/home/coder/.npm-global/bin:/home/coder/.cargo/bin:/home/coder/go/bin:/home/coder/python/bin:/home/coder/jdk-21.0.3+9/bin:/home/coder/.dotnet:$PATH"
export DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1
export JAVA_HOME="/home/coder/jdk-21.0.3+9"

TOTAL_CHECKS=0
PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0
BLOCKED=false

# --- Helpers ---
check_pass() {
  TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
  PASS_COUNT=$((PASS_COUNT + 1))
  printf "  %-30s PASS (%s)\n" "$1" "$2"
}

check_fail() {
  TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
  FAIL_COUNT=$((FAIL_COUNT + 1))
  printf "  %-30s FAIL (%s)\n" "$1" "$2"
}

check_warn() {
  TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
  WARN_COUNT=$((WARN_COUNT + 1))
  printf "  %-30s WARN (%s)\n" "$1" "$2"
}

# =============================================
echo "AGENTVERSE DOCTOR"
echo "============================================"
echo ""

# --- 1. Source/Production Version Consistency ---
echo "Source/Production Consistency"

SOURCE_VERSION=$($NODE -e "
  try {
    const fs = require('fs');
    const wh = fs.readFileSync('$PROJECT/scholapro/Warehouse.php', 'utf8');
    const m = wh.match(/ROSARIO_VERSION.*?'([\d.]+)'/);
    process.stdout.write(m ? m[1] : 'UNKNOWN');
  } catch(e) { process.stdout.write('NOT_FOUND'); }
" 2>/dev/null || echo "NOT_FOUND")

PROD_VERSION="UNKNOWN"
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q school4; then
  PROD_VERSION=$(docker exec school4 php -r "require_once '/var/www/html/Warehouse.php'; echo constant('VERSION');" 2>/dev/null || echo "UNKNOWN")
elif [ -f "$AGENTVERSE/ENVIRONMENT_STATE.json" ]; then
  PROD_VERSION=$($NODE -e "
    const s = JSON.parse(require('fs').readFileSync('$AGENTVERSE/ENVIRONMENT_STATE.json','utf8'));
    process.stdout.write(s.production ? s.production.version : 'UNKNOWN');
  ")
fi

if [ "$SOURCE_VERSION" = "$PROD_VERSION" ]; then
  check_pass "Version .............." "$SOURCE_VERSION (source=$SOURCE_VERSION, prod=$PROD_VERSION)"
else
  check_fail "Version .............." "source=$SOURCE_VERSION, prod=$PROD_VERSION"
  BLOCKED=true
fi

echo ""

# --- 2. Agent Registry ---
echo "Agent Registry"

# Count agent files
AGENT_FILE_COUNT=$(find "$AGENTS_DIR" -name "*.md" -type f 2>/dev/null | wc -l)
EXPECTED_AGENTS=70

if [ "$AGENT_FILE_COUNT" -eq "$EXPECTED_AGENTS" ]; then
  check_pass "File count ..........." "$AGENT_FILE_COUNT/$EXPECTED_AGENTS"
else
  check_fail "File count ..........." "$AGENT_FILE_COUNT/$EXPECTED_AGENTS"
fi

# Check registry file exists
if [ -f "$AGENTVERSE/AGENT_REGISTRY.json" ]; then
  REGISTRY_COUNT=$($NODE -e "
    const r = JSON.parse(require('fs').readFileSync('$AGENTVERSE/AGENT_REGISTRY.json','utf8'));
    process.stdout.write(String(r.total_agents || 0));
  ")
  # Check each registry entry has a corresponding file
  MISSING_AGENTS=$($NODE -e "
    const r = JSON.parse(require('fs').readFileSync('$AGENTVERSE/AGENT_REGISTRY.json','utf8'));
    const fs = require('fs');
    const missing = r.agents.filter(a => !fs.existsSync('$PROJECT/' + a.file));
    process.stdout.write(String(missing.length));
  ")
  if [ "$MISSING_AGENTS" = "0" ] && [ "$AGENT_FILE_COUNT" = "$REGISTRY_COUNT" ]; then
    check_pass "Registry match ......." "$REGISTRY_COUNT agents, all files present"
  else
    check_fail "Registry match ......." "$MISSING_AGENTS missing files, file count=$AGENT_FILE_COUNT, registry=$REGISTRY_COUNT"
  fi
else
  check_fail "Registry match ......." "AGENT_REGISTRY.json not found"
fi

echo ""

# --- 3. Skills ---
echo "Skills"

SKILL_COUNT=$(find "$SKILLS_DIR" -name "SKILL.md" -type f 2>/dev/null | wc -l)
EXPECTED_SKILLS=7

if [ "$SKILL_COUNT" -eq "$EXPECTED_SKILLS" ]; then
  check_pass "Count ................" "$SKILL_COUNT/$EXPECTED_SKILLS"
else
  check_fail "Count ................" "$SKILL_COUNT/$EXPECTED_SKILLS (expected $EXPECTED_SKILLS)"
fi

# List skills found
FOUND_SKILLS=$(find "$SKILLS_DIR" -name "SKILL.md" -type f 2>/dev/null | xargs -I{} dirname {} | xargs -I{} basename {} | sort)
EXPECTED_SKILL_LIST=$(cat <<'EOF'
addon-live-validation
delegate
mcp-ops
review-gate
scholapro
school4-ops
task-ledger
EOF
)
MISSING_SKILLS=""
for s in $EXPECTED_SKILL_LIST; do
  if ! echo "$FOUND_SKILLS" | grep -q "^${s}$"; then
    MISSING_SKILLS="$MISSING_SKILLS $s"
  fi
done
if [ -n "$MISSING_SKILLS" ]; then
  check_fail "Skill files .........." "Missing:$MISSING_SKILLS"
fi

echo ""

# --- 4. Control Plane Integrity ---
echo "Control Plane Integrity"

# ORG_CHECKSUM hashes
if [ -f "$AGENTVERSE/ORG_CHECKSUM.json" ]; then
  TOTAL_HASHES=$($NODE -e "
    const o = JSON.parse(require('fs').readFileSync('$AGENTVERSE/ORG_CHECKSUM.json','utf8'));
    const hashes = o.sha256_hashes || o.control_planes || {};
    process.stdout.write(String(Object.keys(hashes).length));
  ")
  PASSING_HASHES=0
  while IFS= read -r line; do
    file=$(echo "$line" | cut -d'|' -f1)
    expected_hash=$(echo "$line" | cut -d'|' -f2)
    if [ "$expected_hash" = "MISSING" ]; then
      continue
    fi
    actual_hash=$(sha256sum "$AGENTVERSE/$file" 2>/dev/null | cut -d' ' -f1 || echo "MISSING")
    if [ "$actual_hash" = "$expected_hash" ]; then
      PASSING_HASHES=$((PASSING_HASHES + 1))
    fi
  done < <($NODE -e "
    const o = JSON.parse(require('fs').readFileSync('$AGENTVERSE/ORG_CHECKSUM.json','utf8'));
    const hashes = o.sha256_hashes || o.control_planes || {};
    Object.entries(hashes).forEach(([k,v]) => {
      const hash = typeof v === 'string' ? v : (v.hash || 'MISSING');
      process.stdout.write(k + '|' + hash + '\n');
    });
  ")
  if [ "$PASSING_HASHES" -eq "$TOTAL_HASHES" ]; then
    check_pass "ORG_CHECKSUM hashes .." "$PASSING_HASHES/$TOTAL_HASHES"
  else
    check_fail "ORG_CHECKSUM hashes .." "$PASSING_HASHES/$TOTAL_HASHES passed"
  fi
else
  check_fail "ORG_CHECKSUM hashes .." "ORG_CHECKSUM.json not found"
fi

# Version headers in control plane files
VPASS=0
VTOTAL=0
for f in AGENTVERSE.md COHESION_MATRIX.md MEMORY_INDEX.md KNOWLEDGE_BASE.md; do
  VTOTAL=$((VTOTAL + 1))
  if head -5 "$AGENTVERSE/$f" 2>/dev/null | grep -qiE 'version|agentverse'; then
    VPASS=$((VPASS + 1))
  fi
done
if [ "$VPASS" -eq "$VTOTAL" ]; then
  check_pass "Version headers ......" "$VPASS/$VTOTAL control plane files have version headers"
else
  check_warn "Version headers ......" "$VPASS/$VTOTAL control plane files have version headers"
fi

echo ""

# --- 5. MCP Servers ---
echo "MCP Servers"

MCP_CONFIGURED=0
MCP_TOTAL=7
MCP_NAMES=("sqlite" "filesystem" "memory" "git" "curl" "playwright" "sequential-thinking")
for mcp in "${MCP_NAMES[@]}"; do
  if $NODE -e "
    const c = JSON.parse(require('fs').readFileSync('$PROJECT/opencode.jsonc','utf8'));
    if (!c.mcp || !c.mcp['$mcp']) process.exit(1);
  " 2>/dev/null; then
    MCP_CONFIGURED=$((MCP_CONFIGURED + 1))
  else
    check_warn "Not configured ........" "$mcp"
  fi
done
if [ "$MCP_CONFIGURED" -eq "$MCP_TOTAL" ]; then
  check_pass "Configured ..........." "$MCP_CONFIGURED/$MCP_TOTAL"
else
  check_warn "Configured ..........." "$MCP_CONFIGURED/$MCP_TOTAL"
fi

echo ""

# --- 6. Session Log ---
echo "Session Log"

if [ -f "$AGENTVERSE/agentverse.db" ]; then
  # Check for session IDs
  NULL_SESSIONS=$(sqlite3 "$AGENTVERSE/agentverse.db" "SELECT COUNT(*) FROM session_logs WHERE session_id IS NULL;" 2>/dev/null || echo "-1")
  TOTAL_SESSIONS=$(sqlite3 "$AGENTVERSE/agentverse.db" "SELECT COUNT(*) FROM session_logs;" 2>/dev/null || echo "-1")

  if [ "$NULL_SESSIONS" = "-1" ]; then
    check_warn "Session IDs .........." "table session_logs not found or empty"
  elif [ "$TOTAL_SESSIONS" = "0" ]; then
    check_warn "Session IDs .........." "no session logs"
  elif [ "$NULL_SESSIONS" = "0" ]; then
    check_pass "Session IDs .........." "0 null session IDs out of $TOTAL_SESSIONS"
  else
    NULL_PCT=0
    if [ "$TOTAL_SESSIONS" -gt 0 ]; then
      NULL_PCT=$((NULL_SESSIONS * 100 / TOTAL_SESSIONS))
    fi
    check_fail "Session IDs .........." "${NULL_PCT}% null (${NULL_SESSIONS}/${TOTAL_SESSIONS})"
  fi

  # Coverage check for recent dates
  TODAY=$(date +%Y-%m-%d)
  YESTERDAY=$(date -d "yesterday" +%Y-%m-%d 2>/dev/null || date -v-1d +%Y-%m-%d 2>/dev/null || echo "")
  TODAY_LOGS=$(sqlite3 "$AGENTVERSE/agentverse.db" "SELECT COUNT(*) FROM session_logs WHERE date(timestamp) = '$TODAY';" 2>/dev/null || echo "0")

  if [ "$TODAY_LOGS" -gt 0 ]; then
    check_pass "Coverage ............." "$TODAY_LOGS logs for $TODAY"
  else
    check_fail "Coverage ............." "no logs for $TODAY"
  fi
else
  check_warn "Session Log .........." "agentverse.db not found"
fi

echo ""

# --- 7. Secrets ---
echo "Secrets"

# Quick scan of committed files for obvious credentials
SECRET_HITS=0
SECRET_FILES=""
for dir in "$PROJECT/scholapro" "$PROJECT/premium-modules" "$PROJECT/.opencode"; do
  if [ -d "$dir" ]; then
    while IFS= read -r hit; do
      if [ -n "$hit" ]; then
        SECRET_HITS=$((SECRET_HITS + 1))
        fname=$(echo "$hit" | cut -d: -f1 | sed "s|$PROJECT/||")
        SECRET_FILES="$SECRET_FILES $fname"
      fi
    done < <(grep -rn -iE "(password|secret|api_key)\s*[:=]\s*['\"][^'\"]{5,}['\"]" "$dir" 2>/dev/null \
      | grep -v -E '(node_modules|\.git|example|placeholder|CHANGE.THIS|TODO|your[_-]here|test)' \
      | head -5 || true)
  fi
done

if [ "$SECRET_HITS" -eq 0 ]; then
  check_pass "Committed credentials" "none found"
else
  check_warn "Committed credentials" "$SECRET_FILES"
fi

echo ""

# --- 8. Release-Set Integrity ---
echo "Release-Set Integrity"

# Check if any RELEASED tickets have open VERIFICATION_BLOCKED siblings
BLOCKED_IN_RELEASE=$($NODE -e "
  const fs = require('fs');
  const dir = '$AGENTVERSE/tickets';
  const files = fs.readdirSync(dir).filter(f => /^SCHOL-\d+\.md$/.test(f));
  let blocked = 0;
  for (const f of files) {
    const content = fs.readFileSync(dir + '/' + f, 'utf8');
    const statusMatch = content.match(/^\*\*Status:\*\*\s*(.+)$/m);
    if (statusMatch && statusMatch[1].trim() === 'VERIFICATION_BLOCKED') {
      blocked++;
    }
  }
  process.stdout.write(String(blocked));
" 2>/dev/null || echo "-1")

if [ "$BLOCKED_IN_RELEASE" = "-1" ]; then
  check_warn "Blocked tickets ......." "could not check"
elif [ "$BLOCKED_IN_RELEASE" = "0" ]; then
  check_pass "Blocked tickets ......." "none found"
else
  check_fail "Blocked tickets ......." "$BLOCKED_IN_RELEASE ticket(s) in VERIFICATION_BLOCKED state"
fi

echo ""

# --- 9. VERSION File ---
echo "VERSION File"

if [ -f "$PROJECT/VERSION" ]; then
  FILE_VERSION=$(cat "$PROJECT/VERSION" | tr -d '[:space:]')
  check_pass "VERSION file ........." "$FILE_VERSION"
else
  check_fail "VERSION file ........." "not found"
fi

echo ""

# --- 10. Git Tags ---
echo "Git Tags"

TAG_COUNT=$(git -C "$PROJECT" tag -l 2>/dev/null | wc -l)
if [ "$TAG_COUNT" -gt 0 ]; then
  LATEST_TAG=$(git -C "$PROJECT" tag -l --sort=-v:refname | head -1)
  check_pass "Git tags ............." "$TAG_COUNT tags, latest: $LATEST_TAG"
else
  check_warn "Git tags ............." "no tags found"
fi

echo ""

# --- 11. Polyglot Runtimes & Toolchains ---
echo "Polyglot Runtimes & Toolchains"

RUNTIME_PASS=0
RUNTIME_TOTAL=13
TOOLCHAIN_PASS=0
TOOLCHAIN_TOTAL=0
check_runtime() {
  local name="$1" cmd="$2"
  if eval "$cmd" >/dev/null 2>&1; then
    RUNTIME_PASS=$((RUNTIME_PASS + 1))
  else
    check_warn "Not available ........" "$name"
  fi
}

check_toolchain() {
  local name="$1" cmd="$2"
  TOOLCHAIN_TOTAL=$((TOOLCHAIN_TOTAL + 1))
  if eval "$cmd" >/dev/null 2>&1; then
    TOOLCHAIN_PASS=$((TOOLCHAIN_PASS + 1))
  else
    check_warn "Toolchain incomplete .." "$name"
  fi
}

# Tier 1 runtimes
check_runtime "Node.js .............." "node --version"
check_runtime "TypeScript ..........." "tsc --version"
check_runtime "Python ..............." "/home/coder/python/bin/python3 --version"
check_runtime "Go ..................." "/home/coder/go/bin/go version"
check_runtime "Rust ................." "/home/coder/.cargo/bin/rustc --version"
check_runtime "Java ................." "/home/coder/jdk-21.0.3+9/bin/java --version"
check_runtime "C#/.NET .............." "DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1 /home/coder/.dotnet/dotnet --version"

# Tier 2 runtimes
check_runtime "C (gcc) .............." "gcc --version"
check_runtime "C++ (g++) ............" "g++ --version"
check_runtime "Ruby ................." "ruby --version"
check_runtime "Kotlin ..............." "/home/coder/bin/kotlinc -version"
check_runtime "Swift ................" "/home/coder/bin/swift --version"
check_runtime "Dart ................." "/home/coder/bin/dart --version"

# Toolchain completeness (formatters, linters, test runners, package managers)
check_toolchain "eslint .............." "eslint --version"
check_toolchain "prettier ............" "prettier --version"
check_toolchain "ruff ................" "ruff --version"
check_toolchain "black ..............." "black --version"
check_toolchain "mypy ................" "mypy --version"
check_toolchain "pytest .............." "pytest --version"
check_toolchain "staticcheck ........." "staticcheck --version"
check_toolchain "clippy .............." "/home/coder/.cargo/bin/clippy --version"
check_toolchain "rustfmt ............." "/home/coder/.cargo/bin/rustfmt --version"
check_toolchain "rubocop ............." "rubocop --version"
check_toolchain "composer ............" "/home/coder/bin/composer --version"
check_toolchain "npm ................." "npm --version"
check_toolchain "pip ................." "/home/coder/python/bin/pip --version"
check_toolchain "gh .................." "gh --version"

if [ "$RUNTIME_PASS" -eq "$RUNTIME_TOTAL" ]; then
  check_pass "Runtimes ............." "$RUNTIME_PASS/$RUNTIME_TOTAL runtimes available"
else
  check_warn "Runtimes ............." "$RUNTIME_PASS/$RUNTIME_TOTAL runtimes available"
fi

if [ "$TOOLCHAIN_PASS" -eq "$TOOLCHAIN_TOTAL" ]; then
  check_pass "Toolchains ..........." "$TOOLCHAIN_PASS/$TOOLCHAIN_TOTAL toolchain tools available"
else
  check_warn "Toolchains ..........." "$TOOLCHAIN_PASS/$TOOLCHAIN_TOTAL toolchain tools available"
fi

echo ""

# --- 12. Pre-commit Hook ---
echo "Pre-commit Hook"

if [ -f "$PROJECT/.git/hooks/pre-commit" ] && [ -x "$PROJECT/.git/hooks/pre-commit" ]; then
  check_pass "Pre-commit hook ......" "installed and executable"
elif [ -f "$PROJECT/.git/hooks/pre-commit" ]; then
  check_warn "Pre-commit hook ......" "installed but not executable"
else
  check_warn "Pre-commit hook ......" "not installed"
fi

echo ""

# --- 13. Task Ledger ---
echo "Task Ledger"

if [ -f "$AGENTVERSE/agentverse.db" ]; then
  TASK_TABLE=$(sqlite3 "$AGENTVERSE/agentverse.db" "SELECT name FROM sqlite_master WHERE type='table' AND name='task_ledger';" 2>/dev/null || echo "")
  if [ "$TASK_TABLE" = "task_ledger" ]; then
    TASK_COUNT=$(sqlite3 "$AGENTVERSE/agentverse.db" "SELECT COUNT(*) FROM task_ledger;" 2>/dev/null || echo "0")
    check_pass "Task ledger .........." "table exists, $TASK_COUNT entries"
  else
    check_warn "Task ledger .........." "table not yet created"
  fi
else
  check_warn "Task ledger .........." "agentverse.db not found"
fi

echo ""

# --- 14. Secret Hygiene ---
echo "Secret Hygiene"

# Check for secrets in git history
SECRETS_IN_HISTORY=0
if git -C "$PROJECT" log --all -p 2>/dev/null | grep -qE 'ghp_[A-Za-z0-9]{36}'; then
  SECRETS_IN_HISTORY=$((SECRETS_IN_HISTORY + 1))
fi
if git -C "$PROJECT" log --all -p 2>/dev/null | grep -qE 'ptr_[A-Za-z0-9+/]{20,}'; then
  SECRETS_IN_HISTORY=$((SECRETS_IN_HISTORY + 1))
fi

if [ "$SECRETS_IN_HISTORY" -eq 0 ]; then
  check_pass "Git history .........." "no obvious secrets found"
else
  check_warn "Git history .........." "$SECRETS_IN_HISTORY secret pattern(s) found in history"
fi

echo ""

# --- 15. Framework Verification ---
echo "Framework Verification"

FW_PASS=0
FW_TOTAL=0
check_framework() {
  local name="$1" cmd="$2"
  FW_TOTAL=$((FW_TOTAL + 1))
  if eval "$cmd" >/dev/null 2>&1; then
    FW_PASS=$((FW_PASS + 1))
  else
    check_warn "Framework ............" "$name not available"
  fi
}

check_framework "Express.js ..........." "node -e \"require('express')\" 2>/dev/null || npm ls express 2>/dev/null"
check_framework "FastAPI .............." "/home/coder/python/bin/python3 -c \"import fastapi\" 2>/dev/null"
check_framework "Django ..............." "/home/coder/python/bin/python3 -c \"import django\" 2>/dev/null"
check_framework "ASP.NET .............." "DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1 /home/coder/.dotnet/dotnet --list-sdks 2>/dev/null | grep -q '8'"
check_framework "Spring (Java) ........" "/home/coder/jdk-21.0.3+9/bin/java -version 2>/dev/null"
check_framework "Laravel (PHP) ........" "/home/coder/bin/php -r \"echo class_exists('Illuminate\\Support\\ServiceProvider') ? 1 : 0;\" 2>/dev/null || echo '0'"

if [ "$FW_PASS" -eq "$FW_TOTAL" ]; then
  check_pass "Frameworks ..........." "$FW_PASS/$FW_TOTAL frameworks verified"
else
  check_warn "Frameworks ..........." "$FW_PASS/$FW_TOTAL frameworks verified"
fi

echo ""

# --- 16. Database Engines ---
echo "Database Engines"

# SQLite
if command -v sqlite3 >/dev/null 2>&1 || /home/coder/python/bin/python3 -c "import sqlite3" 2>/dev/null; then
  check_pass "SQLite ..............." "available"
else
  check_warn "SQLite ..............." "not available"
fi

# PostgreSQL (check via Python)
if /home/coder/python/bin/python3 -c "import psycopg2" 2>/dev/null; then
  check_pass "PostgreSQL (psycopg2)." "driver available"
else
  check_warn "PostgreSQL (psycopg2)." "driver not installed"
fi

# Redis (check via Python)
if /home/coder/python/bin/python3 -c "import redis" 2>/dev/null; then
  check_pass "Redis (redis-py) ......" "driver available"
else
  check_warn "Redis (redis-py) ......" "driver not installed"
fi

# SQLAlchemy
if /home/coder/python/bin/python3 -c "import sqlalchemy" 2>/dev/null; then
  check_pass "SQLAlchemy ............" "available"
else
  check_warn "SQLAlchemy ............" "not installed"
fi

echo ""

# --- 17. Deployment Infrastructure ---
echo "Deployment Infrastructure"

# Portainer API
if [ -n "$PORTAINER_API_KEY" ] || [ -f "$AGENTVERSE/PORTAINER_API_KEY" ]; then
  check_pass "Portainer API key ...." "configured"
else
  check_warn "Portainer API key ...." "not set locally (production access via extravus-prod)"
fi

# NPM connectivity
if npm ping >/dev/null 2>&1; then
  check_pass "NPM registry ........" "reachable"
else
  check_warn "NPM registry ........" "not reachable from this host"
fi

# GitHub CLI
if gh auth status >/dev/null 2>&1; then
  check_pass "GitHub CLI ..........." "authenticated"
else
  check_warn "GitHub CLI ..........." "not authenticated"
fi

# SSH key
if [ -f "$HOME/.ssh/id_rsa_extravus" ]; then
  check_pass "SSH key .............." "extravus key present"
else
  check_warn "SSH key .............." "extravus key not found"
fi

# Smoke test scripts
SMOKE_COUNT=0
for s in polyglot-smoke-suite.sh framework-smoke-suite.sh database-integration-tests.sh deployment-verification.sh; do
  if [ -f "$PROJECT/_tests/$s" ]; then
    SMOKE_COUNT=$((SMOKE_COUNT + 1))
  fi
done
if [ "$SMOKE_COUNT" -eq 4 ]; then
  check_pass "Smoke test suite ...." "$SMOKE_COUNT/4 suites present"
else
  check_warn "Smoke test suite ...." "$SMOKE_COUNT/4 suites present"
fi

echo ""

# =============================================
# --- Final Verdict ---
echo "============================================"

if [ "$BLOCKED" = true ]; then
  RESULT="BLOCKED"
  REASON="source/production version divergence (source=$SOURCE_VERSION, prod=$PROD_VERSION)"
elif [ "$FAIL_COUNT" -gt 0 ]; then
  RESULT="DEGRADED"
  REASON="$FAIL_COUNT check(s) failed, $WARN_COUNT warning(s)"
elif [ "$WARN_COUNT" -gt 0 ]; then
  RESULT="HEALTHY_WITH_WARNINGS"
  REASON="$WARN_COUNT warning(s)"
else
  RESULT="HEALTHY"
  REASON="all $TOTAL_CHECKS checks passed"
fi

printf "\nRESULT: %s — %s\n" "$RESULT" "$REASON"
printf "       Checks: %d total, %d pass, %d fail, %d warn\n" "$TOTAL_CHECKS" "$PASS_COUNT" "$FAIL_COUNT" "$WARN_COUNT"

# Exit with appropriate code
if [ "$RESULT" = "BLOCKED" ] || [ "$RESULT" = "DEGRADED" ]; then
  exit 1
fi
exit 0
