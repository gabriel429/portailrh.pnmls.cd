# -*- coding: utf-8 -*-

filepath = 'app/Http/Controllers/Api/TacheController.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remplacer $isSENStaff par $isAssistant dans show()
# La ligne contient: || $isSENStaff
content = content.replace(
    '|| $isSENStaff) {',
    '|| $isAssistant) {'
)

# 2. Vérifier qu'il n'y a qu'un remplacement (dans show seulement)
# destroy et updateStatut peuvent encore utiliser $isSENStaff, c'est normal

with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
    f.write(content)

print("Modification effectuée avec succès !")
