#!/bin/bash
# Script de Test et Validation - FicheProduction v2.0 avec Sauvegarde
# Utilisation: ./test-ficheproduction.sh [URL_DOLIBARR] [ORDER_ID]

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOLIBARR_URL=${1:-"http://localhost/dolibarr"}
ORDER_ID=${2:-"1"}
TEST_LOG="test-results-$(date +%Y%m%d-%H%M%S).log"

echo "🚀 Test de Validation - FicheProduction v2.0 avec Sauvegarde"
echo "============================================================="
echo "URL Dolibarr: $DOLIBARR_URL"
echo "Order ID: $ORDER_ID"
echo "Log: $TEST_LOG"
echo ""

# Fonction pour logger les résultats
log_result() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$TEST_LOG"
    echo -e "$1"
}

# Test 1: Vérification des fichiers requis
echo "📁 Test 1: Vérification des fichiers requis"
echo "--------------------------------------------"

check_file() {
    if [ -f "$1" ]; then
        log_result "${GREEN}✅ $1 présent${NC}"
        return 0
    else
        log_result "${RED}❌ $1 manquant${NC}"
        return 1
    fi
}

FILES_OK=true

# Fichiers principaux
check_file "ficheproduction.php" || FILES_OK=false
check_file "js/ficheproduction.js" || FILES_OK=false
check_file "css/ficheproduction.css" || FILES_OK=false
check_file "css/ficheproduction-save.css" || FILES_OK=false

# Classes PHP
check_file "class/ficheproductionmanager.class.php" || FILES_OK=false
check_file "class/ficheproductionsession.class.php" || FILES_OK=false
check_file "class/ficheproductioncolis.class.php" || FILES_OK=false
check_file "class/ficheproductioncolisline.class.php" || FILES_OK=false

# Documentation
check_file "docs/SAUVEGARDE.md" || FILES_OK=false
check_file "docs/MIGRATION.md" || FILES_OK=false
check_file "README-SAUVEGARDE.md" || FILES_OK=false

if [ "$FILES_OK" = true ]; then
    log_result "${GREEN}✅ Tous les fichiers requis sont présents${NC}"
else
    log_result "${RED}❌ Des fichiers requis sont manquants${NC}"
    exit 1
fi

echo ""

# Test 2: Validation du code JavaScript
echo "🔧 Test 2: Validation du code JavaScript"
echo "-----------------------------------------"

if command -v node >/dev/null 2>&1; then
    # Test syntaxe JavaScript
    if node -c js/ficheproduction.js 2>/dev/null; then
        log_result "${GREEN}✅ Syntaxe JavaScript valide${NC}"
    else
        log_result "${RED}❌ Erreur de syntaxe JavaScript${NC}"
    fi
    
    # Vérifier les fonctions exportées
    if grep -q "window.initializeFicheProduction" js/ficheproduction.js; then
        log_result "${GREEN}✅ Fonction initializeFicheProduction exportée${NC}"
    else
        log_result "${RED}❌ Fonction initializeFicheProduction manquante${NC}"
    fi
    
    if grep -q "window.saveColisage" js/ficheproduction.js; then
        log_result "${GREEN}✅ Fonction saveColisage exportée${NC}"
    else
        log_result "${RED}❌ Fonction saveColisage manquante${NC}"
    fi
    
else
    log_result "${YELLOW}⚠️  Node.js non disponible - test JavaScript ignoré${NC}"
fi

echo ""

# Test 3: Validation du code PHP
echo "🐘 Test 3: Validation du code PHP"
echo "----------------------------------"

if command -v php >/dev/null 2>&1; then
    # Test syntaxe PHP
    if php -l ficheproduction.php >/dev/null 2>&1; then
        log_result "${GREEN}✅ Syntaxe PHP valide${NC}"
    else
        log_result "${RED}❌ Erreur de syntaxe PHP${NC}"
    fi
    
    # Vérifier les actions AJAX
    if grep -q "ficheproduction_save_colis" ficheproduction.php; then
        log_result "${GREEN}✅ Action AJAX save_colis présente${NC}"
    else
        log_result "${RED}❌ Action AJAX save_colis manquante${NC}"
    fi
    
    if grep -q "ficheproduction_load_saved_data" ficheproduction.php; then
        log_result "${GREEN}✅ Action AJAX load_saved_data présente${NC}"
    else
        log_result "${RED}❌ Action AJAX load_saved_data manquante${NC}"
    fi
    
else
    log_result "${YELLOW}⚠️  PHP CLI non disponible - test PHP ignoré${NC}"
fi

echo ""

# Test 4: Test d'accessibilité de l'interface
echo "🌐 Test 4: Test d'accessibilité de l'interface"
echo "----------------------------------------------"

if command -v curl >/dev/null 2>&1; then
    # Test accès à la page principale
    URL="$DOLIBARR_URL/custom/ficheproduction/ficheproduction.php?id=$ORDER_ID"
    
    if curl -s -o /dev/null -w "%{http_code}" "$URL" | grep -q "200"; then
        log_result "${GREEN}✅ Page accessible (HTTP 200)${NC}"
        
        # Vérifier le contenu de la page
        PAGE_CONTENT=$(curl -s "$URL")
        
        if echo "$PAGE_CONTENT" | grep -q "Gestionnaire de Colisage v2.0"; then
            log_result "${GREEN}✅ Titre de l'application présent${NC}"
        else
            log_result "${RED}❌ Titre de l'application manquant${NC}"
        fi
        
        if echo "$PAGE_CONTENT" | grep -q "initializeFicheProduction"; then
            log_result "${GREEN}✅ Initialisation JavaScript présente${NC}"
        else
            log_result "${RED}❌ Initialisation JavaScript manquante${NC}"
        fi
        
        if echo "$PAGE_CONTENT" | grep -q "saveColisageBtn"; then
            log_result "${GREEN}✅ Bouton de sauvegarde présent${NC}"
        else
            log_result "${YELLOW}⚠️  Bouton de sauvegarde non visible (droits utilisateur?)${NC}"
        fi
        
    else
        log_result "${RED}❌ Page non accessible - vérifier l'URL et les permissions${NC}"
    fi
    
