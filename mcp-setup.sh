#!/bin/bash

# MCP Framework Setup Script
# Principal AI Systems Architect Implementation

set -e

echo "🚀 Setting up Advanced MCP Framework..."

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Test MCP server connectivity
test_mcp_server() {
    local server_name=$1
    local command=$2
    shift 2
    local args=("$@")
    
    print_status "Testing $server_name server..."
    
    # Create test initialization message
    local init_msg='{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test", "version": "1.0.0"}}}'
    
    if timeout 10s bash -c "echo '$init_msg' | $command ${args[*]}" >/dev/null 2>&1; then
        print_success "$server_name server is working"
        return 0
    else
        print_error "$server_name server failed to respond"
        return 1
    fi
}

# Main setup process
main() {
    print_status "Starting MCP Framework validation..."
    
    # Test filesystem server
    test_mcp_server "Filesystem" "mcp-server-filesystem" "/home/coder/project"
    
    # Test memory server
    test_mcp_server "Memory" "/usr/bin/node" "--max-old-space-size=512" "/usr/lib/node_modules/@modelcontextprotocol/server-memory/dist/index.js"
    
    # Test SQLite server
    test_mcp_server "SQLite" "/home/coder/.local/bin/mcp-server-sqlite" "--db-path" "/home/coder/project/database.db"
    
    # Test Portainer server (will fail without token, but checks if script runs)
    print_status "Testing Portainer server (expect auth error)..."
    if timeout 5s /usr/bin/node --max-old-space-size=256 /home/coder/project/portainer-mcp-server.js >/dev/null 2>&1; then
        print_success "Portainer server script is executable"
    else
        print_warning "Portainer server needs PORTAINER_TOKEN environment variable"
    fi
    
    # Create database file if it doesn't exist
    if [ ! -f "/home/coder/project/database.db" ]; then
        print_status "Creating SQLite database..."
        touch /home/coder/project/database.db
        print_success "Database file created"
    fi
    
    # Validate configuration file
    if [ -f "/home/coder/project/.amazonq/config.json" ]; then
        if python3 -m json.tool /home/coder/project/.amazonq/config.json >/dev/null 2>&1; then
            print_success "Configuration file is valid JSON"
        else
            print_error "Configuration file has invalid JSON syntax"
            exit 1
        fi
    else
        print_error "Configuration file not found"
        exit 1
    fi
    
    print_success "MCP Framework setup completed!"
    print_status "Next steps:"
    echo "  1. Set PORTAINER_TOKEN environment variable for Docker management"
    echo "  2. Restart Amazon Q extension to load new configuration"
    echo "  3. Run validation tests using the provided test queries"
}

main "$@"