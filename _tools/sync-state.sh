#!/bin/bash
set -e

# sync-state.sh — State synchronization for AgentVerse 2.0
# Usage: bash _tools/sync-state.sh
# Recalculates state from actual filesystem and updates CURRENT_STATE.json + ENVIRONMENT_STATE.json

NODE="$(command -v node 2>/dev/null || echo 'node')"
PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
AGENTVERSE="$PROJECT/AGENTVERSE"

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
CHANGES=""

log_change() {
  local what="$1"
  local from="$2"
  local to="$3"
  CHANGES="${CHANGES}${what}: ${from} -> ${to}\n"
}

# --- Read current state ---
CURRENT_STATE="$AGENTVERSE/CURRENT_STATE.json"
ENV_STATE="$AGENTVERSE/ENVIRONMENT_STATE.json"

if [ ! -f "$CURRENT_STATE" ]; then
  echo '{"error":"CURRENT_STATE.json not found"}'
  exit 1
fi

# =============================================
# Recalculate CURRENT_STATE from filesystem
# =============================================

# 1. Agent count
AGENT_COUNT=$(find "$PROJECT/.opencode/agents" -name "*.md" -type f 2>/dev/null | wc -l)
OLD_AGENT_COUNT=$($NODE -p "JSON.parse(require('fs').readFileSync('$CURRENT_STATE','utf8')).agent_count||0")

if [ "$AGENT_COUNT" != "$OLD_AGENT_COUNT" ]; then
  log_change "agent_count" "$OLD_AGENT_COUNT" "$AGENT_COUNT"
fi

