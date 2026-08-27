#!/usr/bin/env python3

import subprocess
import json
import http.server
import socketserver
from urllib.parse import urlparse, parse_qs, unquote
import os
import time
from datetime import datetime

class DockerPortainerHandler(http.server.SimpleHTTPRequestHandler):
    
    def do_GET(self):
        # Parse path
        path = self.path.split('?')[0]
        
        if path == '/':
            self.serve_main_page()
        elif path == '/api/containers':
            self.get_containers_api()
        elif path == '/api/images':
            self.get_images_api()
        elif path == '/api/networks':
            self.get_networks_api()
        elif path == '/api/volumes':
            self.get_volumes_api()
        elif path == '/api/system':
            self.get_system_info()
        else:
            self.send_error(404, "Not Found")
    
    def do_POST(self):
        path = self.path.split('?')[0]
        
        if path == '/api/docker':
            self.execute_docker_command()
        else:
            self.send_error(404, "Not Found")
    
    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.send_header('Access-Control-Max-Age', '86400')
        self.end_headers()
    
    def serve_main_page(self):
        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()
        self.wfile.write(html.encode('utf-8'))
    
    def get_containers_api(self):
        try:
            result = subprocess.run(
                'sudo docker ps -a --format "{{.Names}}\t{{.Status}}\t{{.Image}}\t{{.CreatedAt}}\t{{.Ports}}"',
                shell=True, capture_output=True, text=True, timeout=30
            )
            
            if result.returncode == 0:
                containers = []
                lines = result.stdout.strip().split('\n')[1:]  # Skip header
                
                for line in lines:
                    if line.strip():
                        parts = line.split('\t')
                        if len(parts) >= 5:
                            containers.append({
                                'name': parts[0],
                                'status': parts[1],
                                'image': parts[2],
                                'created': parts[3],
                                'ports': parts[4]
                            })
                
                self.send_json_response({'containers': containers})
            else:
                self.send_json_response({'containers': []})
                
        except Exception as e:
            self.send_json_response({'containers': []})
                
        except Exception as e:
            print(f"Error getting containers: {e}")
            self.send_json_response({'containers': []})
    
    def get_images_api(self):
        try:
            result = subprocess.run(
                'sudo docker images --format "{{.Repository}}\t{{.Tag}}\t{{.ID}}\t{{.CreatedAt}}\t{{.Size}}"',
                shell=True, capture_output=True, text=True, timeout=30
            )
            
            if result.returncode == 0:
                images = []
                lines = result.stdout.strip().split('\n')[1:]  # Skip header
                
                for line in lines:
                    if line.strip():
                        parts = line.split('\t')
                        if len(parts) >= 5:
                            images.append({
                                'repository': parts[0],
                                'tag': parts[1],
                                'id': parts[2],
                                'created': parts[3],
                                'size': parts[4]
                            })
                
                self.send_json_response({'images': images})
            else:
                self.send_json_response({'images': []})
                
        except Exception as e:
            self.send_json_response({'images': []})
    
    def get_networks_api(self):
        try:
            result = subprocess.run(
                'sudo docker network ls --format "{{.Name}}\t{{.Driver}}\t{{.Scope}}"',
                shell=True, capture_output=True, text=True, timeout=30
            )
            
            if result.returncode == 0:
                networks = []
                lines = result.stdout.strip().split('\n')[1:]  # Skip header
                
                for line in lines:
                    if line.strip():
                        parts = line.split('\t')
                        if len(parts) >= 3:
                            networks.append({
                                'name': parts[0],
                                'driver': parts[1],
                                'scope': parts[2]
                            })
                
                self.send_json_response({'networks': networks})
            else:
                self.send_json_response({'networks': []})
                
        except Exception as e:
            self.send_json_response({'networks': []})
    
    def get_volumes_api(self):
        try:
            result = subprocess.run(
                'sudo docker volume ls --format "{{.Driver}}\t{{.Name}}"',
                shell=True, capture_output=True, text=True, timeout=30
            )
            
            if result.returncode == 0:
                volumes = []
                lines = result.stdout.strip().split('\n')[1:]  # Skip header
                
                for line in lines:
                    if line.strip():
                        parts = line.split('\t')
                        if len(parts) >= 2:
                            volumes.append({
                                'driver': parts[0],
                                'name': parts[1]
                            })
                
                self.send_json_response({'volumes': volumes})
            else:
                self.send_json_response({'volumes': []})
                
        except Exception as e:
            self.send_json_response({'volumes': []})
    
    def get_system_info(self):
        try:
            # Get Docker version
            docker_version_result = subprocess.run(['sudo', 'docker', 'version', '--format', '{{.Server.Version}}'], 
                                                  capture_output=True, text=True, timeout=30)
            
            # Get system info
            system_info_result = subprocess.run(['sudo', 'docker', 'system', 'info', '--format', '{{.OSType}} {{.Architecture}}'], 
                                               capture_output=True, text=True, timeout=30)
            
            data = {
                'docker_version': docker_version_result.stdout.strip() if docker_version_result.returncode == 0 else 'Unknown',
                'api_version': '1.41',
                'kernel_version': 'Unknown',
                'os': 'Linux',
                'architecture': 'x86_64',
                'memory': 'Unknown'
            }
            
            if system_info_result.returncode == 0:
                info_parts = system_info_result.stdout.strip().split()
                if len(info_parts) >= 2:
                    data['os'] = info_parts[0]
                    data['architecture'] = info_parts[1]
            
            self.send_json_response(data)
            
        except Exception as e:
            self.send_json_response({
                'docker_version': 'Unknown',
                'api_version': 'Unknown',
                'kernel_version': 'Unknown',
                'os': 'Unknown',
                'architecture': 'Unknown',
                'memory': 'Unknown'
            })
    
    def execute_docker_command(self):
        try:
            content_length = int(self.headers.get('Content-Length', 0))
            post_data = self.rfile.read(content_length)
            
            data = json.loads(post_data.decode('utf-8'))
            command = data.get('command', '')
            
            if not command.startswith('docker '):
                self.send_json_response({'success': False, 'error': 'Only docker commands are allowed'})
                return
            
            # Execute docker command
            full_command = command.replace('docker ', 'sudo docker ')
            result = subprocess.run(full_command, shell=True, capture_output=True, text=True, timeout=60)
            
            response = {
                'success': result.returncode == 0,
                'stdout': result.stdout,
                'stderr': result.stderr,
                'returncode': result.returncode,
                'command': command
            }
            
            self.send_json_response(response)
            
        except Exception as e:
            self.send_json_response({'success': False, 'error': 'Server error: ' + str(e)})
    
    def send_json_response(self, data):
        json_data = json.dumps(data)
        self.send_response(200)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        self.end_headers()
        self.wfile.write(json_data.encode('utf-8'))

def run_server():
    PORT = 8888
    server = socketserver.TCPServer(('', PORT), DockerPortainerHandler)
    server.allow_reuse_address = True
    
    print("🚀 Docker Web UI Server")
    print("=" * 40)
    print(f"✅ Server running on port {PORT}")
    print(f"✅ Access via: https://vscode.edunaija.online/proxy/8888/")
    print("=" * 40)
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n🛑 Server stopped")
        server.shutdown()

if __name__ == '__main__':
    run_server()