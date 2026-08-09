import os
import re

def fix_includes(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        print(f"Failed to read {filepath}: {e}")
        return
        
    pattern = r"(include|require)(_once)?\s*[\(]?\s*['\"]([^'\"]+)['\"]\s*[\)]?\s*;"
    
    def replacer(match):
        func = match.group(1)
        once = match.group(2) or ""
        path = match.group(3)
        if path.startswith('/') or path.startswith('http'):
            return match.group(0)
        return f"{func}{once} __DIR__ . '/{path}';"

    new_content = re.sub(pattern, replacer, content)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Fixed {filepath}")

for root, dirs, files in os.walk('.'):
    # skip encode folder as it might be external lib
    if 'encode' in root or 'temp_decompile' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            fix_includes(os.path.join(root, file))
