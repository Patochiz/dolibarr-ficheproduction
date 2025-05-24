over sur colis ${colisId}`);
                }
            });

            element.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (draggedProduct && isDragging) {
                    debugLog(`📍 Drop sur colis ${colisId} - Produit: ${draggedProduct.ref} (ordre: ${draggedProduct.line_order})`);
                    addProductToColis(colisId, draggedProduct.id, 1);
                } else {
                    debugLog(`❌ Drop échoué - draggedProduct: ${!!draggedProduct}, isDragging: ${isDragging}`);
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

                    // Créer une vignette identique à l'inventaire avec input quantité
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
            
            // Bouton supprimer colis
            const deleteBtn = document.getElementById('deleteColisBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    debugLog(`Bouton supprimer colis cliqué pour colis ${selectedColis.id}`);
                    await deleteColis(selectedColis.id);
                });
            }

            // Input pour les multiples
            const multipleInput = document.getElementById('multipleInput');
            if (multipleInput) {
                multipleInput.addEventListener('change', async (e) => {
                    await updateColisMultiple(selectedColis.id, e.target.value);
                });
            }

            // Boutons supprimer ligne (sur les vignettes)
            const removeLineBtns = container.querySelectorAll('.btn-remove-line');
            removeLineBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = parseInt(e.target.dataset.productId);
                    debugLog(`Bouton supprimer ligne cliqué pour produit ${productId}`);
                    removeProductFromColis(selectedColis.id, productId);
                });
            });

            // Inputs quantité (sur les vignettes)
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

            // Vérifier la disponibilité (basée sur extrafield "nombre")
            const available = product.total - product.used;
            if (available < quantity) {
                alert(`Quantité insuffisante ! Disponible (extrafield "nombre"): ${available}, Demandé: ${quantity}`);
                return;
            }

            // Vérifier si le produit est déjà dans le colis
            const existingProduct = coliData.products.find(p => p.productId === productId);
            
            if (existingProduct) {
                existingProduct.quantity += quantity;
                existingProduct.weight = existingProduct.quantity * product.weight;
                debugLog(`✅ Quantité mise à jour pour ${product.ref}: ${existingProduct.quantity}`);
            } else {
                coliData.products.push({
                    productId: productId,
                    quantity: quantity,
                    weight: quantity * product.weight
                });
                debugLog(`✅ Nouveau produit ajouté: ${product.ref}`);
            }

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Mettre à jour les quantités utilisées (tenir compte des multiples)
            product.used += quantity * coliData.multiple;
            debugLog(`📊 Stock mis à jour ${product.ref}: ${product.used}/${product.total} (extrafield nombre)`);

            // Re-render
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
            updateSummaryTotals(); // Mettre à jour les totaux
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
                    debugLog(`Changement groupe produit: ${currentProductGroup}`);
                    renderInventory();
                });
            }

            // Sélecteur de tri
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function(e) {
                    currentSort = e.target.value;
                    debugLog(`Changement tri: ${currentSort}`);
                    renderInventory();
                });
            }

            // Bouton Nouveau Colis
            const addNewColisBtn = document.getElementById('addNewColisBtn');
            if (addNewColisBtn) {
                addNewColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton nouveau colis cliqué');
                    addNewColis();
                });
            }

            // Bouton Nouveau Colis Libre
            const addNewColisLibreBtn = document.getElementById('addNewColisLibreBtn');
            if (addNewColisLibreBtn) {
                addNewColisLibreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton nouveau colis libre cliqué');
                    showColisLibreModal();
                });
            }

            // Bouton Sauvegarder
            const saveColisBtn = document.getElementById('saveColisBtn');
            if (saveColisBtn) {
                saveColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton sauvegarder cliqué');
                    saveColisage();
                });
            }

            // Bouton Charger
            const loadColisBtn = document.getElementById('loadColisBtn');
            if (loadColisBtn) {
                loadColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton charger cliqué');
                    loadColisage();
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

            // Affichage/masquage de la console de debug (double-clic sur le titre)
            const header = document.querySelector('.header h1');
            if (header) {
                header.addEventListener('dblclick', function() {
                    const debugConsole = document.getElementById('debugConsole');
                    if (debugConsole) {
                        debugConsole.style.display = debugConsole.style.display === 'none' ? 'block' : 'none';
                    }
                });
            }
            
            debugLog('Event listeners configurés');
        }

        // Script pour la fonction d'impression
        function preparePrint() {
            // Sauvegarde l'état actuel de la page
            var originalTitle = document.title;
            
            // Modifie le titre pour l'impression
            document.title = 'Fiche de Production - <?php echo $object->ref; ?>';
            
            // Lance l'impression
            window.print();
            
            // Restaure le titre original après l'impression
            setTimeout(function() {
                document.title = originalTitle;
            }, 1000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM chargé, initialisation...');
            debugLog('🆕 NOUVEAU : Fonctionnalité complète de sauvegarde/chargement implémentée !');
            debugLog('🆕 NOUVEAU : Support des produits libres en base de données !');
            debugLog('📋 NOUVEAU : Tableau récapitulatif des informations de commande ajouté !');
            
            renderInventory();
            renderColisOverview();
            setupEventListeners();
            loadData();
            updateSummaryTotals(); // Initialiser les totaux
            
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
