
            return vignetteElement;
        }

        // Fonction pour créer un produit libre
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

        // Modale Colis Libre
        function showColisLibreModal() {
            const modal = document.getElementById('colisLibreModal');
            const itemsContainer = document.getElementById('colisLibreItems');
            
            // Réinitialiser le contenu
            itemsContainer.innerHTML = '';
            addColisLibreItem(); // Ajouter un premier élément

            modal.classList.add('show');
        }

        function addColisLibreItem() {
            const container = document.getElementById('colisLibreItems');
            const itemId = Date.now();
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'colis-libre-item';
            itemDiv.dataset.itemId = itemId;
            
            itemDiv.innerHTML = `
                <div class="colis-libre-fields">
                    <input type="text" class="libre-name" placeholder="Nom de l'élément (ex: Échantillon Bleu)" required>
                    <input type="number" class="libre-weight" placeholder="Poids (kg)" step="0.1" min="0" value="0.5" required>
                    <input type="number" class="libre-quantity" placeholder="Quantité" min="1" value="1" required>
                    <button type="button" class="btn-remove-libre-item">✕</button>
                </div>
            `;
            
            // Event listener pour supprimer l'élément
            const removeBtn = itemDiv.querySelector('.btn-remove-libre-item');
            removeBtn.addEventListener('click', () => {
                itemDiv.remove();
                // S'assurer qu'il reste au moins un élément
                if (container.children.length === 0) {
                    addColisLibreItem();
                }
            });
            
            container.appendChild(itemDiv);
        }

        async function createColisLibre() {
            const items = document.querySelectorAll('.colis-libre-item');
            const libreProducts = [];
            
            // Valider et récupérer les données
            for (const item of items) {
                const name = item.querySelector('.libre-name').value.trim();
                const weight = parseFloat(item.querySelector('.libre-weight').value);
                const quantity = parseInt(item.querySelector('.libre-quantity').value);
                
                if (!name || isNaN(weight) || weight < 0 || isNaN(quantity) || quantity < 1) {
                    await showConfirm('Veuillez remplir correctement tous les champs.');
                    return false;
                }
                
                libreProducts.push({
                    name: name,
                    weight: weight,
                    quantity: quantity
                });
            }
            
            if (libreProducts.length === 0) {
                await showConfirm('Veuillez ajouter au moins un élément.');
                return false;
            }
            
            // Créer le colis libre
            const newId = Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = Math.max(...colis.map(c => c.number), 0) + 1;
            
            const newColis = {
                id: newId,
                number: newNumber,
                products: [],
                totalWeight: 0,
                maxWeight: 25,
                status: 'ok',
                multiple: 1,
                isLibre: true // Marquer comme colis libre
            };

            // Ajouter chaque produit libre au colis
            libreProducts.forEach(libreData => {
                // Créer le produit libre et l'ajouter à la liste globale
                const libreProduct = createLibreProduct(libreData.name, libreData.weight);
                products.push(libreProduct);
                
                // Ajouter au colis
                newColis.products.push({
                    productId: libreProduct.id,
                    quantity: libreData.quantity,
                    weight: libreData.quantity * libreProduct.weight
                });
            });

            // Recalculer le poids total
            newColis.totalWeight = newColis.products.reduce((sum, p) => sum + p.weight, 0);

            colis.push(newColis);
            
            debugLog(`Colis libre créé avec ${libreProducts.length} éléments`);
            
            // Re-render et sélectionner le nouveau colis
            renderInventory();
            renderColisOverview();
            selectColis(newColis);
            updateSummaryTotals(); // Mettre à jour les totaux
            
            return true;
        }

        // Fonction de tri des produits
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

        // Modales custom
        function showConfirm(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const messageEl = document.getElementById('confirmMessage');
                const okBtn = document.getElementById('confirmOk');
                const cancelBtn = document.getElementById('confirmCancel');

                messageEl.textContent = message;
                modal.classList.add('show');

                const cleanup = () => {
                    modal.classList.remove('show');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                };

                const handleOk = () => {
                    cleanup();
                    resolve(true);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(false);
                };

                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
            });
        }

        function showPrompt(message, defaultValue = '') {
            return new Promise((resolve) => {
                const modal = document.getElementById('promptModal');
                const messageEl = document.getElementById('promptMessage');
                const inputEl = document.getElementById('promptInput');
                const okBtn = document.getElementById('promptOk');
                const cancelBtn = document.getElementById('promptCancel');

                messageEl.textContent = message;
                inputEl.value = defaultValue;
                modal.classList.add('show');
                
                // Focus sur l'input
                setTimeout(() => inputEl.focus(), 100);

                const cleanup = () => {
                    modal.classList.remove('show');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                    inputEl.removeEventListener('keypress', handleKeypress);
                };

                const handleOk = () => {
                    const value = inputEl.value.trim();
                    cleanup();
                    resolve(value || null);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(null);
                };

                const handleKeypress = (e) => {
                    if (e.key === 'Enter') {
                        handleOk();
                    } else if (e.key === 'Escape') {
                        handleCancel();
                    }
                };

                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
                inputEl.addEventListener('keypress', handleKeypress);
            });
        }

        // API AJAX Functions
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('token', TOKEN);
            formData.append('id', ORDER_ID);
            
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                debugLog(`API Call: ${action}`);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                debugLog(`Response: ${text.substring(0, 200)}...`);
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    debugLog(`JSON Parse Error: ${parseError.message}`);
                    return { success: false, error: 'Invalid JSON response' };
                }
            } catch (error) {
                debugLog('Erreur API: ' + error.message);
                return { success: false, error: error.message };
            }
        }

        async function loadData() {
            debugLog('Chargement des données (ordre commande + groupes produits)...');
            const result = await apiCall('ficheproduction_get_data');
            
            if (result && result.products) {
                // Les produits sont déjà dans l'ordre de la commande
                products = result.products;
                productGroups = result.product_groups || [];
                
                debugLog(`Chargé ${products.length} produits dans l'ordre de la commande`);
                debugLog(`Trouvé ${productGroups.length} groupes de produits`);
                
                populateProductGroupSelector();
                renderInventory();
                
                // Après avoir chargé les données de base, essayer de charger les données sauvegardées
                await loadSavedData();
            } else {
                debugLog('Erreur lors du chargement des données');
            }
        }

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

        // Gestion globale des zones de drop
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

        function deactivateDropZones() {
            debugLog('🔴 Désactivation des zones de drop');
            
            // Désactiver toutes les zones de drop
            const dropActiveElements = document.querySelectorAll('.drop-active');
            dropActiveElements.forEach(el => el.classList.remove('drop-active'));
            
            const dropZoneActive = document.querySelectorAll('.drop-zone-active');
            dropZoneActive.forEach(el => el.classList.remove('drop-zone-active'));
        }

        // Fonctions principales
        function addNewColis() {
            debugLog('Ajout nouveau colis');
            const newId = Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = Math.max(...colis.map(c => c.number), 0) + 1;
            
            const newColis = {
                id: newId,
                number: newNumber,
                products: [],
                totalWeight: 0,
                maxWeight: 25,
                status: 'ok',
                multiple: 1,
                isLibre: false
            };

            colis.push(newColis);
            renderColisOverview();
            selectColis(newColis);
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        async function deleteColis(colisId) {
            debugLog(`Tentative suppression colis ID: ${colisId}`);
            
            const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer ce colis ?');
            if (!confirmed) {
                debugLog('Suppression annulée par utilisateur');
                return;
            }

            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                debugLog('ERREUR: Colis non trouvé');
                await showConfirm('Erreur: Colis non trouvé');
                return;
            }
            
            debugLog(`Suppression colis: ${JSON.stringify(coliData)}`);
            
            // Remettre tous les produits dans l'inventaire (sauf les produits libres)
            coliData.products.forEach(p => {
                const product = products.find(prod => prod.id === p.productId);
                if (product && !product.isLibre) {
                    const quantityToRestore = p.quantity * coliData.multiple;
                    product.used -= quantityToRestore;
                    debugLog(`Remise en stock: ${product.ref} +${quantityToRestore}`);
                }
            });

            // Supprimer les produits libres de la liste globale
            if (coliData.isLibre) {
                coliData.products.forEach(p => {
                    const productIndex = products.findIndex(prod => prod.id === p.productId && prod.isLibre);
                    if (productIndex > -1) {
                        products.splice(productIndex, 1);
                        debugLog(`Produit libre supprimé: ${p.productId}`);
                    }
                });
            }

            // Supprimer le colis
            const colisIndex = colis.findIndex(c => c.id === colisId);
            if (colisIndex > -1) {
                colis.splice(colisIndex, 1);
                debugLog('Colis supprimé de la liste');
            }
            
            // Déselectionner si c'était le colis sélectionné
            if (selectedColis && selectedColis.id === colisId) {
                selectedColis = null;
                debugLog('Colis désélectionné');
            }

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals(); // Mettre à jour les totaux
            
            debugLog('Interface mise à jour après suppression');
        }

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

        function renderColisOverview() {
            const tbody = document.getElementById('colisTableBody');
            tbody.innerHTML = '';

            if (colis.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</td></tr>';
                return;
            }

            colis.forEach(c => {
                const weightPercentage = (c.totalWeight / c.maxWeight) * 100;
                let statusIcon = '✅';
                let statusClass = '';
                if (weightPercentage > 90) {
                    statusIcon = '⚠️';
                    statusClass = 'warning';
                } else if (weightPercentage > 100) {
                    statusIcon = '❌';
                    statusClass = 'error';
                }

                // Ligne d'en-tête pour le colis
                const headerRow = document.createElement('tr');
                headerRow.className = 'colis-group-header';
                if (c.isLibre) {
                    headerRow.classList.add('colis-libre');
                }
                headerRow.dataset.colisId = c.id;
                if (selectedColis && selectedColis.id === c.id) {
                    headerRow.classList.add('selected');
                }

                const totalColis = c.multiple;
                const leftText = totalColis > 1 ? `${totalColis} colis` : '1 colis';
                const colisType = c.isLibre ? 'LIBRE' : c.number;
                const rightText = `Colis ${colisType} (${c.products.length} produit${c.products.length > 1 ? 's' : ''}) - ${c.totalWeight.toFixed(1)} Kg ${statusIcon}`;

                headerRow.innerHTML = `
                    <td colspan="6">
                        <div class="colis-header-content">
                            <span class="colis-header-left">${c.isLibre ? '📦' : '📦'} ${leftText}</span>
                            <span class="colis-header-right">${rightText}</span>
                        </div>
                    </td>
                `;

                // Event listener pour sélectionner le colis
                headerRow.addEventListener('click', () => {
                    selectColis(c);
                });

                // Setup drop zone pour l'en-tête du colis (seulement pour colis normaux)
                if (!c.isLibre) {
                    setupDropZone(headerRow, c.id);
                }
                tbody.appendChild(headerRow);

                // Lignes pour chaque produit dans le colis
                if (c.products.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'colis-group-item';
                    if (c.isLibre) {
                        emptyRow.classList.add('colis-libre');
                    }
                    emptyRow.dataset.colisId = c.id;
                    emptyRow.innerHTML = `
                        <td></td>
                        <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                            Colis vide - ${c.isLibre ? 'Colis libre sans éléments' : 'Glissez des produits ici'}
                        </td>
                    `;
                    
                    if (!c.isLibre) {
                        setupDropZone(emptyRow, c.id);
                    }
                    tbody.appendChild(emptyRow);
                } else {
                    c.products.forEach((productInColis, index) => {
                        const product = products.find(p => p.id === productInColis.productId);
                        if (!product) return;

                        const productRow = document.createElement('tr');
                        productRow.className = 'colis-group-item';
                        if (c.isLibre) {
                            productRow.classList.add('colis-libre');
                        }
                        productRow.dataset.colisId = c.id;
                        productRow.dataset.productId = product.id;

                        const dimensionsDisplay = product.isLibre ? 
                            `Poids unit.: ${product.weight}kg` : 
                            `${product.length}×${product.width}`;

                        const colorDisplay = product.isLibre ? 
                            'LIBRE' : 
                            product.color;

                        productRow.innerHTML = `
                            <td></td>
                            <td>
                                <div class="product-label">
                                    <span>${product.name}</span>
                                    <span class="product-color-badge ${product.isLibre ? 'libre-badge' : ''}">${colorDisplay}</span>
                                </div>
                                ${product.ref_ligne ? `<div style="font-size: 10px; color: #888; font-style: italic;">Réf: ${product.ref_ligne}</div>` : ''}
                            </td>
                            <td style="font-weight: bold; text-align: right; vertical-align: top;">
                                ${productInColis.quantity}
                                ${c.multiple > 1 ? `<div style="font-size: 10px; color: #666;">×${c.multiple} = ${productInColis.quantity * c.multiple}</div>` : ''}
                            </td>
                            <td style="font-weight: bold; text-align: left; vertical-align: top;">
                                ${dimensionsDisplay}
                                <div style="font-size: 10px; color: #666;">${productInColis.weight.toFixed(1)}kg</div>
                            </td>
                            <td class="${statusClass}" style="text-align: center;">
                                ${statusIcon}
                            </td>
                            <td>
                                <button class="btn-small btn-edit" title="Modifier quantité" 
                                        data-colis-id="${c.id}" data-product-id="${product.id}">📝</button>
                                <button class="btn-small btn-delete" title="Supprimer" 
                                        data-colis-id="${c.id}" data-product-id="${product.id}">🗑️</button>
                                ${index === 0 ? `<button class="btn-small btn-duplicate" title="Dupliquer colis" 
                                                        data-colis-id="${c.id}">×${c.multiple}</button>` : ''}
                            </td>
                        `;

                        // Event listeners pour les boutons
                        const editBtn = productRow.querySelector('.btn-edit');
                        const deleteBtn = productRow.querySelector('.btn-delete');
                        const duplicateBtn = productRow.querySelector('.btn-duplicate');

                        if (editBtn) {
                            editBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                const stockInfo = product.isLibre ? '' : `\n(Stock disponible: ${product.total - product.used})`;
                                const newQuantity = await showPrompt(
                                    `Nouvelle quantité pour ${product.name} :${stockInfo}`,
                                    productInColis.quantity.toString()
                                );
                                if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                                    updateProductQuantity(c.id, product.id, parseInt(newQuantity));
                                }
                            });
                        }

                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                const confirmed = await showConfirm(
                                    `Supprimer ${product.name} du colis ${c.isLibre ? 'libre' : c.number} ?`
                                );
                                if (confirmed) {
                                    removeProductFromColis(c.id, product.id);
                                }
                            });
                        }

                        if (duplicateBtn) {
                            duplicateBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                await showDuplicateDialog(c.id);
                            });
                        }

                        if (!c.isLibre) {
                            setupDropZone(productRow, c.id);
                        }
                        tbody.appendChild(productRow);
                    });
                }
            });
        }

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

        function selectColis(coliData) {
            debugLog(`Sélection colis ${coliData.id}`);
            selectedColis = coliData;
            renderColisOverview();
            renderColisDetail();
        }

        function renderColisDetail() {
            const container = document.getElementById('colisDetail');
            
            if (!selectedColis) {
                container.innerHTML = '<div class="empty-state">Sélectionnez un colis pour voir les détails</div>';
                return;
            }

            const weightPercentage = (selectedColis.totalWeight / selectedColis.maxWeight) * 100;
            let weightStatus = 'ok';
            if (weightPercentage > 90) weightStatus = 'danger';
            else if (weightPercentage > 70) weightStatus = 'warning';

            const multipleSection = selectedColis.multiple > 1 ? 
                `<div class="duplicate-controls">
                    <span>📦 Ce colis sera créé</span>
                    <input type="number" value="${selectedColis.multiple}" min="1" max="100" 
                           class="duplicate-input" id="multipleInput">
                    <span>fois identique(s)</span>
                    <span style="margin-left: 10px; font-weight: bold;">
                        Total: ${(selectedColis.totalWeight * selectedColis.multiple).toFixed(1)} kg
                    </span>
                </div>` : '';

            const colisTypeText = selectedColis.isLibre ? 'Colis Libre' : `Colis ${selectedColis.number}`;
            const colisTypeIcon = selectedColis.isLibre ? '📦🆓' : '📦';

            container.innerHTML = `
                <div class="colis-detail-header">
                    <h3 class="colis-detail-title">${colisTypeIcon} ${colisTypeText}</h3>
                    <button class="btn-delete-colis" id="deleteColisBtn">🗑️ Supprimer</button>
                </div>

                ${multipleSection}

                <div class="constraints-section">
                    <div class="constraint-item">
                        <div class="constraint-label">Poids:</div>
                        <div class="constraint-values">
                            ${selectedColis.totalWeight.toFixed(1)} / ${selectedColis.maxWeight} kg
                        </div>
                        <div class="constraint-bar">
                            <div class="constraint-progress ${weightStatus}" style="width: ${Math.min(weightPercentage, 100)}%"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 10px; font-weight: bold;">Produits dans ce colis:</div>
                <div class="colis-content" id="colisContent" style="border: 2px dashed #ddd; border-radius: 8px; min-height: 150px; padding: 15px; position: relative;">
                    <div class="drop-hint" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; font-style: italic; pointer-events: none;">
                        ${selectedColis.products.length === 0 ? (selectedColis.isLibre ? 'Colis libre vide' : 'Glissez un produit ici pour l\'ajouter') : ''}
                    </div>
                </div>
            `;

            // Ajouter les vignettes dans la zone de contenu
            const colisContent = document.getElementById('colisContent');
            if (selectedColis.products.length > 0) {
                selectedColis.products.forEach((p, index) => {
                    const product = products.find(prod => prod.id === p.productId);
                    if (!product) return;

                    const vignette = createProductVignette(product, true, p.quantity);
                    
                    // Ajouter bouton supprimer
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-remove-line';
                    removeBtn.textContent = '✕';
                    removeBtn.dataset.productId = p.productId;
                    removeBtn.style.position = 'absolute';
                    removeBtn.style.top = '5px';
                    removeBtn.style.left = '5px';
                    vignette.style.position = 'relative';
                    vignette.appendChild(removeBtn);

                    colisContent.appendChild(vignette);
                });
            }

            // Event listeners pour les boutons et inputs
            const deleteBtn = document.getElementById('deleteColisBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    await deleteColis(selectedColis.id);
                });
            }

            const multipleInput = document.getElementById('multipleInput');
            if (multipleInput) {
                multipleInput.addEventListener('change', async (e) => {
                    await updateColisMultiple(selectedColis.id, e.target.value);
                });
            }

            const removeLineBtns = container.querySelectorAll('.btn-remove-line');
            removeLineBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = parseInt(e.target.dataset.productId);
                    removeProductFromColis(selectedColis.id, productId);
                });
            });

            const quantityInputs = container.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', async (e) => {
                    const productId = parseInt(e.target.dataset.productId);
                    updateProductQuantity(selectedColis.id, productId, e.target.value);
                });
            });

            // Setup drop zone pour le contenu du colis (seulement pour colis normaux)
            if (colisContent && !selectedColis.isLibre) {
                setupDropZone(colisContent, selectedColis.id);
            }
        }

        function addProductToColis(colisId, productId, quantity) {
            debugLog(`🔧 Ajout produit ${productId} (qté: ${quantity}) au colis ${colisId}`);
            
            const coliData = colis.find(c => c.id === colisId);
            const product = products.find(p => p.id === productId);
            
            if (!coliData || !product) {
                debugLog('ERREUR: Colis ou produit non trouvé');
                return;
            }

            // Ne pas permettre d'ajouter des produits normaux aux colis libres
            if (coliData.isLibre) {
                alert('Impossible d\'ajouter des produits de la commande à un colis libre.');
                return;
            }

            // Vérifier la disponibilité
            const available = product.total - product.used;
            if (available < quantity) {
                alert(`Quantité insuffisante ! Disponible: ${available}, Demandé: ${quantity}`);
                return;
            }

            // Vérifier si le produit est déjà dans le colis
            const existingProduct = coliData.products.find(p => p.productId === productId);
            
            if (existingProduct) {
                existingProduct.quantity += quantity;
                existingProduct.weight = existingProduct.quantity * product.weight;
            } else {
                coliData.products.push({
                    productId: productId,
                    quantity: quantity,
                    weight: quantity * product.weight
                });
            }

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Mettre à jour les quantités utilisées
            product.used += quantity * coliData.multiple;

            // Re-render
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
            updateSummaryTotals();
        }

        function removeProductFromColis(colisId, productId) {
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            
            if (!coliData || !productInColis) {
                return;
            }

            // Remettre les quantités dans l'inventaire
            const product = products.find(p => p.id === productId);
            if (product && !product.isLibre) {
                product.used -= productInColis.quantity * coliData.multiple;
            }

            // Supprimer le produit du colis
            const productIndex = coliData.products.findIndex(p => p.productId === productId);
            if (productIndex > -1) {
                coliData.products.splice(productIndex, 1);
            }
            
            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals();
        }

        function updateProductQuantity(colisId, productId, newQuantity) {
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            const product = products.find(p => p.id === productId);
            
            if (!productInColis || !product || !coliData) {
                return;
            }

            const oldQuantity = productInColis.quantity;
            const quantityDiff = parseInt(newQuantity) - oldQuantity;

            // Pour les produits libres, pas de vérification de stock
            if (product.isLibre) {
                productInColis.quantity = parseInt(newQuantity);
                productInColis.weight = productInColis.quantity * product.weight;
                
                coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);
                
                renderInventory();
                renderColisOverview();
                renderColisDetail();
                updateSummaryTotals();
                return;
            }

            // Vérifier la disponibilité pour les produits normaux
            const totalQuantityNeeded = quantityDiff * coliData.multiple;
            const available = product.total - product.used;
            
            if (totalQuantityNeeded > available) {
                alert(`Quantité insuffisante ! Disponible: ${available}, Besoin: ${totalQuantityNeeded}`);
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                if (input) input.value = oldQuantity;
                return;
            }

            // Mettre à jour les quantités
            productInColis.quantity = parseInt(newQuantity);
            productInColis.weight = productInColis.quantity * product.weight;
            product.used += totalQuantityNeeded;

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals();
        }

        async function showDuplicateDialog(colisId) {
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                await showConfirm('Erreur: Colis non trouvé');
                return;
            }

            const currentMultiple = coliData.multiple || 1;
            const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${currentMultiple} colis`;
            const newMultiple = await showPrompt(message, currentMultiple.toString());
            
            if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
                updateColisMultiple(colisId, parseInt(newMultiple));
            } else if (newMultiple !== null) {
                await showConfirm('Veuillez saisir un nombre entier positif');
            }
        }

        async function updateColisMultiple(colisId, multiple) {
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                return;
            }

            const oldMultiple = coliData.multiple;
            const newMultiple = parseInt(multiple);
            
            if (isNaN(newMultiple) || newMultiple < 1) {
                await showConfirm('Le nombre de colis doit être un entier positif');
                return;
            }

            // Calculer la différence pour ajuster les quantités utilisées
            const multipleDiff = newMultiple - oldMultiple;
            
            // Mettre à jour les quantités utilisées pour chaque produit (sauf libres)
            for (const p of coliData.products) {
                const product = products.find(prod => prod.id === p.productId);
                if (product && !product.isLibre) {
                    product.used += p.quantity * multipleDiff;
                    
                    // Vérifier qu'on ne dépasse pas le total disponible
                    if (product.used > product.total) {
                        await showConfirm(`Attention: ${product.ref} - Quantité dépassée! Utilisé: ${product.used}, Total: ${product.total}`);
                        // Revenir à l'ancienne valeur
                        product.used -= p.quantity * multipleDiff;
                        return;
                    }
                }
            }

            coliData.multiple = newMultiple;
            
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
            updateSummaryTotals();
        }

        function setupEventListeners() {
            debugLog('Configuration des event listeners');
            
            // Recherche
            const searchBox = document.getElementById('searchBox');
            if (searchBox) {
                searchBox.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const productItems = document.querySelectorAll('.product-item');
                    
                    productItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(searchTerm) ? 'block' : 'none';
                    });
                });
            }

            // Sélecteur de groupe de produits
            const productGroupSelect = document.getElementById('productGroupSelect');
            if (productGroupSelect) {
                productGroupSelect.addEventListener('change', function(e) {
                    currentProductGroup = e.target.value;
                    renderInventory();
                });
            }

            // Sélecteur de tri
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function(e) {
                    currentSort = e.target.value;
                    renderInventory();
                });
            }

            // Bouton Nouveau Colis
            const addNewColisBtn = document.getElementById('addNewColisBtn');
            if (addNewColisBtn) {
                addNewColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    addNewColis();
                });
            }

            // Bouton Nouveau Colis Libre
            const addNewColisLibreBtn = document.getElementById('addNewColisLibreBtn');
            if (addNewColisLibreBtn) {
                addNewColisLibreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showColisLibreModal();
                });
            }

            // Event listeners pour la modale colis libre
            const colisLibreOk = document.getElementById('colisLibreOk');
            const colisLibreCancel = document.getElementById('colisLibreCancel');
            const addColisLibreItemBtn = document.getElementById('addColisLibreItem');

            if (colisLibreOk) {
                colisLibreOk.addEventListener('click', async () => {
                    const success = await createColisLibre();
                    if (success) {
                        document.getElementById('colisLibreModal').classList.remove('show');
                    }
                });
            }

            if (colisLibreCancel) {
                colisLibreCancel.addEventListener('click', () => {
                    document.getElementById('colisLibreModal').classList.remove('show');
                });
            }

            if (addColisLibreItemBtn) {
                addColisLibreItemBtn.addEventListener('click', addColisLibreItem);
            }

            // Affichage/masquage de la console de debug
            const header = document.querySelector('.header h1');
            if (header) {
                header.addEventListener('dblclick', function() {
                    const debugConsole = document.getElementById('debugConsole');
                    if (debugConsole) {
                        debugConsole.style.display = debugConsole.style.display === 'none' ? 'block' : 'none';
                    }
                });
            }
        }

        // Script pour la fonction d'impression
        function preparePrint() {
            var originalTitle = document.title;
            document.title = 'Fiche de Production - <?php echo $object->ref; ?>';
            window.print();
            setTimeout(function() {
                document.title = originalTitle;
            }, 1000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM chargé, initialisation...');
            debugLog('🆕 NOUVEAU : Fonctionnalité de sauvegarde ajoutée !');
            debugLog('📋 NOUVEAU : Chargement automatique des données sauvegardées');
            
            renderInventory();
            renderColisOverview();
            setupEventListeners();
            loadData(); // Charge les données de base ET les données sauvegardées
            updateSummaryTotals();
            
            debugLog('Initialisation terminée');
            debugLog('Double-cliquez sur le titre pour afficher/masquer cette console');
        });
</script>

<?php
print '</div>'; // End fichecenter
print dol_get_fiche_end();

llxFooter();
$db->close();
?>
