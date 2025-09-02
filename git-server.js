#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const { execSync } = require('child_process');

const server = new Server(
  {
    name: 'git-server',
    version: '1.0.0'
  },
  {
    capabilities: {
      tools: {}
    }
  }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'git_log',
      description: 'Get git commit history',
      inputSchema: {
        type: 'object',
        properties: {
          repo_path: { type: 'string', description: 'Repository path' },
          limit: { type: 'number', description: 'Number of commits', default: 10 }
        },
        required: ['repo_path']
      }
    },
    {
      name: 'git_status',
      description: 'Get git repository status',
      inputSchema: {
        type: 'object',
        properties: {
          repo_path: { type: 'string', description: 'Repository path' }
        },
        required: ['repo_path']
      }
    },
    {
      name: 'git_diff',
      description: 'Get git diff',
      inputSchema: {
        type: 'object',
        properties: {
          repo_path: { type: 'string', description: 'Repository path' },
          commit: { type: 'string', description: 'Commit hash (optional)' }
        },
        required: ['repo_path']
      }
    }
  ]
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  
  try {
    let output;
    
    switch (name) {
      case 'git_log':
        output = execSync(`git -C "${args.repo_path}" log --oneline -${args.limit || 10}`, { encoding: 'utf8' });
        break;
      case 'git_status':
        output = execSync(`git -C "${args.repo_path}" status --porcelain`, { encoding: 'utf8' });
        break;
      case 'git_diff':
        const diffCmd = args.commit 
          ? `git -C "${args.repo_path}" diff ${args.commit}`
          : `git -C "${args.repo_path}" diff`;
        output = execSync(diffCmd, { encoding: 'utf8' });
        break;
      default:
        throw new Error(`Unknown tool: ${name}`);
    }
    
    return {
      content: [{ type: 'text', text: output || 'No output' }]
    };
  } catch (error) {
    return {
      content: [{ type: 'text', text: `Error: ${error.message}` }],
      isError: true
    };
  }
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('Git MCP server running');
}

main().catch(console.error);