#!/bin/bash
# verify-release-set.sh — Mechanical release-set verification for AgentVerse 2.0
# Usage: bash _tools/verify-release-set.sh <TICKET_ID> [TICKET_ID ...]
#   or:  bash _tools/verify-release-set.sh --all-released
#   or:  bash _tools/verify-release-set.sh --allow-missing-verdicts <TICKET_ID> [...]
#
# Inspects actual ticket state data. Fails closed on any uncertainty.
# Exit codes: 0 = ALLOW, 1 = DENY
#
# Verdict gating:
#   - Reads ALL <id>-G*.verdict.json files (not just the first).
#   - Any applicable verdict of FAIL or NOT_VERIFIED (or a malformed/
#     ambiguous verdict) DENIES release eligibility.
#   - A passing early gate (e.g. G0) never masks a failing later gate (e.g. G5).
#   - Missing verdict evidence DENIES by default (deterministic, not silent);
#     --allow-missing-verdicts upgrades it to a visible WARN (fail-open).
#   - An explicit disposition entry in
#     AGENTVERSE/history/release-verdict-dispositions.json may DISPOSE a
#     specific historical FAIL/NOT_VERIFIED (see SCHOL-109 G5). A disposition
#     does NOT convert the verdict to PASS; it is reported as DISPOSITIONED
#     with its rationale and remains visible in verification output.

NODE="$(command -v node 2>/dev/null || echo 'node')"
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT="$(cd "$SELF_DIR/.." && pwd)"
AGENTVERSE="$PROJECT/AGENTVERSE"
# AV_TICKET_DIR / AV_DISPOSITIONS_FILE allow fixture-based testing against
# isolated temporary directories without touching the live ticket area.
TICKET_DIR="${AV_TICKET_DIR:-$AGENTVERSE/tickets}"
DISPOSITIONS_FILE="${AV_DISPOSITIONS_FILE:-$AGENTVERSE/history/release-verdict-dispositions.json}"

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Parse arguments
TICKET_IDS=()
ALL_RELEASED=false
ALLOW_MISSING=false

for arg in "$@"; do
  if [ "$arg" = "--all-released" ]; then
    ALL_RELEASED=true
  elif [ "$arg" = "--allow-missing-verdicts" ]; then
    ALLOW_MISSING=true
  else
    TICKET_IDS+=("$arg")
  fi
done

