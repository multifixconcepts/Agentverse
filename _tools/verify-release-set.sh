#!/bin/bash
# verify-release-set.sh — Mechanical release-set verification for AgentVerse 2.0
# Usage: bash _tools/verify-release-set.sh <TICKET_ID> [TICKET_ID ...]
#   or:  bash _tools/verify-release-set.sh --all-released
#
# Inspects actual ticket state data. Fails closed on any uncertainty.
# Exit codes: 0 = ALLOW, 1 = DENY

NODE="$(command -v node 2>/dev/null || echo 'node')"
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT="$(cd "$SELF_DIR/.." && pwd)"
AGENTVERSE="$PROJECT/AGENTVERSE"
TICKET_DIR="$AGENTVERSE/tickets"

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Parse arguments
TICKET_IDS=()
ALL_RELEASED=false

for arg in "$@"; do
  if [ "$arg" = "--all-released" ]; then
    ALL_RELEASED=true
  else
    TICKET_IDS+=("$arg")
  fi
done

# If --all-released, collect all RELEASED tickets
if [ "$ALL_RELEASED" = true ]; then
  RELEASED=$($NODE -e "
    const fs = require('fs');
    const dir = '$TICKET_DIR';
    const files = fs.readdirSync(dir).filter(f => /^SCHOL-\d+\.md$/.test(f));
    const released = [];
    for (const f of files) {
      const content = fs.readFileSync(dir + '/' + f, 'utf8');
      const statusMatch = content.match(/^\*\*Status:\*\*\s*(.+)$/m);
      if (statusMatch && statusMatch[1].trim() === 'RELEASED') {
        released.push('SCHOL-' + f.match(/SCHOL-(\d+)/)[1]);
      }
    }
    process.stdout.write(released.join(' '));
  ")
  TICKET_IDS=($RELEASED)
fi

if [ ${#TICKET_IDS[@]} -eq 0 ]; then
  echo '{"error":"Usage: bash verify-release-set.sh <TICKET_ID> [...] or --all-released"}'
  exit 1
fi

# Write verification script to temp file
VERIFY_SCRIPT=$(mktemp /tmp/verify-release-set-XXXXX.js)
trap "rm -f $VERIFY_SCRIPT" EXIT

cat > "$VERIFY_SCRIPT" << 'NODESCRIPT'
const fs = require('fs');
const TICKET_DIR = process.env.TICKET_DIR;
const TIMESTAMP = process.env.TIMESTAMP;
const ticketIds = process.env.TICKET_LIST.split(' ').filter(Boolean);

const results = [];
const blockers = [];
let hasUnknown = false;
let hasAmbiguous = false;

for (const id of ticketIds) {
  const ticketFile = TICKET_DIR + '/' + id + '.md';
  let status = 'UNKNOWN';
  let exists = false;
  let hasVerdict = false;
  let verdictResult = '';

  if (fs.existsSync(ticketFile)) {
    exists = true;
    const content = fs.readFileSync(ticketFile, 'utf8');
    const statusMatch = content.match(/^\*\*Status:\*\*\s*(.+)$/m);
    status = statusMatch ? statusMatch[1].trim() : 'UNKNOWN';

    const escapedId = id.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const verdictPattern = new RegExp('^' + escapedId + '-G[0-9]+\\.verdict\\.json$');
    const verdictFiles = fs.readdirSync(TICKET_DIR).filter(f => verdictPattern.test(f));
    if (verdictFiles.length > 0) {
      hasVerdict = true;
      try {
        const verdict = JSON.parse(fs.readFileSync(TICKET_DIR + '/' + verdictFiles[0], 'utf8'));
        verdictResult = verdict.verdict || 'UNKNOWN';
      } catch(e) {
        verdictResult = 'PARSE_ERROR';
      }
    }
  }

  let eligible = false;
  let reason = '';

  if (!exists) {
    reason = 'Ticket file not found';
    hasUnknown = true;
  } else if (status === 'UNKNOWN') {
    reason = 'Ticket status could not be read';
    hasUnknown = true;
  } else if (status === 'VERIFICATION_BLOCKED') {
    reason = 'VERIFICATION_BLOCKED - release prohibited';
    blockers.push({ id, status, reason });
  } else if (status === 'IN_PROGRESS' || status === 'DELEGATED') {
    reason = 'Ticket not yet implemented';
    blockers.push({ id, status, reason });
  } else if (status === 'RELEASED') {
    eligible = true;
    reason = 'Already released';
  } else if (status.startsWith('OPEN')) {
    reason = 'Ticket not started';
    blockers.push({ id, status, reason });
  } else if (status === 'IMPLEMENTED' || status === 'TESTED' || status === 'VERIFIED' || status === 'ACCEPTED') {
    eligible = true;
    reason = 'Status allows release eligibility check';
  } else {
    reason = 'Ambiguous status: ' + status;
    hasAmbiguous = true;
  }

  results.push({ id, exists, status, has_verdict: hasVerdict, verdict_result: verdictResult, eligible, reason });
}

let verdict, exitCode;
const blockedCount = blockers.length;
const totalCount = results.length;

if (blockedCount > 0) { verdict = 'DENY'; exitCode = 1; }
else if (hasUnknown) { verdict = 'DENY'; exitCode = 1; }
else if (hasAmbiguous) { verdict = 'DENY'; exitCode = 1; }
else { verdict = 'ALLOW'; exitCode = 0; }

const output = {
  timestamp: TIMESTAMP,
  release_set: ticketIds,
  total: totalCount,
  blockers: blockedCount,
  verdict,
  exit_code: exitCode,
  results,
  ...(blockedCount > 0 ? { blocker_details: blockers } : {})
};

process.stdout.write(JSON.stringify(output, null, 2));
process.exit(exitCode);
NODESCRIPT

export TICKET_DIR TIMESTAMP TICKET_LIST="${TICKET_IDS[*]}"
$NODE "$VERIFY_SCRIPT"
EXIT_CODE=$?

# Write release-set verification record
RECORD_FILE="$AGENTVERSE/tickets/SCHOL-814-release-set-verification.json"
$NODE -e "const d=JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')); require('fs').writeFileSync('$RECORD_FILE', JSON.stringify(d,null,2));" < <($NODE "$VERIFY_SCRIPT" 2>/dev/null) 2>/dev/null || true

exit $EXIT_CODE