else
    log_result "${YELLOW}⚠️  cURL non disponible - test d'accessibilité ignoré${NC}"
fi

echo ""

# Test 5: Validation des styles CSS
echo "🎨 Test 5: Validation des styles CSS"
echo "------------------------------------"

# Vérifier les classes CSS importantes
if grep -q "\.modal-overlay" css/ficheproduction-save.css; then
    log_result "${GREEN}✅ Styles de modales présents${NC}"
else
    log_result "${RED}❌ Styles de modales manquants${NC}"
fi

if grep -q "\.save-progress" css/ficheproduction-save.css; then
    log_result "${GREEN}✅ Styles de progression présents${NC}"
else
    log_result "${RED}❌ Styles de progression manquants${NC}"
fi

if grep -q "\.libre-badge" css/ficheproduction-save.css; then
    log_result "${GREEN}✅ Styles colis libres présents${NC}"
else
    log_result "${RED}❌ Styles colis libres manquants${NC}"
fi

echo ""

# Test 6: Validation de la structure de base de données
echo "🗄️  Test 6: Validation de la structure de base de données"
echo "---------------------------------------------------------"

check_sql_file() {
    if [ -f "sql/$1" ]; then
        if grep -q "CREATE TABLE" "sql/$1"; then
            log_result "${GREEN}✅ $1 contient une structure de table valide${NC}"
        else
            log_result "${RED}❌ $1 ne contient pas de structure de table${NC}"
        fi
    else
        log_result "${RED}❌ Fichier SQL $1 manquant${NC}"
    fi
}

check_sql_file "llx_ficheproduction_session.sql"
check_sql_file "llx_ficheproduction_colis.sql"
check_sql_file "llx_ficheproduction_colis_line.sql"
check_sql_file "llx_ficheproduction_colis_line_v2.sql"

echo ""

# Test 7: Test de la documentation
echo "📚 Test 7: Validation de la documentation"
echo "-----------------------------------------"

check_doc_content() {
    if [ -f "$1" ]; then
        LINES=$(wc -l < "$1")
        if [ "$LINES" -gt 50 ]; then
            log_result "${GREEN}✅ $1 contient une documentation substantielle ($LINES lignes)${NC}"
        else
            log_result "${YELLOW}⚠️  $1 documentation courte ($LINES lignes)${NC}"
        fi
    else
        log_result "${RED}❌ Documentation $1 manquante${NC}"
    fi
}

check_doc_content "docs/SAUVEGARDE.md"
check_doc_content "docs/MIGRATION.md"
check_doc_content "README-SAUVEGARDE.md"

echo ""

# Résumé final
echo "📊 RÉSUMÉ DES TESTS"
echo "==================="

# Compter les résultats
TOTAL_TESTS=$(grep -c "✅\|❌\|⚠️" "$TEST_LOG")
SUCCESS_TESTS=$(grep -c "✅" "$TEST_LOG")
FAILED_TESTS=$(grep -c "❌" "$TEST_LOG")
WARNING_TESTS=$(grep -c "⚠️" "$TEST_LOG")

log_result ""
log_result "Total des tests: $TOTAL_TESTS"
log_result "${GREEN}Succès: $SUCCESS_TESTS${NC}"
log_result "${RED}Échecs: $FAILED_TESTS${NC}"
log_result "${YELLOW}Avertissements: $WARNING_TESTS${NC}"

# Calcul du pourcentage de réussite
if [ "$TOTAL_TESTS" -gt 0 ]; then
    SUCCESS_PERCENT=$((SUCCESS_TESTS * 100 / TOTAL_TESTS))
    log_result "Taux de réussite: $SUCCESS_PERCENT%"
    
    if [ "$SUCCESS_PERCENT" -ge 90 ]; then
        log_result "${GREEN}🎉 EXCELLENT - Prêt pour la production!${NC}"
    elif [ "$SUCCESS_PERCENT" -ge 75 ]; then
        log_result "${YELLOW}👍 BON - Quelques ajustements nécessaires${NC}"
    else
        log_result "${RED}⚠️  ATTENTION - Corrections importantes requises${NC}"
    fi
fi

echo ""
echo "📝 Log détaillé sauvegardé dans: $TEST_LOG"
echo ""

# Recommandations finales
echo "🎯 RECOMMANDATIONS"
echo "=================="

if [ "$FAILED_TESTS" -gt 0 ]; then
    echo "1. Corriger les erreurs signalées en rouge (❌)"
fi

if [ "$WARNING_TESTS" -gt 0 ]; then
    echo "2. Vérifier les avertissements en jaune (⚠️)"
fi

echo "3. Tester manuellement la sauvegarde/chargement"
echo "4. Valider avec différents types d'utilisateurs"
echo "5. Tester sur plusieurs navigateurs"

echo ""
echo "✨ Test terminé - Vérifiez le log pour les détails"
