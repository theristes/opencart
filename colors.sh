#!/bin/bash

# Check if 6 args are provided
if [ "$#" -ne 6 ]; then
  echo "Usage: ./colors <MAIN_COLOR> <SECONDARY_COLOR> <WHITE_COLOR> <ALERT_COLOR> <DARK_COLOR> <GRAY_COLOR>"
  exit 1
fi

# Read keys from colors.env
declare -A COLORS
while IFS='=' read -r key value; do
  COLORS[$key]=$value
done < colors.env

# Assign args
NEW_MAIN_COLOR=$1
NEW_SECONDARY_COLOR=$2
NEW_WHITE_COLOR=$3
NEW_ALERT_COLOR=$4
NEW_DARK_COLOR=$5
NEW_GRAY_COLOR=$6

# Find and replace all colors in files (skip binary files)
find . -type f ! -name 'colors.env' ! -path './colors' -exec grep -Iq . {} \; -print | while read file; do
  sed -i \
    -e "s/${COLORS[MAIN_COLOR]}/$NEW_MAIN_COLOR/g" \
    -e "s/${COLORS[SECONDARY_COLOR]}/$NEW_SECONDARY_COLOR/g" \
    -e "s/${COLORS[WHITE_COLOR]}/$NEW_WHITE_COLOR/g" \
    -e "s/${COLORS[ALERT_COLOR]}/$NEW_ALERT_COLOR/g" \
    -e "s/${COLORS[DARK_COLOR]}/$NEW_DARK_COLOR/g" \
    -e "s/${COLORS[GRAY_COLOR]}/$NEW_GRAY_COLOR/g" \
    "$file"
done

# Update colors.env with the new colors
cat <<EOF > colors.env
MAIN_COLOR=${NEW_MAIN_COLOR}
SECONDARY_COLOR=${NEW_SECONDARY_COLOR}
WHITE_COLOR=${NEW_WHITE_COLOR}
ALERT_COLOR=${NEW_ALERT_COLOR}
DARK_COLOR=${NEW_DARK_COLOR}
GRAY_COLOR=${NEW_GRAY_COLOR}
EOF

echo "✅ Colors updated!"
