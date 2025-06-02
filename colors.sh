#!/bin/bash

# Default colors in case no parameters are passed
DEFAULT_MAIN_COLOR="#615dbd"
DEFAULT_SECONDARY_COLOR="#b5b3e6"
DEFAULT_WHITE_COLOR="#FFFFFF"
DEFAULT_ALERT_COLOR="#EA4A3A"
DEFAULT_DARK_COLOR="#000000"
DEFAULT_GRAY_COLOR="#DFDFDF"

# Check if 6 args are provided, if not use defaults
if [ "$#" -eq 0 ]; then
  echo "No parameters passed, using default colors."
  NEW_MAIN_COLOR=$DEFAULT_MAIN_COLOR
  NEW_SECONDARY_COLOR=$DEFAULT_SECONDARY_COLOR
  NEW_WHITE_COLOR=$DEFAULT_WHITE_COLOR
  NEW_ALERT_COLOR=$DEFAULT_ALERT_COLOR
  NEW_DARK_COLOR=$DEFAULT_DARK_COLOR
  NEW_GRAY_COLOR=$DEFAULT_GRAY_COLOR
elif [ "$#" -eq 1 ]; then
  echo "One parameter passed, applying it to all colors."
  NEW_MAIN_COLOR=$1
  NEW_SECONDARY_COLOR=$1
  NEW_WHITE_COLOR=$1
  NEW_ALERT_COLOR=$1
  NEW_DARK_COLOR=$1
  NEW_GRAY_COLOR=$1
else
  # Read keys from colors.env if available
  declare -A COLORS
  if [ -f colors.env ]; then
    while IFS='=' read -r key value; do
      COLORS[$key]=$value
    done < colors.env
  else
    # Set colors.env if not found
    echo "colors.env not found, creating a new one with default values."
    cat <<EOF > colors.env
MAIN_COLOR=$DEFAULT_MAIN_COLOR
SECONDARY_COLOR=$DEFAULT_SECONDARY_COLOR
WHITE_COLOR=$DEFAULT_WHITE_COLOR
ALERT_COLOR=$DEFAULT_ALERT_COLOR
DARK_COLOR=$DEFAULT_DARK_COLOR
GRAY_COLOR=$DEFAULT_GRAY_COLOR
EOF
    echo "✅ colors.env created with default values."
  fi

  # Assign args
  NEW_MAIN_COLOR=${1:-${COLORS[MAIN_COLOR]}}
  NEW_SECONDARY_COLOR=${2:-${COLORS[SECONDARY_COLOR]}}
  NEW_WHITE_COLOR=${3:-${COLORS[WHITE_COLOR]}}
  NEW_ALERT_COLOR=${4:-${COLORS[ALERT_COLOR]}}
  NEW_DARK_COLOR=${5:-${COLORS[DARK_COLOR]}}
  NEW_GRAY_COLOR=${6:-${COLORS[GRAY_COLOR]}}
fi

# Debug output to ensure colors are being set correctly
echo "Using the following colors:"
echo "MAIN_COLOR: $NEW_MAIN_COLOR"
echo "SECONDARY_COLOR: $NEW_SECONDARY_COLOR"
echo "WHITE_COLOR: $NEW_WHITE_COLOR"
echo "ALERT_COLOR: $NEW_ALERT_COLOR"
echo "DARK_COLOR: $NEW_DARK_COLOR"
echo "GRAY_COLOR: $NEW_GRAY_COLOR"

# Ensure that none of the color variables are empty
if [ -z "$NEW_MAIN_COLOR" ] || [ -z "$NEW_SECONDARY_COLOR" ] || [ -z "$NEW_WHITE_COLOR" ] || [ -z "$NEW_ALERT_COLOR" ] || [ -z "$NEW_DARK_COLOR" ] || [ -z "$NEW_GRAY_COLOR" ]; then
  echo "Error: One or more color values are empty. Please check your parameters or colors.env."
  exit 1
fi

# Function to convert RGB to Hex
rgb_to_hex() {
    local r=$1 g=$2 b=$3
    printf "#%02X%02X%02X\n" "$r" "$g" "$b"
}

# Function to handle the RGB values and replace them
replace_rgb_colors() {
    local file="$1"
    local regex="rgb\([[:space:]]*\([0-9]\{1,3\}\),[[:space:]]*\([0-9]\{1,3\}\),[[:space:]]*\([0-9]\{1,3\}\)[[:space:]]*\)"
    local match color_hex
    while IFS= read -r line; do
        if [[ $line =~ $regex ]]; then
            r="${BASH_REMATCH[1]}"
            g="${BASH_REMATCH[2]}"
            b="${BASH_REMATCH[3]}"
            color_hex=$(rgb_to_hex "$r" "$g" "$b")
            # Debug: print the sed command before running it
            echo "Replacing RGB ($color_hex) with $NEW_MAIN_COLOR"
            sed -i "s/$color_hex/$NEW_MAIN_COLOR/g" "$file"
            sed -i "s/$color_hex/$NEW_SECONDARY_COLOR/g" "$file"
            sed -i "s/$color_hex/$NEW_WHITE_COLOR/g" "$file"
            sed -i "s/$color_hex/$NEW_ALERT_COLOR/g" "$file"
            sed -i "s/$color_hex/$NEW_DARK_COLOR/g" "$file"
            sed -i "s/$color_hex/$NEW_GRAY_COLOR/g" "$file"
        fi
    done < "$file"
}

# Find and replace all colors in files (skip binary files and those that don't match extensions)
find . -type f \( -iname "*.css" -o -iname "*.scss" -o -iname "*.less" -o -iname "*.js" -o -iname "*.html" -o -iname "*.tpl" -o -iname "*.twig" \) ! -name 'colors.env' ! -path './colors' -exec grep -Iq . {} \; -print | while read file; do
    # Print out the actual sed commands for debugging
    echo "Running sed replacements for file: $file"
    echo "sed -i -e \"s/${COLORS[MAIN_COLOR]}/$NEW_MAIN_COLOR/g\""
    echo "sed -i -e \"s/${COLORS[SECONDARY_COLOR]}/$NEW_SECONDARY_COLOR/g\""
    echo "sed -i -e \"s/${COLORS[WHITE_COLOR]}/$NEW_WHITE_COLOR/g\""
    echo "sed -i -e \"s/${COLORS[ALERT_COLOR]}/$NEW_ALERT_COLOR/g\""
    echo "sed -i -e \"s/${COLORS[DARK_COLOR]}/$NEW_DARK_COLOR/g\""
    echo "sed -i -e \"s/${COLORS[GRAY_COLOR]}/$NEW_GRAY_COLOR/g\""
    # Replace hex colors first
    sed -i \
      -e "s/${COLORS[MAIN_COLOR]}/$NEW_MAIN_COLOR/g" \
      -e "s/${COLORS[SECONDARY_COLOR]}/$NEW_SECONDARY_COLOR/g" \
      -e "s/${COLORS[WHITE_COLOR]}/$NEW_WHITE_COLOR/g" \
      -e "s/${COLORS[ALERT_COLOR]}/$NEW_ALERT_COLOR/g" \
      -e "s/${COLORS[DARK_COLOR]}/$NEW_DARK_COLOR/g" \
      -e "s/${COLORS[GRAY_COLOR]}/$NEW_GRAY_COLOR/g" \
      "$file"

    # Handle RGB color replacements
    replace_rgb_colors "$file"

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
