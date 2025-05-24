/**
 * JavaScript pour le module Fiche de Production v2.0
 * Interface drag & drop moderne pour le colisage
 *
 * Copyright (C) 2025 SuperAdmin
 */

class ColisageManager {
    constructor(config) {
        this.config = config;
        this.products = [];
        this.colis = [];
        this.session = null;
        this.selectedColis = null;
        this.draggedProduct = null;
        this.draggedColisLine = null;
        this.currentSort = 'ref';
        this.currentFilter = 'all';
        
        // Éléments DOM
        this.inventoryList = null;
        this.colisTableBody = null;
        this.colisDetail = null;
        this.searchBox = null;
        this.filterSelect = null;
        this.sortSelect = null;
        this.addNewColisBtn = null;
        
        // Debug
        this.debugConsole = null;
        this.debugEnabled = false;
    }
    
    /**
     * Initialise le gestionnaire de colisage
     */
    init() {
        this.debugLog('Initialisation du ColisageManager v2.0');
        
        // Récupérer les éléments DOM
        this.inventoryList = document.getElementById('inventoryList');
        this.colisTableBody = document.getElementById('colisTableBody');
        this.colisDetail = document.getElementById('colisDetail');
        this.searchBox = document.getElementById('searchBox');
        this.filterSelect = document.getElementById('filterSelect');
        this.sortSelect = document.getElementById('sortSelect');
        this.addNewColisBtn = document.getElementById('addNewColisBtn');
        
        if (!this.inventoryList || !this.colisTableBody || !this.colisDetail) {
            console.error('Éléments DOM manquants pour le ColisageManager');
            return;
        }
        
        // Créer la console de debug
        this.createDebugConsole();
        
        // Configurer les event listeners
        this.setupEventListeners();
        
        // Charger les données initiales
        this.loadData();
        
        this.debugLog('ColisageManager initialisé avec succès');
    }
    
    /**
     * Crée la console de debug
     */
    createDebugConsole() {
        this.debugConsole = document.createElement('div');
        this.debugConsole.className = 'debug-console';
        this.debugConsole.id = 'debugConsole';
        document.body.appendChild(this.debugConsole);
        
        // Double-clic sur le titre pour afficher/masquer la console
        const title = document.querySelector('.colisage-title');
        if (title) {
            title.addEventListener('dblclick', () => {
                this.debugEnabled = !this.debugEnabled;
                this.debugConsole.style.display = this.debugEnabled ? 'block' : 'none';
            });
        }
    }
    
    /**
     * Log de debug
     */
    debugLog(message) {
        console.log(message);
        if (this.debugConsole && this.debugEnabled) {
            const timestamp = new Date().toLocaleTimeString();
            this.debugConsole.innerHTML += `${timestamp}: ${message}<br>`;
            this.debugConsole.scrollTop = this.debugConsole.scrollHeight;
        }
    }
    
