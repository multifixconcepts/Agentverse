#!/bin/bash

# MCP Infrastructure Validation Suite
# Tests all servers with natural language queries

echo "🧪 MCP Infrastructure Validation Suite"
echo "======================================"

# Test queries for each server
echo "
📋 VALIDATION TEST QUERIES:

🔒 SECURE COMMAND SERVER:
'Q, use the command server to show me the status of all Docker containers using docker ps.'
'Q, execute docker-compose ps to see the status of our services.'
'Q, run git status to check the current repository state.'

📚 GIT SERVER:
'Q, use the git server to get a summary of the recent activity in the academia-360 repository.'
'Q, show me the last 5 commits in the current repository using the git server.'
'Q, get the git diff for the latest changes in the project.'

🌐 CURL SERVER:
'Q, use the curl server to check the health of our API at https://clientflow.edunaija.online/health.'
'Q, make a GET request to our school management API to test connectivity.'
'Q, fetch the status page from our main website using the curl server.'

🤖 N8N AUTOMATION SERVER:
'Q, ask the n8n server to list all available workflows and show me the one for School Onboarding.'
'Q, use the n8n server to trigger the Student Data Processing workflow.'
'Q, get the execution history for our automated backup workflow from n8n.'

🗄️ EXISTING SERVERS (Validation):
'Q, use the filesystem server to show me the structure of our project directory.'
'Q, query the SQLite database for our latest school registrations.'
'Q, use the memory server to store information about our current deployment status.'
'Q, check Portainer for the status of our containerized applications.'

🎯 INTEGRATION TESTS:
'Q, use git to check recent changes, then use the command server to build and deploy.'
'Q, trigger the onboarding workflow via n8n, then check the database for new entries.'
'Q, use curl to test our API, then store the results in memory for analysis.'
"

# Function to test server connectivity
test_server() {
    local server_name=$1
    local command=$2
    shift 2
    local args=("$@")
    
    echo "Testing $server_name..."
    
    local init_msg='{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test", "version": "1.0.0"}}}'
    
    if timeout 5s bash -c "echo '$init_msg' | $command ${args[*]}" >/dev/null 2>&1; then
        echo "✅ $server_name server is operational"
        return 0
    else
        echo "❌ $server_name server failed to respond"
        return 1
    fi
}

echo "
🔧 SERVER CONNECTIVITY TESTS:
"

# Test all servers
test_server "Secure Command" "/usr/bin/node" "/home/coder/project/secure-command-server.js"
test_server "Git" "/usr/bin/node" "/home/coder/project/git-server.js"  
test_server "Curl" "/usr/bin/node" "/home/coder/project/curl-server.js"
test_server "N8N" "/usr/bin/node" "/home/coder/project/n8n-server.js"

echo "
🎉 Validation suite complete! Use the test queries above with Amazon Q."