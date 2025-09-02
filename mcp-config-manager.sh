#!/bin/bash

# MCP Configuration Manager
# Handles config updates and Amazon Q extension restarts

CONFIG_FILE="/home/coder/project/.amazonq/config.json"
GLOBAL_CONFIG="/home/coder/.config/code-server/amazon-q-config.json"

update_config() {
    echo "🔧 Updating MCP configuration..."
    
    # Backup existing config
    if [ -f "$CONFIG_FILE" ]; then
        cp "$CONFIG_FILE" "${CONFIG_FILE}.backup.$(date +%s)"
        echo "✅ Configuration backed up"
    fi
    
    # Copy to global config location
    if [ -f "$CONFIG_FILE" ]; then
        cp "$CONFIG_FILE" "$GLOBAL_CONFIG"
        echo "✅ Global configuration updated"
    fi
    
    # Validate JSON
    if python3 -m json.tool "$CONFIG_FILE" >/dev/null 2>&1; then
        echo "✅ Configuration is valid JSON"
    else
        echo "❌ Configuration has invalid JSON syntax"
        exit 1
    fi
}

restart_extension() {
    echo "🔄 Attempting to restart Amazon Q processes..."
    
    # Find and restart Amazon Q processes
    pkill -f "Amazon Q Helper" || echo "No Amazon Q Helper process found"
    
    echo "✅ Process restart attempted"
    echo "ℹ️  Please manually restart the Amazon Q extension in VS Code if needed"
}

show_status() {
    echo "📊 MCP Configuration Status:"
    echo "  Config file: $CONFIG_FILE"
    echo "  Global config: $GLOBAL_CONFIG"
    
    if [ -f "$CONFIG_FILE" ]; then
        echo "  ✅ Local config exists"
        echo "  Servers configured: $(jq -r '.mcpServers | keys | length' "$CONFIG_FILE")"
        echo "  Server names: $(jq -r '.mcpServers | keys | join(", ")' "$CONFIG_FILE")"
    else
        echo "  ❌ Local config missing"
    fi
    
    if [ -f "$GLOBAL_CONFIG" ]; then
        echo "  ✅ Global config exists"
    else
        echo "  ❌ Global config missing"
    fi
}

case "$1" in
    "update")
        update_config
        ;;
    "restart")
        restart_extension
        ;;
    "status")
        show_status
        ;;
    "full")
        update_config
        restart_extension
        ;;
    *)
        echo "Usage: $0 {update|restart|status|full}"
        echo "  update  - Update configuration files"
        echo "  restart - Restart Amazon Q processes"
        echo "  status  - Show configuration status"
        echo "  full    - Update config and restart processes"
        exit 1
        ;;
esac