#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const { execSync } = require('child_process');

const server = new Server(
  {
    name: 'curl-server',
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
      name: 'http_request',
      description: 'Make HTTP requests safely',
      inputSchema: {
        type: 'object',
        properties: {
          url: { type: 'string', description: 'URL to request' },
          method: { type: 'string', enum: ['GET', 'POST', 'PUT', 'DELETE'], default: 'GET' },
          headers: { type: 'object', description: 'HTTP headers' },
          data: { type: 'string', description: 'Request body' },
          timeout: { type: 'number', default: 30 }
        },
        required: ['url']
      }
    }
  ]
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  
  if (name === 'http_request') {
    try {
      let curlCmd = ['curl', '-s', '--max-time', args.timeout || 30];
      
      // Add method
      if (args.method && args.method !== 'GET') {
        curlCmd.push('-X', args.method);
      }
      
      // Add headers
      if (args.headers) {
        Object.entries(args.headers).forEach(([key, value]) => {
          curlCmd.push('-H', `${key}: ${value}`);
        });
      }
      
      // Add data
      if (args.data) {
        curlCmd.push('-d', args.data);
      }
      
      // Add URL
      curlCmd.push(args.url);
      
      const output = execSync(curlCmd.join(' '), { 
        encoding: 'utf8',
        timeout: (args.timeout || 30) * 1000
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
  console.error('Curl MCP server running');
}

main().catch(console.error);