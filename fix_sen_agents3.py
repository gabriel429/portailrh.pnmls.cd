# -*- coding: utf-8 -*-

filepath = 'app/Http/Controllers/Api/ExecutiveDashboardController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Find the second usort (SEN/SEL one)
# Match 1 is at position 46891
usort_pos = 46891

# Find the end of the foreach loop before usort
# We need to find: "            }" that is the closing of foreach
# Then code to insert before usort

# Let's look at what's just before usort
preview_start = max(0, usort_pos - 300)
print("Context before usort:")
print(content[preview_start:usort_pos])

print("\n\n===== Position after usort =====")
print(content[usort_pos:usort_pos+100])