# 2. Tickets — collect via node to avoid newline issues
TICKET_DATA=$($NODE -e "
  const fs = require('fs');
  const dir = '$AGENTVERSE/tickets';
  const files = fs.readdirSync(dir).filter(f => /^SCHOL-\d+\.md$/.test(f));
  const nums = files.map(f => parseInt(f.match(/SCHOL-(\d+)/)[1]));
  const latest = nums.length ? 'SCHOL-' + Math.max(...nums).toString().padStart(3,'0') : 'NONE';
  const count = files.length;

  const released = [];
  const inProgress = [];
  const delegated = [];
  const verificationBlocked = [];
  const openNext = [];

  const KNOWN = ['RELEASED','IN_PROGRESS','DELEGATED','VERIFICATION_BLOCKED','OPEN','CLOSED','RESOLVED'];
  function normalizeStatus(raw) {
    const s = raw.trim();
    if (!s) return '';
    const upper = s.toUpperCase();
    for (const k of ['IN PROGRESS','VERIFICATION BLOCKED','VERIFICATION_BLOCKED']) {
      if (upper.startsWith(k)) return k === 'IN PROGRESS' ? 'IN_PROGRESS' : 'VERIFICATION_BLOCKED';
    }
    const first = upper.split(/[\s(—:]/)[0];
    return KNOWN.includes(first) ? first : first;
  }

  for (const f of files) {
    const content = fs.readFileSync(dir + '/' + f, 'utf8');
    const statusMatch = content.match(/^-?\s*\*\*Status:\*\*\s*(.+)$/m);
    const status = statusMatch ? normalizeStatus(statusMatch[1]) : '';
    const id = 'SCHOL-' + f.match(/SCHOL-(\d+)/)[1];
    if (status === 'RELEASED') released.push(id);
    else if (status === 'IN_PROGRESS') inProgress.push(id);
    else if (status === 'DELEGATED') delegated.push(id);
    else if (status === 'VERIFICATION_BLOCKED') verificationBlocked.push(id);
    else if (status === 'OPEN') openNext.push(id);
  }

  process.stdout.write(JSON.stringify({
    count, latest,
    released: released.sort(),
    inProgress: inProgress.sort(),
    delegated: delegated.sort(),
    verificationBlocked: verificationBlocked.sort(),
    openNext: openNext.sort()
  }));
")

TICKET_COUNT=$(echo "$TICKET_DATA" | $NODE -p "JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).count")
LATEST_TICKET=$(echo "$TICKET_DATA" | $NODE -p "JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).latest")
RELEASED_TICKETS=$(echo "$TICKET_DATA" | $NODE -p "JSON.stringify(JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).released)")
IN_PROGRESS_TICKETS=$(echo "$TICKET_DATA" | $NODE -p "JSON.stringify(JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).inProgress)")
DELEGATED_TICKETS=$(echo "$TICKET_DATA" | $NODE -p "JSON.stringify(JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).delegated)")
VERIFICATION_BLOCKED_TICKETS=$(echo "$TICKET_DATA" | $NODE -p "JSON.stringify(JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).verificationBlocked)")
OPEN_NEXT_TICKETS=$(echo "$TICKET_DATA" | $NODE -p "JSON.stringify(JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).openNext)")

# 3. Latest KB entry
LATEST_KB=$($NODE -e "
  const fs = require('fs');
  try {
    const content = fs.readFileSync('$AGENTVERSE/KNOWLEDGE_BASE.md', 'utf8');
    const matches = [...content.matchAll(/KB-(\d+)/g)];
    const nums = matches.map(m => parseInt(m[1]));
    process.stdout.write(nums.length ? 'KB-' + Math.max(...nums).toString().padStart(4,'0') : 'NONE');
  } catch(e) { process.stdout.write('NONE'); }
")

# 4. RosarioSIS versions
SOURCE_VERSION=$($NODE -e "
  try {
    const fs = require('fs');
    const wh = fs.readFileSync('$PROJECT/scholapro/Warehouse.php', 'utf8');
    const m = wh.match(/ROSARIO_VERSION.*?'([\d.]+)'/);
    process.stdout.write(m ? m[1] : 'UNKNOWN');
  } catch(e) { process.stdout.write('NOT_FOUND'); }
")

PROD_VERSION="UNKNOWN"
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q school4; then
  PROD_VERSION=$(docker exec school4 php -r "require_once '/var/www/html/Warehouse.php'; echo constant('ROSARIO_VERSION');" 2>/dev/null || echo "UNKNOWN")
elif [ -f "$ENV_STATE" ]; then
  PROD_VERSION=$($NODE -p "JSON.parse(require('fs').readFileSync('$ENV_STATE','utf8')).production?.version||'UNKNOWN'")
fi

if [ "$SOURCE_VERSION" = "$PROD_VERSION" ] || [ "$PROD_VERSION" = "UNKNOWN" ]; then
  VERSION_CONSISTENCY="CONSISTENT"
else
  VERSION_CONSISTENCY="DIVERGENT"
fi

# --- Write new CURRENT_STATE ---
$NODE -e "
  const newState = {
    schema_version: '2.0',
    last_verified: '$TIMESTAMP',
    last_verifier: 'sync-state',
    project: 'scholapro',
    rosariosis_version: {
      source: '$SOURCE_VERSION',
      production: '$PROD_VERSION',
      consistency: '$VERSION_CONSISTENCY'
    },
    active_tickets: {
      IN_PROGRESS: $IN_PROGRESS_TICKETS,
      DELEGATED: $DELEGATED_TICKETS,
      VERIFICATION_BLOCKED: $VERIFICATION_BLOCKED_TICKETS,
      OPEN_NEXT: $OPEN_NEXT_TICKETS
    },
    released_tickets: $RELEASED_TICKETS,
    latest_ticket: '$LATEST_TICKET',
    latest_kb_entry: '$LATEST_KB',
    agent_count: $AGENT_COUNT,
    gate_chain: 'G0→G1→G2→G3→G4→G5→G6',
    truth_principle: 'CLAIM ≠ FACT',
    verification_requirement: 'Every gate advance requires machine-verifiable evidence, not agent declarations'
  };
  require('fs').writeFileSync('$CURRENT_STATE', JSON.stringify(newState, null, 2));
  process.stdout.write('CURRENT_STATE.json updated\n');
"

# =============================================
# Recalculate ENVIRONMENT_STATE from filesystem
# =============================================

if [ -f "$ENV_STATE" ]; then
  # Check MCP server configs via node
  MCP_STATUS=$($NODE -e "
    const names = ['sqlite','filesystem','memory','git','curl','playwright','sequential-thinking'];
    const cfg = JSON.parse(require('fs').readFileSync('$PROJECT/opencode.jsonc','utf8'));
    const out = {};
    names.forEach(n => { out[n] = (cfg.mcp && cfg.mcp[n]) ? 'CONFIGURED' : 'MISSING'; });
    process.stdout.write(JSON.stringify(out));
  ")

  # Database/container status
  DB_STATUS="UNKNOWN"
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -q db-school4; then
    DB_STATUS="RUNNING"
  elif [ -f "$AGENTVERSE/agentverse.db" ]; then
    DB_STATUS="LOCAL_ONLY"
  fi

  CONTAINER_STATUS="UNKNOWN"
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -q school4; then
    CONTAINER_STATUS="RUNNING"
  else
    CONTAINER_STATUS="NOT_RUNNING"
  fi

  SOURCE_STATUS="MISSING"
  if [ -d "$PROJECT/scholapro" ]; then
    SOURCE_STATUS="EXISTS"
    if [ -f "$PROJECT/scholapro/Warehouse.php" ]; then
      SOURCE_STATUS="VALID"
    fi
  fi

  CONSISTENCY_ISSUE="none"
  CONSISTENCY_BLOCKING="false"
  if [ "$VERSION_CONSISTENCY" = "DIVERGENT" ]; then
    CONSISTENCY_ISSUE="Source is $SOURCE_VERSION, production is $PROD_VERSION"
    CONSISTENCY_BLOCKING="true"
  fi

  $NODE -e "
    const newState = {
      schema_version: '2.0',
      last_checked: '$TIMESTAMP',
      source: {
        path: '$PROJECT/scholapro',
        version: '$SOURCE_VERSION',
        defined_in: 'scholapro/Warehouse.php:20',
        status: '$SOURCE_STATUS'
      },
      production: {
        container: 'school4',
        host: 'extravus-prod',
        url: 'https://school4.edunaija.online',
        version: '$PROD_VERSION',
        upgraded_from: '12.4.2',
        upgrade_date: '2026-08-17',
        status: '$CONTAINER_STATUS'
      },
      database: {
        container: 'db-school4',
        name: 'school4_db',
        engine: 'MariaDB',
        version: '12.3.2',
        status: '$DB_STATUS'
      },
      consistency: {
        source_production_match: $([ "$VERSION_CONSISTENCY" = "CONSISTENT" ] && echo "true" || echo "false"),
        issue: '$CONSISTENCY_ISSUE',
        resolution_required: '$([ "$CONSISTENCY_BLOCKING" = "true" ] && echo "Update scholapro/ source to $PROD_VERSION and commit." || echo "none")',
        blocking: $CONSISTENCY_BLOCKING
      },
      mcp_servers: $MCP_STATUS
    };
    require('fs').writeFileSync('$ENV_STATE', JSON.stringify(newState, null, 2));
    process.stdout.write('ENVIRONMENT_STATE.json updated\n');
  "
fi

# =============================================
# Output summary
# =============================================
echo ""
echo "STATE SYNC SUMMARY"
echo "=========================================="
echo "Timestamp: $TIMESTAMP"
echo ""
echo "Changes detected:"
if [ -n "$CHANGES" ]; then
  echo -e "$CHANGES"
else
  echo "  (no changes)"
fi
echo ""
echo "Current state:"
echo "  Agent count:         $AGENT_COUNT"
echo "  Latest ticket:       $LATEST_TICKET"
echo "  Latest KB entry:     $LATEST_KB"
echo "  Ticket count:        $TICKET_COUNT"
echo "  Source version:      $SOURCE_VERSION"
echo "  Production version:  $PROD_VERSION"
echo "  Version consistency: $VERSION_CONSISTENCY"
echo "  Container status:    $CONTAINER_STATUS"
echo "  DB status:           $DB_STATUS"
echo ""
echo "Files updated:"
echo "  - $CURRENT_STATE"
[ -f "$ENV_STATE" ] && echo "  - $ENV_STATE"
echo "=========================================="
echo "Sync complete."
