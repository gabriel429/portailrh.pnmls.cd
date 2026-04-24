# -*- coding: utf-8 -*-
import re

filepath = 'app/Http/Controllers/Api/ExecutiveDashboardController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Search for usort pattern more flexibly
# Pattern: usort($items, fn...
pattern = r'usort\(\$items,\s*fn\s*\(\$a,\s*\$b\)'

matches = list(re.finditer(pattern, content))
print(f"Found {len(matches)} usort matches")

for idx, m in enumerate(matches):
    start = max(0, m.start() - 30)
    end = min(len(content), m.end() + 80)
    snippet = content[start:end]
    print(f"\nMatch {idx}:")
    print(f"  Position: {m.start()}")
    print(f"  Context: {repr(snippet[:150])}")
