/**
 * FicheProduction v2.0 - Module Drag & Drop
 * Gestion complète du système de drag & drop
 */

// ============================================================================
// GESTION DRAG & DROP
// ============================================================================

/**
 * Activer les zones de drop quand un élément est en cours de drag
 */
function activateDropZones() {
    if (!isDragging) return;
    
    debugLog('🎯 Activation des zones de drop');
    
    // Activer toutes les lignes du tableau colis
    const allColisRows = document.querySelectorAll('#colisTableBody tr');
    allColisRows.forEach(row => {
        if (row.dataset.colisId || row.classList.contains('colis-group-header') || row.classList.contains('colis-group-item')) {
            row.classList.add('drop-active');
        }
    });
    
    // Activer la zone de détail du colis sélectionné
    const colisContent = document.getElementById('colisContent');
    if (colisContent && selectedColis) {
        colisContent.classList.add('drop-zone-active');
    }
}

/**
 * Désactiver toutes les zones de drop
 */
function deactivateDropZones() {
    debugLog('🔴 Désactivation des zones de drop');
    
    // Désactiver toutes les zones de drop
    const dropActiveElements = document.querySelectorAll('.drop-active');
    dropActiveElements.forEach(el => el.classList.remove('drop-active'));
    
    const dropZoneActive = document.querySelectorAll('.drop-zone-active');
    dropZoneActive.forEach(el => el.classList.remove('drop-zone-active'));
}

/**
 * Configurer une zone de drop pour un élément donné
 * @param {HTMLElement} element - Élément à configurer comme zone de drop
 * @param {number} colisId - ID du colis cible
 */
function setupDropZone(element, colisId) {
    element.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    element.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (draggedProduct && isDragging) {
            debugLog(`📍 Drop sur colis ${colisId} - Produit: ${draggedProduct.ref}`);
            addProductToColis(colisId, draggedProduct.id, 1);
        }
    });
}

/**
 * Configurer les événements de drag pour un élément produit
 * @param {HTMLElement} productElement - Élément produit à rendre draggable
 * @param {Object} product - Données du produit
 */
function setupProductDragEvents(productElement, product) {
    productElement.addEventListener('dragstart', function(e) {
        const available = product.total - product.used;
        if (available === 0) {
            e.preventDefault();
            return;
        }
        
        isDragging = true;
        draggedProduct = product;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'copy';
        debugLog(`🚀 Drag start: ${product.ref || product.name}`);
        
        // Activer les zones de drop après un délai
        setTimeout(() => {
            activateDropZones();
        }, 50);
    });

    productElement.addEventListener('dragend', function(e) {
        this.classList.remove('dragging');
        isDragging = false;
        draggedProduct = null;
        debugLog(`🛑 Drag end: ${product.ref || product.name}`);
        
        // Désactiver les zones de drop
        deactivateDropZones();
    });
}

/**
 * Configurer le drag & drop pour les lignes de colis (réorganisation)
 * @param {HTMLElement} colisLineElement - Élément ligne de colis
 * @param {number} colisId - ID du colis
 * @param {number} productId - ID du produit dans le colis
 */
function setupColisLineDragEvents(colisLineElement, colisId, productId) {
    colisLineElement.draggable = true;
    
    colisLineElement.addEventListener('dragstart', function(e) {
        isDragging = true;
        draggedColisLine = { colisId, productId };
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        debugLog(`🚀 Drag start ligne colis: ${colisId}-${productId}`);
        
        // Activer les zones de drop pour réorganisation
        setTimeout(() => {
            activateColisReorderZones();
        }, 50);
    });

    colisLineElement.addEventListener('dragend', function(e) {
        this.classList.remove('dragging');
        isDragging = false;
        draggedColisLine = null;
        debugLog(`🛑 Drag end ligne colis`);
        
        // Désactiver les zones de drop
        deactivateColisReorderZones();
    });
}

/**
 * Activer les zones de drop pour la réorganisation des colis
 */
function activateColisReorderZones() {
    if (!isDragging || !draggedColisLine) return;
    
    debugLog('🎯 Activation zones réorganisation colis');
    
    // Activer tous les autres colis pour permettre le déplacement entre colis
    const allColisRows = document.querySelectorAll('#colisTableBody tr[data-colis-id]');
    allColisRows.forEach(row => {
        const colisId = parseInt(row.dataset.colisId);
        if (colisId && colisId !== draggedColisLine.colisId) {
            row.classList.add('drop-active-reorder');
        }
    });
}

/**
 * Désactiver les zones de drop pour la réorganisation
 */
function deactivateColisReorderZones() {
    debugLog('🔴 Désactivation zones réorganisation');
    
    const reorderElements = document.querySelectorAll('.drop-active-reorder');
    reorderElements.forEach(el => el.classList.remove('drop-active-reorder'));
}

/**
 * Configurer une zone de drop pour la réorganisation entre colis
 * @param {HTMLElement} element - Élément colis cible
 * @param {number} targetColisId - ID du colis cible
 */
