/**
 * FicheProduction v2.0 - Core Module
 * Variables globales et fonctions de base
 */

// ============================================================================
// VARIABLES GLOBALES
// ============================================================================

// Données principales
let products = [];
let productGroups = [];
let colis = [];
let selectedColis = null;

// États du drag & drop
let draggedProduct = null;
let draggedColisLine = null;
let isDragging = false;

// Filtres et tri
let currentProductGroup = 'all';
let currentSort = 'original';

// Configuration
let ORDER_ID, TOKEN;
let savedDataLoaded = false;

// ============================================================================
// OBJETS NAMESPACE
// ============================================================================

// Namespace principal pour organiser les modules
window.FicheProduction = {
    // Données
    data: {
        products: () => products,
        productGroups: () => productGroups,
        colis: () => colis,
        selectedColis: () => selectedColis,
        setSelectedColis: (colis) => { selectedColis = colis; },
        setProducts: (newProducts) => { products = newProducts; },
        setProductGroups: (newGroups) => { productGroups = newGroups; },
        setColis: (newColis) => { colis = newColis; },
        addColis: (newColis) => { colis.push(newColis); },
        removeColis: (colisId) => {
            const index = colis.findIndex(c => c.id === colisId);
            if (index > -1) colis.splice(index, 1);
        },
        savedDataLoaded: () => savedDataLoaded,
        setSavedDataLoaded: (value) => { savedDataLoaded = value; }
    },
    
    // États
    state: {
        isDragging: () => isDragging,
        setDragging: (value) => { isDragging = value; },
        draggedProduct: () => draggedProduct,
        setDraggedProduct: (product) => { draggedProduct = product; },
        draggedColisLine: () => draggedColisLine,
        setDraggedColisLine: (line) => { draggedColisLine = line; },
        currentSort: () => currentSort,
        setCurrentSort: (sort) => { currentSort = sort; },
        currentProductGroup: () => currentProductGroup,
        setCurrentProductGroup: (group) => { currentProductGroup = group; }
    },
    
    // Configuration
    config: {
        orderId: () => ORDER_ID,
        token: () => TOKEN,
        setConfig: (orderId, token) => {
            ORDER_ID = orderId;
            TOKEN = token;
        }
    },
    
    // Modules (seront ajoutés par les autres fichiers)
    ajax: {},
    inventory: {},
    colis: {},
    dragdrop: {},
    ui: {},
    libre: {},
    utils: {}
};

// ============================================================================
// FONCTIONS UTILITAIRES DE BASE
// ============================================================================

/**
 * Fonction de debug centralisée
 */
function debugLog(message) {
    console.log(message);
    const debugConsole = document.getElementById('debugConsole');
    if (debugConsole) {
        debugConsole.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '<br>';
        debugConsole.scrollTop = debugConsole.scrollHeight;
    }
}

/**
 * Utilitaire pour générer des IDs uniques
 */
