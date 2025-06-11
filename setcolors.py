import os
import sys

ALLOWED_EXTENSIONS = {".css", ".scss", ".less", ".js", ".html", ".tpl", ".twig"}

def load_colors(env_file):
    colors = {}
    with open(env_file, "r") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            if "=" in line:
                key, val = line.split("=", 1)
                colors[key.strip()] = val.strip()
    return colors

def replace_colors_in_file(file_path, replacements):
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    original_content = content

    for old_color, new_color in replacements.items():
        content = content.replace(old_color, new_color)

    if content != original_content:
        with open(file_path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Updated: {file_path}")

def walk_and_replace(root_dir, replacements):
    for subdir, _, files in os.walk(root_dir):
        for file in files:
            if os.path.splitext(file)[1] in ALLOWED_EXTENSIONS:
                file_path = os.path.join(subdir, file)
                replace_colors_in_file(file_path, replacements)

def main():
    if len(sys.argv) != 4:
        print("Usage: python replace_colors.py colors.env new-colors.env /path/to/search")
        return

    old_env = sys.argv[1]
    new_env = sys.argv[2]
    target_dir = sys.argv[3]

    old_colors = load_colors(old_env)
    new_colors = load_colors(new_env)

    # Build replacement dict: {old_color_val: new_color_val}
    replacements = {}
    for key, old_val in old_colors.items():
        if key in new_colors:
            new_val = new_colors[key]
            if old_val.lower() != new_val.lower():
                replacements[old_val] = new_val

    if not replacements:
        print("No changes detected between the files.")
        return

    walk_and_replace(target_dir, replacements)

if __name__ == "__main__":
    main()
