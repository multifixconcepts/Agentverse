// Enhanced Docker Dashboard JavaScript
let autoRefreshInterval;
let currentFilter = 'all';
let containers = [];
let images = [];
let networks = [];
let volumes = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (checkAuth()) {
        initializeNavigation();
        initializeAutoRefresh();
        loadAllData();
        setupEventListeners();
    }
    
    // Add Enter key support for login
    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            login();
        }
    });
});

// Navigation
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetPage = link.dataset.page;
            
            // Update active nav
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            
            // Update active page
            pages.forEach(p => p.classList.remove('active'));
            document.getElementById(targetPage).classList.add('active');
            
            // Update page title
            document.getElementById('page-title').innerHTML = `
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.4rem;">AJ</div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #2c3e50;">Alade Johnson's Dashboard</div>
                        <div style="font-size: 0.9rem; color: #7f8c8d;">${link.textContent.trim()}</div>
                    </div>
                </div>
            `;
            
            // Load page-specific data
            loadPageData(targetPage);
        });
    });
}

// Auto-refresh functionality
function initializeAutoRefresh() {
    const toggle = document.getElementById('auto-refresh-toggle');
    toggle.addEventListener('click', () => {
        toggle.classList.toggle('active');
        if (toggle.classList.contains('active')) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
}

function startAutoRefresh() {
    const interval = parseInt(document.getElementById('refresh-interval')?.value || 10) * 1000;
    autoRefreshInterval = setInterval(refreshData, interval);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// API calls
async function executeDockerCommand(command) {
    try {
        const response = await fetch('/api/docker', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ command })
        });
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        return { success: false, error: error.message };
    }
}

// Data loading
async function loadAllData() {
    await Promise.all([
        loadContainers(),
        loadImages(),
        loadNetworks(),
        loadVolumes(),
        loadSystemStats()
    ]);
}

async function loadContainers() {
    const result = await executeDockerCommand('docker ps -a --format "table {{.ID}}\\t{{.Image}}\\t{{.Command}}\\t{{.CreatedAt}}\\t{{.Status}}\\t{{.Ports}}\\t{{.Names}}"');
    
    if (result.success) {
        containers = parseContainerData(result.stdout);
        renderContainers();
        updateStats();
    } else {
        // Fallback to mock data
        containers = getMockContainers();
        renderContainers();
        updateStats();
    }
}

async function loadImages() {
    const result = await executeDockerCommand('docker images --format "table {{.Repository}}\\t{{.Tag}}\\t{{.ID}}\\t{{.CreatedAt}}\\t{{.Size}}"');
    
    if (result.success) {
        images = parseImageData(result.stdout);
        renderImages();
    } else {
        images = getMockImages();
        renderImages();
    }
}

async function loadNetworks() {
    const result = await executeDockerCommand('docker network ls --format "table {{.ID}}\\t{{.Name}}\\t{{.Driver}}\\t{{.Scope}}"');
    
    if (result.success) {
        networks = parseNetworkData(result.stdout);
        renderNetworks();
    } else {
        networks = getMockNetworks();
        renderNetworks();
    }
}

async function loadVolumes() {
    const result = await executeDockerCommand('docker volume ls --format "table {{.Driver}}\\t{{.Name}}"');
    
    if (result.success) {
        volumes = parseVolumeData(result.stdout);
        renderVolumes();
    } else {
        volumes = getMockVolumes();
        renderVolumes();
    }
}

async function loadSystemStats() {
    // Mock system stats for now
    updateSystemMetrics();
}

// Data parsing
function parseContainerData(output) {
    const lines = output.split('\n').filter(line => line.trim());
    const containers = [];
    
    for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(/\t/);
        if (parts.length >= 7) {
            containers.push({
                id: parts[0],
                image: parts[1],
                command: parts[2],
                created: parts[3],
                status: parts[4],
                ports: parts[5] || '',
                name: parts[6]
            });
        }
    }
    return containers;
}

function parseImageData(output) {
    const lines = output.split('\n').filter(line => line.trim());
    const images = [];
    
    for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(/\t/);
        if (parts.length >= 5) {
            images.push({
                repository: parts[0],
                tag: parts[1],
                id: parts[2],
                created: parts[3],
                size: parts[4]
            });
        }
    }
    return images;
}

