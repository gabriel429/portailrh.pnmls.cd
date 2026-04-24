# -*- coding: utf-8 -*-
import re

with open('app/Http/Controllers/Api/ExecutiveDashboardController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# The strings we want to replace (the corrupted ones)
# We need to find them by hex pattern since terminal encoding is messy
replacements = [
    # Replace all variations of the corrupted organe names
    ('Secr\xc7\xb0tariat Ex\xc7\xb0cutif National', "Agent::ORGANES[0]"),
    ('Secr\xc7\xb0tariat Ex\xc7\xb0cutif Provincial', "Agent::ORGANES[1]"),
    ('Secr\xc7\xb0tariat Ex\xc7\xb0cutif Local', "Agent::ORGANES[2]"),
]

# First, let's see what's actually in the file
for i, char in enumerate(content):
    if ord(char) > 127:
        # Show non-ASCII chars with context
        start = max(0, i-5)
        end = min(len(content), i+5)
        context = content[start:end]
        print(f"Position {i}: char=U+{ord(char):04X} context='{context}'")

print("\n--- Checking for replacement matches ---")
for old, new in replacements:
    count = content.count(old)
    if count > 0:
        print(f"Found {count}x '{old[:20]}...' -> '{new}'")
    else:
        print(f"Not found: '{old[:20]}...'")
