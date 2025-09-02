# MCP Framework Validation Guide
## Principal AI Systems Architect Implementation

### 🔒 Security Hardening Implemented

**✅ Filesystem Jailing**
- Removed path from args array
- Implemented `MCP_SERVER_FILESYSTEM_ROOT` environment variable
- Strictly confined to `/home/coder/project` directory

**✅ Shell Command Restriction**
- Removed insecure shell-mcp-server
- Replaced with memory server for enhanced capabilities
- No direct shell access to prevent security vulnerabilities

**✅ Environment Sanitization**
- All servers use isolated environment variables
- No host environment variable passthrough
- Secure token handling for Portainer integration

### 🏗️ Server Architecture

| Server | Purpose | Security Level | Resource Limit |
|--------|---------|----------------|----------------|
| **filesystem** | File operations | High (jailed) | Default |
| **memory** | Knowledge graph | Medium | 512MB |
| **sqlite** | Database ops | High (isolated) | Default |
| **portainer** | Docker management | High (token-based) | 256MB |

### 🧪 Validation Test Queries

#### 1. Filesystem Server Tests
```
Please use the filesystem server to generate a tree view of the project's main directory.
```

```
Please use the filesystem server to read the contents of the MCP_VALIDATION_GUIDE.md file.
```

```
Please use the filesystem server to create a new file called 'test-validation.txt' with the content 'MCP Framework Working'.
```

#### 2. Memory Server Tests
```
Please use the memory server to store the fact that 'This project uses an advanced MCP framework for development'.
```

```
Please use the memory server to recall any stored information about this project.
```

#### 3. SQLite Database Tests
```
Please use the SQLite server to check if a 'users' table exists in the database.
```

```
Please use the SQLite server to create a simple 'projects' table with columns: id, name, status.
```

```
Please use the SQLite server to insert a test record into the projects table.
```

#### 4. Portainer Server Tests (After Token Setup)
```
Please use the Portainer server to list all Docker containers.
```

```
Please use the Portainer server to get the logs from the most recent container.
```

### 🔧 Configuration Management

#### Update Configuration
```bash
./mcp-config-manager.sh update
```

#### Restart Amazon Q Extension
```bash
./mcp-config-manager.sh restart
```

#### Full Update and Restart
```bash
./mcp-config-manager.sh full
```

#### Check Status
```bash
./mcp-config-manager.sh status
```

### 🐳 Portainer Integration Setup

1. **Get Portainer API Token:**
   ```bash
   # Access Portainer UI at http://localhost:9000
   # Go to User Settings > Access tokens
   # Create new token and copy it
   ```

2. **Set Environment Variable:**
   ```bash
   export PORTAINER_TOKEN="your_api_token_here"
   ```

3. **Update Configuration:**
   ```bash
   # Edit .amazonq/config.json and replace <PORTAINER_API_TOKEN>
   ./mcp-config-manager.sh full
   ```

### 📊 Performance Monitoring

#### Memory Usage Limits
- **Memory Server**: 512MB (`--max-old-space-size=512`)
- **Portainer Server**: 256MB (`--max-old-space-size=256`)

#### Health Check Command
```bash
./mcp-setup.sh
```

### 🚨 Troubleshooting

#### Common Issues

1. **Server Connection Failed**
   - Check if server binary exists
   - Verify environment variables
   - Run health check script

2. **Permission Denied**
   - Ensure scripts are executable: `chmod +x *.sh`
   - Check file permissions in project directory

3. **JSON Configuration Error**
   - Validate JSON syntax: `python3 -m json.tool .amazonq/config.json`
   - Check for trailing commas or syntax errors

4. **Portainer Authentication**
   - Verify PORTAINER_TOKEN is set
   - Check Portainer URL accessibility
   - Ensure token has proper permissions

### 🎯 Success Criteria

**✅ All servers respond to initialization**
**✅ Filesystem operations work within jail**
**✅ Database operations execute successfully**
**✅ Memory server stores and retrieves data**
**✅ Configuration is valid and loaded**

### 📈 Next Steps for Production

1. **Database Configuration**
   - Replace SQLite with PostgreSQL for production
   - Set up connection pooling
   - Implement backup strategies

2. **Monitoring Integration**
   - Add health check endpoints
   - Implement logging aggregation
   - Set up alerting for server failures

3. **Security Enhancements**
   - Implement rate limiting
   - Add request validation
   - Set up audit logging

4. **Scalability Improvements**
   - Container orchestration
   - Load balancing for multiple instances
   - Resource auto-scaling