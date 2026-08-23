#!/usr/bin/env bash
# generate-changes.sh — Automated CHANGES.md generator for AgentVerse 2.0
#
# Reads released tickets and git log to produce CHANGES.md entries.
# No agent has to remember to update CHANGES.md.
#
# Usage:
#   ./_tools/generate-changes.sh [--output FILE]
#
# Output: writes CHANGES.md in project root (or --output path).

set -euo pipefail

# ── Config ─────────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TICKETS_DIR="$PROJECT_ROOT/AGENTVERSE/Tickets"
OUTPUT_FILE="$PROJECT_ROOT/CHANGES.md"
GIT_LOG_LIMIT=200

# ── Parse args ─────────────────────────────────────────────────────────────

while [[ $# -gt 0 ]]; do
    case "$1" in
        --output)
            OUTPUT_FILE="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

# ── Functions ──────────────────────────────────────────────────────────────

emit_header() {
    cat <<'HEADER'
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

HEADER
}

emit_ticket_entries() {
    if [[ ! -d "$TICKETS_DIR" ]]; then
        echo "<!-- No tickets directory found -->"
        return
    fi

    # Find all released tickets (STATUS.md contains "Released" or "DONE")
    for ticket_dir in "$TICKETS_DIR"/*/; do
        [[ -d "$ticket_dir" ]] || continue

        local status_file="$ticket_dir/STATUS.md"
        [[ -f "$status_file" ]] || continue

        local status
        status=$(grep -iE '(status|state|phase)' "$status_file" 2>/dev/null | head -1 || true)

        if echo "$status" | grep -qiE '(released|done|completed|merged)'; then
            local ticket_name
            ticket_name=$(basename "$ticket_dir")

            local title="$ticket_name"
            if [[ -f "$ticket_dir/TICKET.md" ]]; then
                local extracted_title
                extracted_title=$(grep -m1 '^#\s\+' "$ticket_dir/TICKET.md" 2>/dev/null | sed 's/^#\s\+//' || true)
                [[ -n "$extracted_title" ]] && title="$extracttracted_title"
            fi

            echo "### $title"
            echo ""

            # Extract summary from TICKET.md if available
            if [[ -f "$ticket_dir/TICKET.md" ]]; then
                # Grab first paragraph after the title
                awk '/^##\s+Summary|^##\s+Description/{found=1; next} found && /^$/{exit} found{print}' \
                    "$ticket_dir/TICKET.md" 2>/dev/null | head -5 || true
            fi

            echo ""
            echo "- Released via automated pipeline"
            echo ""
        fi
    done
}

emit_git_log_entries() {
    if ! command -v git &>/dev/null; then
        return
    fi

    if [[ ! -d "$PROJECT_ROOT/.git" ]]; then
        echo "<!-- Not a git repository — skipping git log -->"
        return
    fi

    echo "### Recent Git History"
    echo ""
    echo "Automatically generated from git log:"
    echo ""

    # Get recent commits with conventional-commit style parsing
    git -C "$PROJECT_ROOT" log --oneline --no-merges -n "$GIT_LOG_LIMIT" 2>/dev/null | while IFS= read -r line; do
        local hash subject
        hash=$(echo "$line" | awk '{print $1}')
        subject=$(echo "$line" | sed "s/^$hash //")

        # Classify by conventional commit prefix
        local category="Changed"
        if echo "$subject" | grep -qiE '^feat'; then
            category="Added"
        elif echo "$subject" | grep -qiE '^fix'; then
            category="Fixed"
        elif echo "$subject" | grep -qiE '^perf'; then
            category="Performance"
        elif echo "$subject" | grep -qiE '^docs'; then
            category="Documentation"
        elif echo "$subject" | grep -qiE '^refactor'; then
            category="Changed"
        elif echo "$subject" | grep -qiE '^test'; then
            category="Testing"
        elif echo "$subject" | grep -qiE '^chore|ci'; then
            category="Maintenance"
        fi

        echo "- **[$category]** $subject (\`$hash\`)"
    done

    echo ""
}

# ── Main ───────────────────────────────────────────────────────────────────

main() {
    {
        emit_header
        echo "## [Unreleased]"
        echo ""

        # Ticket-based entries
        emit_ticket_entries

        # Git log entries
        emit_git_log_entries

        echo "---"
        echo ""
        echo "*Generated automatically by \`_tools/generate-changes.sh\`*"
    } > "$OUTPUT_FILE"

    echo "CHANGES.md generated: $OUTPUT_FILE" >&2
}

main "$@"
