import os
import re
from math import sqrt

# === CONFIGURATION ===
MAIN_COLOR = 'var(--maincolor)'  # Target for blues/purples
BLACK_COLOR = '#000'
WHITE_COLOR = '#fff'

MAIN_COLOR_RGB = (97, 93, 189)  # Your main color (as RGB)
BLACK_RGB = (0, 0, 0)
WHITE_RGB = (255, 255, 255)

# Files to scan
REPO_PATH = './'
FILE_EXTENSIONS = ('.css', '.scss', '.less', '.html', '.tpl', '.twig', '.js')

# Regex patterns for colors
COLOR_PATTERNS = [
    r'#[0-9a-fA-F]{3,8}',
    r'rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)',
    r'rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[0-9.]+\s*\)',
    r'hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)',
    r'hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*[0-9.]+\s*\)',
]

COLOR_REGEX = re.compile('|'.join(COLOR_PATTERNS), re.IGNORECASE)

# Convert hex to RGB
def hex_to_rgb(hex_color):
    hex_color = hex_color.lstrip('#')
    if len(hex_color) == 3:
        hex_color = ''.join([c*2 for c in hex_color])
    return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))

# Convert rgb()/rgba() to RGB
def parse_rgb(rgb_str):
    nums = [int(float(n.strip())) for n in rgb_str[rgb_str.find('(')+1:rgb_str.find(')')].split(',')[:3]]
    return tuple(nums)

# Calculate color distance
def color_distance(c1, c2):
    return sqrt(sum((a - b) ** 2 for a, b in zip(c1, c2)))

# Decide replacement
def map_color_to_target(rgb):
    if color_distance(rgb, MAIN_COLOR_RGB) < 120:  # Adjust this threshold as needed
        return MAIN_COLOR
    elif color_distance(rgb, BLACK_RGB) < 80:
        return BLACK_COLOR
    elif color_distance(rgb, WHITE_RGB) < 80:
        return WHITE_COLOR
    elif abs(rgb[0] - rgb[1]) < 20 and abs(rgb[1] - rgb[2]) < 20:
        # Grays fallback: close R, G, B values
        return BLACK_COLOR if sum(rgb) < 384 else WHITE_COLOR
    else:
        return None  # No change

# Replace colors in files
for root, _, files in os.walk(REPO_PATH):
    for file in files:
        if file.endswith(FILE_EXTENSIONS):
            file_path = os.path.join(root, file)
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                original_content = content

                def replace_color(match):
                    color = match.group(0).lower()
                    rgb = None
                    if color.startswith('#'):
                        try:
                            rgb = hex_to_rgb(color)
                        except:
                            return color
                    elif color.startswith('rgb'):
                        try:
                            rgb = parse_rgb(color)
                        except:
                            return color
                    if rgb:
                        new_color = map_color_to_target(rgb)
                        return new_color if new_color else color
                    return color

                content = COLOR_REGEX.sub(replace_color, content)

                if content != original_content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Updated {file_path}")

            except Exception as e:
                print(f"❌ Error in {file_path}: {e}")

print("\n🎨 All colors simplified! Add this to your CSS:\n")
print(":root {\n  --maincolor: rgb(97, 93, 189);\n}")
