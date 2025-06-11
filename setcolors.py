import os
import re

# Constants
ALLOWED_EXTENSIONS = {".css", ".scss", ".less", ".js", ".html", ".tpl", ".twig"}
OLD_ENV_FILE = "colors.env"
NEW_ENV_FILE = "new-colors.env"
TARGET_DIR = "."
DRY_RUN = False  # Set to True to preview changes only


def hex_to_rgb_tuple(hex_color):
    hex_color = hex_color.strip().lstrip("#")
    if len(hex_color) != 6:
        return None
    try:
        r = int(hex_color[0:2], 16)
        g = int(hex_color[2:4], 16)
        b = int(hex_color[4:6], 16)
        return (r, g, b)
    except ValueError:
        return None


def normalize_rgb(r, g, b):
    return f"rgb({r}, {g}, {b})"


def normalize_rgba(r, g, b, a=1):
    return f"rgba({r}, {g}, {b}, {a})"


def generate_rgb_regex(r, g, b):
    return re.compile(
        rf"rgb\s*\(\s*{r}\s*,\s*{g}\s*,\s*{b}\s*\)", re.IGNORECASE
    )


def generate_rgba_regex(r, g, b):
    return re.compile(
        rf"rgba\s*\(\s*{r}\s*,\s*{g}\s*,\s*{b}\s*,\s*1(?:\.0*)?\s*\)", re.IGNORECASE
    )


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

    for hex_old, data in replacements.items():
        hex_new = data["hex_new"]
        rgb_old = data["rgb_old"]
        rgb_new = data["rgb_new"]
        rgb_new_tuple = data["rgb_new_tuple"]
        bare_rgb_old = data["bare_rgb_old"]
        bare_rgb_new = data["bare_rgb_new"]
        regex_rgb_old = data["regex_rgb_old"]
        regex_rgba_old = data["regex_rgba_old"]

        # Replace HEX
        content = content.replace(hex_old, hex_new)

        # Replace normalized rgb/rgba
        content = content.replace(rgb_old, rgb_new)
        content = content.replace(rgb_old.replace(" ", ""), rgb_new)

        # Replace bare RGB like: 97, 93, 189
        content = content.replace(bare_rgb_old, bare_rgb_new)

        # Regex flexible match
        content = regex_rgb_old.sub(rgb_new, content)
        content = regex_rgba_old.sub(normalize_rgba(*rgb_new_tuple), content)

    if content != original_content:
        if DRY_RUN:
            print(f"[DRY-RUN] Would update: {file_path}")
        else:
            with open(file_path, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Updated: {file_path}")


def walk_and_replace(root_dir, replacements):
    for subdir, _, files in os.walk(root_dir):
        for file in files:
            if os.path.splitext(file)[1] in ALLOWED_EXTENSIONS:
                file_path = os.path.join(subdir, file)
                replace_colors_in_file(file_path, replacements)


def normalize_hex(hexcode):
    hexcode = hexcode.strip().upper()
    return f"#{hexcode.lstrip('#')}" if not hexcode.startswith("#") else hexcode


def main():
    print("Reading color mappings...")

    old_colors = load_colors(OLD_ENV_FILE)
    new_colors = load_colors(NEW_ENV_FILE)

    replacements = {}
    for key, old_hex in old_colors.items():
        if key in new_colors:
            new_hex = new_colors[key]

            hex_old = normalize_hex(old_hex)
            hex_new = normalize_hex(new_hex)

            if hex_old.lower() != hex_new.lower():
                rgb_old_tuple = hex_to_rgb_tuple(hex_old)
                rgb_new_tuple = hex_to_rgb_tuple(hex_new)

                if rgb_old_tuple and rgb_new_tuple:
                    rgb_old = normalize_rgb(*rgb_old_tuple)
                    rgb_new = normalize_rgb(*rgb_new_tuple)

                    bare_rgb_old = f"{rgb_old_tuple[0]}, {rgb_old_tuple[1]}, {rgb_old_tuple[2]}"
                    bare_rgb_new = f"{rgb_new_tuple[0]}, {rgb_new_tuple[1]}, {rgb_new_tuple[2]}"

                    regex_rgb = generate_rgb_regex(*rgb_old_tuple)
                    regex_rgba = generate_rgba_regex(*rgb_old_tuple)

                    replacements[hex_old] = {
                        "hex_new": hex_new,
                        "rgb_old": rgb_old,
                        "rgb_new": rgb_new,
                        "rgb_new_tuple": rgb_new_tuple,
                        "bare_rgb_old": bare_rgb_old,
                        "bare_rgb_new": bare_rgb_new,
                        "regex_rgb_old": regex_rgb,
                        "regex_rgba_old": regex_rgba,
                    }

    if not replacements:
        print("No changes to apply.")
        return

    print("Replacing HEX and RGB(A) colors...")
    walk_and_replace(TARGET_DIR, replacements)
    print("Done.")


if __name__ == "__main__":
    main()