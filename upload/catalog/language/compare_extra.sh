#!/bin/bash

# Script to find extra files and folders in dir2 compared to dir1

usage() {
  echo "Usage: $0 <directory1> <directory2>"
  echo "Compares directory2 to directory1 and lists files/folders present only in directory2."
  echo "This script helps identify 'extra' items in directory2 that are not in directory1."
  echo "Example: $0 dir1 dir2"
  exit 1
}

if [ "$#" -ne 2 ]; then
  usage
fi

dir1="$1"
dir2="$2"

if [ ! -d "$dir1" ]; then
  echo "Error: Directory '$dir1' ('$dir1') does not exist or is not a directory."
  usage
fi

if [ ! -d "$dir2" ]; then
  echo "Error: Directory '$dir2' ('$dir2') does not exist or is not a directory."
  usage
fi

echo "--- Comparing directories: ---"
echo "--- Finding extra items in '$dir2' (compared to '$dir1') ---"

echo ""
echo "--- Extra Folders in '$dir2': ---"
find "$dir2" -mindepth 1 -type d | while IFS= read -r item2; do
  relative_item=$(echo "$item2" | sed "s|^$dir2/||")
  item1="$dir1/$relative_item"
  if ! [ -e "$item1" ]; then
    echo "$relative_item"
  fi
done

echo ""
echo "--- Extra Files in '$dir2': ---"
find "$dir2" -mindepth 1 -type f | while IFS= read -r item2; do
  relative_item=$(echo "$item2" | sed "s|^$dir2/||")
  item1="$dir1/$relative_item"
  if ! [ -e "$item1" ]; then
    echo "$relative_item"
  fi
done

echo ""
echo "--- Comparison complete ---"
echo "--- (The list above shows folders and files present in '$dir2' but NOT in '$dir1') ---"