function generateUniqueId(prefix = 'id') {
    return prefix + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Utilitaire pour formater les poids
 */
function formatWeight(weight) {
    return parseFloat(weight).toFixed(1);
}

/**
 * Utilitaire pour vérifier si un élément est visible
 */
function isElementVisible(element) {
    return element && element.offsetWidth > 0 && element.offsetHeight > 0;
}

/**
 * Utilitaire pour nettoyer les écouteurs d'événements
 */
function removeAllEventListeners(element) {
    if (element && element.parentNode) {
        const newElement = element.cloneNode(true);
        element.parentNode.replaceChild(newElement, element);
        return newElement;
    }
    return null;
}

// ============================================================================
// GESTION DES ERREURS GLOBALES
// ============================================================================

/**
 * Gestionnaire d'erreur global
 */
function handleGlobalError(error, context = 'Unknown') {
    debugLog(`ERREUR [${context}]: ${error.message || error}`);
    console.error(`FicheProduction Error [${context}]:`, error);
    
    // Afficher un message d'erreur à l'utilisateur si nécessaire
    if (window.FicheProduction && window.FicheProduction.ui && window.FicheProduction.ui.showError) {
        window.FicheProduction.ui.showError(`Erreur dans ${context}: ${error.message || error}`);
    }
}

/**
 * Wrapper pour les fonctions async avec gestion d'erreur
 */
function safeAsync(asyncFunction, context = 'AsyncFunction') {
    return async function(...args) {
        try {
            return await asyncFunction.apply(this, args);
        } catch (error) {
            handleGlobalError(error, context);
            throw error; // Re-throw pour permettre la gestion spécifique si nécessaire
        }
    };
}

// ============================================================================
// FONCTION D'INITIALISATION PRINCIPALE
// ============================================================================

/**
 * Fonction principale d'initialisation
 * Appelée une fois que tous les modules sont chargés
 */
function initializeFicheProduction(orderId, token) {
    try {
        debugLog('='.repeat(50));
        debugLog('🚀 INITIALISATION FICHEPRODUCTION V2.0 MODULAIRE');
        debugLog('='.repeat(50));
        
        // Configuration de base
        FicheProduction.config.setConfig(orderId, token);
        debugLog(`Configuration: Order ID=${orderId}`);
        
        // Vérifier que tous les modules sont chargés
        const requiredModules = ['ajax', 'inventory', 'colis', 'dragdrop', 'ui', 'libre', 'utils'];
        const missingModules = requiredModules.filter(module => 
            !FicheProduction[module] || Object.keys(FicheProduction[module]).length === 0
        );
        
        if (missingModules.length > 0) {
            debugLog(`⚠️ Modules manquants: ${missingModules.join(', ')} - Chargement partiel`);
        } else {
            debugLog('✅ Tous les modules sont chargés');
        }
        
        // Initialisation des modules dans l'ordre
        if (FicheProduction.ui.initialize) {
            FicheProduction.ui.initialize();
            debugLog('✅ Module UI initialisé');
        }
        
        if (FicheProduction.dragdrop.initialize) {
            FicheProduction.dragdrop.initialize();
            debugLog('✅ Module Drag&Drop initialisé');
        }
        
        if (FicheProduction.inventory.initialize) {
            FicheProduction.inventory.initialize();
            debugLog('✅ Module Inventory initialisé');
        }
        
        if (FicheProduction.colis.initialize) {
            FicheProduction.colis.initialize();
            debugLog('✅ Module Colis initialisé');
        }
        
        // Chargement des données
        if (FicheProduction.ajax.loadData) {
            FicheProduction.ajax.loadData()
                .then(() => {
                    debugLog('✅ Données chargées avec succès');
                    debugLog('🎉 Initialisation modulaire terminée');
                    debugLog('💡 Double-cliquez sur le titre pour afficher/masquer la console de debug');
                })
                .catch(error => {
                    handleGlobalError(error, 'DataLoading');
                });
        } else {
            debugLog('⚠️ Module AJAX non disponible - chargement des données impossible');
        }
        
    } catch (error) {
        handleGlobalError(error, 'Initialization');
    }
}

// ============================================================================
// FONCTIONS DE COMPATIBILITÉ
// ============================================================================

/**
 * Fonctions globales pour maintenir la compatibilité avec l'ancien code
 */
function saveColisage() {
    if (FicheProduction.ajax && FicheProduction.ajax.saveColisage) {
        return FicheProduction.ajax.saveColisage();
    }
    debugLog('❌ Fonction saveColisage non disponible - module AJAX manquant');
}

function preparePrint() {
    if (FicheProduction.utils && FicheProduction.utils.preparePrint) {
        return FicheProduction.utils.preparePrint();
    }
    // Fonction de base en cas de module manquant
    var originalTitle = document.title;
    document.title = 'Fiche de Production - Commande';
    window.print();
    setTimeout(function() {
        document.title = originalTitle;
    }, 1000);
}

// ============================================================================
// FONCTIONS EXPORTÉES GLOBALEMENT
// ============================================================================

// Export des fonctions principales pour utilisation dans le HTML
window.initializeFicheProduction = initializeFicheProduction;
window.debugLog = debugLog;
window.saveColisage = saveColisage;
window.preparePrint = preparePrint;

// Export du namespace principal
window.FicheProduction = FicheProduction;

// Messages de chargement
debugLog('📦 Module Core chargé - Architecture modulaire v2.0');