/**
 * FicheProduction v2.0 - AJAX Module
 * Gestion des communications avec le serveur
 */

(function() {
    'use strict';

    // ============================================================================
    // MODULE AJAX
    // ============================================================================

    const AjaxModule = {
        
        /**
         * Initialisation du module AJAX
         */
        initialize() {
            debugLog('🌐 Initialisation du module AJAX');
        },

        /**
         * Fonction principale d'appel API
         */
        async apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('token', FicheProduction.config.token());
            formData.append('id', FicheProduction.config.orderId());
            
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                debugLog(`🌐 API Call: ${action}`);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                debugLog(`📡 Response reçue: ${text.substring(0, 200)}...`);
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    debugLog(`❌ JSON Parse Error: ${parseError.message}`);
                    return { success: false, error: 'Invalid JSON response' };
                }
            } catch (error) {
                debugLog('❌ Erreur API: ' + error.message);
                return { success: false, error: error.message };
            }
        },

        /**
         * Charger les données de base de la commande
         */
        async loadData() {
            debugLog('📊 Chargement des données (ordre commande + groupes produits)...');
            const result = await this.apiCall('ficheproduction_get_data');
            
            if (result && result.products) {
                // Les produits sont déjà dans l'ordre de la commande
                FicheProduction.data.setProducts(result.products);
                FicheProduction.data.setProductGroups(result.product_groups || []);
                
                debugLog(`✅ Chargé ${result.products.length} produits dans l'ordre de la commande`);
                debugLog(`✅ Trouvé ${result.product_groups ? result.product_groups.length : 0} groupes de produits`);
                
                // Remplir le sélecteur de groupes
                if (FicheProduction.inventory && FicheProduction.inventory.populateProductGroupSelector) {
                    FicheProduction.inventory.populateProductGroupSelector();
                }
                
                // Rendu initial de l'inventaire
                if (FicheProduction.inventory && FicheProduction.inventory.renderInventory) {
                    FicheProduction.inventory.renderInventory();
                }
                
                // Rendu initial des colis
                if (FicheProduction.colis && FicheProduction.colis.renderColisOverview) {
                    FicheProduction.colis.renderColisOverview();
                }
                
                // Après avoir chargé les données de base, essayer de charger les données sauvegardées
                await this.loadSavedData();
            } else {
                debugLog('❌ Erreur lors du chargement des données');
            }
        },

        /**
         * Charger les données sauvegardées
         */
        async loadSavedData() {
            if (FicheProduction.data.savedDataLoaded()) return; // Éviter les chargements multiples

            try {
                debugLog('💾 Chargement des données sauvegardées...');
                const result = await this.apiCall('ficheproduction_load_saved_data');

                if (result.success && result.colis && result.colis.length > 0) {
                    debugLog(`✅ Données sauvegardées trouvées: ${result.colis.length} colis`);
                    
                    // Convertir les données sauvegardées au format JavaScript
                    const convertedColis = this.convertSavedDataToJS(result.colis);
                    
                    // Remplacer les colis actuels par les données sauvegardées
                    FicheProduction.data.setColis(convertedColis);
                    
                    // Mettre à jour les quantités utilisées dans l'inventaire
                    if (FicheProduction.inventory && FicheProduction.inventory.updateInventoryFromSavedData) {
                        FicheProduction.inventory.updateInventoryFromSavedData();
                    }
                    
                    // Re-render
                    if (FicheProduction.inventory && FicheProduction.inventory.renderInventory) {
                        FicheProduction.inventory.renderInventory();
                    }
                    if (FicheProduction.colis && FicheProduction.colis.renderColisOverview) {
                        FicheProduction.colis.renderColisOverview();
                    }
                    if (FicheProduction.colis && FicheProduction.colis.updateSummaryTotals) {
                        FicheProduction.colis.updateSummaryTotals();
                    }
                    
                    FicheProduction.data.setSavedDataLoaded(true);
                    debugLog('✅ Données sauvegardées chargées avec succès');
                } else {
                    debugLog('ℹ️ Aucune donnée sauvegardée trouvée ou erreur: ' + (result.message || 'Erreur inconnue'));
                }
                
            } catch (error) {
                debugLog('❌ Erreur lors du chargement des données sauvegardées: ' + error.message);
            }
        },

        /**
         * Convertir les données sauvegardées au format JavaScript
         */
        convertSavedDataToJS(savedColis) {
            const convertedColis = [];
            const currentColis = FicheProduction.data.colis();
            let maxColisId = Math.max(...currentColis.map(c => c.id), 0);

            savedColis.forEach(savedColi => {
                const newColis = {
                    id: ++maxColisId,
                    number: savedColi.number,
                    products: [],
                    totalWeight: savedColi.totalWeight,
                    maxWeight: savedColi.maxWeight,
                    status: savedColi.status,
                    multiple: savedColi.multiple,
                    isLibre: savedColi.isLibre || false
                };

                // Convertir les produits
                savedColi.products.forEach(savedProduct => {
                    if (savedProduct.isLibre) {
                        // Créer un produit libre temporaire
                        const libreProduct = this.createLibreProduct(savedProduct.name, savedProduct.weight);
                        const products = FicheProduction.data.products();
                        products.push(libreProduct);
                        FicheProduction.data.setProducts(products);
                        
                        newColis.products.push({
                            productId: libreProduct.id,
                            quantity: savedProduct.quantity,
                            weight: savedProduct.quantity * savedProduct.weight
                        });
                    } else {
                        // Produit standard - trouver dans l'inventaire existant
                        const product = FicheProduction.data.products().find(p => !p.isLibre && this.matchSavedProduct(p, savedProduct));
                        if (product) {
                            newColis.products.push({
                                productId: product.id,
                                quantity: savedProduct.quantity,
                                weight: savedProduct.quantity * savedProduct.weight
                            });
                        }
                    }
                });

                convertedColis.push(newColis);
            });

            return convertedColis;
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
                weight: parseFloat(weight),
                isLibre: true,
                total: 9999, // Pas de limite pour les produits libres
                used: 0
            };
        },

        /**
         * Vérifier si un produit correspond aux données sauvegardées
         */
        matchSavedProduct(product, savedProduct) {
            // Simple matching par ID de produit Dolibarr si disponible
            return savedProduct.productId && product.line_id === savedProduct.productId;
        },

        /**
         * Sauvegarder le colisage
         */
        async saveColisage() {
            const colis = FicheProduction.data.colis();
            
            if (colis.length === 0) {
                if (FicheProduction.ui && FicheProduction.ui.showConfirm) {
                    await FicheProduction.ui.showConfirm('Aucun colis à sauvegarder.');
                }
                return;
            }

            // Afficher la modale de progression
            if (FicheProduction.ui && FicheProduction.ui.showSaveProgress) {
                FicheProduction.ui.showSaveProgress();
            }

            try {
                // Préparer les données pour la sauvegarde
                if (FicheProduction.ui && FicheProduction.ui.updateSaveProgress) {
                    FicheProduction.ui.updateSaveProgress(25, 'Préparation des données...');
                }
                const colisageData = this.prepareColisageDataForSave();

                if (FicheProduction.ui && FicheProduction.ui.updateSaveProgress) {
                    FicheProduction.ui.updateSaveProgress(50, 'Envoi des données...');
                }
                const result = await this.apiCall('ficheproduction_save_colis', {
                    colis_data: JSON.stringify(colisageData)
                });

                if (FicheProduction.ui && FicheProduction.ui.updateSaveProgress) {
                    FicheProduction.ui.updateSaveProgress(75, 'Traitement...');
                }
                
                if (result.success) {
                    if (FicheProduction.ui && FicheProduction.ui.updateSaveProgress) {
                        FicheProduction.ui.updateSaveProgress(100, 'Sauvegarde terminée !');
                    }
                    
                    setTimeout(() => {
                        if (FicheProduction.ui && FicheProduction.ui.hideSaveProgress) {
                            FicheProduction.ui.hideSaveProgress();
                        }
                        if (FicheProduction.ui && FicheProduction.ui.showConfirm) {
                            FicheProduction.ui.showConfirm(`✅ Colisage sauvegardé avec succès !\n\n${result.message}\nSession ID: ${result.session_id}`);
                        }
                        debugLog(`✅ Sauvegarde réussie: ${result.message}`);
                    }, 500);
                } else {
                    if (FicheProduction.ui && FicheProduction.ui.hideSaveProgress) {
                        FicheProduction.ui.hideSaveProgress();
                    }
                    if (FicheProduction.ui && FicheProduction.ui.showConfirm) {
                        await FicheProduction.ui.showConfirm(`❌ Erreur lors de la sauvegarde :\n${result.error || result.message}`);
                    }
                    debugLog(`❌ Erreur sauvegarde: ${result.error || result.message}`);
                }

            } catch (error) {
                if (FicheProduction.ui && FicheProduction.ui.hideSaveProgress) {
                    FicheProduction.ui.hideSaveProgress();
                }
                if (FicheProduction.ui && FicheProduction.ui.showConfirm) {
                    await FicheProduction.ui.showConfirm(`❌ Erreur technique :\n${error.message}`);
                }
                debugLog(`❌ Erreur technique: ${error.message}`);
            }
        },

        /**
         * Préparer les données pour la sauvegarde
         */
        prepareColisageDataForSave() {
            const colis = FicheProduction.data.colis();
            const products = FicheProduction.data.products();
            
            return colis.map(c => ({
                number: c.number,
                maxWeight: c.maxWeight,
                totalWeight: c.totalWeight,
                multiple: c.multiple,
                status: c.status,
                isLibre: c.isLibre || false,
                products: c.products.map(p => {
                    const product = products.find(prod => prod.id === p.productId);
                    if (!product) return null;

                    if (product.isLibre) {
                        return {
                            isLibre: true,
                            name: product.name,
                            description: '',
                            quantity: p.quantity,
                            weight: product.weight
                        };
                    } else {
                        return {
                            isLibre: false,
                            productId: p.productId,
                            quantity: p.quantity,
                            weight: product.weight
                        };
                    }
                }).filter(p => p !== null)
            }));
        }
    };

    // ============================================================================
    // EXPORT DU MODULE
    // ============================================================================

    // Ajouter le module au namespace principal
    if (window.FicheProduction) {
        window.FicheProduction.ajax = AjaxModule;
        debugLog('📦 Module AJAX chargé et intégré');
    } else {
        console.warn('FicheProduction namespace not found. Module AJAX not integrated.');
    }

})();