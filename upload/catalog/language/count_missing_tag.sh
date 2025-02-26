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
count_missing_tags() {
  local file1="$1"
  local file2="$2"

  # Extract translation keys from both files
  keys1=$(extract_keys "$file1")
  keys2=$(extract_keys "$file2")

  # Count how many tags from dir1 are missing in dir2
  missing_count=$(comm -23 <(echo "$keys1") <(echo "$keys2") | wc -l)

  # If there are missing tags, print the file and count
  if [ "$missing_count" -gt 0 ]; then
    echo "$missing_count missing tags in $file2"
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
      count_missing_tags "$file1" "$file2"
    else
      echo "File $relative_path not found in $subdir2"
    fi
  done
}

# Call the recursive comparison function
compare_directories "$dir1" "$dir2"
