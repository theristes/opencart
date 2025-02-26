#!/bin/bash

# Check if exactly two arguments are provided
if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <dir1> <dir2>"
  exit 1
fi

dir1="$1"
dir2="$2"

# Function to extract translation keys from files using sed
extract_keys() {
  local file="$1"
  # Using sed to extract keys between $_[' and ']'
  sed -n "s/.*\[\(.*\)\].*/\1/p" "$file" | sort
}

# Compare files between two directories
compare_files() {
  local file1="$1"
  local file2="$2"

  # Extract translation keys from both files
  keys1=$(extract_keys "$file1")
  keys2=$(extract_keys "$file2")

  # Check for tags in dir1 but not in dir2
  missing_in_dir2=$(comm -23 <(echo "$keys1") <(echo "$keys2"))
  if [ -n "$missing_in_dir2" ]; then
    echo "Missing tags in $file2 (compared to $file1):"
    echo "$missing_in_dir2"
  fi
}

# Recursive function to loop through files in subdirectories
compare_directories() {
  local subdir1="$1"
  local subdir2="$2"

  # Loop through all PHP files in the first directory
  find "$subdir1" -type f -name "*.php" | while read file1; do
    # Get the relative path of the file within the directory
    relative_path="${file1#$subdir1/}"
    file2="$subdir2/$relative_path"

    if [ -f "$file2" ]; then
      compare_files "$file1" "$file2"
    else
      # If file doesn't exist in the second directory, no output is shown for it.
      continue
    fi
  done
}

# Call the recursive comparison function
compare_directories "$dir1" "$dir2"