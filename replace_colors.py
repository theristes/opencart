import os
import re
import math

MAIN_COLOR = 'var(--maincolor)'
REPO_PATH = './'
FILE_EXTENSIONS = ('.css', '.scss', '.less', '.html', '.tpl', '.twig', '.js')

# Color patterns to match
COLOR_PATTERNS = [
    r'#[0-9a-fA-F]{3,8}',  # Hex colors (3, 4, 6, or 8 digits)
    r'rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)',  # RGB colors
    r'rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)',  # RGBA colors
]

COLOR_REGEX = re.compile('|'.join(COLOR_PATTERNS), re.IGNORECASE)

# Your main color in RGB (used for similarity comparison)
MAIN_COLOR_RGB = (97, 93, 189)  # Purple-blue color

def hex_to_rgb(hex_color):
    """Convert hex color to RGB tuple."""
    hex_color = hex_color.lstrip('#')
    if len(hex_color) == 3:
        hex_color = ''.join([c*2 for c in hex_color])
    if len(hex_color) == 6:
        return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))
    elif len(hex_color) == 8:  # Handle alpha channel
        return tuple(int(hex_color[i:i+2], 16) for i in (0, 2, 4))
    return None

def parse_rgb(rgb_str):
    """Parse RGB/RGBA string to RGB tuple."""
    parts = [p.strip() for p in rgb_str[rgb_str.find('(')+1:rgb_str.rfind(')')].split(',')]
    return tuple(int(float(parts[i])) for i in range(3))

def rgb_to_hsl(r, g, b):
    """Convert RGB to HSL (Hue, Saturation, Lightness)."""
    r, g, b = [x/255.0 for x in (r, g, b)]
    max_val = max(r, g, b)
    min_val = min(r, g, b)
    h, s, l = 0, 0, (max_val + min_val) / 2
    
    if max_val != min_val:
        d = max_val - min_val
        s = d / (2 - max_val - min_val) if l > 0.5 else d / (max_val + min_val)
        if max_val == r:
            h = (g - b) / d + (6 if g < b else 0)
        elif max_val == g:
            h = (b - r) / d + 2
        else:
            h = (r - g) / d + 4
        h *= 60
    
    return h, s, l

def should_replace_color(rgb):
    """Determine if a color should be replaced based on similarity to main color."""
    # Convert both colors to HSL
    h1, s1, l1 = rgb_to_hsl(*rgb)
    h2, s2, l2 = rgb_to_hsl(*MAIN_COLOR_RGB)
    
    # Check if color is in blue-purple range (190-290 hue)
    is_blue_purple = 190 <= h1 <= 290 if h1 is not None else False
    
    # Check if color has sufficient saturation (>20%)
    has_sufficient_saturation = s1 > 0.2
    
    # Check if color isn't too light/dark (20% < lightness < 80%)
    has_good_lightness = 0.2 < l1 < 0.8
    
    # Check if color is similar to main color (within 30° hue difference)
    is_similar = abs(h1 - h2) < 30 if h1 is not None and h2 is not None else False
    
    return (is_blue_purple and has_sufficient_saturation and 
            has_good_lightness and is_similar)

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
                        
                        if rgb and len(rgb) >= 3 and should_replace_color(rgb[:3]):
                            return MAIN_COLOR
                    except Exception as e:
                        print(f"Color conversion error for {color}: {e}")
                    return color

                content = COLOR_REGEX.sub(replace_color, content)

                if content != original_content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Updated {file_path}")

            except Exception as e:
                print(f"❌ Error processing {file_path}: {e}")

print("\n🎨 Script complete! Only similar blue-purple colors replaced with maincolor.\n")
print("Suggested CSS variable definition:")
print(":root {")
print("  --maincolor: rgb(97, 93, 189);")
print("}")