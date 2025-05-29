import os
import re

# Path to your OpenCart project (set to current dir by default)
REPO_PATH = './'

# Supported file types
FILE_EXTENSIONS = ('.css', '.scss', '.less', '.html', '.tpl', '.twig', '.js')

# Color regex patterns
patterns = [
    r'#[0-9a-fA-F]{3,8}',                                  # Hex
    r'rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)',               # RGB
    r'rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[0-9.]+\s*\)',# RGBA
    r'hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)',           # HSL
    r'hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*[0-9.]+\s*\)', # HSLA
]

# Named colors (short list; add more if needed)
named_colors = [
    'white', 'black', 'red', 'green', 'blue', 'yellow', 'pink', 'gray', 'grey', 'purple', 'orange', 'brown', 'cyan', 'magenta'
]
named_colors_pattern = r'\b(' + '|'.join(named_colors) + r')\b'
patterns.append(named_colors_pattern)

# Compile regex
color_regex = re.compile('|'.join(patterns), re.IGNORECASE)

# Find colors
found_colors = set()

for root, _, files in os.walk(REPO_PATH):
    for file in files:
        if file.endswith(FILE_EXTENSIONS):
            try:
                with open(os.path.join(root, file), 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    matches = color_regex.findall(content)
                    for match in matches:
                        if isinstance(match, tuple):
                            match = next(filter(None, match))  # Get first non-empty match
                        found_colors.add(match.lower())
            except Exception as e:
                print(f"Error reading {file}: {e}")

# Output
found_colors = sorted(found_colors)
print(f"\n🎨 Found {len(found_colors)} unique colors:\n")
for color in found_colors:
    print(color)

# Save to file
with open('colors_found.txt', 'w') as f:
    for color in found_colors:
        f.write(color + '\n')

print("\n✅ Colors saved to colors_found.txt")
