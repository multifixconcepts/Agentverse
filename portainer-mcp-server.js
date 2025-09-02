#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { CallToolRequestSchema, ListToolsRequestSchema } from '@modelcontextprotocol/sdk/types.js';
import fetch from 'node-fetch';

const PORTAINER_URL = process.env.PORTAINER_URL || 'https://server1.extravus.com';
const PORTAINER_TOKEN = process.env.PORTAINER_TOKEN;

class PortainerMCPServer {
  constructor() {
    this.server = new Server(
      {
        name: 'portainer-mcp-server',
        version: '1.0.0',
        title: 'Portainer API Server',
        description: 'MCP server for Portainer container management'
      },
      {
        capabilities: {
          tools: {}
        }
      }
    );

    this.setupToolHandlers();
    this.setupErrorHandling();
  }

  setupErrorHandling() {
    this.server.onerror = (error) => console.error('[MCP Error]', error);
    process.on('SIGINT', async () => {
      await this.server.close();
      process.exit(0);
    });
  }

  setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'list_containers',
          description: 'List all containers across all endpoints',
          inputSchema: {
            type: 'object',
            properties: {
              endpointId: { type: 'number', description: 'Endpoint ID (optional)' }
            }
          }
        },
        {
          name: 'container_logs',
          description: 'Get logs from a specific container',
          inputSchema: {
            type: 'object',
            properties: {
              endpointId: { type: 'number', description: 'Endpoint ID' },
              containerId: { type: 'string', description: 'Container ID' },
              tail: { type: 'number', description: 'Number of lines to tail', default: 100 }
            },
            required: ['endpointId', 'containerId']
          }
        },
        {
          name: 'container_stats',
          description: 'Get container statistics',
          inputSchema: {
            type: 'object',
            properties: {
              endpointId: { type: 'number', description: 'Endpoint ID' },
              containerId: { type: 'string', description: 'Container ID' }
            },
            required: ['endpointId', 'containerId']
          }
        },
        {
          name: 'list_stacks',
          description: 'List all Docker stacks',
          inputSchema: {
            type: 'object',
            properties: {
              endpointId: { type: 'number', description: 'Endpoint ID (optional)' }
            }
          }
        }
      ]
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'list_containers':
            return await this.listContainers(args.endpointId);
          case 'container_logs':
            return await this.getContainerLogs(args.endpointId, args.containerId, args.tail);
          case 'container_stats':
            return await this.getContainerStats(args.endpointId, args.containerId);
          case 'list_stacks':
            return await this.listStacks(args.endpointId);
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [{ type: 'text', text: `Error: ${error.message}` }],
          isError: true
        };
      }
    });
  }

  async makePortainerRequest(endpoint, method = 'GET') {
    if (!PORTAINER_TOKEN) {
      throw new Error('PORTAINER_TOKEN environment variable not set');
    }

    const response = await fetch(`${PORTAINER_URL}/api${endpoint}`, {
      method,
      headers: {
        'X-API-Key': PORTAINER_TOKEN,
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`Portainer API error: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  }

  async listContainers(endpointId) {
    const endpoint = endpointId ? `/endpoints/${endpointId}/docker/containers/json?all=true` : '/endpoints';
    const data = await this.makePortainerRequest(endpoint);
    
    return {
      content: [{
        type: 'text',
        text: JSON.stringify(data, null, 2)
      }]
    };
  }

  async getContainerLogs(endpointId, containerId, tail = 100) {
    const endpoint = `/endpoints/${endpointId}/docker/containers/${containerId}/logs?stdout=true&stderr=true&tail=${tail}`;
    const data = await this.makePortainerRequest(endpoint);
    
    return {
      content: [{
        type: 'text',
        text: typeof data === 'string' ? data : JSON.stringify(data, null, 2)
      }]
    };
  }

  async getContainerStats(endpointId, containerId) {
    const endpoint = `/endpoints/${endpointId}/docker/containers/${containerId}/stats?stream=false`;
    const data = await this.makePortainerRequest(endpoint);
    
    return {
      content: [{
        type: 'text',
        text: JSON.stringify(data, null, 2)
      }]
    };
  }

  async listStacks(endpointId) {
    const endpoint = endpointId ? `/endpoints/${endpointId}/stacks` : '/stacks';
    const data = await this.makePortainerRequest(endpoint);
    
    return {
      content: [{
        type: 'text',
        text: JSON.stringify(data, null, 2)
      }]
    };
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('Portainer MCP server running on stdio');
  }
}

const server = new PortainerMCPServer();
server.run().catch(console.error);