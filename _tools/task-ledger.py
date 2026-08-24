#!/usr/bin/env python3
"""AGENTVERSE Task Ledger — Record provenance entries.

Usage:
  python3 _tools/task-ledger.py --session <id> --agent <id> --action <action> [--ticket <id>] [--file <path>] [--evidence <text>] [--status <status>]

Examples:
  python3 _tools/task-ledger.py --session "2026-08-24-hardening" --agent "chief-architect" --action "gitignore_hardening" --file ".gitignore"
  python3 _tools/task-ledger.py --session "2026-08-24-hardening" --agent "backend-engineer" --action "task_ledger_create" --evidence "table created in agentverse.db"
"""

import argparse
import sqlite3
import json
import os
import sys
from datetime import datetime

DB_PATH = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "AGENTVERSE", "agentverse.db")

def log_task(session_id, agent_id, action, ticket_id=None, target_file=None, evidence=None, status="completed", metadata=None):
    db = sqlite3.connect(DB_PATH)
    db.execute(
        "INSERT INTO task_ledger (session_id, agent_id, ticket_id, action, target_file, evidence, timestamp, status, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        (session_id, agent_id, ticket_id, action, target_file, evidence, datetime.utcnow().isoformat(), status, json.dumps(metadata) if metadata else None)
    )
    db.commit()
    entry_id = db.execute("SELECT last_insert_rowid()").fetchone()[0]
    db.close()
    return entry_id

def list_tasks(session_id=None, limit=20):
    db = sqlite3.connect(DB_PATH)
    if session_id:
        rows = db.execute("SELECT * FROM task_ledger WHERE session_id = ? ORDER BY timestamp DESC LIMIT ?", (session_id, limit)).fetchall()
    else:
        rows = db.execute("SELECT * FROM task_ledger ORDER BY timestamp DESC LIMIT ?", (limit,)).fetchall()
    db.close()
    return rows

def main():
    parser = argparse.ArgumentParser(description="AGENTVERSE Task Ledger")
    parser.add_argument("--session", help="Session ID")
    parser.add_argument("--agent", help="Agent ID")
    parser.add_argument("--action", help="Action performed")
    parser.add_argument("--ticket", help="Ticket ID")
    parser.add_argument("--file", help="Target file")
    parser.add_argument("--evidence", help="Evidence text")
    parser.add_argument("--status", default="completed", help="Status")
    parser.add_argument("--list", action="store_true", help="List recent tasks")
    parser.add_argument("--limit", type=int, default=20, help="Limit for list")
    
    args = parser.parse_args()
    
    if args.list:
        tasks = list_tasks(args.session, args.limit)
        for t in tasks:
            print(f"  [{t[8]}] {t[3]} | {t[2]} | {t[4]} | {t[5] or '-'} | {t[6] or '-'}")
        return
    
    if not args.session or not args.agent or not args.action:
        parser.error("--session, --agent, and --action are required")
    
    entry_id = log_task(
        session_id=args.session,
        agent_id=args.agent,
        action=args.action,
        ticket_id=args.ticket,
        target_file=args.file,
        evidence=args.evidence,
        status=args.status
    )
    print(f"Task logged: #{entry_id}")

if __name__ == "__main__":
    main()
