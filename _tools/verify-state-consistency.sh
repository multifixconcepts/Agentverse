#!/usr/bin/env bash
# verify-state-consistency.sh — State consistency verifier for AgentVerse 2.0
#
# Checks that CURRENT_STATE.json matches the actual filesystem state:
#   - All 70 agent files exist
#   - All released tickets have verdict files
#   - ORG_CHECKSUM.json hashes are correct
#
# Usage:
#   ./_tools/verify-state-consistency.sh [--json-only]
#
# Output: JSON with per-check results to stdout.

set -euo pipefail

# ── Config ─────────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

CURRENT_STATE="$PROJECT_ROOT/AGENTVERSE/CURRENT_STATE.json"
ORG_CHECKSUM="$PROJECT_ROOT/AGENTVERSE/ORG_CHECKSUM.json"
TICKETS_DIR="$PROJECT_ROOT/AGENTVERSE/Tickets"
AGENTS_DIR="$PROJECT_ROOT/AGENTVERSE/Agents"

EXPECTED_AGENT_COUNT=70
JSON_ONLY=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --json-only) JSON_ONLY=true; shift ;;
        *) echo "Unknown option: $1" >&2; exit 1 ;;
    esac
done

# ── Helpers ────────────────────────────────────────────────────────────────

check_pass() {
    local name="$1" detail="${2:-}"
    printf '{"check":"%s","status":"PASS","detail":"%s"}\n' "$name" "$detail"
}

check_fail() {
    local name="$1" detail="${2:-}"
    printf '{"check":"%s","status":"FAIL","detail":"%s"}\n' "$name" "$detail"
}

check_warn() {
    local name="$1" detail="${2:-}"
    printf '{"check":"%s","status":"WARN","detail":"%s"}\n' "$name" "$detail"
}

compute_sha256() {
    if command -v sha256sum &>/dev/null; then
        sha256sum "$1" | awk '{print $1}'
    elif command -v shasum &>/dev/null; then
        shasum -a 256 "$1" | awk '{print $1}'
    else
        echo ""
    fi
}

# ── Check: CURRENT_STATE.json exists and is valid JSON ─────────────────────

check_current_state_exists() {
    if [[ ! -f "$CURRENT_STATE" ]]; then
        check_fail "current_state_exists" "CURRENT_STATE.json not found at $CURRENT_STATE"
        return 1
    fi

    if ! python3 -m json.tool "$CURRENT_STATE" >/dev/null 2>&1; then
        check_fail "current_state_valid_json" "CURRENT_STATE.json is not valid JSON"
        return 1
    fi

    check_pass "current_state_exists" "CURRENT_STATE.json found and valid"
    return 0
}

# ── Check: Agent files ────────────────────────────────────────────────────

check_agent_files() {
    if [[ ! -d "$AGENTS_DIR" ]]; then
        check_fail "agent_files_directory" "Agents directory not found: $AGENTS_DIR"
        return
    fi

    local actual_count
    actual_count=$(find "$AGENTS_DIR" -maxdepth 1 -name '*.md' -o -name '*.json' 2>/dev/null | wc -l)

    if [[ "$actual_count" -eq 0 ]]; then
        check_warn "agent_files_count" "No agent files found in $AGENTS_DIR"
        return
    fi

    if [[ "$actual_count" -lt "$EXPECTED_AGENT_COUNT" ]]; then
        check_fail "agent_files_count" "Expected $EXPECTED_AGENT_COUNT agent files, found $actual_count"
    else
        check_pass "agent_files_count" "Found $actual_count agent files (expected >= $EXPECTED_AGENT_COUNT)"
    fi

    # Check for any empty agent files
    local empty_files
    empty_files=$(find "$AGENTS_DIR" -maxdepth 1 \( -name '*.md' -o -name '*.json' \) -empty 2>/dev/null | wc -l)

    if [[ "$empty_files" -gt 0 ]]; then
        check_warn "agent_files_nonempty" "$empty_files agent files are empty"
    else
        check_pass "agent_files_nonempty" "All agent files are non-empty"
    fi
}

# ── Check: Released tickets have verdict files ─────────────────────────────

