 # -*- coding: utf-8 -*-
import re, sys

filepath = 'app/Http/Controllers/Api/ExecutiveDashboardController.php'

with open(filepath, 'rb') as f:
    raw = f.read()

# Decode as UTF-8
try:
    content = raw.decode('utf-8')
except UnicodeDecodeError:
    # Try CP1252
    content = raw.decode('cp1252')

# Replace corrupted strings with Agent::ORGANES references
# The corrupted strings contain invalid utf-8 bytes interpreted as cp1252
# They appear as variation selector chars or replacement chars

replacements_done = 0

# Pattern 1: The first $organes array (in index method) - all three lines
# We need to find lines like: 'sen' => '...', 
# We'll use the Agent::ORGANES constant instead

# Count occurrences before
print(f"File size: {len(content)} chars")

# Let's find the corrupted strings by their unique character patterns
# They contain chars like \u0178 (Ÿ), \u0182 (Ƃ), etc.
import unicodedata

# Find all lines with odd high-unicode chars in the organe context
lines = content.split('\n')
for i, line in enumerate(lines):
    if 'organe' in line.lower() and ('=>' in line or '=' in line):
        has_odd = False
        for ch in line:
            if ord(ch) > 127 and not (0x00C0 <= ord(ch) <= 0x00FF):  # beyond Latin-1 Supplement
                has_odd = True
        if has_odd:
            print(f"L{i+1}: has odd unicode: {line.strip()[:80]}")

print("\n--- Now performing replacements ---")

# Instead of fixing encoding, replace the ENTIRE line with correct Agent::ORGANES usage
# Strategy: find corrupted organ definitions and replace them

# The 3 places:
# 1. index() method: $organes = [...]
# 2. organeDetail(): $organes = [...]
# 3. provinceDetail(): $organes = [...]

# Let's find them by context
for i, line in enumerate(lines):
    stripped = line.strip()
    
    # Find the corrupted organe lines
    if "'sen' => '" in stripped and ("'sep' => '" in stripped or i+1 < len(lines) and "'sep' => '" in lines[i+1]):
        # This is a $organes assignment containing sen, sep, sel
        if any(ord(ch) > 0x017F for ch in stripped):  # has corrupted chars
            print(f"Found corrupted organes at L{i+1}")
            # Replace the whole block with clean version using Agent::ORGANES
            idx = i
            while idx < len(lines) and not lines[idx].strip().startswith('];'):
                idx += 1
            
            indent = re.match(r'^(\s*)', line).group(1)
            
            clean_block = (
                f"{indent}$organNoms = Agent::ORGANES;\n"
                f"{indent}$organes = [\n"
                f"{indent}    'sen' => $organNoms[0],\n"
                f"{indent}    'sep' => $organNoms[1],\n"
                f"{indent}    'sel' => $organNoms[2],\n"
                f"{indent}];"
            )
            
            lines[i] = clean_block
            # Remove the extra lines
            for j in range(i+1, idx+1):
                lines[j] = None  # mark for removal
            replacements_done += 1
            print(f"  Replaced block from L{i+1} to L{idx+1}")

# Also find $organeMap assignments with corrupted strings
for i, line in enumerate(lines):
    if line is None:
        continue
    stripped = line.strip()
    if 'organeMap' in stripped and '[' in stripped:
        # Check if next lines have corrupted organe names
        has_corrupted = False
        j = i
        while j < len(lines) and j < i + 5:
            if lines[j] and any(ord(ch) > 0x017F for ch in lines[j]) and ("National" in lines[j] or "Provincial" in lines[j] or "Local" in lines[j]):
                has_corrupted = True
                break
            j += 1
        
        if has_corrupted:
            print(f"Found corrupted organeMap at L{i+1}")
            indent = re.match(r'^(\s*)', line).group(1)
            lines[i] = f"{indent}$organeMap = ["
            # Fix the individual lines
            j = i + 1
            while j < len(lines) and lines[j] and not '];' in lines[j]:
                if lines[j] and ("National" in lines[j] or "Provincial" in lines[j] or "Local" in lines[j]):
                    # Extract the key part
                    m = re.match(r'(\s*)(\S+\s*=>\s*)(.*)', lines[j])
                    if m:
                        key_part = m.group(2)
                        if 'National' in lines[j]:
                            lines[j] = f"{m.group(1)}{key_part}$organNoms[0],"
                        elif 'Provincial' in lines[j]:
                            lines[j] = f"{m.group(1)}{key_part}$organNoms[1],"
                        elif 'Local' in lines[j]:
                            lines[j] = f"{m.group(1)}{key_part}$organNoms[2],"
                        replacements_done += 1
                j += 1
            # Replace the closing
            # Add $organNoms before the block
            lines[i] = f"{indent}$organNoms = Agent::ORGANES;\n{lines[i]}"

# Remove None lines
lines = [l for l in lines if l is not None]

content = '\n'.join(lines)

with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
    f.write(content)

print(f"\nDone! {replacements_done} replacements made.")
