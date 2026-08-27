#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');

class N8NClient {
  constructor() {
    this.baseUrl = process.env.N8N_URL || 'http://localhost:5678';
    this.apiKey = process.env.N8N_API_KEY;
    this.username = process.env.N8N_USERNAME || 'extravus';
    this.password = process.env.N8N_PASSWORD || 'REDACTED_N8N_PASSWORD';
  }

  async makeRequest(endpoint, options = {}) {
    const fetch = (await import('node-fetch')).default;
    const url = `${this.baseUrl}/api/v1${endpoint}`;
    
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    if (this.apiKey) {
      headers['X-N8N-API-KEY'] = this.apiKey;
    } else {
      // Basic auth fallback
      const auth = Buffer.from(`${this.username}:${this.password}`).toString('base64');
      headers['Authorization'] = `Basic ${auth}`;
    }

    const response = await fetch(url, {
      method: options.method || 'GET',
      headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
      timeout: 30000
    });

    if (!response.ok) {
      throw new Error(`N8N API error: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  }
}

const server = new Server(
  {
    name: 'n8n-server',
    version: '1.0.0'
  },
  {
    capabilities: {
      tools: {}
    }
  }
);

const n8nClient = new N8NClient();

server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: [
    {
      name: 'list_workflows',
      description: 'List all N8N workflows',
      inputSchema: {
        type: 'object',
        properties: {
          active: { type: 'boolean', description: 'Filter by active status' }
        }
      }
    },
    {
      name: 'get_workflow',
      description: 'Get workflow details by ID',
      inputSchema: {
        type: 'object',
        properties: {
          workflow_id: { type: 'string', description: 'Workflow ID' }
        },
        required: ['workflow_id']
      }
    },
    {
      name: 'trigger_workflow',
      description: 'Trigger workflow execution',
      inputSchema: {
        type: 'object',
        properties: {
          workflow_id: { type: 'string', description: 'Workflow ID' },
          data: { type: 'object', description: 'Input data for workflow' }
        },
        required: ['workflow_id']
      }
    },
    {
      name: 'get_executions',
      description: 'Get workflow execution history',
      inputSchema: {
        type: 'object',
        properties: {
          workflow_id: { type: 'string', description: 'Workflow ID' },
          limit: { type: 'number', default: 10 }
        }
      }
    }
  ]
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  
  try {
    let result;
    
    switch (name) {
      case 'list_workflows':
        result = await n8nClient.makeRequest('/workflows');
        if (args.active !== undefined) {
          result.data = result.data.filter(w => w.active === args.active);
        }
        break;
        
      case 'get_workflow':
        result = await n8nClient.makeRequest(`/workflows/${args.workflow_id}`);
        break;
        
      case 'trigger_workflow':
        result = await n8nClient.makeRequest(`/workflows/${args.workflow_id}/execute`, {
          method: 'POST',
          body: args.data || {}
        });
        break;
        
      case 'get_executions':
        const execEndpoint = args.workflow_id 
          ? `/executions?workflowId=${args.workflow_id}&limit=${args.limit || 10}`
          : `/executions?limit=${args.limit || 10}`;
        result = await n8nClient.makeRequest(execEndpoint);
        break;
        
      default:
        throw new Error(`Unknown tool: ${name}`);
    }
    
    return {
      content: [{ type: 'text', text: JSON.stringify(result, null, 2) }]
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
  console.error('N8N MCP server running');
}

main().catch(console.error);