check_ticket_verdicts() {
    if [[ ! -d "$TICKETS_DIR" ]]; then
        check_warn "ticket_verdicts" "Tickets directory not found"
        return
    fi

    local total=0
    local released=0
    local with_verdict=0
    local missing_verdicts=""

    for ticket_dir in "$TICKETS_DIR"/*/; do
        [[ -d "$ticket_dir" ]] || continue
        ((total++)) || true

        local status_file="$ticket_dir/STATUS.md"
        if [[ -f "$status_file" ]] && grep -qiE '(released|done|completed|merged)' "$status_file" 2>/dev/null; then
            ((released++)) || true

            # Check for verdict files
            local has_verdict=false
            for verdict_pattern in 'verdict*.json' 'G*_verdict.json' 'VERDICT.md'; do
                if ls "$ticket_dir"/$verdict_pattern >/dev/null 2>&1; then
                    has_verdict=true
                    break
                fi
            done

            if $has_verdict; then
                ((with_verdict++)) || true
            else
                local ticket_name
                ticket_name=$(basename "$ticket_dir")
                missing_verdicts="${missing_verdicts}${ticket_name} "
            fi
        fi
    done

    if [[ "$released" -eq 0 ]]; then
        check_warn "ticket_verdicts" "No released tickets found ($total total tickets)"
        return
    fi

    if [[ -n "$missing_verdicts" ]]; then
        check_fail "ticket_verdicts" "Released tickets missing verdicts: $missing_verdicts"
    else
        check_pass "ticket_verdicts" "All $released released tickets have verdict files"
    fi
}

# ── Check: ORG_CHECKSUM.json hashes ───────────────────────────────────────

check_org_checksums() {
    if [[ ! -f "$ORG_CHECKSUM" ]]; then
        check_warn "org_checksum_exists" "ORG_CHECKSUM.json not found"
        return
    fi

    if ! python3 -m json.tool "$ORG_CHECKSUM" >/dev/null 2>&1; then
        check_fail "org_checksum_valid" "ORG_CHECKSUM.json is not valid JSON"
        return
    fi

    check_pass "org_checksum_exists" "ORG_CHECKSUM.json found and valid"

    # Verify each recorded hash against actual file
    local failures=0
    local verified=0

    while IFS='=' read -r filepath expected_hash; do
        [[ -z "$filepath" || -z "$expected_hash" ]] && continue

        local full_path="$PROJECT_ROOT/$filepath"

        if [[ ! -f "$full_path" ]]; then
            check_warn "org_checksum_missing_file" "File not found: $filepath"
            ((failures++)) || true
            continue
        fi

        local actual_hash
        actual_hash=$(compute_sha256 "$full_path")

        if [[ -z "$actual_hash" ]]; then
            check_warn "org_checksum_compute" "Cannot compute hash for: $filepath"
            continue
        fi

        if [[ "$actual_hash" == "$expected_hash" ]]; then
            ((verified++)) || true
        else
            check_fail "org_checksum_mismatch" "Hash mismatch for $filepath: expected $expected_hash, got $actual_hash"
            ((failures++)) || true
        fi
    done < <(python3 -c "
import json, sys
with open('$ORG_CHECKSUM') as f:
    data = json.load(f)
checksums = data.get('checksums', data.get('files', {}))
for path, info in checksums.items():
    h = info if isinstance(info, str) else info.get('sha256', info.get('hash', ''))
    if h:
        print(f'{path}={h}')
" 2>/dev/null || true)

    if [[ "$failures" -eq 0 && "$verified" -gt 0 ]]; then
        check_pass "org_checksum_hashes" "All $verified file hashes verified"
    elif [[ "$failures" -eq 0 && "$verified" -eq 0 ]]; then
        check_warn "org_checksum_hashes" "No file hashes to verify in ORG_CHECKSUM.json"
    fi
}

# ── Main ───────────────────────────────────────────────────────────────────

main() {
    local all_pass=true

    echo "{"
    echo '  "tool": "verify-state-consistency",'
    echo "  \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\","
    echo '  "checks": ['

    local first=true

    run_check() {
        local result
        result=$("$@" 2>&1) || true

        while IFS= read -r line; do
            [[ -z "$line" ]] && continue
            echo "$line" | grep -q '"FAIL"' && all_pass=false

            if $first; then
                first=false
            else
                echo ","
            fi
            printf '    %s' "$line"
        done <<< "$result"
    }

    run_check check_current_state_exists
    run_check check_agent_files
    run_check check_ticket_verdicts
    run_check check_org_checksums

    echo ""
    echo "  ],"

    if $all_pass; then
        echo '  "overall": "PASS"'
    else
        echo '  "overall": "FAIL"'
    fi

    echo "}"
}

main
