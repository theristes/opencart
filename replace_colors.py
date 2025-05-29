import os
import re

# Set your main color
MAIN_COLOR = 'var(--maincolor)'  # or 'rgb(97, 93, 189)' if you want direct replacement

# Define what "blue" and "purple" colors look like
blue_purple_keywords = ['blue', 'purple', 'violet', '#0000ff', '#0000f', '#800080', '#8a2be2', '#4b0082']
blue_purple_regex = r'\b(' + '|'.join(blue_purple_keywords) + r')\b'

# Also match color formats (hex/rgb/etc.) that are close to blue/purple
color_patterns = [
    r'#[0-9a-fA-F]{3,8}',
    r'rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)',
    r'rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[0-9.]+\s*\)',
    r'hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)',
    r'hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*[0-9.]+\s*\)',
]

color_regex = re.compile('|'.join(color_patterns), re.IGNORECASE)
blue_purple_regex = re.compile(blue_purple_regex, re.IGNORECASE)

# Path to your OpenCart repo
REPO_PATH = './'

# Supported file types
FILE_EXTENSIONS = ('.css', '.scss', '.less', '.html', '.tpl', '.twig', '.js')

# Function to check if a color is "blue/purple-like" by RGB
def is_blue_purple_like(color):
    color = color.lower()
    # Named colors
    if blue_purple_regex.search(color):
        return True
    # RGB/hex checks (very basic)
    hex_match = re.match(r'#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})', color)
    if hex_match:
        hex_value = hex_match.group(0)
        if len(hex_value) == 4:
            hex_value = '#' + ''.join([c*2 for c in hex_value[1:]])
        r = int(hex_value[1:3], 16)
        g = int(hex_value[3:5], 16)
        b = int(hex_value[5:7], 16)
        if b > r and b > g:
            return True
        if r > 100 and b > 100 and r - b < 50:
            return True
    rgb_match = re.match(r'rgb(a)?\((.*?)\)', color)
    if rgb_match:
        values = [int(v.strip()) for v in rgb_match.group(2).split(',')[:3]]
        r, g, b = values
        if b > r and b > g:
            return True
        if r > 100 and b > 100 and r - b < 50:
            return True
    return False

# Go through files and replace colors
for root, _, files in os.walk(REPO_PATH):
    for file in files:
        if file.endswith(FILE_EXTENSIONS):
            file_path = os.path.join(root, file)
            try:
                with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                original_content = content

                # Replace colors
                def replace_func(match):
                    color = match.group(0)
                    if is_blue_purple_like(color):
                        return MAIN_COLOR
                    return color

                content = color_regex.sub(replace_func, content)
                content = blue_purple_regex.sub(MAIN_COLOR, content)

                if content != original_content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Updated {file_path}")
            except Exception as e:
                print(f"❌ Error reading {file_path}: {e}")

print("\n🎨 All done! All blues/purples replaced with your MAIN COLOR 🎨")
