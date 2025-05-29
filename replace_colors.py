import os
import re

# Define your 6 colors
COLOR_MAP = {
    'DARK_ONE': '#615dbd',
    'LIGHT_ONE': '#FFFFFF',
    'GREEN_ONE': '#615dbd',
    'RED_ONE': '#DC3545',
    'BLACK_ONE': '#000000',
    'DARK_ONE_LIGHTER': '#7a76d6'
}

def is_gray(rgb, threshold=10):
    r, g, b = rgb
    return abs(r - g) < threshold and abs(r - b) < threshold and abs(g - b) < threshold


def hex_to_rgb(hexcode):
    hexcode = hexcode.lstrip('#')
    return tuple(int(hexcode[i:i+2], 16) for i in (0, 2, 4))

def rgb_distance(rgb1, rgb2):
    return sum((a - b) ** 2 for a, b in zip(rgb1, rgb2)) ** 0.5

def find_closest_color(hexcode):
    input_rgb = hex_to_rgb(hexcode)
    if is_gray(input_rgb):
        return COLOR_MAP['BLACK_ONE']

    min_diff = float('inf')
    closest = None
    for name, color_hex in COLOR_MAP.items():
        target_rgb = hex_to_rgb(color_hex)
        diff = rgb_distance(input_rgb, target_rgb)
        if diff < min_diff:
            min_diff = diff
            closest = color_hex
    return closest

def replace_colors_in_file(filepath):
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()

    # Regex for hex colors and rgb() colors
    hex_pattern = r'#[0-9A-Fa-f]{6}'
    rgb_pattern = r'rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)'
    changes = 0

    def hex_replacer(match):
        original = match.group(0)
        new_color = find_closest_color(original)
        nonlocal changes
        if original.lower() != new_color.lower():
            changes += 1
        return new_color

    def rgb_replacer(match):
        r, g, b = map(int, match.groups())
        hex_color = '#%02X%02X%02X' % (r, g, b)
        new_color = find_closest_color(hex_color)
        nonlocal changes
        if hex_color.lower() != new_color.lower():
            changes += 1
        return new_color

    content = re.sub(hex_pattern, hex_replacer, content)
    content = re.sub(rgb_pattern, rgb_replacer, content)

    if changes > 0:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'{filepath}: {changes} colors replaced')

def walk_and_replace(directory):
    for root, _, files in os.walk(directory):
        for file in files:
            if file.endswith(('.css', '.scss', '.less', '.js', '.html', '.tpl', '.twig')):
                replace_colors_in_file(os.path.join(root, file))

walk_and_replace('.')