# If --all-released, collect all RELEASED tickets (normalized status parsing:
# bare or bullet-prefixed, first declaration, RELEASED* with optional annotation)
if [ "$ALL_RELEASED" = true ]; then
  RELEASED=$($NODE -e "
    const fs = require('fs');
    const dir = '$TICKET_DIR';
    const files = fs.readdirSync(dir).filter(f => /^SCHOL-\d+\.md$/.test(f));
    const released = [];
    for (const f of files) {
      const content = fs.readFileSync(dir + '/' + f, 'utf8');
      const m = content.match(/^-?\s*\*\*Status:\*\*\s*(.+)$/m);
      if (m && /^RELEASED/i.test(m[1].trim())) {
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
const DISPOSITIONS_FILE = process.env.DISPOSITIONS_FILE;
const TIMESTAMP = process.env.TIMESTAMP;
const ALLOW_MISSING = process.env.ALLOW_MISSING_VERDICTS === 'true';
const ticketIds = process.env.TICKET_LIST.split(' ').filter(Boolean);

let dispositions = [];
if (DISPOSITIONS_FILE && fs.existsSync(DISPOSITIONS_FILE)) {
  try {
    const d = JSON.parse(fs.readFileSync(DISPOSITIONS_FILE, 'utf8'));
    dispositions = Array.isArray(d.entries) ? d.entries : [];
  } catch (e) {
    // Unreadable disposition artifact -> nothing is dispositioned (default block)
  }
}

function dispositionFor(id, gate) {
  const normGate = String(gate).replace(/^G/i, '');
  return dispositions.find(x => x.ticket === id &&
    (String(x.gate).replace(/^G/i, '') === normGate)) || null;
}

function normalizeStatus(raw) {
  const s = (raw || '').trim();
  if (!s) return '';
  const upper = s.toUpperCase();
  if (upper.startsWith('IN PROGRESS')) return 'IN_PROGRESS';
  const first = upper.split(/[\s(—:]/)[0];
  return first;
}

const results = [];
const blockers = [];
const handledDispositions = [];
let hasUnknown = false;
let hasAmbiguous = false;

// A deterministic verdict-classification for one verdict file.
// Returns { classification, verdict, status, gate, disposition }
function classifyVerdict(id, filename) {
  const gate = (filename.match(/-G([0-9]+)\.verdict\.json$/) || [])[1] || '?';
  let obj = null;
  let parseOk = true;
  try {
    obj = JSON.parse(fs.readFileSync(TICKET_DIR + '/' + filename, 'utf8'));
  } catch (e) {
    parseOk = false;
  }
  const disposition = dispositionFor(id, gate);

  if (!parseOk) {
    return { classification: 'PARSE_ERROR', verdict: 'PARSE_ERROR', status: '', gate, disposition };
  }

  const v = (obj.verdict || '').toString().trim().toUpperCase();
  const st = (obj.status || '').toString().trim().toUpperCase();

  if (disposition) {
    // Explicit historical disposition: NOT converted to PASS, reported as such.
    return { classification: 'DISPOSITIONED', verdict: v || 'UNKNOWN', status: st || 'UNKNOWN', gate, disposition };
  }

  if (v === 'FAIL') return { classification: 'FAIL', verdict: 'FAIL', status: st, gate, disposition: null };
  if (st === 'NOT_VERIFIED') return { classification: 'NOT_VERIFIED', verdict: v, status: 'NOT_VERIFIED', gate, disposition: null };
  if (v === 'PASS') return { classification: 'PASS', verdict: 'PASS', status: st, gate, disposition: null };
  return { classification: 'AMBIGUOUS', verdict: v || 'UNKNOWN', status: st, gate, disposition: null };
}

for (const id of ticketIds) {
  const ticketFile = TICKET_DIR + '/' + id + '.md';
  let status = 'UNKNOWN';
  let exists = false;

  if (fs.existsSync(ticketFile)) {
    exists = true;
    const content = fs.readFileSync(ticketFile, 'utf8');
    const statusMatch = content.match(/^-?\s*\*\*Status:\*\*\s*(.+)$/m);
    status = statusMatch ? normalizeStatus(statusMatch[1]) : 'UNKNOWN';
  }

  const escapedId = id.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
  const verdictPattern = new RegExp('^' + escapedId + '-G[0-9]+\\.verdict\\.json$');
  let verdictFiles = [];
  if (exists) {
    try {
      verdictFiles = fs.readdirSync(TICKET_DIR).filter(f => verdictPattern.test(f)).sort();
    } catch (e) { verdictFiles = []; }
  }

  const parsedVerdicts = verdictFiles.map(f => classifyVerdict(id, f));

  let eligible = false;
  let reason = '';
  const localBlockers = [];

  if (!exists) {
    reason = 'Ticket file not found';
    hasUnknown = true;
    localBlockers.push('TICKET_MISSING');
  } else if (status === 'UNKNOWN') {
    reason = 'Ticket status could not be read';
    hasUnknown = true;
    localBlockers.push('STATUS_UNKNOWN');
  } else if (status === 'VERIFICATION_BLOCKED') {
    reason = 'VERIFICATION_BLOCKED - release prohibited';
    localBlockers.push('VERIFICATION_BLOCKED');
  } else if (status === 'IN_PROGRESS' || status === 'DELEGATED') {
    reason = 'Ticket not yet implemented';
    localBlockers.push('NOT_IMPLEMENTED');
  } else if (status === 'RELEASED' || status === 'IMPLEMENTED' || status === 'TESTED' || status === 'VERIFIED' || status === 'ACCEPTED') {
    eligible = true;
    reason = status === 'RELEASED' ? 'Already released' : 'Status allows release eligibility check';
  } else if (status === 'OPEN' || status.startsWith('OPEN')) {
    reason = 'Ticket not started';
    localBlockers.push('NOT_STARTED');
  } else {
    reason = 'Ambiguous status: ' + status;
    hasAmbiguous = true;
    localBlockers.push('STATUS_AMBIGUOUS');
  }

  // Verdict gating applies to eligible tickets.
  const verdictBlockers = [];
  for (const v of parsedVerdicts) {
    if (v.classification === 'FAIL' || v.classification === 'NOT_VERIFIED' ||
        v.classification === 'PARSE_ERROR' || v.classification === 'AMBIGUOUS') {
      verdictBlockers.push(v.classification + '@G' + v.gate);
    } else if (v.classification === 'DISPOSITIONED') {
      handledDispositions.push({ id, gate: v.gate, original_verdict: v.verdict, disposition: v.disposition.disposition });
    }
  }

  if (eligible && parsedVerdicts.length === 0) {
    if (ALLOW_MISSING) {
      reason = reason + ' (missing verdict evidence, WARN per --allow-missing-verdicts)';
    } else {
      verdictBlockers.push('MISSING_VERDICT_EVIDENCE');
      reason = reason + ' (missing required verdict evidence -> DENY)';
    }
  }

  const idBlocked = localBlockers.length > 0 || verdictBlockers.length > 0;
  if (idBlocked) {
    const all = localBlockers.concat(verdictBlockers);
    blockers.push({ id, status, reason, blockers: all });
  }

  results.push({
    id,
    exists,
    status,
    eligible,
    reason,
    verdict_files: verdictFiles,
    verdicts: parsedVerdicts.map(v => ({
      gate: v.gate,
      verdict: v.verdict,
      status: v.status,
      classification: v.classification,
      dispositioned: v.classification === 'DISPOSITIONED' ? true : false
    }))
  });
}

const blockedCount = blockers.length;
const totalCount = results.length;
let verdict, exitCode;

if (blockedCount > 0 || hasUnknown || hasAmbiguous) { verdict = 'DENY'; exitCode = 1; }
else { verdict = 'ALLOW'; exitCode = 0; }

const output = {
  timestamp: TIMESTAMP,
  release_set: ticketIds,
  total: totalCount,
  blockers: blockedCount,
  verdict,
  exit_code: exitCode,
  dispositions_applied: handledDispositions,
  default_verdict_gating: 'DENY on any FAIL / NOT_VERIFIED / malformed / ambiguous / missing(unless --allow-missing-verdicts)',
  results,
  ...(blockedCount > 0 ? { blocker_details: blockers } : {})
};

process.stdout.write(JSON.stringify(output, null, 2));
process.exit(exitCode);
NODESCRIPT

export TICKET_DIR DISPOSITIONS_FILE TIMESTAMP ALLOW_MISSING_VERDICTS="$ALLOW_MISSING" TICKET_LIST="${TICKET_IDS[*]}"
$NODE "$VERIFY_SCRIPT"
EXIT_CODE=$?

# Write release-set verification record (canonical regenerable artifact)
RECORD_FILE="${AV_RECORD_FILE:-$AGENTVERSE/tickets/SCHOL-814-release-set-verification.json}"
$NODE -e "const d=JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')); require('fs').writeFileSync('$RECORD_FILE', JSON.stringify(d,null,2));" < <($NODE "$VERIFY_SCRIPT" 2>/dev/null) 2>/dev/null || true

exit $EXIT_CODE
