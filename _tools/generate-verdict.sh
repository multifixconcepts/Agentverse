#!/bin/bash
set -e

# generate-verdict.sh — Gate verdict generator for AgentVerse 2.0
# Usage: bash _tools/generate-verdict.sh <TICKET_ID> <GATE_ID>
# Generates a structured JSON verdict file from actual artifacts.

NODE="/usr/lib/code-server/lib/node"
PROJECT="/home/coder/project"
AGENTVERSE="$PROJECT/AGENTVERSE"
TICKET_DIR="$AGENTVERSE/tickets"

TICKET_ID="${1:-}"
GATE_ID="${2:-}"

if [ -z "$TICKET_ID" ] || [ -z "$GATE_ID" ]; then
  echo '{"error":"Usage: bash generate-verdict.sh <TICKET_ID> <GATE_ID>","example":"bash generate-verdict.sh SCHOL-109 G5"}'
  exit 1
fi

TICKET_FILE="$TICKET_DIR/$TICKET_ID.md"
if [ ! -f "$TICKET_FILE" ]; then
  echo '{"error":"Ticket file not found","ticket":"'"$TICKET_ID"'"}'
  exit 1
fi

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Write node script to temp file to avoid bash escaping issues
VERDICT_SCRIPT=$(mktemp /tmp/gen-verdict-XXXXX.js)
trap "rm -f $VERDICT_SCRIPT" EXIT

cat > "$VERDICT_SCRIPT" << 'NODESCRIPT'
const fs = require('fs');
const { execSync } = require('child_process');
const crypto = require('crypto');

const PROJECT = process.env.PROJECT;
const AGENTVERSE = process.env.AGENTVERSE;
const TICKET_ID = process.env.TICKET_ID;
const GATE_ID = process.env.GATE_ID;
const TIMESTAMP = process.env.TIMESTAMP;
const TICKET_DIR = process.env.TICKET_DIR;

const TICKET_FILE = TICKET_DIR + '/' + TICKET_ID + '.md';
const content = fs.readFileSync(TICKET_FILE, 'utf8');

