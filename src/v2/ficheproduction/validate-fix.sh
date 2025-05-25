#!/bin/bash

# ============================================================================
# SCRIPT DE VALIDATION - CORRECTION MODULES FICHEPRODUCTION v2.0
# ============================================================================

echo "🔧 Script de Validation - Correction Modules FicheProduction v2.0"
echo "================================================================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIXED_FILES=(
    "js/ficheproduction-core-fixed.js"
    "js/ficheproduction-inventory-fixed.js"
    "js/ficheproduction-colis-fixed.js"
    "js/ficheproduction-dragdrop-fixed.js"
    "js/ficheproduction-ui-fixed.js"
    "ficheproduction-test-fixed.php"
    "test-modules-fixed.html"
)

CRITICAL_FUNCTIONS=(
    "FicheProduction.inventory.renderInventory"
    "FicheProduction.colis.addNewColis"
    "FicheProduction.dragdrop.setupDropZone"
    "FicheProduction.ui.showConfirm"
)

echo ""
echo "📁 Répertoire de travail: $SCRIPT_DIR"
echo ""

# ============================================================================
# 1. VÉRIFICATION DE L'EXISTENCE DES FICHIERS CORRIGÉS
# ============================================================================

echo "1️⃣ Vérification des fichiers corrigés..."
echo "----------------------------------------"

missing_files=0
for file in "${FIXED_FILES[@]}"; do
    if [ -f "$SCRIPT_DIR/$file" ]; then
        echo -e "${GREEN}✅${NC} $file"
    else
        echo -e "${RED}❌${NC} $file ${RED}(MANQUANT)${NC}"
        ((missing_files++))
    fi
done

if [ $missing_files -eq 0 ]; then
    echo -e "\n${GREEN}✅ Tous les fichiers corrigés sont présents${NC}"
else
    echo -e "\n${RED}❌ $missing_files fichier(s) manquant(s)${NC}"
fi

# ============================================================================
# 2. VÉRIFICATION DU CONTENU DES FICHIERS
# ============================================================================

echo ""
echo "2️⃣ Vérification du contenu des fichiers..."
echo "-------------------------------------------"

# Vérifier que le core-fixed contient le nouveau système d'enregistrement
if [ -f "$SCRIPT_DIR/js/ficheproduction-core-fixed.js" ]; then
    if grep -q "registerModule" "$SCRIPT_DIR/js/ficheproduction-core-fixed.js"; then
        echo -e "${GREEN}✅${NC} Core: Système d'enregistrement présent"
    else
        echo -e "${RED}❌${NC} Core: Système d'enregistrement manquant"
    fi
    
    if grep -q "FicheProductionCoreReady" "$SCRIPT_DIR/js/ficheproduction-core-fixed.js"; then
        echo -e "${GREEN}✅${NC} Core: Événement de synchronisation présent"
    else
        echo -e "${RED}❌${NC} Core: Événement de synchronisation manquant"
    fi
fi

# Vérifier que les modules contiennent les fonctions critiques
modules_check=(
    "inventory-fixed.js:renderInventory"
    "colis-fixed.js:addNewColis"
    "dragdrop-fixed.js:setupDropZone"
    "ui-fixed.js:showConfirm"
)

for check in "${modules_check[@]}"; do
    IFS=':' read -r file_suffix function_name <<< "$check"
    file_path="$SCRIPT_DIR/js/ficheproduction-$file_suffix"
    
    if [ -f "$file_path" ]; then
        if grep -q "function $function_name" "$file_path"; then
            echo -e "${GREEN}✅${NC} $file_suffix: Function $function_name présente"
        else
            echo -e "${RED}❌${NC} $file_suffix: Function $function_name manquante"
        fi
    fi
done

# ============================================================================
# 3. GÉNÉRATION DU RAPPORT DE TEST HTML
# ============================================================================

echo ""
echo "3️⃣ Génération du rapport de test..."
echo "-----------------------------------"