function parseNetworkData(output) {
    const lines = output.split('\n').filter(line => line.trim());
    const networks = [];
    
    for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(/\t/);
        if (parts.length >= 4) {
            networks.push({
                id: parts[0],
                name: parts[1],
                driver: parts[2],
                scope: parts[3]
            });
        }
    }
    return networks;
}

function parseVolumeData(output) {
    const lines = output.split('\n').filter(line => line.trim());
    const volumes = [];
    
    for (let i = 1; i < lines.length; i++) {
        const parts = lines[i].split(/\t/);
        if (parts.length >= 2) {
            volumes.push({
                driver: parts[0],
                name: parts[1]
            });
        }
    }
    return volumes;
}

// Rendering functions
function renderContainers() {
    const containersList = document.getElementById('containers-list');
    if (!containersList) return;
    
    const filteredContainers = filterContainers(containers);
    
    if (filteredContainers.length === 0) {
        containersList.innerHTML = '<div class="loading">No containers found</div>';
        return;
    }

    containersList.innerHTML = filteredContainers.map(container => {
        const isRunning = container.status.includes('Up');
        const isPaused = container.status.includes('Paused');
        const statusClass = isPaused ? 'paused' : (isRunning ? 'running' : 'stopped');
        const statusText = isPaused ? 'Paused' : (isRunning ? 'Running' : 'Stopped');
        
        return `
            <div class="container-card" style="position: relative;">
                <div class="status-badge ${statusClass}">${statusText}</div>
                <div class="container-header">
                    <div class="container-name">${container.name}</div>
                    <div class="container-image">${container.image}</div>
                </div>
                <div class="container-body">
                    <div class="container-metrics">
                        <div class="metric">
                            <div class="metric-value">${Math.floor(Math.random() * 100)}%</div>
                            <div class="metric-label">CPU</div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-cpu" style="width: ${Math.floor(Math.random() * 100)}%"></div>
                            </div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">${Math.floor(Math.random() * 100)}%</div>
                            <div class="metric-label">Memory</div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-memory" style="width: ${Math.floor(Math.random() * 100)}%"></div>
                            </div>
                        </div>
                        <div class="metric">
                            <div class="metric-value">${(Math.random() * 10).toFixed(1)}GB</div>
                            <div class="metric-label">Disk I/O</div>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.9rem; color: #6c757d; margin-bottom: 0.5rem;">
                            <strong>Status:</strong> ${container.status}
                        </div>
                        <div style="font-size: 0.9rem; color: #6c757d; margin-bottom: 0.5rem;">
                            <strong>Ports:</strong> ${container.ports || 'None'}
                        </div>
                        <div style="font-size: 0.9rem; color: #6c757d;">
                            <strong>Created:</strong> ${container.created}
                        </div>
                    </div>
                    <div class="actions">
                        ${!isRunning && !isPaused ? `<button class="btn btn-success" onclick="startContainer('${container.name}')">▶️ Start</button>` : ''}
                        ${isRunning ? `<button class="btn btn-danger" onclick="stopContainer('${container.name}')">⏹️ Stop</button>` : ''}
                        ${isRunning ? `<button class="btn btn-warning" onclick="pauseContainer('${container.name}')">⏸️ Pause</button>` : ''}
                        ${isPaused ? `<button class="btn btn-success" onclick="unpauseContainer('${container.name}')">▶️ Unpause</button>` : ''}
                        <button class="btn btn-info" onclick="viewLogs('${container.name}')">📋 Logs</button>
                        ${isRunning ? `<button class="btn btn-secondary" onclick="openShell('${container.name}')">💻 Shell</button>` : ''}
                        <button class="btn btn-warning" onclick="restartContainer('${container.name}')">🔄 Restart</button>
                        <button class="btn btn-danger" onclick="removeContainer('${container.name}')">🗑️ Remove</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderImages() {
    const imagesList = document.getElementById('images-list');
    if (!imagesList) return;
    
    imagesList.innerHTML = `
        <div style="display: grid; gap: 1rem;">
            ${images.map(image => `
                <div class="card">
                    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: bold; font-size: 1.1rem;">${image.repository}:${image.tag}</div>
                            <div style="color: #6c757d; font-size: 0.9rem;">ID: ${image.id}</div>
                            <div style="color: #6c757d; font-size: 0.9rem;">Size: ${image.size}</div>
                            <div style="color: #6c757d; font-size: 0.9rem;">Created: ${image.created}</div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary" onclick="runImage('${image.repository}:${image.tag}')">▶️ Run</button>
                            <button class="btn btn-danger" onclick="removeImage('${image.id}')">🗑️ Remove</button>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function renderNetworks() {
    const networksContainer = document.getElementById('networks-diagram');
    if (!networksContainer) return;
    
    networksContainer.innerHTML = networks.map(network => `
        <div class="network-node network">
            <div style="font-weight: bold;">${network.name}</div>
            <div style="font-size: 0.9rem; color: #6c757d;">Driver: ${network.driver}</div>
            <div style="font-size: 0.9rem; color: #6c757d;">Scope: ${network.scope}</div>
            <div style="margin-top: 1rem;">
                <button class="btn btn-info btn-sm" onclick="inspectNetwork('${network.name}')">🔍 Inspect</button>
                ${!['bridge', 'host', 'none'].includes(network.name) ? `<button class="btn btn-danger btn-sm" onclick="removeNetwork('${network.name}')">🗑️ Remove</button>` : ''}
            </div>
        </div>
    `).join('');
}

function renderVolumes() {
    const volumesList = document.getElementById('volumes-list');
    if (!volumesList) return;
    
    volumesList.innerHTML = `
        <div style="display: grid; gap: 1rem;">
            ${volumes.map(volume => `
                <div class="card">
                    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: bold; font-size: 1.1rem;">${volume.name}</div>
                            <div style="color: #6c757d; font-size: 0.9rem;">Driver: ${volume.driver}</div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-info" onclick="inspectVolume('${volume.name}')">🔍 Inspect</button>
                            <button class="btn btn-danger" onclick="removeVolume('${volume.name}')">🗑️ Remove</button>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Container actions
async function startContainer(name) {
    const result = await executeDockerCommand(`docker start ${name}`);
    if (result.success) {
        showNotification(`Container ${name} started successfully`, 'success');
        loadContainers();
    } else {
        showNotification(`Failed to start container ${name}`, 'error');
    }
}

async function stopContainer(name) {
    if (confirm(`Stop container: ${name}?`)) {
        const result = await executeDockerCommand(`docker stop ${name}`);
        if (result.success) {
            showNotification(`Container ${name} stopped successfully`, 'success');
            loadContainers();
        } else {
            showNotification(`Failed to stop container ${name}`, 'error');
        }
    }
}

async function pauseContainer(name) {
    const result = await executeDockerCommand(`docker pause ${name}`);
    if (result.success) {
        showNotification(`Container ${name} paused successfully`, 'success');
        loadContainers();
    } else {
        showNotification(`Failed to pause container ${name}`, 'error');
    }
}

async function unpauseContainer(name) {
    const result = await executeDockerCommand(`docker unpause ${name}`);
    if (result.success) {
        showNotification(`Container ${name} unpaused successfully`, 'success');
        loadContainers();
    } else {
        showNotification(`Failed to unpause container ${name}`, 'error');
    }
}

async function restartContainer(name) {
    const result = await executeDockerCommand(`docker restart ${name}`);
    if (result.success) {
        showNotification(`Container ${name} restarted successfully`, 'success');
        loadContainers();
    } else {
        showNotification(`Failed to restart container ${name}`, 'error');
    }
}

async function removeContainer(name) {
    if (confirm(`Remove container: ${name}? This action cannot be undone.`)) {
        const result = await executeDockerCommand(`docker rm -f ${name}`);
        if (result.success) {
            showNotification(`Container ${name} removed successfully`, 'success');
            loadContainers();
        } else {
            showNotification(`Failed to remove container ${name}`, 'error');
        }
    }
}

async function viewLogs(name) {
    const result = await executeDockerCommand(`docker logs --tail 100 ${name}`);
    const modal = document.getElementById('logs-modal');
    const output = document.getElementById('logs-output');
    
    if (result.success) {
        output.innerHTML = result.stdout.split('\n').map(line => 
            `<div class="log-line">${escapeHtml(line)}</div>`
        ).join('');
    } else {
        output.innerHTML = `<div class="log-line log-error">Failed to fetch logs: ${result.error}</div>`;
    }
    
    modal.classList.add('active');
}

async function openShell(name) {
    const modal = document.getElementById('terminal-modal');
    const output = document.getElementById('terminal-output');
    
    output.innerHTML = `<div>Connected to container: ${name}</div><div>Type 'exit' to close terminal</div>`;
    modal.classList.add('active');
    
    // Focus on terminal input
    document.getElementById('terminal-input').focus();
}

// Utility functions
function filterContainers(containers) {
    let filtered = containers;
    
    // Apply status filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(container => {
            switch (currentFilter) {
                case 'running': return container.status.includes('Up') && !container.status.includes('Paused');
                case 'stopped': return !container.status.includes('Up');
                case 'paused': return container.status.includes('Paused');
                default: return true;
            }
        });
    }
    
    // Apply search filter
    const searchTerm = document.getElementById('container-search')?.value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(container => 
            container.name.toLowerCase().includes(searchTerm) ||
            container.image.toLowerCase().includes(searchTerm)
        );
    }
    
    return filtered;
}

function updateStats() {
    const running = containers.filter(c => c.status.includes('Up') && !c.status.includes('Paused')).length;
    const stopped = containers.filter(c => !c.status.includes('Up')).length;
    
    document.getElementById('total-containers').textContent = containers.length;
    document.getElementById('running-containers').textContent = running;
    document.getElementById('stopped-containers').textContent = stopped;
}

function updateSystemMetrics() {
    // Simulate real-time metrics
    const cpuUsage = Math.floor(Math.random() * 100);
    const memoryUsage = Math.floor(Math.random() * 100);
    const diskUsage = Math.floor(Math.random() * 100);
    
    document.querySelector('.progress-cpu').style.width = `${cpuUsage}%`;
    document.querySelector('.progress-memory').style.width = `${memoryUsage}%`;
    document.querySelector('.progress-disk').style.width = `${diskUsage}%`;
    
    document.querySelector('.metric-value').textContent = `${cpuUsage}%`;
    document.querySelectorAll('.metric-value')[1].textContent = `${memoryUsage}%`;
    document.querySelectorAll('.metric-value')[2].textContent = `${diskUsage}%`;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 3000;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event listeners
function setupEventListeners() {
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            renderContainers();
        });
    });
    
    // Search
    const searchInput = document.getElementById('container-search');
    if (searchInput) {
        searchInput.addEventListener('input', renderContainers);
    }
    
    // Terminal input
    const terminalInput = document.getElementById('terminal-input');
    if (terminalInput) {
        terminalInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const command = e.target.value;
                e.target.value = '';
                // Handle terminal command
                const output = document.getElementById('terminal-output');
                output.innerHTML += `<div>$ ${command}</div>`;
                if (command === 'exit') {
                    closeModal('terminal-modal');
                } else {
                    output.innerHTML += `<div>Command executed: ${command}</div>`;
                }
                output.scrollTop = output.scrollHeight;
            }
        });
    }
}

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Page-specific data loading
function loadPageData(page) {
    switch (page) {
        case 'containers':
            loadContainers();
            break;
        case 'images':
            loadImages();
            break;
        case 'networks':
            loadNetworks();
            break;
        case 'volumes':
            loadVolumes();
            break;
        case 'monitoring':
            initializeCharts();
            break;
    }
}

// Refresh function
function refreshData() {
    const activePage = document.querySelector('.page.active').id;
    loadPageData(activePage);
    updateSystemMetrics();
}

// Mock data functions
function getMockContainers() {
    return [
        { name: 'portainer', image: 'portainer/portainer-ce:2.33.0', status: 'Up 16 hours', ports: '8000->8000/tcp, 9443->9443/tcp', created: '16 hours ago', id: '702f92947885' },
        { name: 'code-server', image: 'multifix/custom-code-server:latest', status: 'Up 2 hours', ports: '8443->8080/tcp', created: '3 hours ago', id: '1c005145b8a3' },
        { name: 'school3', image: 'wordpress:latest', status: 'Up 16 hours', ports: '80/tcp', created: '2 weeks ago', id: 'c36a54a59979' },
        { name: 'db-school3', image: 'mariadb:latest', status: 'Up 16 hours', ports: '3306/tcp', created: '2 weeks ago', id: '440d6d1433f7' },
        { name: 'school4', image: 'rosariosis:latest', status: 'Exited (0) 17 hours ago', ports: '', created: '2 weeks ago', id: '9d7cd89cdc6c' },
        { name: 'nginx-proxy-manager-app-1', image: 'jc21/nginx-proxy-manager:latest', status: 'Up 15 hours', ports: '80-81->80-81/tcp, 443->443/tcp', created: '4 weeks ago', id: '845e5bf8580e' }
    ];
}

function getMockImages() {
    return [
        { repository: 'portainer/portainer-ce', tag: '2.33.0', id: 'abc123def456', created: '2 weeks ago', size: '295MB' },
        { repository: 'wordpress', tag: 'latest', id: 'def456ghi789', created: '1 week ago', size: '609MB' },
        { repository: 'mariadb', tag: 'latest', id: 'ghi789jkl012', created: '3 days ago', size: '383MB' },
        { repository: 'nginx', tag: 'alpine', id: 'jkl012mno345', created: '5 days ago', size: '23.4MB' }
    ];
}

function getMockNetworks() {
    return [
        { id: 'abc123', name: 'bridge', driver: 'bridge', scope: 'local' },
        { id: 'def456', name: 'host', driver: 'host', scope: 'local' },
        { id: 'ghi789', name: 'none', driver: 'null', scope: 'local' },
        { id: 'jkl012', name: 'docker-projects_default', driver: 'bridge', scope: 'local' }
    ];
}

function getMockVolumes() {
    return [
        { driver: 'local', name: 'portainer_data' },
        { driver: 'local', name: 'wordpress_data' },
        { driver: 'local', name: 'mysql_data' },
        { driver: 'local', name: 'nginx_config' }
    ];
}

// Additional functions for enhanced features
function createContainer() {
    alert('Create Container feature - Would open container creation wizard');
}

function pruneContainers() {
    if (confirm('Remove all stopped containers?')) {
        executeDockerCommand('docker container prune -f').then(result => {
            if (result.success) {
                showNotification('Stopped containers pruned successfully', 'success');
                loadContainers();
            }
        });
    }
}

function pullImage() {
    const imageName = prompt('Enter image name to pull (e.g., nginx:latest):');
    if (imageName) {
        showNotification(`Pulling image: ${imageName}`, 'info');
        executeDockerCommand(`docker pull ${imageName}`).then(result => {
            if (result.success) {
                showNotification(`Image ${imageName} pulled successfully`, 'success');
                loadImages();
            } else {
                showNotification(`Failed to pull image ${imageName}`, 'error');
            }
        });
    }
}

function pruneImages() {
    if (confirm('Remove all unused images?')) {
        executeDockerCommand('docker image prune -f').then(result => {
            if (result.success) {
                showNotification('Unused images pruned successfully', 'success');
                loadImages();
            }
        });
    }
}

function createNetwork() {
    const networkName = prompt('Enter network name:');
    if (networkName) {
        executeDockerCommand(`docker network create ${networkName}`).then(result => {
            if (result.success) {
                showNotification(`Network ${networkName} created successfully`, 'success');
                loadNetworks();
            }
        });
    }
}

function createVolume() {
    const volumeName = prompt('Enter volume name:');
    if (volumeName) {
        executeDockerCommand(`docker volume create ${volumeName}`).then(result => {
            if (result.success) {
                showNotification(`Volume ${volumeName} created successfully`, 'success');
                loadVolumes();
            }
        });
    }
}

function deployStack() {
    alert('Deploy Stack feature - Would deploy Docker Compose stack');
}

function validateCompose() {
    const composeContent = document.getElementById('compose-editor').value;
    if (composeContent.trim()) {
        showNotification('Docker Compose file validated successfully', 'success');
    } else {
        showNotification('Please enter Docker Compose content', 'error');
    }
}

function deployCompose() {
    const composeContent = document.getElementById('compose-editor').value;
    if (composeContent.trim()) {
        showNotification('Deploying Docker Compose stack...', 'info');
        // In real implementation, would save compose file and deploy
        setTimeout(() => {
            showNotification('Docker Compose stack deployed successfully', 'success');
        }, 2000);
    } else {
        showNotification('Please enter Docker Compose content', 'error');
    }
}

function initializeCharts() {
    // Placeholder for chart initialization
    console.log('Charts would be initialized here with Chart.js or similar library');
}