#!/usr/bin/env node
'use strict';
const { DatabaseSync } = require('node:sqlite');
const { Server } = require('@modelcontextprotocol/sdk/server');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} = require('@modelcontextprotocol/sdk/types.js');

const dbPath =
  process.argv[2] || process.env.AGENTVERSE_DB || '/home/coder/project/AGENTVERSE/agentverse.db';
const db = new DatabaseSync(dbPath);
db.exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');

const server = new Server(
  { name: 'agentverse-sqlite', version: '1.0.0' },
  { capabilities: { tools: {} } }
);

const READ_HEAD = /^(SELECT|PRAGMA|EXPLAIN)\b/i;

function runQuery(query) {
  const trimmed = String(query).trim();
  if (!trimmed) return { content: [{ type: 'text', text: 'empty query' }] };
  const stmt = db.prepare(trimmed);
  if (READ_HEAD.test(trimmed)) {
    const rows = stmt.all();
    return { content: [{ type: 'text', text: JSON.stringify(rows, null, 2) }] };
  }
  const info = stmt.run();
  return {
    content: [{ type: 'text', text: JSON.stringify({ changes: Number(info.changes), lastInsertRowid: info.lastInsertRowid }) }],
  };
}

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'query_db',
      description: 'Run a read-only SQL query (SELECT/PRAGMA/EXPLAIN) against the Agentverse database at ' + dbPath,
      inputSchema: { type: 'object', properties: { query: { type: 'string' } }, required: ['query'] },
    },
    {
      name: 'write_db',
      description: 'Run a single write SQL statement (INSERT/UPDATE/DELETE/DDL) against the Agentverse database at ' + dbPath,
      inputSchema: { type: 'object', properties: { query: { type: 'string' } }, required: ['query'] },
    },
  ],
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  if (name === 'query_db' || name === 'write_db') return runQuery(args && args.query);
  throw new Error(`Unknown tool: ${name}`);
});

const transport = new StdioServerTransport();
server.connect(transport);
