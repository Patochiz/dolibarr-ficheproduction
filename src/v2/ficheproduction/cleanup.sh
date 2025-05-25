#!/bin/bash

# 🧹 Script de nettoyage automatique des fichiers obsolètes
# Version: 1.0 - Date: 2025-05-25

echo "🧹 Nettoyage du module FicheProduction v2.0"
echo "============================================="

# Répertoire de base du module
BASE_DIR="$(dirname "$0")"
cd "$BASE_DIR"

echo "📍 Répertoire de travail: $(pwd)"

# Fichiers à supprimer
FILES_TO_DELETE=(
    # Fichiers de debug
    "debug-modules.html"
    "debug-test.php"
    
    # Versions de test du fichier principal
    "ficheproduction-final.php"
    "ficheproduction-new.php"
    "ficheproduction-test-fixed.php"
    "ficheproduction-test.php"
    
    # Fichiers de test HTML
    "test-modules-fixed.html"
    "test-modules-standalone.html"
    "test-modules.html"
    
    # Scripts de test
    "test-ficheproduction.sh"
    "validate-fix.sh"
    
    # READMEs temporaires
    "README_CORRECTION_MODULES.md"
    "README_TESTS.md"
    "RESTRUCTURATION_README.md"
    "RÉSUMÉ_CORRECTION.md"
)

# JavaScript files à nettoyer (versions en double)
JS_FILES_TO_DELETE=(
    "js/ficheproduction-colis-fixed.js"
    "js/ficheproduction-core-fixed.js"
    "js/ficheproduction-dragdrop-fixed.js"
    "js/ficheproduction-inventory-fixed.js"
    "js/ficheproduction-ui-fixed.js"
    # Garder seulement les versions sans "-fixed" et ficheproduction.js principal
)

echo "🗑️  Suppression des fichiers obsolètes..."

# Compter les fichiers supprimés
DELETED_COUNT=0

# Supprimer les fichiers principaux
for file in "${FILES_TO_DELETE[@]}"; do
    if [ -f "$file" ]; then
        echo "   ❌ Suppression: $file"
        rm "$file"
        ((DELETED_COUNT++))
    else
        echo "   ℹ️  Déjà absent: $file"
    fi
done

# Supprimer les fichiers JavaScript en double
for file in "${JS_FILES_TO_DELETE[@]}"; do
    if [ -f "$file" ]; then
        echo "   ❌ Suppression: $file"
        rm "$file"
        ((DELETED_COUNT++))
    else
        echo "   ℹ️  Déjà absent: $file"
    fi
done

echo ""
echo "✅ Nettoyage terminé!"
echo "📊 Résumé:"
echo "   - Fichiers supprimés: $DELETED_COUNT"
echo ""

# Vérifier les fichiers restants importants
echo "📋 Fichiers principaux conservés:"
if [ -f "ficheproduction.php" ]; then
    echo "   ✅ ficheproduction.php (fichier principal)"
else
    echo "   ❌ ATTENTION: ficheproduction.php manquant!"
fi

if [ -f "js/ficheproduction.js" ]; then
    echo "   ✅ js/ficheproduction.js (JavaScript principal)"
else
    echo "   ❌ ATTENTION: js/ficheproduction.js manquant!"
fi

if [ -f "css/ficheproduction.css" ]; then
    echo "   ✅ css/ficheproduction.css (CSS principal)"
else
    echo "   ❌ ATTENTION: css/ficheproduction.css manquant!"
fi

# Vérifier les classes
echo ""
echo "📁 Classes PHP:"
CLASSES_DIR="class"
if [ -d "$CLASSES_DIR" ]; then
    CLASS_COUNT=$(find "$CLASSES_DIR" -name "*.class.php" | wc -l)
    echo "   ✅ $CLASS_COUNT classes trouvées dans le répertoire class/"
    find "$CLASSES_DIR" -name "*.class.php" | sed 's/^/      /'
else
    echo "   ❌ ATTENTION: Répertoire class/ manquant!"
fi

echo ""
echo "🎯 Instructions post-nettoyage:"
echo "1. Vérifiez que l'interface fonctionne correctement"
echo "2. Testez l'affichage des produits (problème résolu avec inclusion JS)"
echo "3. Testez la sauvegarde (améliorée avec validation renforcée)"
echo "4. Supprimez ce script de nettoyage une fois satisfait"

echo ""
echo "📚 Documentation:"
echo "   - Voir CORRECTIONS_APPLIQUEES.md pour le détail des corrections"
echo "   - Voir README.md pour la documentation générale"

echo ""
echo "🏁 Nettoyage terminé avec succès!"
