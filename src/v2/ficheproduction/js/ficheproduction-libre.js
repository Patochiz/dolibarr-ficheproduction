/**
 * FicheProduction v2.0 - Libre Module
 * Gestion spécifique des colis libres
 */

(function() {
    'use strict';

    // ============================================================================
    // MODULE LIBRE
    // ============================================================================

    const LibreModule = {
        
        /**
         * Initialisation du module libre
         */
        initialize() {
            debugLog('🆓 Initialisation du module Libre (colis libres)');
            this.setupEventListeners();
        },

        /**
         * Configuration des écouteurs d'événements spécifiques aux colis libres
         */
        setupEventListeners() {
            // Bouton Nouveau Colis Libre
            const addNewColisLibreBtn = document.getElementById('addNewColisLibreBtn');
            if (addNewColisLibreBtn) {
                addNewColisLibreBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showColisLibreModal();
                });
            }

            // Event listeners pour la modale colis libre
            this.setupModalEvents();
        },

        /**
         * Configurer les événements de la modale colis libre
         */
        setupModalEvents() {
            const colisLibreOk = document.getElementById('colisLibreOk');
            const colisLibreCancel = document.getElementById('colisLibreCancel');
            const addColisLibreItemBtn = document.getElementById('addColisLibreItem');

            if (colisLibreOk) {
                colisLibreOk.addEventListener('click', async () => {
                    const success = await this.createColisLibre();
                    if (success) {
                        const modal = document.getElementById('colisLibreModal');
                        if (modal) {
                            modal.classList.remove('show');
                        }
                    }
                });
            }

            if (colisLibreCancel) {
                colisLibreCancel.addEventListener('click', () => {
                    const modal = document.getElementById('colisLibreModal');
                    if (modal) {
                        modal.classList.remove('show');
                    }
                });
            }

            if (addColisLibreItemBtn) {
                addColisLibreItemBtn.addEventListener('click', () => {
                    this.addColisLibreItem();
                });
            }
        },

        /**
         * Afficher la modale de création de colis libre
         */
        showColisLibreModal() {
            const modal = document.getElementById('colisLibreModal');
            const itemsContainer = document.getElementById('colisLibreItems');
            
            if (!modal || !itemsContainer) {
                debugLog('⚠️ Éléments de modale colis libre manquants');
                return;
            }
            
            // Réinitialiser le contenu
            itemsContainer.innerHTML = '';
            this.addColisLibreItem(); // Ajouter un premier élément

            modal.classList.add('show');
            debugLog('🆓 Modale colis libre affichée');
        },

        /**
         * Ajouter un élément au formulaire de colis libre
         */
        addColisLibreItem() {
            const container = document.getElementById('colisLibreItems');
            if (!container) {
                debugLog('⚠️ Container colisLibreItems non trouvé');
                return;
            }
            
            const itemId = Date.now() + Math.random();
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'colis-libre-item';
            itemDiv.dataset.itemId = itemId;
            
            itemDiv.innerHTML = `
                <div class="colis-libre-fields" style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="text" class="libre-name" placeholder="Nom de l'élément (ex: Échantillon Bleu)" 
                           style="flex: 2; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" required>
                    <input type="number" class="libre-weight" placeholder="Poids (kg)" step="0.1" min="0" value="0.5" 
                           style="width: 100px; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" required>
                    <input type="number" class="libre-quantity" placeholder="Quantité" min="1" value="1" 
                           style="width: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" required>
                    <button type="button" class="btn-remove-libre-item" 
                            style="background: #f44336; color: white; border: none; border-radius: 3px; padding: 8px; cursor: pointer;">✕</button>
                </div>
            `;
            
            // Event listener pour supprimer l'élément
            const removeBtn = itemDiv.querySelector('.btn-remove-libre-item');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    itemDiv.remove();
                    // S'assurer qu'il reste au moins un élément
                    if (container.children.length === 0) {
                        this.addColisLibreItem();
                    }
                });
            }
            
            container.appendChild(itemDiv);
            
            // Focus sur le champ nom du nouvel élément
            const nameInput = itemDiv.querySelector('.libre-name');
            if (nameInput) {
                setTimeout(() => nameInput.focus(), 100);
            }
            
            debugLog('➕ Élément libre ajouté au formulaire');
        },

        /**
         * Valider les données du formulaire colis libre
         */
        validateColisLibreData() {
            const items = document.querySelectorAll('.colis-libre-item');
            const libreProducts = [];
            const errors = [];
            
            // Valider et récupérer les données
            items.forEach((item, index) => {
                const name = item.querySelector('.libre-name')?.value?.trim() || '';
                const weightStr = item.querySelector('.libre-weight')?.value || '0';
                const quantityStr = item.querySelector('.libre-quantity')?.value || '1';
                
                const weight = parseFloat(weightStr);
                const quantity = parseInt(quantityStr);
                
                // Validations
                if (!name) {
                    errors.push(`Élément ${index + 1}: Le nom est requis`);
                }
                
                if (isNaN(weight) || weight < 0) {
                    errors.push(`Élément ${index + 1}: Le poids doit être un nombre positif`);
                }
                
                if (isNaN(quantity) || quantity < 1) {
                    errors.push(`Élément ${index + 1}: La quantité doit être un entier positif`);
                }
                
                // Si pas d'erreur, ajouter à la liste
                if (name && !isNaN(weight) && weight >= 0 && !isNaN(quantity) && quantity >= 1) {
                    libreProducts.push({
                        name: name,
                        weight: weight,
                        quantity: quantity
                    });
                }
            });
            
            return {
                isValid: errors.length === 0 && libreProducts.length > 0,
                errors: errors,
                products: libreProducts
            };
        },

        /**
         * Créer un colis libre
         */
        async createColisLibre() {
            const validation = this.validateColisLibreData();
            
            if (!validation.isValid) {
                const ui = FicheProduction.ui;
                const message = validation.errors.length > 0 ? 
                    validation.errors.join('\n') : 
                    'Veuillez ajouter au moins un élément valide.';
                
                if (ui && ui.showError) {
                    await ui.showError(message);
                } else {
                    alert(message);
                }
                return false;
            }
            
            debugLog(`🆓 Création colis libre avec ${validation.products.length} éléments`);
            
            // Créer le colis libre
            const utils = FicheProduction.utils;
            const colis = FicheProduction.data.colis();
            
            const newId = utils ? utils.generateColisId() : Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = utils ? utils.generateColisNumber() : Math.max(...colis.map(c => c.number), 0) + 1;
            
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
            const products = FicheProduction.data.products();
            
            validation.products.forEach(libreData => {
                // Créer le produit libre et l'ajouter à la liste globale
                const libreProduct = this.createLibreProduct(libreData.name, libreData.weight);
                products.push(libreProduct);
                
                // Ajouter au colis
                newColis.products.push({
                    productId: libreProduct.id,
                    quantity: libreData.quantity,
                    weight: libreData.quantity * libreProduct.weight
                });
            });

            // Recalculer le poids total
            newColis.totalWeight = newColis.products.reduce((sum, p) => sum + (p.weight || 0), 0);

            // Ajouter à la liste des colis
            FicheProduction.data.setProducts(products);
            FicheProduction.data.addColis(newColis);
            
            debugLog(`✅ Colis libre créé: ID=${newId}, ${validation.products.length} éléments, poids total=${newColis.totalWeight.toFixed(1)}kg`);
            
            // Re-render et sélectionner le nouveau colis
            if (FicheProduction.inventory && FicheProduction.inventory.render) {
                FicheProduction.inventory.render();
            }
            
            if (FicheProduction.colis) {
                if (FicheProduction.colis.renderOverview) {
                    FicheProduction.colis.renderOverview();
                }
                if (FicheProduction.colis.selectColis) {
                    FicheProduction.colis.selectColis(newColis);
                }
            }
            
            if (FicheProduction.utils && FicheProduction.utils.updateSummaryTotals) {
                FicheProduction.utils.updateSummaryTotals();
            }
            
            // Afficher un message de succès
            if (FicheProduction.ui && FicheProduction.ui.showToast) {
                FicheProduction.ui.showToast(`Colis libre créé avec ${validation.products.length} éléments`, 'success');
            }
            
            return true;
        },

        /**
         * Créer un produit libre
         */
        createLibreProduct(name, weight) {
            const products = FicheProduction.data.products();
            const newId = Math.max(...products.map(p => p.id), 10000) + 1;
            
            return {
                id: newId,
                name: name,
                weight: parseFloat(weight) || 0,
                isLibre: true,
                total: 9999, // Pas de limite pour les produits libres
                used: 0,
                ref: `LIBRE_${newId}`,
                color: 'LIBRE'
            };
        },

        /**
         * Valider un colis libre existant
         */
        validateExistingColisLibre(colis) {
            if (!colis || !colis.isLibre) {
                return { isValid: false, reason: 'Ce n\'est pas un colis libre' };
            }
            
            if (!colis.products || colis.products.length === 0) {
                return { isValid: false, reason: 'Le colis libre est vide' };
            }
            
            const products = FicheProduction.data.products();
            const invalidProducts = colis.products.filter(p => {
                const product = products.find(prod => prod.id === p.productId);
                return !product || !product.isLibre;
            });
            
            if (invalidProducts.length > 0) {
                return { 
                    isValid: false, 
                    reason: `${invalidProducts.length} produit(s) libre(s) introuvable(s) dans le colis` 
                };
            }
            
            return { isValid: true };
        },

        /**
         * Dupliquer un colis libre
         */
        duplicateColisLibre(originalColisId) {
            const colis = FicheProduction.data.colis();
            const originalColis = colis.find(c => c.id === originalColisId);
            
            if (!originalColis || !originalColis.isLibre) {
                debugLog('❌ Colis libre à dupliquer non trouvé');
                return null;
            }
            
            const validation = this.validateExistingColisLibre(originalColis);
            if (!validation.isValid) {
                debugLog(`❌ Validation colis libre échouée: ${validation.reason}`);
                return null;
            }
            
            const utils = FicheProduction.utils;
            const newId = utils ? utils.generateColisId() : Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = utils ? utils.generateColisNumber() : Math.max(...colis.map(c => c.number), 0) + 1;
            
            // Créer une copie profonde du colis original
            const duplicatedColis = {
                id: newId,
                number: newNumber,
                products: [...originalColis.products], // Copie des références produits
                totalWeight: originalColis.totalWeight,
                maxWeight: originalColis.maxWeight,
                status: originalColis.status,
                multiple: 1, // Réinitialiser le multiple
                isLibre: true
            };
            
            FicheProduction.data.addColis(duplicatedColis);
            
            debugLog(`✅ Colis libre dupliqué: ${originalColisId} → ${newId}`);
            
            return duplicatedColis;
        },

        /**
         * Modifier un élément libre dans un colis
         */
        async editLibreElement(colisId, productId) {
            const colis = FicheProduction.data.colis();
            const coliData = colis.find(c => c.id === colisId);
            
            if (!coliData || !coliData.isLibre) {
                debugLog('❌ Colis libre non trouvé pour modification');
                return false;
            }
            
            const products = FicheProduction.data.products();
            const product = products.find(p => p.id === productId && p.isLibre);
            const productInColis = coliData.products.find(p => p.productId === productId);
            
            if (!product || !productInColis) {
                debugLog('❌ Produit libre non trouvé pour modification');
                return false;
            }
            
            const ui = FicheProduction.ui;
            
            // Demander le nouveau nom
            const newName = ui && ui.showPrompt ?
                await ui.showPrompt('Nouveau nom pour cet élément libre:', product.name) :
                prompt('Nouveau nom pour cet élément libre:', product.name);
                
            if (newName === null) return false; // Annulé
            
            // Demander le nouveau poids
            const newWeight = ui && ui.showPrompt ?
                await ui.showPrompt('Nouveau poids (kg):', product.weight.toString()) :
                prompt('Nouveau poids (kg):', product.weight.toString());
                
            if (newWeight === null) return false; // Annulé
            
            const weightValue = parseFloat(newWeight);
            if (isNaN(weightValue) || weightValue < 0) {
                const message = 'Le poids doit être un nombre positif';
                if (ui && ui.showError) {
                    await ui.showError(message);
                } else {
                    alert(message);
                }
                return false;
            }
            
            // Mettre à jour le produit
            product.name = newName.trim() || product.name;
            product.weight = weightValue;
            
            // Recalculer le poids dans le colis
            productInColis.weight = productInColis.quantity * product.weight;
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + (p.weight || 0), 0);
            
            // Mettre à jour les données
            FicheProduction.data.setProducts(products);
            FicheProduction.data.setColis(colis);
            
            // Re-render
            if (FicheProduction.inventory && FicheProduction.inventory.render) {
                FicheProduction.inventory.render();
            }
            
            if (FicheProduction.colis) {
                if (FicheProduction.colis.renderOverview) {
                    FicheProduction.colis.renderOverview();
                }
                if (FicheProduction.colis.renderDetail) {
                    FicheProduction.colis.renderDetail();
                }
            }
            
            debugLog(`✅ Élément libre modifié: ${product.name}, ${product.weight}kg`);
            
            if (ui && ui.showToast) {
                ui.showToast('Élément libre modifié avec succès', 'success');
            }
            
            return true;
        },

        /**
         * Supprimer tous les colis libres
         */
        async deleteAllColisLibres() {
            const colis = FicheProduction.data.colis();
            const colisLibres = colis.filter(c => c.isLibre);
            
            if (colisLibres.length === 0) {
                const ui = FicheProduction.ui;
                const message = 'Aucun colis libre à supprimer';
                
                if (ui && ui.showConfirm) {
                    await ui.showConfirm(message);
                } else {
                    alert(message);
                }
                return;
            }
            
            const ui = FicheProduction.ui;
            const confirmed = ui && ui.showConfirm ?
                await ui.showConfirm(`Supprimer tous les ${colisLibres.length} colis libres ?`) :
                confirm(`Supprimer tous les ${colisLibres.length} colis libres ?`);
                
            if (!confirmed) return;
            
            // Supprimer tous les colis libres
            const remainingColis = colis.filter(c => !c.isLibre);
            
            // Supprimer tous les produits libres
            const products = FicheProduction.data.products();
            const remainingProducts = products.filter(p => !p.isLibre);
            
            // Réinitialiser les données
            FicheProduction.data.setColis(remainingColis);
            FicheProduction.data.setProducts(remainingProducts);
            
            // Désélectionner si un colis libre était sélectionné
            const selectedColis = FicheProduction.data.selectedColis();
            if (selectedColis && selectedColis.isLibre) {
                FicheProduction.data.setSelectedColis(null);
            }
            
            // Re-render
            if (FicheProduction.inventory && FicheProduction.inventory.render) {
                FicheProduction.inventory.render();
            }
            
            if (FicheProduction.colis) {
                if (FicheProduction.colis.renderOverview) {
                    FicheProduction.colis.renderOverview();
                }
                if (FicheProduction.colis.renderDetail) {
                    FicheProduction.colis.renderDetail();
                }
            }
            
            if (FicheProduction.utils && FicheProduction.utils.updateSummaryTotals) {
                FicheProduction.utils.updateSummaryTotals();
            }
            
            debugLog(`✅ ${colisLibres.length} colis libres supprimés`);
            
            if (ui && ui.showToast) {
                ui.showToast(`${colisLibres.length} colis libres supprimés`, 'success');
            }
        },

        /**
         * Exporter la liste des éléments libres
         */
        exportLibreElements() {
            const colis = FicheProduction.data.colis();
            const products = FicheProduction.data.products();
            
            const colisLibres = colis.filter(c => c.isLibre);
            
            if (colisLibres.length === 0) {
                debugLog('⚠️ Aucun colis libre à exporter');
                return null;
            }
            
            const exportData = {
                exportDate: new Date().toISOString(),
                orderId: FicheProduction.config.orderId(),
                totalColisLibres: colisLibres.length,
                colis: colisLibres.map(c => ({
                    number: c.number,
                    totalWeight: c.totalWeight,
                    multiple: c.multiple,
                    elements: c.products.map(p => {
                        const product = products.find(prod => prod.id === p.productId);
                        return product ? {
                            name: product.name,
                            weight: product.weight,
                            quantity: p.quantity,
                            totalWeight: p.weight
                        } : null;
                    }).filter(e => e !== null)
                }))
            };
            
            debugLog('📄 Export des éléments libres préparé');
            
            return exportData;
        },

        /**
         * Statistiques des colis libres
         */
        getLibreStatistics() {
            const colis = FicheProduction.data.colis();
            const products = FicheProduction.data.products();
            
            const colisLibres = colis.filter(c => c.isLibre);
            const produitsLibres = products.filter(p => p.isLibre);
            
            const stats = {
                nombreColisLibres: colisLibres.length,
                nombreProduitsLibres: produitsLibres.length,
                poidsTotal: colisLibres.reduce((sum, c) => sum + (c.totalWeight * (c.multiple || 1)), 0),
                quantiteTotale: colisLibres.reduce((sum, c) => {
                    return sum + c.products.reduce((productSum, p) => productSum + (p.quantity || 0), 0) * (c.multiple || 1);
                }, 0),
                poidsMovenne: 0,
                elementsPlusLourds: [],
                elementsPlusLegers: []
            };
            
            if (stats.nombreColisLibres > 0) {
                stats.poidsMovenne = stats.poidsTotal / stats.nombreColisLibres;
            }
            
            // Trouver les éléments les plus lourds et légers
            const tousElements = [];
            colisLibres.forEach(c => {
                c.products.forEach(p => {
                    const product = products.find(prod => prod.id === p.productId);
                    if (product) {
                        tousElements.push({
                            nom: product.name,
                            poidsUnitaire: product.weight,
                            quantite: p.quantity,
                            poidsTotal: p.weight
                        });
                    }
                });
            });
            
            if (tousElements.length > 0) {
                tousElements.sort((a, b) => b.poidsUnitaire - a.poidsUnitaire);
                stats.elementsPlusLourds = tousElements.slice(0, 3);
                stats.elementsPlusLegers = tousElements.slice(-3).reverse();
            }
            
            return stats;
        }
    };

    // ============================================================================
    // EXPORT DU MODULE
    // ============================================================================

    // Ajouter le module au namespace principal
    if (window.FicheProduction) {
        window.FicheProduction.libre = LibreModule;
        debugLog('📦 Module Libre chargé et intégré');
    } else {
        console.warn('FicheProduction namespace not found. Module Libre not integrated.');
    }

})();