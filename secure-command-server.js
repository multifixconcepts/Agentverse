#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const { execSync } = require('child_process');

// Strict allowlist for SaaS development
const ALLOWED_COMMANDS = [
  'docker', 'docker-compose', 'npm', 'npx', 'php', 'composer',
  'git', 'grep', 'find', 'curl', 'ls', 'cat', 'pwd', 'whoami',
  'ps', 'df', 'du', 'tail', 'head', 'wc', 'sort', 'uniq', 'sudo'
];

const server = new Server(
  {
    name: 'secure-command-server',
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
      name: 'execute_command',
      description: 'Execute secure whitelisted commands for SaaS development',
      inputSchema: {
        type: 'object',
        properties: {
          command: { type: 'string', description: 'Command to execute' },
          args: { type: 'array', items: { type: 'string' }, description: 'Command arguments' }
        },
        required: ['command']
      }
    }
  ]
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  
  if (name === 'execute_command') {
    const { command, args: cmdArgs = [] } = args;
    
    // Security check
    if (!ALLOWED_COMMANDS.includes(command)) {
      return {
        content: [{ type: 'text', text: `Error: Command '${command}' not in allowlist` }],
        isError: true
      };
    }
    
    try {
      const fullCommand = [command, ...cmdArgs].join(' ');
      const output = execSync(fullCommand, { 
        encoding: 'utf8', 
        timeout: 30000,
        maxBuffer: 1024 * 1024 
      });
      
      return {
        content: [{ type: 'text', text: output }]
      };
    } catch (error) {
      return {
        content: [{ type: 'text', text: `Error: ${error.message}` }],
        isError: true
      };
    }
  }
  
  throw new Error(`Unknown tool: ${name}`);
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('Secure Command MCP server running');
}

main().catch(console.error);