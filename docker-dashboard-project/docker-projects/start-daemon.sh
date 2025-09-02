#!/bin/bash
cd /home/coder/project/docker-projects

# Kill existing dashboard process if running
pkill -f "python3 server.py"

# Start dashboard in background
nohup python3 server.py > dashboard.log 2>&1 &

echo "🚀 Alade Johnson's Docker Dashboard started in background"
echo "📊 Dashboard URL: http://localhost:8888"
echo "📱 Mobile access: Use your server's IP address"
echo "📋 Logs: tail -f /home/coder/project/docker-projects/dashboard.log"
echo "🛑 Stop: pkill -f 'python3 server.py'"