function setupColisReorderDropZone(element, targetColisId) {
    element.addEventListener('dragover', function(e) {
        if (draggedColisLine && draggedColisLine.colisId !== targetColisId) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }
    });

    element.addEventListener('drop', function(e) {
        if (draggedColisLine && draggedColisLine.colisId !== targetColisId) {
            e.preventDefault();
            e.stopPropagation();
            
            debugLog(`📍 Déplacement ligne de colis ${draggedColisLine.colisId} vers ${targetColisId}`);
            moveProductBetweenColis(draggedColisLine.colisId, targetColisId, draggedColisLine.productId);
        }
    });
}

/**
 * Déplacer un produit d'un colis vers un autre
 * @param {number} sourceColisId - ID du colis source
 * @param {number} targetColisId - ID du colis cible
 * @param {number} productId - ID du produit à déplacer
 */
function moveProductBetweenColis(sourceColisId, targetColisId, productId) {
    const sourceColis = colis.find(c => c.id === sourceColisId);
    const targetColis = colis.find(c => c.id === targetColisId);
    
    if (!sourceColis || !targetColis) {
        debugLog('ERREUR: Colis source ou cible non trouvé');
        return;
    }

    // Ne pas permettre de déplacer entre colis libres et normaux
    if (sourceColis.isLibre !== targetColis.isLibre) {
        alert('Impossible de déplacer des produits entre colis normaux et colis libres.');
        return;
    }

    const productInSource = sourceColis.products.find(p => p.productId === productId);
    if (!productInSource) {
        debugLog('ERREUR: Produit non trouvé dans le colis source');
        return;
    }

    const product = products.find(p => p.id === productId);
    if (!product) {
        debugLog('ERREUR: Produit non trouvé dans la liste globale');
        return;
    }

    // Vérifier s'il y a assez de stock pour le colis cible (pour les produits normaux)
    if (!product.isLibre) {
        const quantityNeeded = productInSource.quantity * targetColis.multiple;
        const currentUsed = product.used;
        const wouldBeUsed = currentUsed - (productInSource.quantity * sourceColis.multiple) + quantityNeeded;
        
        if (wouldBeUsed > product.total) {
            alert(`Stock insuffisant pour déplacer ce produit. Stock total: ${product.total}, Serait utilisé: ${wouldBeUsed}`);
            return;
        }
    }

    // Supprimer du colis source
    const sourceIndex = sourceColis.products.findIndex(p => p.productId === productId);
    if (sourceIndex > -1) {
        sourceColis.products.splice(sourceIndex, 1);
    }

    // Recalculer le poids du colis source
    sourceColis.totalWeight = sourceColis.products.reduce((sum, p) => sum + p.weight, 0);

    // Ajouter au colis cible
    const existingInTarget = targetColis.products.find(p => p.productId === productId);
    if (existingInTarget) {
        existingInTarget.quantity += productInSource.quantity;
        existingInTarget.weight = existingInTarget.quantity * product.weight;
    } else {
        targetColis.products.push({
            productId: productId,
            quantity: productInSource.quantity,
            weight: productInSource.quantity * product.weight
        });
    }

    // Recalculer le poids du colis cible
    targetColis.totalWeight = targetColis.products.reduce((sum, p) => sum + p.weight, 0);

    // Mettre à jour les quantités utilisées (pour les produits normaux)
    if (!product.isLibre) {
        product.used = product.used - (productInSource.quantity * sourceColis.multiple) + (productInSource.quantity * targetColis.multiple);
    }

    // Re-render
    renderInventory();
    renderColisOverview();
    renderColisDetail();
    updateSummaryTotals();
    
    debugLog(`Produit ${productId} déplacé du colis ${sourceColisId} vers ${targetColisId}`);
}

/**
 * Initialiser tous les événements de drag & drop pour l'interface
 */
function initializeDragAndDrop() {
    debugLog('🎯 Initialisation du système drag & drop');
    
    // Les événements de drag pour les produits sont configurés dans renderInventory()
    // Les événements de drop pour les colis sont configurés dans renderColisOverview()
    // Les événements pour le détail des colis sont configurés dans renderColisDetail()
    
    // Configurer les événements globaux de drag & drop
    document.addEventListener('dragover', function(e) {
        e.preventDefault(); // Permettre le drop
    });
    
    document.addEventListener('drop', function(e) {
        // Empêcher le drop sur le document en général
        if (!e.target.closest('.drop-active, .drop-zone-active, .drop-active-reorder')) {
            e.preventDefault();
        }
    });
    
    debugLog('✅ Système drag & drop initialisé');
}

// Export des fonctions pour utilisation par d'autres modules
window.activateDropZones = activateDropZones;
window.deactivateDropZones = deactivateDropZones;
window.setupDropZone = setupDropZone;
window.setupProductDragEvents = setupProductDragEvents;
window.setupColisLineDragEvents = setupColisLineDragEvents;
window.activateColisReorderZones = activateColisReorderZones;
window.deactivateColisReorderZones = deactivateColisReorderZones;
window.setupColisReorderDropZone = setupColisReorderDropZone;
window.moveProductBetweenColis = moveProductBetweenColis;
window.initializeDragAndDrop = initializeDragAndDrop;