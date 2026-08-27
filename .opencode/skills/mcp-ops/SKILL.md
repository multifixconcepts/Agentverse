---
name: mcp-ops
description: Using the recovered Agentverse MCP servers (filesystem, memory, sqlite, git, curl, portainer, n8n, secure-command). Use when operating MCP tooling or troubleshooting server connectivity.
---

# MCP Operations

Recovered MCP infrastructure (source: git HEAD `complete-mcp-config.json`, local `*.server.js` at project root).

## Servers

| Server | Local script / command | Notes |
|--------|------------------------|-------|
| filesystem | `mcp-server-filesystem` (root `/home/coder/project`) | project file access |
| memory | memory server → `.memory/memory.json` | L0 shared graph memory |
| sqlite | `simple-sqlite-mcp.js` | sqlite queries |
| git | `git-server.js` | git operations |
| curl | `curl-server.js` | HTTP fetches (env `HTTP_TIMEOUT`, `MAX_RESPONSE_SIZE`) |
| portainer | `portainer-mcp-server.js` | Portainer — URL via env `PORTAINER_URL` (default `http://127.0.0.1:9443`), token from env, never commit |
| n8n | `n8n-server.js` | n8n — URL via env `N8N_URL` (default `http://localhost:5678`), credentials from env |
| secure-command | `secure-command-server.js` | gated shell commands |

## Operating rules

- **Secrets:** portainer token and n8n credentials are secrets. Use `{env:VAR}` interpolation or host-side config; never hardcode into committed `opencode.jsonc`.
- **Read-first:** prefer read-only calls (inspect, list, query) over mutating ones.
- **Production gating:** mutating ops (container/stack/prod data changes) belong to P-tier agents (portainer-ops, docker-ops, db-admin, host-ops-specialist) and must be logged in the ticket.
- **Troubleshoot:** server scripts live at `/home/coder/project/*.server.js`; node runtime is `/usr/lib/code-server/lib/node`. Validate with a dry call before relying on results.
- **Filesystem root** is jailed to `/home/coder/project`; host access goes through the SSH `extravus-prod` path (secure-connector/host-ops-specialist), not the filesystem server.
