import os

# Constants
ALLOWED_EXTENSIONS = {".css", ".scss", ".less", ".js", ".html", ".tpl", ".twig"}
OLD_ENV_FILE = "colors.env"
NEW_ENV_FILE = "new-colors.env"
TARGET_DIR = "."

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
    print("Reading color mappings...")

    old_colors = load_colors(OLD_ENV_FILE)
    new_colors = load_colors(NEW_ENV_FILE)

    replacements = {}
    for key, old_val in old_colors.items():
        if key in new_colors:
            new_val = new_colors[key]
            if old_val.lower() != new_val.lower():
                replacements[old_val] = new_val

    if not replacements:
        print("No changes to apply.")
        return

    print("Applying color replacements...")
    walk_and_replace(TARGET_DIR, replacements)
    print("Done.")

if __name__ == "__main__":
    main()
