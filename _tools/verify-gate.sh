#!/bin/bash
set -e

# verify-gate.sh — Gate verification engine for AgentVerse 2.0
# Usage: bash _tools/verify-gate.sh <TICKET_ID> [GATE_ID]
# Reads the ticket file, extracts acceptance criteria, and attempts machine verification.

NODE="$(command -v node 2>/dev/null || echo 'node')"
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT="$(cd "$SELF_DIR/.." && pwd)"
TICKET_DIR="$PROJECT/AGENTVERSE/tickets"
SCRIPT_DIR="$SELF_DIR"

TICKET_ID="${1:-}"
GATE_ID="${2:-G5}"

if [ -z "$TICKET_ID" ]; then
  echo '{"error":"Usage: bash verify-gate.sh <TICKET_ID> [GATE_ID]","example":"bash verify-gate.sh SCHOL-109 G5"}'
  exit 1
fi

TICKET_FILE="$TICKET_DIR/$TICKET_ID.md"
if [ ! -f "$TICKET_FILE" ]; then
  echo '{"error":"Ticket file not found","ticket":"'"$TICKET_ID"'","expected":"'"$TICKET_FILE"'"}'
  exit 1
fi

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Write node script to temp file to avoid bash escaping issues
VERIFY_SCRIPT=$(mktemp /tmp/verify-gate-XXXXX.js)
trap "rm -f $VERIFY_SCRIPT" EXIT

cat > "$VERIFY_SCRIPT" << 'NODESCRIPT'
const fs = require('fs');
const { execSync } = require('child_process');

const PROJECT = process.env.PROJECT;
const TICKET_FILE = process.env.TICKET_FILE;
const TICKET_ID = process.env.TICKET_ID;
const GATE_ID = process.env.GATE_ID;
const TIMESTAMP = process.env.TIMESTAMP;

const content = fs.readFileSync(TICKET_FILE, 'utf8');

// Extract PHP files mentioned in ticket (deduplicated by basename)
const allPhpPaths = [...content.matchAll(/\/[^\s"']+\.php/g)].map(m => m[0]);
const phpByBasename = {};
for (const p of allPhpPaths) {
  const bn = p.split('/').pop();
  if (!phpByBasename[bn]) phpByBasename[bn] = p;
}

// Find actual local paths for PHP files
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

// Extract URLs from ticket (strip trailing punctuation from markdown)
const urlRegex = /https?:\/\/[^\s"'()\[\]]+/g;
const rawUrls = [...content.matchAll(urlRegex)].map(m => m[0].replace(/[`',;:!?\])}]+$/, ''));
const uniqueUrls = [...new Set(rawUrls)];

const acResults = [];

// --- AC: PHP syntax checks ---
const uniqueBasenames = Object.keys(phpByBasename);
for (const bn of uniqueBasenames) {
  const localPath = findLocalPath(bn);
  let status = 'UNVERIFIED';
  let output = 'PHP file not found locally';
  let runtime = 'none';

  if (localPath) {
    try {
      output = execSync('php -l "' + localPath + '" 2>&1', { encoding: 'utf8', timeout: 10000 }).trim();
      status = output.includes('No syntax errors') ? 'VERIFIED' : 'UNVERIFIED';
      runtime = 'local';
    } catch(e) {
      // Local php unavailable — try Docker fallback
      try {
        const dockerCmd = 'docker exec school4 php -l "/var/www/html/' + localPath.replace(PROJECT + '/', '') + '" 2>&1';
        output = execSync(dockerCmd, { encoding: 'utf8', timeout: 15000 }).trim();
        status = output.includes('No syntax errors') ? 'VERIFIED' : 'UNVERIFIED';
        runtime = 'docker';
      } catch(e2) {
        output = (e.stdout || e.stderr || '').trim() || 'php not available locally or via Docker';
        runtime = 'none';
      }
    }
  }

  acResults.push({
    id: 'AC' + acResults.length,
    description: 'php -l syntax check: ' + bn,
    status,
    method: 'php -l',
    runtime: runtime || 'unknown',
    output: output.substring(0, 500)
  });
}

if (uniqueBasenames.length === 0) {
  acResults.push({
    id: 'AC0',
    description: 'No PHP files found in ticket',
    status: 'UNVERIFIED',
    method: 'none',
    output: 'No PHP file references found in ticket'
  });
}

// --- AC: HTTP checks ---
const checkUrls = uniqueUrls.length > 0 ? uniqueUrls : ['https://school4.edunaija.online'];
for (const url of checkUrls) {
  let status = 'UNVERIFIED';
  let output = '';
  try {
    const httpCode = execSync('curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 --max-time 10 "' + url + '" 2>/dev/null || echo 000', { encoding: 'utf8', timeout: 15000 }).trim();
    output = 'HTTP ' + httpCode;
    status = httpCode === '200' ? 'VERIFIED' : 'UNVERIFIED';
  } catch(e) {
    output = 'curl failed: ' + (e.message || 'unknown error').substring(0, 200);
  }
  acResults.push({
    id: 'AC' + acResults.length,
    description: 'HTTP check: ' + url.substring(0, 120),
    status,
    method: 'curl',
    output: output.substring(0, 500)
  });
}

// --- AC: File existence ---
for (const bn of uniqueBasenames) {
  const localPath = findLocalPath(bn);
  acResults.push({
    id: 'AC' + acResults.length,
    description: 'File exists: ' + bn,
    status: localPath ? 'VERIFIED' : 'UNVERIFIED',
    method: 'file_exists',
    output: localPath ? 'Found at: ' + localPath : 'Not found: ' + bn
  });
}

// --- Calculate verdict ---
const verified = acResults.filter(r => r.status === 'VERIFIED').length;
const total = acResults.length;

// Check for BLOCKED conditions: no ticket file, no PHP files found, infrastructure unavailable
let blocked = false;
let blockedReason = '';
if (total === 0) {
  blocked = true;
  blockedReason = 'No verifiable artifacts found in ticket';
} else if (acResults.every(r => r.output && r.output.includes('not found'))) {
  blocked = true;
  blockedReason = 'All referenced files not found locally';
}

let overall, verdict;
if (blocked) {
  overall = 'VERIFICATION_BLOCKED';
  verdict = 'BLOCKED';
} else if (verified === total && total > 0) {
  overall = 'ALL_VERIFIED';
  verdict = 'PASS';
} else if (verified > 0) {
  overall = 'PARTIAL';
  verdict = 'NOT_ALL_VERIFIED';
} else {
  overall = 'NONE_VERIFIED';
  verdict = 'FAIL';
}

const output = {
  ticket: TICKET_ID,
  gate: GATE_ID,
  timestamp: TIMESTAMP,
  ac_results: acResults,
  summary: { total, verified, unverified: total - verified },
  overall,
  verdict,
  ...(blocked ? { blocked_reason: blockedReason } : {})
};

process.stdout.write(JSON.stringify(output, null, 2));
NODESCRIPT

export PROJECT TICKET_FILE TICKET_ID GATE_ID TIMESTAMP
$NODE "$VERIFY_SCRIPT"
