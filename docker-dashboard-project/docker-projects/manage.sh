#!/bin/bash

# Docker Project Management Script

case "$1" in
    "list")
        echo "=== All Containers ==="
        docker ps -a --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
        ;;
    "running")
        echo "=== Running Containers ==="
        docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
        ;;
    "start")
        if [ -z "$2" ]; then
            echo "Usage: ./manage.sh start <container_name>"
            exit 1
        fi
        docker start "$2"
        ;;
    "stop")
        if [ -z "$2" ]; then
            echo "Usage: ./manage.sh stop <container_name>"
            exit 1
        fi
        docker stop "$2"
        ;;
    "logs")
        if [ -z "$2" ]; then
            echo "Usage: ./manage.sh logs <container_name>"
            exit 1
        fi
        docker logs -f "$2"
        ;;
    "shell")
        if [ -z "$2" ]; then
            echo "Usage: ./manage.sh shell <container_name>"
            exit 1
        fi
        docker exec -it "$2" /bin/bash
        ;;
    *)
        echo "Usage: $0 {list|running|start|stop|logs|shell} [container_name]"
        echo ""
        echo "Commands:"
        echo "  list     - Show all containers"
        echo "  running  - Show running containers"
        echo "  start    - Start a container"
        echo "  stop     - Stop a container"
        echo "  logs     - View container logs"
        echo "  shell    - Access container shell"
        ;;
esac