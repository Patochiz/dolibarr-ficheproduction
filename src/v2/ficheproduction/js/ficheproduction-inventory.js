/**
 * FicheProduction v2.0 - Module Inventory
 * Gestion de l'inventaire des produits
 */

// ============================================================================
// GESTION DE L'INVENTAIRE
// ============================================================================

/**
 * Créer une vignette produit (utilisée dans inventaire et colis)
 * @param {Object} product - Données du produit
 * @param {boolean} isInColis - Si le produit est affiché dans un colis
 * @param {number} currentQuantity - Quantité actuelle pour les produits dans les colis
 * @returns {HTMLElement} - Élément DOM de la vignette
 */
function createProductVignette(product, isInColis = false, currentQuantity = 1) {
    // Gestion des produits libres (pas de contraintes de stock)
    if (product.isLibre) {
        const vignetteElement = document.createElement('div');
        vignetteElement.className = 'product-item libre-item';
        if (isInColis) {
            vignetteElement.classList.add('in-colis');
        }

        const quantityInputHtml = isInColis ? `
            <div class="quantity-input-container">
                <span class="quantity-input-label">Qté:</span>
                <input type="number" class="quantity-input" value="${currentQuantity}" min="1" 
                       data-product-id="${product.id}">
            </div>
        ` : '';

        vignetteElement.innerHTML = `
            <div class="product-header">
                <span class="product-ref">${product.name}</span>
                <span class="product-color libre-badge">LIBRE</span>
            </div>
            
            <div class="product-dimensions">
                Poids unitaire: ${product.weight}kg
            </div>
            <div class="quantity-info">
                <span class="libre-info">📦 Élément libre</span>
            </div>
            ${quantityInputHtml}
            <div class="status-indicator libre"></div>
        `;

        return vignetteElement;
    }

    // Produits normaux (existant)
    const available = product.total - product.used;
    const percentage = (product.used / product.total) * 100;
    let status = 'available';
    
    if (available === 0) status = 'exhausted';
    else if (product.used > 0) status = 'partial';

    const vignetteElement = document.createElement('div');
    vignetteElement.className = `product-item ${status}`;
    if (isInColis) {
        vignetteElement.classList.add('in-colis');
    }
    if (!isInColis) {
        vignetteElement.draggable = status !== 'exhausted';
        vignetteElement.dataset.productId = product.id;
    }

    // Ajouter input de quantité pour les vignettes dans les colis
    const quantityInputHtml = isInColis ? `
        <div class="quantity-input-container">
            <span class="quantity-input-label">Qté:</span>
            <input type="number" class="quantity-input" value="${currentQuantity}" min="1" 
                   data-product-id="${product.id}">
        </div>
    ` : '';

    vignetteElement.innerHTML = `
        <div class="product-header">
            <span class="product-ref">${product.name}</span>
            <span class="product-color">${product.color}</span>
        </div>
        
        <div class="product-dimensions">
            L: ${product.length}mm × l: ${product.width}mm ${product.ref_ligne ? `<strong>Réf: ${product.ref_ligne}</strong>` : ''}
        </div>
        <div class="quantity-info">
            <span class="quantity-used">${product.used}</span>
            <span>/</span>
            <span class="quantity-total">${product.total}</span>
            <div class="quantity-bar">
                <div class="quantity-progress" style="width: ${percentage}%"></div>
            </div>
        </div>
        ${quantityInputHtml}
        <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
    `;

    return vignetteElement;
}

/**
 * Créer un produit libre
 * @param {string} name - Nom du produit
 * @param {number} weight - Poids unitaire
 * @param {number} quantity - Quantité (par défaut 1)
 * @returns {Object} - Objet produit libre
 */
function createLibreProduct(name, weight, quantity = 1) {
    const newId = Math.max(...products.map(p => p.id), 10000) + 1;
    return {
        id: newId,
        name: name,
        weight: parseFloat(weight),
        isLibre: true,
        total: 9999, // Pas de limite pour les produits libres
        used: 0
    };
}

