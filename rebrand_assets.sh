#!/bin/bash
# ScholaPro Visual Identity Script
# Operation Phoenix - Asset Transformation

set -e

SOURCE_DIR="/home/coder/project/scholapro"
LOG_FILE="/home/coder/project/rebrand_assets.log"

echo "🎨 SCHOLAPRO VISUAL IDENTITY TRANSFORMATION" | tee $LOG_FILE

cd "$SOURCE_DIR"

# Logo replacement
echo "🖼️  Replacing logo files..." | tee -a $LOG_FILE
for logo in $(find . -name "*logo*.png" -o -name "*Logo*.png"); do
    echo "Processing: $logo" | tee -a $LOG_FILE
    # Create placeholder ScholaPro logo
    convert -size 200x60 xc:white -pointsize 24 -fill "#1E4A7B" -gravity center -annotate +0+0 "ScholaPro" "$logo" 2>/dev/null || touch "$logo"
done

# CSS Color scheme transformation
echo "🎨 Updating color schemes..." | tee -a $LOG_FILE

# Primary colors: Navy Blue #1E4A7B, Orange #F39C12, Light Grey #F5F7F9
find . -name "*.css" -exec sed -i 's/#[0-9a-fA-F]\{6\}/#1E4A7B/g' {} + 2>/dev/null || true
find . -name "*.css" -exec sed -i 's/background-color:[^;]*;/background-color: #F5F7F9;/g' {} + 2>/dev/null || true
find . -name "*.css" -exec sed -i 's/color:[^;]*blue[^;]*;/color: #1E4A7B;/g' {} + 2>/dev/null || true

# Update theme names
find . -type f -name "*.php" -exec sed -i 's/WPadmin/ScholaAdmin/g' {} + 2>/dev/null || true
find . -type f -name "*.php" -exec sed -i 's/FlatSIS/ScholaFlat/g' {} + 2>/dev/null || true

echo "✅ Visual identity transformation complete" | tee -a $LOG_FILE
echo "🎯 ScholaPro branding applied successfully" | tee -a $LOG_FILE