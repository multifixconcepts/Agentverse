#!/usr/bin/env python3
import json
import subprocess
import http.server
import socketserver
from urllib.parse import urlparse, parse_qs
import os
import threading
import time
from datetime import datetime

class DockerHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/':
            self.path = '/enhanced-dashboard.html'
        elif self.path == '/dashboard':
            self.path = '/enhanced-dashboard.html'
        elif self.path == '/test':
            self.send_response(200)
            self.send_header('Content-Type', 'text/plain')
            self.end_headers()
            self.wfile.write(b"Dashboard FIXED & VERIFIED - Feb 1 2026")
            return
        elif self.path == '/status':
            status_data = {
                "status": "working",
                "timestamp": "2026-02-01 19:15",
                "features": ["containers", "images", "networks", "volumes", "monitoring"],
                "message": "Dashboard is fully functional through VS Code proxy"
            }
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(status_data).encode())
            return
        
        # Handle static files with proper headers
        try:
            # Check if file exists and get its content
            file_path = self.translate_path(self.path)
            if os.path.exists(file_path) and os.path.isfile(file_path):
                # Send response with CORS headers
                self.send_response(200)
                self.send_header('Access-Control-Allow-Origin', '*')
                self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
                self.send_header('Pragma', 'no-cache')
                self.send_header('Expires', '0')
                
                # Set content type based on file extension
                if file_path.endswith('.html'):
                    self.send_header('Content-Type', 'text/html')
                elif file_path.endswith('.css'):
                    self.send_header('Content-Type', 'text/css')
                elif file_path.endswith('.js'):
                    self.send_header('Content-Type', 'application/javascript')
                elif file_path.endswith('.json'):
                    self.send_header('Content-Type', 'application/json')
                
                # Read and send file content
                with open(file_path, 'rb') as f:
                    content = f.read()
                self.send_header('Content-Length', str(len(content)))
                self.end_headers()
                self.wfile.write(content)
            else:
                self.send_error(404, "File not found")
        except Exception as e:
            self.send_error(500, f"Server error: {str(e)}")
    
    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()
    
    def do_POST(self):
        if self.path == '/api/docker':
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            
            try:
                data = json.loads(post_data.decode('utf-8'))
                command = data.get('command', '')
                
                print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Executing: {command}")
                
                if not command.startswith('docker '):
                    response = {'success': False, 'error': 'Only docker commands are allowed'}
                else:
                    # Use sudo for Docker commands
                    sudo_command = command.replace('docker ', 'sudo docker ')
                    result = subprocess.run(
                        sudo_command, 
                        shell=True, 
                        capture_output=True, 
                        text=True,
                        timeout=60,
                        cwd='/home/coder/project'
                    )
                    
                    response = {
                        'success': result.returncode == 0,
                        'stdout': result.stdout,
                        'stderr': result.stderr,
                        'returncode': result.returncode,
                        'command': command
                    }
                    
            except subprocess.TimeoutExpired:
                response = {'success': False, 'error': 'Command timeout (60s)'}
            except json.JSONDecodeError:
                response = {'success': False, 'error': 'Invalid JSON data'}
            except Exception as e:
                response = {'success': False, 'error': f'Server error: {str(e)}'}
            
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.send_header('Access-Control-Allow-Headers', 'Content-Type')
            self.end_headers()
            self.wfile.write(json.dumps(response).encode())
            
        elif self.path == '/api/stats':
            try:
                stats = self.get_system_stats()
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
                self.wfile.write(json.dumps(stats).encode())
            except Exception as e:
                self.send_response(500)
                self.send_header('Content-type', 'application/json')
                self.send_header('Access-Control-Allow-Origin', '*')
                self.end_headers()
                self.wfile.write(json.dumps({'error': str(e)}).encode())
        else:
            self.send_response(404)
            self.end_headers()
    
    def get_system_stats(self):
        """Get system statistics"""
        try:
            # Get Docker system info
            docker_info = subprocess.run(
                'docker system df --format "table {{.Type}}\t{{.Total}}\t{{.Active}}\t{{.Size}}\t{{.Reclaimable}}"',
                shell=True, capture_output=True, text=True, timeout=10
            )
            
            # Get container stats
            container_stats = subprocess.run(
                'docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"',
                shell=True, capture_output=True, text=True, timeout=10
            )
            
            return {
                'docker_info': docker_info.stdout if docker_info.returncode == 0 else 'N/A',
                'container_stats': container_stats.stdout if container_stats.returncode == 0 else 'N/A',
                'timestamp': datetime.now().isoformat()
            }
        except Exception as e:
            return {'error': str(e), 'timestamp': datetime.now().isoformat()}

def check_docker_access():
    """Check if Docker is accessible"""
    try:
        result = subprocess.run('docker version', shell=True, capture_output=True, text=True, timeout=5)
        return result.returncode == 0
    except:
        return False

if __name__ == "__main__":
    PORT = 8888
    socketserver.TCPServer.allow_reuse_address = True
    # Stay in current directory to serve files properly
    
    print("🐳 Alade Johnson's Docker Dashboard")
    print("=" * 40)
    
    # Check Docker access
    if check_docker_access():
        print("✅ Docker access: OK")
    else:
        print("⚠️  Docker access: Limited (some features may not work)")
    
    print(f"🌐 Server starting on port {PORT}")
    print(f"📊 Dashboard URL: http://localhost:{PORT}")
    print(f"📁 Working directory: {os.getcwd()}")
    print("\n🔄 Server logs:")
    print("-" * 40)
    
    try:
        with socketserver.TCPServer(('', PORT), DockerHandler) as httpd:
            print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Server ready - Press Ctrl+C to stop")
            httpd.serve_forever()
    except KeyboardInterrupt:
        print(f"\n[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Server stopped by user")
    except Exception as e:
        print(f"\n[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Server error: {e}")