cat > "$SCRIPT_DIR/rapport-validation.html" << 'EOF'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Validation - Correction Modules</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .test-section { border: 1px solid #ddd; margin: 20px 0; padding: 20px; border-radius: 8px; }
        .console { background: #1a1a1a; color: #00ff00; padding: 15px; border-radius: 4px; font-family: monospace; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Rapport de Validation - Correction Modules</h1>
    
    <div class="test-section">
        <h2>📊 Status en Temps Réel</h2>
        <button class="btn" onclick="runValidation()">🔍 Lancer Validation</button>
        <button class="btn" onclick="testCriticalFunctions()">⚡ Tester Fonctions Critiques</button>
        <div id="validation-results"></div>
    </div>
    
    <div class="test-section">
        <h2>🐛 Console de Debug</h2>
        <div id="debug-console" class="console"></div>
    </div>
    
    <script src="js/ficheproduction-core-fixed.js"></script>
    <script src="js/ficheproduction-inventory-fixed.js"></script>
    <script src="js/ficheproduction-colis-fixed.js"></script>
    <script src="js/ficheproduction-dragdrop-fixed.js"></script>
    <script src="js/ficheproduction-ui-fixed.js"></script>
    
    <script>
        const debugConsole = document.getElementById('debug-console');
        const results = document.getElementById('validation-results');
        
        // Capture des logs
        const originalLog = console.log;
        console.log = function(...args) {
            originalLog.apply(console, args);
            debugConsole.innerHTML += args.join(' ') + '\n';
            debugConsole.scrollTop = debugConsole.scrollHeight;
        };
        
        function runValidation() {
            results.innerHTML = '<h3>🔍 Validation en cours...</h3>';
            
            const tests = [
                {
                    name: 'Namespace Principal',
                    test: () => typeof window.FicheProduction !== 'undefined',
                    critical: true
                },
                {
                    name: 'Module Inventory',
                    test: () => window.FicheProduction && window.FicheProduction.inventory,
                    critical: true
                },
                {
                    name: 'Function renderInventory',
                    test: () => window.FicheProduction && window.FicheProduction.inventory && window.FicheProduction.inventory.renderInventory,
                    critical: true
                },
                {
                    name: 'Module Colis',
                    test: () => window.FicheProduction && window.FicheProduction.colis,
                    critical: true
                },
                {
                    name: 'Function addNewColis',
                    test: () => window.FicheProduction && window.FicheProduction.colis && window.FicheProduction.colis.addNewColis,
                    critical: true
                },
                {
                    name: 'Module DragDrop',
                    test: () => window.FicheProduction && window.FicheProduction.dragdrop,
                    critical: true
                },
                {
                    name: 'Function setupDropZone',
                    test: () => window.FicheProduction && window.FicheProduction.dragdrop && window.FicheProduction.dragdrop.setupDropZone,
                    critical: true
                },
                {
                    name: 'Module UI',
                    test: () => window.FicheProduction && window.FicheProduction.ui,
                    critical: true
                },
                {
                    name: 'Function showConfirm',
                    test: () => window.FicheProduction && window.FicheProduction.ui && window.FicheProduction.ui.showConfirm,
                    critical: true
                }
            ];
            
            let html = '<h3>📋 Résultats de Validation</h3><ul>';
            let criticalFailures = 0;
            
            tests.forEach(test => {
                const passed = test.test();
                const icon = passed ? '✅' : '❌';
                const className = passed ? 'success' : 'error';
                
                if (!passed && test.critical) {
                    criticalFailures++;
                }
                
                html += `<li class="${className}">${icon} ${test.name}</li>`;
            });
            
            html += '</ul>';
            
            if (criticalFailures === 0) {
                html += '<div class="success"><h4>🎉 Tous les tests critiques passent!</h4><p>La correction est opérationnelle.</p></div>';
            } else {
                html += `<div class="error"><h4>❌ ${criticalFailures} test(s) critique(s) échoué(s)</h4><p>La correction nécessite des ajustements.</p></div>`;
            }
            
            results.innerHTML = html;
        }
        
        function testCriticalFunctions() {
            if (!window.FicheProduction) {
                results.innerHTML = '<div class="error">❌ Namespace FicheProduction non disponible</div>';
                return;
            }
            
            let html = '<h3>⚡ Test des Fonctions Critiques</h3>';
            
            // Test renderInventory
            try {
                if (typeof FicheProduction.inventory.renderInventory === 'function') {
                    html += '<div class="success">✅ renderInventory: Fonction disponible</div>';
                } else {
                    html += '<div class="error">❌ renderInventory: Non disponible</div>';
                }
            } catch (e) {
                html += '<div class="error">❌ renderInventory: Erreur - ' + e.message + '</div>';
            }
            
            // Test addNewColis
            try {
                if (typeof FicheProduction.colis.addNewColis === 'function') {
                    html += '<div class="success">✅ addNewColis: Fonction disponible</div>';
                } else {
                    html += '<div class="error">❌ addNewColis: Non disponible</div>';
                }
            } catch (e) {
                html += '<div class="error">❌ addNewColis: Erreur - ' + e.message + '</div>';
            }
            
            // Test setupDropZone
            try {
                if (typeof FicheProduction.dragdrop.setupDropZone === 'function') {
                    html += '<div class="success">✅ setupDropZone: Fonction disponible</div>';
                } else {
                    html += '<div class="error">❌ setupDropZone: Non disponible</div>';
                }
            } catch (e) {
                html += '<div class="error">❌ setupDropZone: Erreur - ' + e.message + '</div>';
            }
            
            // Test showConfirm
            try {
                if (typeof FicheProduction.ui.showConfirm === 'function') {
                    html += '<div class="success">✅ showConfirm: Fonction disponible</div>';
                    html += '<button class="btn" onclick="testConfirmDialog()">🧪 Tester Dialog</button>';
                } else {
                    html += '<div class="error">❌ showConfirm: Non disponible</div>';
                }
            } catch (e) {
                html += '<div class="error">❌ showConfirm: Erreur - ' + e.message + '</div>';
            }
            
            results.innerHTML = html;
        }
        
        async function testConfirmDialog() {
            try {
                const result = await FicheProduction.ui.showConfirm('Test de la boîte de dialogue de confirmation. Cliquez OK pour confirmer.');
                if (result) {
                    FicheProduction.ui.showToast('✅ Dialog de confirmation fonctionne!', 'success');
                } else {
                    FicheProduction.ui.showToast('Dialog annulé', 'info');
                }
            } catch (e) {
                console.error('Erreur test dialog:', e);
            }
        }
        
        // Auto-lancement de la validation
        setTimeout(() => {
            runValidation();
        }, 200);
    </script>
</body>
</html>
EOF

echo -e "${GREEN}✅${NC} Rapport généré: $SCRIPT_DIR/rapport-validation.html"

# ============================================================================
# 4. INSTRUCTIONS D'UTILISATION
# ============================================================================

echo ""
echo "4️⃣ Instructions d'utilisation..."
echo "--------------------------------"

echo ""
echo -e "${BLUE}🚀 Pour tester la correction:${NC}"
echo "1. Ouvrir le fichier 'test-modules-fixed.html' dans un navigateur"
echo "2. Ouvrir le fichier 'rapport-validation.html' pour un diagnostic complet"
echo "3. Pour Dolibarr: utiliser 'ficheproduction-test-fixed.php'"
echo ""

echo -e "${BLUE}🔧 Pour intégrer en production:${NC}"
echo "1. Remplacer les fichiers JS originaux par les versions '-fixed'"
echo "2. Mettre à jour les références dans le PHP principal"
echo "3. Surveiller les logs de la console navigateur"
echo ""

echo -e "${BLUE}📊 Files générés pour les tests:${NC}"
echo "- rapport-validation.html (diagnostic complet)"
echo "- test-modules-fixed.html (test fonctionnel)"
echo "- ficheproduction-test-fixed.php (test Dolibarr)"
echo ""

# ============================================================================
# 5. RÉSUMÉ FINAL
# ============================================================================

echo "================================================================="
echo -e "${GREEN}✅ Script de validation terminé${NC}"

if [ $missing_files -eq 0 ]; then
    echo -e "${GREEN}🎉 Correction prête pour les tests!${NC}"
    echo ""
    echo "Prochaines étapes:"
    echo "1. Ouvrir rapport-validation.html"
    echo "2. Vérifier que tous les tests passent"
    echo "3. Tester l'affichage des produits dans l'inventaire"
else
    echo -e "${RED}⚠️ Certains fichiers sont manquants${NC}"
    echo "Vérifiez que tous les fichiers corrigés ont été créés."
fi

echo "================================================================="