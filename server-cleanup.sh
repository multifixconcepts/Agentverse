#!/bin/bash

# Main Server Cleanup Script
# Run this on your main server where Portainer is installed

set -e

echo "🧹 Starting Main Server Cleanup..."

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }

# 1. Docker Cleanup
print_status "Cleaning Docker resources..."

# Remove stopped containers
print_status "Removing stopped containers..."
sudo docker container prune -f

# Remove unused images
print_status "Removing unused images..."
sudo docker image prune -a -f

# Remove unused volumes
print_status "Removing unused volumes..."
sudo docker volume prune -f

# Remove unused networks
print_status "Removing unused networks..."
sudo docker network prune -f

# Remove build cache
print_status "Removing build cache..."
sudo docker builder prune -f

# 2. System Cleanup
print_status "Cleaning system files..."

# Clean package cache
sudo apt-get autoremove -y
sudo apt-get autoclean

# Clean logs older than 7 days
sudo journalctl --vacuum-time=7d

# Clean temporary files
sudo find /tmp -type f -atime +7 -delete 2>/dev/null || true
sudo find /var/tmp -type f -atime +7 -delete 2>/dev/null || true

# 3. Docker-specific cleanup
print_status "Deep Docker cleanup..."

# Remove all unused Docker data
sudo docker system prune -a -f --volumes

# 4. Check disk usage
print_status "Final disk usage:"
df -h /
sudo docker system df

print_success "Main server cleanup completed!"
echo "Run 'sudo docker system df' to verify cleanup results"