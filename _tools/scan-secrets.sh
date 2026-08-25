#!/bin/bash
set -e

# scan-secrets.sh — Automated secret scanning for AgentVerse 2.0
# Usage: bash _tools/scan-secrets.sh <directory-or-file> [file2 ...]
# Runs the 4 G4 secret patterns from VERIFICATION_CONTRACT.md
# Outputs JSON with findings.

NODE="$(command -v node 2>/dev/null || echo 'node')"
PROJECT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [ $# -eq 0 ]; then
  echo '{"error":"Usage: bash scan-secrets.sh <directory-or-file> [file2 ...]","example":"bash scan-secrets.sh scholapro"}'
  exit 1
fi

# Write node script to temp file to avoid bash escaping issues
SCAN_SCRIPT=$(mktemp /tmp/scan-secrets-XXXXX.js)
trap "rm -f $SCAN_SCRIPT" EXIT

cat > "$SCAN_SCRIPT" << 'NODESCRIPT'
const fs = require('fs');
const path = require('path');

const targets = process.argv.slice(2);
const TIMESTAMP = new Date().toISOString().replace(/\.\d+Z$/, 'Z');

const findings = [];
let filesScanned = 0;

const EXTENSIONS = new Set(['.php', '.js', '.py', '.sh', '.conf', '.yml', '.yaml', '.env', '.json', '.md', '.sql', '.html', '.css']);
const EXCLUDE_DIRS = new Set(['node_modules', '.git', 'backup']);

function addFinding(file, line, pattern, severity, context) {
  findings.push({
    file: file,
    line: parseInt(line) || 0,
    pattern: pattern,
    severity: severity,
    context: (context || '').substring(0, 500)
  });
}

function isExcluded(context) {
  const lower = context.toLowerCase();
  if (/(example|placeholder|change\.this|your[_-]here|xxx|todo|<.*>)/.test(lower)) return true;
  if (/(sha256|md5|hash|checksum|[a-f0-9]{32,}|begin)/.test(lower)) return true;
  if (/(redacted|none|empty|not[_-]?set|<none>)/.test(lower)) return true;
  if (/path\s+d=/.test(lower)) return true;
  if (/^\s*$/.test(context.trim())) return true;
  return false;
}

function scanFile(filePath) {
  filesScanned++;
  let content;
  try {
    content = fs.readFileSync(filePath, 'utf8');
  } catch(e) { return; }

  const lines = content.split('\n');

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const lineNum = i + 1;

    // Pattern 1: password/secret/api_key/token/credential assignments
    if (/(password|secret|api_key|token|credential)\s*[:=]\s*['"][^'"]{3,}['"]/i.test(line)) {
      if (!isExcluded(line)) {
        addFinding(filePath, lineNum, 'password/secret/api_key/token/credential assignment', 'HIGH', line.trim());
      }
    }

    // Pattern 2: Base64 blobs (>40 chars)
    const b64Match = line.match(/[A-Za-z0-9+/]{40,}={0,2}/);
    if (b64Match) {
      if (!isExcluded(line) && !/\.(json|md|db|sql)/i.test(filePath)) {
        addFinding(filePath, lineNum, 'Base64 blob (>40 chars)', 'MEDIUM', line.trim());
      }
    }

    // Pattern 3: Private key headers
    if (/-----BEGIN (RSA |EC )?PRIVATE KEY-----/.test(line)) {
      addFinding(filePath, lineNum, 'Private key header', 'CRITICAL', line.trim());
    }

    // Pattern 4: IP addresses (excluding private/localhost and SVG paths)
    const ipMatch = line.match(/\b(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\b/);
    if (ipMatch) {
      const ip = ipMatch[1];
      if (!/^(127\.|0\.0\.0\.0|255\.255|172\.(1[6-9]|2[0-9]|3[01])\.|10\.|192\.168\.)/.test(ip)) {
        if (!/\.(json|md|db|sql)/i.test(filePath) && !isExcluded(line)) {
          addFinding(filePath, lineNum, 'IP address', 'LOW', line.trim());
        }
      }
    }
  }
}

function walkDir(dir) {
  let entries;
  try {
    entries = fs.readdirSync(dir, { withFileTypes: true });
  } catch(e) { return; }

  for (const entry of entries) {
    if (EXCLUDE_DIRS.has(entry.name)) continue;

    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      walkDir(fullPath);
    } else if (entry.isFile()) {
      const ext = path.extname(entry.name);
      if (EXTENSIONS.has(ext)) {
        scanFile(fullPath);
      }
    }
  }
}

for (const target of targets) {
  try {
    const stat = fs.statSync(target);
    if (stat.isFile()) {
      scanFile(target);
    } else if (stat.isDirectory()) {
      walkDir(target);
    }
  } catch(e) {
    // Skip inaccessible targets
  }
}

const verdict = findings.length > 0 ? 'FINDINGS' : 'CLEAN';

const output = {
  scan_date: TIMESTAMP,
  scan_targets: targets,
  files_scanned: filesScanned,
  findings_count: findings.length,
  findings: findings,
  verdict: verdict
};

process.stdout.write(JSON.stringify(output, null, 2));
NODESCRIPT

$NODE "$SCAN_SCRIPT" "$@"