// Extract metadata
const titleLine = content.split('\n')[0] || '';
const title = titleLine.replace(/^#\s*/, '');
const statusMatch = content.match(/^\*\*Status:\*\*\s*(.+)$/m);
const typeMatch = content.match(/^\*\*Type:\*\*\s*(.+)$/m);
const priorityMatch = content.match(/^\*\*Priority:\*\*\s*(.+)$/m);
const ticketStatus = statusMatch ? statusMatch[1].trim() : 'UNKNOWN';
const ticketType = typeMatch ? typeMatch[1].trim() : 'UNKNOWN';
const ticketPriority = priorityMatch ? priorityMatch[1].trim() : 'UNKNOWN';

// Extract PHP files (deduplicated by basename)
const allPhpPaths = [...content.matchAll(/\/[^\s"']+\.php/g)].map(m => m[0]);
const phpByBasename = {};
for (const p of allPhpPaths) {
  const bn = p.split('/').pop();
  if (!phpByBasename[bn]) phpByBasename[bn] = p;
}

function findLocalPath(basename) {
  const directPaths = allPhpPaths.filter(p => p.endsWith('/' + basename));
  for (const p of directPaths) {
    if (fs.existsSync(p)) return p;
  }
  const searchDirs = [PROJECT + '/scholapro', PROJECT + '/premium-modules'];
  for (const dir of searchDirs) {
    try {
      const result = execSync('find "' + dir + '" -name "' + basename + '" -type f 2>/dev/null | head -1', { encoding: 'utf8' }).trim();
      if (result) return result;
    } catch(e) {}
  }
  return null;
}

// --- Gate-specific verification ---
const acResults = [];
const evidence = [];
let artifactHash = '';

if (GATE_ID === 'G0') {
  acResults.push(
    { id: 'G0-AC1', description: 'Ticket has title', status: title ? 'MET' : 'NOT_MET', evidence: 'Title: ' + title },
    { id: 'G0-AC2', description: 'Ticket has status', status: ticketStatus !== 'UNKNOWN' ? 'MET' : 'NOT_MET', evidence: 'Status: ' + ticketStatus },
    { id: 'G0-AC3', description: 'Ticket has type', status: ticketType !== 'UNKNOWN' ? 'MET' : 'NOT_MET', evidence: 'Type: ' + ticketType }
  );
} else if (GATE_ID === 'G1' || GATE_ID === 'G5') {
  const basenames = Object.keys(phpByBasename);
  let syntaxPass = 0;
  let syntaxTotal = basenames.length || 1;
  let runtime = 'none';

  for (const bn of basenames) {
    const localPath = findLocalPath(bn);
    let output = '';
    let result = 'SKIP';
    if (localPath) {
      try {
        output = execSync('php -l "' + localPath + '" 2>&1', { encoding: 'utf8', timeout: 10000 }).trim();
        result = output.includes('No syntax errors') ? 'PASS' : 'FAIL';
        if (result === 'PASS') syntaxPass++;
        runtime = 'local';
      } catch(e) {
        // Local php unavailable — try Docker fallback
        try {
          const dockerPath = '/var/www/html/' + localPath.replace(PROJECT + '/', '');
          const dockerCmd = 'docker exec school4 php -l "' + dockerPath + '" 2>&1';
          output = execSync(dockerCmd, { encoding: 'utf8', timeout: 15000 }).trim();
          result = output.includes('No syntax errors') ? 'PASS' : 'FAIL';
          if (result === 'PASS') syntaxPass++;
          runtime = 'docker';
        } catch(e2) {
          output = (e.stdout || e.stderr || '').trim() || 'php not available locally or via Docker';
          result = 'FAIL';
          runtime = 'none';
        }
      }
    } else {
      output = 'File not found locally: ' + bn;
    }
    evidence.push({ check: 'php -l', target: localPath || bn, result, runtime, output: output.substring(0, 500) });
  }

  if (basenames.length === 0) {
    syntaxPass = 1;
    evidence.push({ check: 'php -l', target: 'N/A', result: 'SKIP', output: 'No PHP files in ticket scope' });
  }

  acResults.push({
    id: GATE_ID + '-AC1',
    description: 'All PHP files pass syntax check',
    status: syntaxPass === syntaxTotal ? 'MET' : 'NOT_MET',
    evidence: syntaxPass + '/' + syntaxTotal + ' passed'
  });

  if (GATE_ID === 'G5') {
    let httpCode = '000';
    try {
      httpCode = execSync('curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 --max-time 10 "https://school4.edunaija.online" 2>/dev/null || echo 000', { encoding: 'utf8', timeout: 15000 }).trim();
    } catch(e) {}
    evidence.push({ check: 'curl', target: 'https://school4.edunaija.online', result: 'HTTP ' + httpCode, output: 'HTTP status ' + httpCode });
    acResults.push({
      id: GATE_ID + '-AC2',
      description: 'Production HTTP 200',
      status: httpCode === '200' ? 'MET' : 'NOT_MET',
      evidence: 'HTTP ' + httpCode
    });
  }
} else {
  acResults.push({
    id: GATE_ID + '-AC1',
    description: 'Gate-specific checks',
    status: 'NOT_MET',
    evidence: 'Gate ' + GATE_ID + ' verification not implemented'
  });
}

// --- Artifact hash ---
const hashParts = [];
for (const bn of Object.keys(phpByBasename)) {
  const p = findLocalPath(bn);
  if (p) {
    try {
      const hash = execSync('sha256sum "' + p + '"', { encoding: 'utf8' }).trim().split(' ')[0];
      hashParts.push(hash);
    } catch(e) {}
  }
}
artifactHash = hashParts.length > 0
  ? crypto.createHash('sha256').update(hashParts.join('')).digest('hex')
  : '';

// --- Gate verdict ---
const met = acResults.filter(r => r.status === 'MET').length;
const total = acResults.length;

// Check for BLOCKED conditions: infrastructure unavailable, files not found
let blocked = false;
let blockedReason = '';
if (total === 0) {
  blocked = true;
  blockedReason = 'No verifiable artifacts found';
} else if (evidence.every(e => e.result === 'SKIP' || (e.output && e.output.includes('not found')))) {
  blocked = true;
  blockedReason = 'All verification targets unavailable';
}

let verdict, status;
if (blocked) {
  verdict = 'BLOCKED';
  status = 'VERIFICATION_BLOCKED';
} else if (met === total && total > 0) {
  verdict = 'PASS';
  status = 'VERIFIED';
} else {
  verdict = 'FAIL';
  status = 'NOT_VERIFIED';
}

const verdictObj = {
  ticket: TICKET_ID,
  gate: GATE_ID,
  timestamp: TIMESTAMP,
  ticket_title: title,
  ticket_status: ticketStatus,
  ticket_type: ticketType,
  ticket_priority: ticketPriority,
  ac_results: acResults,
  tests: [],
  evidence,
  artifact_hash: artifactHash,
  summary: { total_ac: total, met, not_met: total - met },
  verdict,
  status,
  ...(blocked ? { blocked_reason: blockedReason } : {})
};

// Write verdict file
const verdictPath = AGENTVERSE + '/tickets/' + TICKET_ID + '-' + GATE_ID + '.verdict.json';
fs.writeFileSync(verdictPath, JSON.stringify(verdictObj, null, 2));
process.stdout.write(JSON.stringify(verdictObj, null, 2));
process.stderr.write('Verdict written to: ' + verdictPath + '\n');
NODESCRIPT

export PROJECT AGENTVERSE TICKET_ID GATE_ID TIMESTAMP TICKET_DIR
$NODE "$VERDICT_SCRIPT"
