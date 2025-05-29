import os
import re
from math import sqrt, atan2, degrees

REPO_PATH = './'
MAIN_COLOR = 'var(--maincolor)'
FILE_EXTENSIONS = ('.css', '.scss', '.less', '.html', '.tpl', '.twig', '.js')

COLOR_PATTERNS = [
    r'#[0-9a-fA-F]{3,8}',
    r'rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)',
]

COLOR_REGEX = re.compile('|'.join(COLOR_PATTERNS), re.IGNORECASE)

def hex_to_rgb(hex_color):
    hex_color = hex_color.lstrip('#')
    if len(hex_color) == 3:
        hex_color = ''.join([c*2 for c in hex_color])
    return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))

def parse_rgb(rgb_str):
    nums = [int(float(n.strip())) for n in rgb_str[rgb_str.find('(')+1:rgb_str.find(')')].split(',')[:3]]
    return tuple(nums)

def rgb_to_hue(r, g, b):
    r /= 255
    g /= 255
    b /= 255
    mx = max(r, g, b)
    mn = min(r, g, b)
    delta = mx - mn

    if delta == 0:
        return 0
    elif mx == r:
        hue = (60 * ((g - b) / delta) + 360) % 360
    elif mx == g:
        hue = (60 * ((b - r) / delta) + 120) % 360
    elif mx == b:
        hue = (60 * ((r - g) / delta) + 240) % 360
    else:
        hue = 0
    return hue

def is_blue_purple(rgb):
    hue = rgb_to_hue(*rgb)
    return 190 <= hue <= 290

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
                    try:
                        if color.startswith('#'):
                            rgb = hex_to_rgb(color)
                        elif color.startswith('rgb'):
                            rgb = parse_rgb(color)
                        else:
                            return color
                        if is_blue_purple(rgb):
                            return MAIN_COLOR
                    except:
                        return color
                    return color

                content = COLOR_REGEX.sub(replace_color, content)

                if content != original_content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Updated {file_path}")

            except Exception as e:
                print(f"❌ Error in {file_path}: {e}")

print("\n🎨 Blue, Indigo, Purple tones replaced with your main color!\n")
print(":root {\n  --maincolor: rgb(97, 93, 189);\n}")