    /**
     * Configure les event listeners
     */
    setupEventListeners() {
        // Recherche
        if (this.searchBox) {
            this.searchBox.addEventListener('input', (e) => {
                this.filterProducts(e.target.value);
            });
        }
        
        // Filtre
        if (this.filterSelect) {
            this.filterSelect.addEventListener('change', (e) => {
                this.currentFilter = e.target.value;
                this.renderInventory();
            });
        }
        
        // Tri
        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', (e) => {
                this.currentSort = e.target.value;
                this.renderInventory();
            });
        }
        
        // Bouton nouveau colis
        if (this.addNewColisBtn) {
            this.addNewColisBtn.addEventListener('click', () => {
                this.addNewColis();
            });
        }
    }
    
    /**
     * Charge les données depuis le serveur
     */
    async loadData() {
        try {
            this.debugLog('Chargement des données...');
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_get_data',
                    id: this.config.orderId,
                    token: this.config.token
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            this.products = data.products || [];
            this.colis = data.colis || [];
            this.session = data.session;
            
            this.debugLog(`Données chargées: ${this.products.length} produits, ${this.colis.length} colis`);
            
            // Rendre l'interface
            this.renderInventory();
            this.renderColisOverview();
            this.renderColisDetail();
            
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            this.showNotification('Erreur lors du chargement des données', 'error');
        }
    }
    
    /**
     * Filtre les produits par terme de recherche
     */
    filterProducts(searchTerm) {
        const productItems = this.inventoryList.querySelectorAll('.product-item');
        const term = searchTerm.toLowerCase();
        
        productItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? 'block' : 'none';
        });
    }
    
    /**
     * Rend l'inventaire des produits
     */
    renderInventory() {
        if (!this.inventoryList) return;
        
        this.inventoryList.innerHTML = '';
        
        // Trier les produits
        const sortedProducts = [...this.products].sort((a, b) => {
            switch(this.currentSort) {
                case 'ref': return a.ref.localeCompare(b.ref);
                case 'label': return a.label.localeCompare(b.label);
                case 'length': return b.length - a.length;
                case 'width': return b.width - a.width;
                case 'color': return a.color.localeCompare(b.color);
                default: return 0;
            }
        });
        
        // Filtrer les produits
        const filteredProducts = sortedProducts.filter(product => {
            const available = product.total - product.used;
            switch(this.currentFilter) {
                case 'available': return available > 0 && product.used === 0;
                case 'partial': return available > 0 && product.used > 0;
                case 'exhausted': return available === 0;
                default: return true;
            }
        });
        
        // Créer les éléments produits
        filteredProducts.forEach(product => {
            const productElement = this.createProductElement(product);
            this.inventoryList.appendChild(productElement);
        });
    }
    
    /**
     * Crée un élément produit pour l'inventaire
     */
    createProductElement(product) {
        const available = product.total - product.used;
        const percentage = (product.used / product.total) * 100;
        let status = 'available';
        
        if (available === 0) status = 'exhausted';
        else if (product.used > 0) status = 'partial';
        
        const productElement = document.createElement('div');
        productElement.className = `product-item ${status}`;
        productElement.draggable = status !== 'exhausted';
        productElement.dataset.productId = product.id;
        
        productElement.innerHTML = `
            <div class="product-header">
                <span class="product-ref">${product.ref}</span>
                <span class="product-color">${product.color}</span>
            </div>
            <div class="product-name">${product.label}</div>
            <div class="product-dimensions">
                L: ${product.length}mm × l: ${product.width}mm
            </div>
            <div class="quantity-info">
                <span class="quantity-used">${product.used}</span>
                <span>/</span>
                <span class="quantity-total">${product.total}</span>
                <div class="quantity-bar">
                    <div class="quantity-progress" style="width: ${percentage}%"></div>
                </div>
            </div>
            <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
        `;
        
        // Event listeners pour le drag & drop
        if (status !== 'exhausted') {
            productElement.addEventListener('dragstart', (e) => {
                this.draggedProduct = product;
                productElement.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'copy';
                this.debugLog(`Début drag produit: ${product.ref}`);
            });
            
            productElement.addEventListener('dragend', () => {
                productElement.classList.remove('dragging');
                this.draggedProduct = null;
                this.debugLog('Fin drag produit');
            });
        }
        
        return productElement;
    }
    
    /**
     * Rend la vue d'ensemble des colis
     */
    renderColisOverview() {
        if (!this.colisTableBody) return;
        
        this.colisTableBody.innerHTML = '';
        
        if (this.colis.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="6" style="text-align: center; font-style: italic; color: #999; padding: 20px;">
                    ${this.config.translations.createNewColis}
                </td>
            `;
            this.colisTableBody.appendChild(emptyRow);
            return;
        }
        
        this.colis.forEach(coli => {
            this.renderColisInTable(coli);
        });
    }
    
    /**
     * Rend un colis dans le tableau
     */
    renderColisInTable(coli) {
        const weightPercentage = (coli.poids_total / coli.poids_max) * 100;
        let statusIcon = '✅';
        let statusClass = '';
        
        if (weightPercentage > 100) {
            statusIcon = '❌';
            statusClass = 'error';
        } else if (weightPercentage > 90) {
            statusIcon = '⚠️';
            statusClass = 'warning';
        }
        
        const multipleDisplay = coli.multiple_colis > 1 ? ` (×${coli.multiple_colis})` : '';
        
        // En-tête du colis
        const headerRow = document.createElement('tr');
        headerRow.className = 'colis-group-header';
        headerRow.dataset.colisId = coli.id;
        
        if (this.selectedColis && this.selectedColis.id === coli.id) {
            headerRow.classList.add('selected');
        }
        
        headerRow.innerHTML = `
            <td colspan="6">
                <strong>📦 ${this.config.translations.colis || 'Colis'} ${coli.numero_colis}${multipleDisplay}</strong>
                <span style="margin-left: 15px; color: #666;">
                    ${coli.products.length} produit${coli.products.length > 1 ? 's' : ''} • 
                    ${coli.poids_total.toFixed(1)} kg • 
                    ${statusIcon}
                </span>
            </td>
        `;
        
        // Sélection du colis
        headerRow.addEventListener('click', () => {
            this.selectColis(coli);
        });
        
        // Drop zone sur l'en-tête
        this.setupDropZone(headerRow, coli.id);
        
        this.colisTableBody.appendChild(headerRow);
        
        // Lignes des produits
        if (coli.products.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'colis-group-item';
            emptyRow.innerHTML = `
                <td></td>
                <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                    ${this.config.translations.emptyColis} - ${this.config.translations.dragProductHere}
                </td>
            `;
            
            this.setupDropZone(emptyRow, coli.id);
            this.colisTableBody.appendChild(emptyRow);
        } else {
            coli.products.forEach((productInColis, index) => {
                const product = this.products.find(p => p.id === productInColis.product_id);
                if (!product) return;
                
                const productRow = this.createProductRowInColis(coli, product, productInColis, index);
                this.colisTableBody.appendChild(productRow);
            });
        }
    }
    
    /**
     * Crée une ligne de produit dans un colis
     */
    createProductRowInColis(coli, product, productInColis, index) {
        const productRow = document.createElement('tr');
        productRow.className = 'colis-group-item';
        productRow.dataset.colisId = coli.id;
        productRow.dataset.productId = product.id;
        
        productRow.innerHTML = `
            <td></td>
            <td>
                <div class="product-label">
                    <span>${product.label}</span>
                    <span class="product-color-badge">${product.color}</span>
                </div>
                <div style="font-size: 11px; color: #666;">${product.ref}</div>
            </td>
            <td style="font-weight: bold; text-align: right; vertical-align: top;">
                ${productInColis.quantite}
                ${coli.multiple_colis > 1 ? `<div style="font-size: 10px; color: #666;">×${coli.multiple_colis} = ${productInColis.quantite * coli.multiple_colis}</div>` : ''}
            </td>
            <td style="font-weight: bold; text-align: left; vertical-align: top;">
                ${product.length}×${product.width}
                <div style="font-size: 10px; color: #666;">${productInColis.poids_total?.toFixed(1) || '0.0'}kg</div>
            </td>
            <td class="${this.getWeightStatusClass(coli)}" style="text-align: center;">
                ${this.getStatusIcon(coli)}
            </td>
            <td>
                <button class="btn-small btn-edit" title="${this.config.translations.edit || 'Modifier'}" 
                        data-colis-id="${coli.id}" data-product-id="${product.id}">📝</button>
                <button class="btn-small btn-delete" title="${this.config.translations.delete || 'Supprimer'}" 
                        data-colis-id="${coli.id}" data-product-id="${product.id}">🗑️</button>
                ${index === 0 ? `<button class="btn-small btn-duplicate" title="Dupliquer colis" 
                                        data-colis-id="${coli.id}">×${coli.multiple_colis}</button>` : ''}
            </td>
        `;
        
        // Event listeners pour les boutons
        this.setupProductRowButtons(productRow, coli, product, productInColis);
        
        // Drop zone
        this.setupDropZone(productRow, coli.id);
        
        return productRow;
    }
    
    /**
     * Configure les boutons d'une ligne de produit
     */
    setupProductRowButtons(row, coli, product, productInColis) {
        const editBtn = row.querySelector('.btn-edit');
        const deleteBtn = row.querySelector('.btn-delete');
        const duplicateBtn = row.querySelector('.btn-duplicate');
        
        if (editBtn) {
            editBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const newQuantity = await this.showPrompt(
                    `Nouvelle quantité pour ${product.ref} :`,
                    productInColis.quantite.toString()
                );
                if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                    await this.updateProductQuantity(coli.id, product.id, parseInt(newQuantity));
                }
            });
        }
        
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const confirmed = await this.showConfirm(
                    `Supprimer ${product.ref} du colis ${coli.numero_colis} ?`
                );
                if (confirmed) {
                    await this.removeProductFromColis(coli.id, product.id);
                }
            });
        }
        
        if (duplicateBtn) {
            duplicateBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                await this.showDuplicateDialog(coli.id);
            });
        }
    }
    
    /**
     * Configure une zone de drop
     */
    setupDropZone(element, colisId) {
        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            element.style.background = '#e8f5e8';
        });
        
        element.addEventListener('dragleave', () => {
            element.style.background = '';
        });
        
        element.addEventListener('drop', async (e) => {
            e.preventDefault();
            element.style.background = '';
            
            if (this.draggedProduct) {
                await this.addProductToColis(colisId, this.draggedProduct.id, 1);
            }
        });
    }
    
    /**
     * Sélectionne un colis
     */
    selectColis(coli) {
        this.debugLog(`Sélection colis ${coli.id}`);
        this.selectedColis = coli;
        this.renderColisOverview();
        this.renderColisDetail();
    }
    
    /**
     * Rend le détail du colis sélectionné
     */
    renderColisDetail() {
        if (!this.colisDetail) return;
        
        if (!this.selectedColis) {
            this.colisDetail.innerHTML = `
                <div class="empty-state">
                    ${this.config.translations.emptyState}<br>
                    ${this.config.translations.createNewColis}
                </div>
            `;
            return;
        }
        
        const coli = this.selectedColis;
        const weightPercentage = (coli.poids_total / coli.poids_max) * 100;
        let weightStatus = 'ok';
        
        if (weightPercentage > 100) weightStatus = 'danger';
        else if (weightPercentage > 90) weightStatus = 'warning';
        
        const multipleSection = coli.multiple_colis > 1 ? 
            `<div class="duplicate-controls">
                <span>📦 Ce colis sera créé</span>
                <input type="number" value="${coli.multiple_colis}" min="1" max="100" 
                       class="duplicate-input" id="multipleInput">
                <span>fois identique(s)</span>
                <span style="margin-left: 10px; font-weight: bold;">
                    Total: ${(coli.poids_total * coli.multiple_colis).toFixed(1)} kg
                </span>
            </div>` : '';
        
        this.colisDetail.innerHTML = `
            <div class="colis-detail-header">
                <h3 class="colis-detail-title">📦 Colis ${coli.numero_colis}</h3>
                <button class="btn-delete-colis" id="deleteColisBtn">🗑️ ${this.config.translations.delete || 'Supprimer'}</button>
            </div>
            
            ${multipleSection}
            
            <div class="constraints-section">
                <div class="constraint-item">
                    <div class="constraint-label">Poids:</div>
                    <div class="constraint-values">
                        ${coli.poids_total.toFixed(1)} / ${coli.poids_max} kg
                    </div>
                    <div class="constraint-bar">
                        <div class="constraint-progress ${weightStatus}" style="width: ${Math.min(weightPercentage, 100)}%"></div>
                    </div>
                </div>
            </div>
            
            <div class="colis-content" id="colisContent">
                ${coli.products.map((p, index) => {
                    const product = this.products.find(prod => prod.id === p.product_id);
                    if (!product) return '';
                    return `
                        <div class="colis-line" draggable="true" data-line-index="${index}">
                            <span class="drag-handle">⋮⋮</span>
                            <span class="line-product">${product.ref} - ${product.label}</span>
                            <input type="number" class="line-quantity" value="${p.quantite}" min="1" 
                                   data-product-id="${p.product_id}">
                            <span class="line-weight">${(p.poids_total || 0).toFixed(1)} kg</span>
                            <button class="btn-remove-line" data-product-id="${p.product_id}">✕</button>
                        </div>
                    `;
                }).join('')}
                <div class="drop-hint">${this.config.translations.dragProductHere || 'Glissez un produit ici pour l\'ajouter'}</div>
            </div>
        `;
        
        // Event listeners
        this.setupColisDetailEventListeners();
    }
    
    /**
     * Configure les event listeners du détail du colis
     */
    setupColisDetailEventListeners() {
        // Bouton supprimer colis
        const deleteBtn = document.getElementById('deleteColisBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
                const confirmed = await this.showConfirm(this.config.translations.confirmDeleteColis);
                if (confirmed) {
                    await this.deleteColis(this.selectedColis.id);
                }
            });
        }
        
        // Input multiple
        const multipleInput = document.getElementById('multipleInput');
        if (multipleInput) {
            multipleInput.addEventListener('change', async (e) => {
                await this.updateColisMultiple(this.selectedColis.id, e.target.value);
            });
        }
        
        // Boutons supprimer ligne
        const removeLineBtns = this.colisDetail.querySelectorAll('.btn-remove-line');
        removeLineBtns.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const productId = parseInt(e.target.dataset.productId);
                const confirmed = await this.showConfirm(this.config.translations.confirmDeleteProduct);
                if (confirmed) {
                    await this.removeProductFromColis(this.selectedColis.id, productId);
                }
            });
        });
        
        // Inputs quantité
        const quantityInputs = this.colisDetail.querySelectorAll('.line-quantity');
        quantityInputs.forEach(input => {
            input.addEventListener('change', async (e) => {
                const productId = parseInt(e.target.dataset.productId);
                await this.updateProductQuantity(this.selectedColis.id, productId, e.target.value);
            });
        });
        
        // Drop zone du contenu
        const colisContent = document.getElementById('colisContent');
        if (colisContent) {
            this.setupDropZone(colisContent, this.selectedColis.id);
        }
    }
    
    /**
     * Ajoute un nouveau colis
     */
    async addNewColis() {
        try {
            this.debugLog('Ajout nouveau colis');
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_add_colis',
                    id: this.config.orderId,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(this.config.translations.colisCreated, 'success');
                await this.loadData(); // Recharger les données
            } else {
                this.showNotification('Erreur lors de la création du colis', 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de l\'ajout du colis:', error);
            this.showNotification('Erreur lors de la création du colis', 'error');
        }
    }
    
    /**
     * Supprime un colis
     */
    async deleteColis(colisId) {
        try {
            this.debugLog(`Suppression colis ${colisId}`);
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_delete_colis',
                    colis_id: colisId,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(this.config.translations.colisDeleted, 'success');
                this.selectedColis = null;
                await this.loadData();
            } else {
                this.showNotification('Erreur lors de la suppression du colis', 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de la suppression du colis:', error);
            this.showNotification('Erreur lors de la suppression du colis', 'error');
        }
    }
    
    /**
     * Ajoute un produit à un colis
     */
    async addProductToColis(colisId, productId, quantite) {
        try {
            this.debugLog(`Ajout produit ${productId} au colis ${colisId}`);
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_add_product',
                    colis_id: colisId,
                    product_id: productId,
                    quantite: quantite,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(this.config.translations.productAdded, 'success');
                await this.loadData();
            } else {
                this.showNotification(this.config.translations.insufficientQuantity, 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de l\'ajout du produit:', error);
            this.showNotification('Erreur lors de l\'ajout du produit', 'error');
        }
    }
    
    /**
     * Supprime un produit d'un colis
     */
    async removeProductFromColis(colisId, productId) {
        try {
            this.debugLog(`Suppression produit ${productId} du colis ${colisId}`);
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_remove_product',
                    colis_id: colisId,
                    product_id: productId,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(this.config.translations.productRemoved, 'success');
                await this.loadData();
            } else {
                this.showNotification('Erreur lors de la suppression du produit', 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de la suppression du produit:', error);
            this.showNotification('Erreur lors de la suppression du produit', 'error');
        }
    }
    
    /**
     * Met à jour la quantité d'un produit
     */
    async updateProductQuantity(colisId, productId, newQuantity) {
        try {
            this.debugLog(`Mise à jour quantité: colis ${colisId}, produit ${productId}, qté ${newQuantity}`);
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_update_quantity',
                    colis_id: colisId,
                    product_id: productId,
                    quantite: newQuantity,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(this.config.translations.quantityUpdated, 'success');
                await this.loadData();
            } else {
                this.showNotification(this.config.translations.insufficientQuantity, 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de la mise à jour de la quantité:', error);
            this.showNotification('Erreur lors de la mise à jour', 'error');
        }
    }
    
    /**
     * Met à jour le multiple d'un colis
     */
    async updateColisMultiple(colisId, multiple) {
        try {
            this.debugLog(`Mise à jour multiple colis ${colisId}: ${multiple}`);
            
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ficheproduction_update_multiple',
                    colis_id: colisId,
                    multiple: multiple,
                    token: this.config.token
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Multiple mis à jour', 'success');
                await this.loadData();
            } else {
                this.showNotification('Erreur lors de la mise à jour du multiple', 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de la mise à jour du multiple:', error);
            this.showNotification('Erreur lors de la mise à jour', 'error');
        }
    }
    
    /**
     * Affiche le dialogue de duplication
     */
    async showDuplicateDialog(colisId) {
        const coli = this.colis.find(c => c.id === colisId);
        if (!coli) return;
        
        const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${coli.multiple_colis} colis`;
        const newMultiple = await this.showPrompt(message, coli.multiple_colis.toString());
        
        if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
            await this.updateColisMultiple(colisId, parseInt(newMultiple));
        }
    }
    
    /**
     * Affiche une notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Suppression automatique
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }
    
    /**
     * Affiche une boîte de confirmation
     */
    showConfirm(message) {
        return new Promise((resolve) => {
            if (confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }
    
    /**
     * Affiche une boîte de saisie
     */
    showPrompt(message, defaultValue = '') {
        return new Promise((resolve) => {
            const result = prompt(message, defaultValue);
            resolve(result);
        });
    }
    
    /**
     * Obtient la classe CSS pour le statut du poids
     */
    getWeightStatusClass(coli) {
        const percentage = (coli.poids_total / coli.poids_max) * 100;
        if (percentage > 100) return 'error';
        if (percentage > 90) return 'warning';
        return '';
    }
    
    /**
     * Obtient l'icône de statut
     */
    getStatusIcon(coli) {
        const percentage = (coli.poids_total / coli.poids_max) * 100;
        if (percentage > 100) return '❌';
        if (percentage > 90) return '⚠️';
        return '✅';
    }
}

// Export pour utilisation globale
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ColisageManager;
} else {
    window.ColisageManager = ColisageManager;
}