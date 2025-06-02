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

# Function to escape special characters in sed
escape_sed() {
  echo "$1" | sed 's/[&/\]/\\&/g'
}

replace_colors_in_file() {
  local file="$1"
  echo "Running replacements for file: $file"

  # Only replace if the color is different from the default
  if [ "$NEW_MAIN_COLOR" != "$DEFAULT_MAIN_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_MAIN_COLOR")#$NEW_MAIN_COLOR#g" "$file"
  fi
  if [ "$NEW_SECONDARY_COLOR" != "$DEFAULT_SECONDARY_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_SECONDARY_COLOR")#$NEW_SECONDARY_COLOR#g" "$file"
  fi
  if [ "$NEW_WHITE_COLOR" != "$DEFAULT_WHITE_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_WHITE_COLOR")#$NEW_WHITE_COLOR#g" "$file"
  fi
  if [ "$NEW_ALERT_COLOR" != "$DEFAULT_ALERT_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_ALERT_COLOR")#$NEW_ALERT_COLOR#g" "$file"
  fi
  if [ "$NEW_DARK_COLOR" != "$DEFAULT_DARK_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_DARK_COLOR")#$NEW_DARK_COLOR#g" "$file"
  fi
  if [ "$NEW_GRAY_COLOR" != "$DEFAULT_GRAY_COLOR" ]; then
    sed -i -e "s#$(escape_sed "$DEFAULT_GRAY_COLOR")#$NEW_GRAY_COLOR#g" "$file"
  fi
}

# Find and replace all colors in files (skip binary files and those that don't match extensions)
find . -type f \( -iname "*.css" -o -iname "*.scss" -o -iname "*.less" -o -iname "*.js" -o -iname "*.html" -o -iname "*.tpl" -o -iname "*.twig" \) ! -name 'colors.env' ! -path './colors' -exec grep -Iq . {} \; -print | while read file; do
  replace_colors_in_file "$file"
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
