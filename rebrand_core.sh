#!/bin/bash
# ScholaPro Rebranding Engine - Core Script
# Operation Phoenix - Automated Brand Transformation

set -e

BACKUP_DIR="/home/coder/project/scholapro_backup_$(date +%Y%m%d_%H%M%S)"
SOURCE_DIR="/home/coder/project/scholapro"
LOG_FILE="/home/coder/project/rebrand_core.log"

echo "🚀 SCHOLAPRO REBRANDING ENGINE INITIATED" | tee $LOG_FILE
echo "Timestamp: $(date)" | tee -a $LOG_FILE

# Create backup
echo "📦 Creating backup..." | tee -a $LOG_FILE
cp -r "$SOURCE_DIR" "$BACKUP_DIR"
echo "✅ Backup created: $BACKUP_DIR" | tee -a $LOG_FILE

# Core brand replacements
echo "🔄 Executing core brand transformations..." | tee -a $LOG_FILE

cd "$SOURCE_DIR"

# Primary brand replacements
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.md" -o -name "*.txt" \) -exec sed -i 's/RosarioSIS/ScholaPro/g' {} + 2>/dev/null || true
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.md" -o -name "*.txt" \) -exec sed -i 's/rosariosis/scholapro/g' {} + 2>/dev/null || true
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.md" -o -name "*.txt" \) -exec sed -i 's/Rosario SIS/ScholaPro/g' {} + 2>/dev/null || true
find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" -o -name "*.md" -o -name "*.txt" \) -exec sed -i 's/Student Information System/Educational Management Platform/g' {} + 2>/dev/null || true

# Count changes
CHANGED_FILES=$(find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" \) -exec grep -l "ScholaPro" {} + 2>/dev/null | wc -l)

echo "✅ Brand transformation complete" | tee -a $LOG_FILE
echo "📊 Files modified: $CHANGED_FILES" | tee -a $LOG_FILE
echo "🎯 Operation Phoenix: Core rebranding successful" | tee -a $LOG_FILE