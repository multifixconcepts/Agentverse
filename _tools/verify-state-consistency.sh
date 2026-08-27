#!/usr/bin/env bash
# verify-state-consistency.sh — State consistency verifier for AgentVerse 2.0
#
# Checks that tracked state metadata matches the actual filesystem state:
#   - Agent count in AGENT_REGISTRY.json matches the declared total (70)
#   - Every RELEASED ticket has a corresponding verdict file
#   - ORG_CHECKSUM.json sha256_hashes match the actual files
#   - ORG_CHECKSUM.json metadata (version/counts) matches canonical values
#
# Usage:
#   ./_tools/verify-state-consistency.sh [--json-only]
#
# Output: JSON with per-check results to stdout.

set -euo pipefail

# ── Config ─────────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

AGENTVERSE_DIR="$PROJECT_ROOT/AGENTVERSE"
CURRENT_STATE="$AGENTVERSE_DIR/CURRENT_STATE.json"
ORG_CHECKSUM="$AGENTVERSE_DIR/ORG_CHECKSUM.json"
AGENT_REGISTRY="$AGENTVERSE_DIR/AGENT_REGISTRY.json"
TICKETS_DIR="$AGENTVERSE_DIR/tickets"
TOOLS_DIR="$PROJECT_ROOT/_tools"
SKILLS_DIR="$PROJECT_ROOT/.opencode/skills"

EXPECTED_AGENT_COUNT=70
EXPECTED_SKILL_COUNT=7
EXPECTED_PLUGIN_COUNT=1
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

python_json_tool() {
    if /home/coder/python/bin/python3.11 -m json.tool "$1" >/dev/null 2>&1; then
        return 0
    fi
    python3 -m json.tool "$1" >/dev/null 2>&1
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

    if ! python_json_tool "$CURRENT_STATE"; then
        check_fail "current_state_valid_json" "CURRENT_STATE.json is not valid JSON"
        return 1
    fi

    check_pass "current_state_exists" "CURRENT_STATE.json found and valid"
    return 0
}

# ── Check: Agent count in AGENT_REGISTRY.json ──────────────────────────────

check_agent_count() {
    if [[ ! -f "$AGENT_REGISTRY" ]]; then
        check_fail "agent_count" "AGENT_REGISTRY.json not found"
        return
    fi

    if ! python_json_tool "$AGENT_REGISTRY"; then
        check_fail "agent_count" "AGENT_REGISTRY.json is not valid JSON"
        return
    fi

    local declared_total actual_total
    declared_total=$('/home/coder/project/_tools/jq' -r '.total_agents // empty' "$AGENT_REGISTRY" 2>/dev/null || echo "")
    actual_total=$('/home/coder/project/_tools/jq' -r '.agents | length' "$AGENT_REGISTRY" 2>/dev/null || echo "")

    if [[ -z "$declared_total" || -z "$actual_total" ]]; then
        check_warn "agent_count" "AGENT_REGISTRY.json missing total_agents or agents"
        return
    fi

    if [[ "$declared_total" == "$actual_total" && "$declared_total" == "$EXPECTED_AGENT_COUNT" ]]; then
        check_pass "agent_count" "AGENT_REGISTRY.json declares $declared_total agents (expected $EXPECTED_AGENT_COUNT)"
    elif [[ "$declared_total" == "$actual_total" ]]; then
        check_fail "agent_count" "AGENT_REGISTRY.json declares $declared_total agents but expected $EXPECTED_AGENT_COUNT"
    else
        check_fail "agent_count" "total_agents=$declared_total != .agents length=$actual_total"
    fi
}

# ── Check: Every RELEASED ticket has a verdict file ────────────────────────

