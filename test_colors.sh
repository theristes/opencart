#!/bin/bash

# Test script for colors.sh

# Create a temporary test environment
TEST_DIR="./test_env"
mkdir -p "$TEST_DIR"
cd "$TEST_DIR" || exit 1

# Create a mock colors.env file
cat <<EOF > colors.env
MAIN_COLOR=#615dbd
SECONDARY_COLOR=#b5b3e6
WHITE_COLOR=#FFFFFF
ALERT_COLOR=#EA4A3A
DARK_COLOR=#000000
GRAY_COLOR=#DFDFDF
EOF

# Create mock files to test color replacement
echo "This is a file with MAIN_COLOR: #615dbd and SECONDARY_COLOR: #b5b3e6" > file1.txt
echo "Another file with ALERT_COLOR: #EA4A3A and DARK_COLOR: #000000" > file2.txt

# Copy the colors.sh script into the test environment
cp ../colors.sh .

# Run the colors.sh script with new colors
./colors.sh "#123456" "#654321" "#CCCCCC" "#FF0000" "#111111" "#AAAAAA"

# Validate the changes
if grep -q "#123456" file1.txt && grep -q "#654321" file1.txt; then
  echo "✅ MAIN_COLOR and SECONDARY_COLOR updated successfully in file1.txt"
else
  echo "❌ MAIN_COLOR and SECONDARY_COLOR not updated in file1.txt"
  exit 1
fi

if grep -q "#FF0000" file2.txt && grep -q "#111111" file2.txt; then
  echo "✅ ALERT_COLOR and DARK_COLOR updated successfully in file2.txt"
else
  echo "❌ ALERT_COLOR and DARK_COLOR not updated in file2.txt"
  exit 1
fi

# Validate colors.env updates
if grep -q "MAIN_COLOR=#123456" colors.env && grep -q "SECONDARY_COLOR=#654321" colors.env; then
  echo "✅ colors.env updated successfully"
else
  echo "❌ colors.env not updated"
  exit 1
fi

# Cleanup
cd ..
rm -rf "$TEST_DIR"

echo "✅ All tests passed!"
