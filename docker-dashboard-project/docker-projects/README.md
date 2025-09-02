# Docker Projects Management

This workspace manages all Docker containers and projects from code-server.

## Current Projects
- **School3**: WordPress + MariaDB (Port: 80)
- **School5**: WordPress + MariaDB (ARM64)
- **Gibbon2**: Educational platform (Port: 8082)
- **Gibbon**: Educational platform (Port: 8090)
- **U-Recline**: Custom web app
- **N8N**: Workflow automation (Port: 8083)
- **School4**: RosarioSIS (Currently stopped)

## Quick Commands
```bash
# List all containers
docker ps -a

# Start/stop containers
docker start <container_name>
docker stop <container_name>

# View logs
docker logs <container_name>

# Access container shell
docker exec -it <container_name> /bin/bash
```

## Portainer Access
- Web UI: http://localhost:8000
- HTTPS: https://localhost:9443