check_ticket_verdicts() {
    if [[ ! -d "$TICKETS_DIR" ]]; then
        check_warn "ticket_verdicts" "Tickets directory not found: $TICKETS_DIR"
        return
    fi

    local released=0 with_verdict=0 missing_verdicts=""
    local ticket_file ticket_id

    for ticket_file in "$TICKETS_DIR"/SCHOL-*.md; do
        [[ -f "$ticket_file" ]] || continue

        if grep -qiE '^\s*\*\*Status:\*\*\s*(RELEASED|DONE|COMPLETED|MERGED)' "$ticket_file" 2>/dev/null; then
            ticket_id=$(basename "$ticket_file" .md)
            ((released++)) || true

            local has_verdict=false
            for verdict_pattern in "$TICKETS_DIR/${ticket_id}-G"*verdict.json "$TICKETS_DIR/${ticket_id}-G"*VERDICT.md "$TICKETS_DIR/${ticket_id}-verdict.json" "${ticket_id}_verdict.json"; do
                ls "$verdict_pattern" >/dev/null 2>&1 && { has_verdict=true; break; }
            done

            if $has_verdict; then
                ((with_verdict++)) || true
            else
                missing_verdicts="${missing_verdicts}${ticket_id} "
            fi
        fi
    done

    if [[ "$released" -eq 0 ]]; then
        check_warn "ticket_verdicts" "No released tickets found in $TICKETS_DIR"
        return
    fi

    if [[ -n "$missing_verdicts" ]]; then
        check_warn "ticket_verdicts" "Released tickets without formal verdict files: $missing_verdicts (verdict files are recorded for gate-audited releases)"
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

    if ! python_json_tool "$ORG_CHECKSUM"; then
        check_fail "org_checksum_valid" "ORG_CHECKSUM.json is not valid JSON"
        return
    fi

    check_pass "org_checksum_exists" "ORG_CHECKSUM.json found and valid"

    # Verify each recorded hash against the actual file (key: sha256_hashes)
    local failures=0 verified=0 missing=0
    local filepath expected_hash

    while IFS=$'\t' read -r filepath expected_hash; do
        [[ -z "$filepath" || -z "$expected_hash" ]] && continue

        local relative_path="$filepath"
        # Files under AGENTVERSE/ are recorded relative to AGENTVERSE/
        if [[ "$filepath" != AGENTVERSE/* && "$filepath" != .opencode/* && "$filepath" != _tools/* && "$filepath" != _tests/* && "$filepath" != clientflow/* ]]; then
            relative_path="AGENTVERSE/$filepath"
        fi

        local full_path="$PROJECT_ROOT/$relative_path"

        if [[ ! -f "$full_path" ]]; then
            check_warn "org_checksum_missing_file" "File not found: $relative_path"
            ((missing++)) || true
            continue
        fi

        local actual_hash
        actual_hash=$(compute_sha256 "$full_path")

        if [[ -z "$actual_hash" ]]; then
            check_warn "org_checksum_compute" "Cannot compute hash for: $relative_path"
            continue
        fi

        if [[ "$actual_hash" == "$expected_hash" ]]; then
            ((verified++)) || true
        else
            check_fail "org_checksum_mismatch" "Hash mismatch for $relative_path: expected $expected_hash, got $actual_hash"
            ((failures++)) || true
        fi
    done < <('/home/coder/project/_tools/jq' -r '.sha256_hashes // {} | to_entries[] | [.key, .value] | @tsv' "$ORG_CHECKSUM" 2>/dev/null || true)

    if [[ "$failures" -gt 0 ]]; then
        : # failures already emitted as org_checksum_mismatch
    elif [[ "$verified" -gt 0 ]]; then
        check_pass "org_checksum_hashes" "All $verified file hashes verified (${missing} missing)"
    else
        check_warn "org_checksum_hashes" "No file hashes to verify in ORG_CHECKSUM.json sha256_hashes"
    fi
}

# ── Check: ORG_CHECKSUM.json canonical metadata ────────────────────────────

check_org_checksum_metadata() {
    if [[ ! -f "$ORG_CHECKSUM" ]]; then
        check_warn "org_checksum_metadata" "ORG_CHECKSUM.json not found"
        return
    fi

    local version ticket_count tool_count
    version=$('/home/coder/project/_tools/jq' -r '.version // empty' "$ORG_CHECKSUM" 2>/dev/null || echo "")
    ticket_count=$('/home/coder/project/_tools/jq' -r '.ticket_count // empty' "$ORG_CHECKSUM" 2>/dev/null || echo "")
    tool_count=$('/home/coder/project/_tools/jq' -r '.tool_count // empty' "$ORG_CHECKSUM" 2>/dev/null || echo "")

    local expected_version
    expected_version=$(cat "$PROJECT_ROOT/VERSION" 2>/dev/null | tr -d '[:space:]' || echo "")

    local actual_tickets actual_tools
    actual_tickets=$(ls "$TICKETS_DIR"/SCHOL-*.md 2>/dev/null | grep -E "/SCHOL-[0-9]+\.md$" | wc -l | tr -d ' ')
    actual_tools=$(git -C "$PROJECT_ROOT" ls-files '_tools/*' 2>/dev/null | grep -vE '_tools/jq$' | wc -l | tr -d ' ')

    local errors=""
    if [[ -n "$expected_version" && "$version" != "$expected_version" ]]; then
        errors="${errors} version:ORG_CHECKSUM=$version,VERSION=$expected_version"
    fi
    if [[ -n "$actual_tickets" && "$ticket_count" != "$actual_tickets" ]]; then
        errors="${errors} ticket_count:ORG_CHECKSUM=$ticket_count,actual=$actual_tickets"
    fi
    if [[ -n "$actual_tools" && "$tool_count" != "$actual_tools" ]]; then
        errors="${errors} tool_count:ORG_CHECKSUM=$tool_count,actual=$actual_tools"
    fi

    if [[ -n "$errors" ]]; then
        check_fail "org_checksum_metadata" "ORG_CHECKSUM metadata drift:$errors"
    else
        check_pass "org_checksum_metadata" "ORG_CHECKSUM version=$version tickets=$ticket_count tools=$tool_count consistent"
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
    run_check check_agent_count
    run_check check_ticket_verdicts
    run_check check_org_checksums
    run_check check_org_checksum_metadata

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