/**
 * Mettre à jour l'inventaire basé sur les données sauvegardées
 */
function updateInventoryFromSavedData() {
    // Réinitialiser toutes les quantités utilisées
    products.forEach(p => {
        if (!p.isLibre) {
            p.used = 0;
        }
    });

    // Recalculer les quantités utilisées basées sur les colis sauvegardés
    colis.forEach(c => {
        c.products.forEach(p => {
            const product = products.find(prod => prod.id === p.productId);
            if (product && !product.isLibre) {
                product.used += p.quantity * c.multiple;
            }
        });
    });
}

/**
 * Remplir le sélecteur de groupes de produits
 */
function populateProductGroupSelector() {
    const selector = document.getElementById('productGroupSelect');
    
    // Conserver l'option "Tous les produits"
    selector.innerHTML = '<option value="all">Tous les produits</option>';
    
    // Ajouter les groupes de produits
    productGroups.forEach(group => {
        const option = document.createElement('option');
        option.value = group.key;
        option.textContent = `${group.name} - ${group.color}`;
        selector.appendChild(option);
    });
    
    debugLog(`Sélecteur rempli avec ${productGroups.length} groupes`);
}

/**
 * Fonction de tri des produits
 * @param {Array} productsList - Liste des produits à trier
 * @param {string} sortType - Type de tri
 * @returns {Array} - Liste triée
 */
function sortProducts(productsList, sortType) {
    const sorted = [...productsList];
    
    switch(sortType) {
        case 'original':
            // Trier par line_order (ordre original de la commande)
            return sorted.sort((a, b) => a.line_order - b.line_order);
            
        case 'length_asc':
            return sorted.sort((a, b) => a.length - b.length);
            
        case 'length_desc':
            return sorted.sort((a, b) => b.length - a.length);
            
        case 'width_asc':
            return sorted.sort((a, b) => a.width - b.width);
            
        case 'width_desc':
            return sorted.sort((a, b) => b.width - a.width);
            
        case 'name_asc':
            return sorted.sort((a, b) => a.name.localeCompare(b.name));
            
        case 'name_desc':
            return sorted.sort((a, b) => b.name.localeCompare(a.name));
            
        default:
            return sorted.sort((a, b) => a.line_order - b.line_order);
    }
}

/**
 * Rendre l'inventaire des produits
 */
function renderInventory() {
    const container = document.getElementById('inventoryList');
    container.innerHTML = '';

    // Filtrer les produits selon le groupe sélectionné (exclure les produits libres)
    let filteredProducts = products.filter(p => !p.isLibre);
    if (currentProductGroup !== 'all') {
        const selectedGroup = productGroups.find(g => g.key === currentProductGroup);
        if (selectedGroup) {
            filteredProducts = filteredProducts.filter(product => selectedGroup.products.includes(product.id));
            debugLog(`Filtrage par groupe "${currentProductGroup}": ${filteredProducts.length} produits`);
        }
    }

    // Trier les produits selon le critère sélectionné
    const sortedProducts = sortProducts(filteredProducts, currentSort);
    debugLog(`Tri appliqué: ${currentSort} - ${sortedProducts.length} produits`);

    sortedProducts.forEach(product => {
        const productElement = createProductVignette(product, false);

        // Événements drag & drop
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
            debugLog(`🚀 Drag start: ${product.ref}`);
            
            // Activer les zones de drop après un délai
            setTimeout(() => {
                activateDropZones();
            }, 50);
        });

        productElement.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            isDragging = false;
            draggedProduct = null;
            debugLog(`🛑 Drag end: ${product.ref}`);
            
            // Désactiver les zones de drop
            deactivateDropZones();
        });

        container.appendChild(productElement);
    });
}

// Export des fonctions pour utilisation par d'autres modules
window.createProductVignette = createProductVignette;
window.createLibreProduct = createLibreProduct;
window.updateInventoryFromSavedData = updateInventoryFromSavedData;
window.populateProductGroupSelector = populateProductGroupSelector;
window.sortProducts = sortProducts;
window.renderInventory = renderInventory;