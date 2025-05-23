/**
 * Script JS pour le module Fiche de Production avec AG Grid
 */

// Objet global pour stocker les instances AG Grid
var FicheProduction = {
    // Tableaux associatifs pour stocker les instances
    grids: {},
    gridData: {},
    
    // Initialisation du module
    init: function() {
        console.log('Initialisation du module Fiche de Production avec AG Grid');
        
        // Rechercher tous les conteneurs de grids sur la page
        var containers = document.querySelectorAll('.ficheproduction-jspreadsheet-container');
        console.log('Nombre de conteneurs trouvés:', containers.length);
        
        // Initialiser tous les grids trouvés
        containers.forEach(function(container) {
            var productId = container.id.replace('spreadsheet-', '');
            if (productId) {
                console.log('Initialisation du grid pour le conteneur:', container.id);
                FicheProduction.initGrid(productId, container);
            }
        });
        
        // Calculer le total des colis après initialisation
        this.updateTotalPackages();
        
        // Initialiser la comparaison des quantités
        this.compareQuantities();
        
        // Ajouter un bouton de débogage
        //this.addDebugButton();
        
        // Forcer un recalcul après un court délai pour s'assurer que tout est chargé
        setTimeout(function() {
            FicheProduction.forceRecalculateAllGrids();
        }, 500);
    },
    
    // Fonction pour recalculer et afficher les valeurs pour chaque grid
    forceRecalculateAllGrids: function() {
        console.log('Forçage du recalcul de toutes les grids');
        
        // Pour chaque grid, forcer le recalcul
        for (var productId in this.grids) {
            if (this.grids[productId] && this.grids[productId].api) {
                console.log('Recalcul de la grid pour le produit', productId);
                
                // Forcer le rafraîchissement de toutes les cellules
                this.grids[productId].api.refreshCells({
                    force: true
                });
                
                // Vérifier et afficher les données pour débogage
                console.log('Données du grid ' + productId + ':', JSON.stringify(this.gridData[productId]));
                
                // Calculer manuellement le total pour vérification
                var manualTotal = 0;
                if (this.gridData[productId]) {
                    this.gridData[productId].forEach(function(row) {
                        var a = parseFloat(row.nbrColis) || 0;
                        var b = parseFloat(row.nbrElements) || 0;
                        var c = parseFloat(row.longueur) || 0;
                        var d = parseFloat(row.largeur) || 0;
                        var rowTotal = a * b * c / 1000 * d / 1000;
                        console.log('Ligne avec a=' + a + ', b=' + b + ', c=' + c + ', d=' + d + ' => total=' + rowTotal.toFixed(3));
                        manualTotal += rowTotal;
                    });
                }
                console.log('Total manuel calculé pour grid ' + productId + ': ' + manualTotal.toFixed(3));
            }
        }
        
        // Mettre à jour les totaux et comparaisons
        this.updateTotalPackages();
        this.compareQuantities();
    },
    
    // Ajoute un bouton de débogage pour forcer le recalcul
    addDebugButton: function() {
        var debugContainer = document.createElement('div');
        debugContainer.style.margin = '20px 0';
        debugContainer.style.textAlign = 'right';
        
        var debugButton = document.createElement('button');
        debugButton.className = 'butAction';
        debugButton.innerHTML = 'Recalculer les grids';
        debugButton.onclick = function() {
            FicheProduction.forceRecalculateAllGrids();
        };
        
        debugContainer.appendChild(debugButton);
        
        // Ajouter le bouton à la fin du conteneur principal
        var mainContainer = document.querySelector('.ficheproduction-container');
        if (mainContainer) {
            mainContainer.appendChild(debugContainer);
        }
    },
    
    // Initialise un AG Grid pour un produit donné
initGrid: function(productId, container) {
    console.log('Début initialisation grid pour le produit', productId);
    
    try {
        // Nettoyer et préparer le conteneur
        container.innerHTML = '';
        container.classList.add('ag-theme-alpine');
        //container.style.height = '300px';
        container.style.width = '100%';
        
        // S'assurer que le conteneur est visible dans sa section de produit
        var productGroup = container.closest('.product-group');
        if (productGroup) {
            // Ajouter une marge pour séparer les tableaux
            productGroup.style.marginBottom = '30px';
            productGroup.style.paddingBottom = '20px';
            productGroup.style.borderBottom = '1px solid #ddd';
        }
        
        // Largeur du produit pour la colonne D
        var productWidth = document.getElementById('product-width-' + productId) ? 
                          document.getElementById('product-width-' + productId).value : '0';
        
        // Récupérer l'extrafield ref_ligne s'il existe
        var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                       document.getElementById('product-ref-ligne-' + productId).value : '';
        
        // Définition des colonnes
        var columnDefs = [
            { field: 'nbrColis', headerName: 'Nbr Colis', editable: true, width: 120, type: 'numericColumn' },
            { field: 'nbrElements', headerName: 'Nbr éléments', editable: true, width: 120, type: 'numericColumn' },
            { field: 'longueur', headerName: 'Longueur', editable: true, width: 120, type: 'numericColumn' },
            { 
                field: 'largeur', 
                headerName: 'Largeur', 
                editable: false, 
                width: 120,
                type: 'numericColumn',
                valueGetter: function() {
                    return parseFloat(productWidth);
                }
            },
            { 
                field: 'quantite', 
                headerName: 'Quantité', 
                editable: false, 
                width: 120,
                type: 'numericColumn',
                // Méthode valueGetter plus robuste pour calculer la quantité avec la logique conditionnelle
                valueGetter: function(params) {
                    if (!params.data) return 0;
                    
                    // Récupérer les valeurs A, B, C, D en s'assurant qu'elles sont des nombres
                    var a = parseFloat(params.data.nbrColis) || 0;
                    var b = parseFloat(params.data.nbrElements) || 0;
                    var c = parseFloat(params.data.longueur) || 0;
                    var d = parseFloat(params.data.largeur) || 0;
                    
                    // Calculer E avec la logique conditionnelle
                    var quantity = 0;
                    
                    if (c === 0 && d === 0) {
                        // Si C et D sont à 0: A × B
                        quantity = a * b;
                    } else if (c === 0) {
                        // Si C est à 0: A × B × D/1000
                        quantity = a * b * d / 1000;
                    } else if (d === 0) {
                        // Si D est à 0: A × B × C/1000
                        quantity = a * b * c / 1000;
                    } else {
                        // Formule standard: A × B × C/1000 × D/1000
                        quantity = a * b * c / 1000 * d / 1000;
                    }
                    
                    // Stocker la valeur précise pour les calculs futurs
                    params.data.calculatedQuantity = quantity;
                    
                    // Retourner la valeur formatée pour l'affichage
                    return quantity.toFixed(3);
                }
            },
            { 
                field: 'refLigne', 
                headerName: 'Ref', 
                editable: true, 
                width: 120
            }
        ];
        
        // Initialiser avec une ligne vide
        this.gridData[productId] = [{
            nbrColis: 0,
            nbrElements: 0,
            longueur: 0,
            largeur: parseFloat(productWidth),
            quantite: 0,
            refLigne: refLigne
        }];
        
        // Configuration de la grid
        var gridOptions = {
            columnDefs: columnDefs,
            rowData: this.gridData[productId],
            defaultColDef: {
                resizable: true,
                sortable: true,
                filter: true
            },
            rowSelection: 'multiple',
            suppressRowClickSelection: false,
            suppressDragLeaveHidesColumns: true,
            onCellValueChanged: function(event) {
                // Forcer le rafraîchissement de la colonne quantité
                event.api.refreshCells({
                    force: true,
                    columns: ['quantite']
                });
                
                // Mise à jour du total de colis
                FicheProduction.updateTotalPackagesForProduct(productId);
                
                // Comparaison des quantités
                FicheProduction.compareQuantities();
                
                // Sauvegarder les modifications
                setTimeout(function() {
                    FicheProduction.saveData(productId);
                }, 100);
            },
            domLayout: 'autoHeight',
            // Événement déclenché après le rendu complet du grid
            onGridReady: function(params) {
                console.log('Grid prêt pour le produit', productId);
                // Comparaison des quantités après initialisation complète
                setTimeout(function() {
                    FicheProduction.compareQuantities();
                }, 500);
            }
        };
        
        // Créer la grid
        new agGrid.Grid(container, gridOptions);
        
        // Stocker la référence à la grid
        this.grids[productId] = gridOptions;
        
        console.log('Grid initialisé avec succès pour le produit', productId);
        
        // Charger les données existantes
        this.loadExistingData(productId);
        
        // Mise à jour du total de colis pour ce produit
        this.updateTotalPackagesForProduct(productId);
        
        // Ajouter les boutons d'action
        this.addActionButtons(productId);
        
    } catch (e) {
        console.error('Erreur lors de l\'initialisation du grid:', e);
        console.error('Détails:', e.message, e.stack);
        
        // Afficher un message d'erreur dans le conteneur
        container.innerHTML = '<div style="padding: 10px; color: red; background-color: #fff3cd; border: 1px solid #ffeeba;">' +
                              'Erreur lors de l\'initialisation du tableau: ' + e.message + '</div>';
    }
},
    
    // Charge les données existantes pour un produit
    loadExistingData: function(productId) {
        var self = this;
        var fk_commande = document.getElementById('fk_commande').value;
        var token = document.getElementById('token').value;
        
        // Requête AJAX pour charger les données
        var xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.pathname + '?action=load_data&productId=' + productId + '&fk_commande=' + fk_commande + '&token=' + token, true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data) {
                            self.loadData(productId, response.data);
                        } else if (response.error) {
                            console.error('Erreur lors du chargement des données:', response.error);
                        }
                    } catch (e) {
                        console.log('Pas de données existantes ou format invalide:', e.message);
                    }
                } else {
                    console.error('Erreur HTTP lors du chargement des données:', xhr.status);
                    console.error('Réponse du serveur:', xhr.responseText);
                }
            }
        };
        
        xhr.send();
    },
    
    // Ajoute une ligne au grid
    addRow: function(productId) {
        console.log('Ajout d\'une ligne pour le produit', productId);
        
        if (this.grids[productId] && this.grids[productId].api) {
            try {
                // Récupérer la largeur du produit
                var productWidth = document.getElementById('product-width-' + productId) ? 
                                  document.getElementById('product-width-' + productId).value : '0';
                
                // Récupérer l'extrafield ref_ligne s'il existe
                var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                               document.getElementById('product-ref-ligne-' + productId).value : '';
                
                // Créer un objet pour la nouvelle ligne
                var newRow = {
                    nbrColis: 0,
                    nbrElements: 0,
                    longueur: 0,
                    largeur: parseFloat(productWidth),
                    quantite: 0,
                    refLigne: refLigne
                };
                
                // Ajouter la ligne au tableau de données
                this.gridData[productId].push(newRow);
                
                // Mettre à jour la grid
                this.grids[productId].api.setRowData(this.gridData[productId]);
                
                // Mise à jour du total de colis
                this.updateTotalPackagesForProduct(productId);
                
                // Comparaison des quantités
                this.compareQuantities();
                
                // Sauvegarder les modifications
                setTimeout(function() {
                    FicheProduction.saveData(productId);
                }, 100);
                
                console.log('Ligne ajoutée avec succès');
            } catch (e) {
                console.error('Erreur lors de l\'ajout d\'une ligne:', e);
                alert('Erreur lors de l\'ajout d\'une ligne: ' + e.message);
            }
        } else {
            console.error('Grid non trouvé ou API non disponible pour le produit ' + productId);
            alert('Erreur: Tableau non trouvé pour ce produit.');
        }
    },
    
    // Supprime la(les) ligne(s) sélectionnée(s)
    deleteRow: function(productId) {
        console.log('Suppression de ligne(s) pour le produit', productId);
        
        if (this.grids[productId] && this.grids[productId].api) {
            try {
                // Obtenir les nœuds de ligne sélectionnés
                var selectedNodes = this.grids[productId].api.getSelectedNodes();
                
                if (selectedNodes && selectedNodes.length > 0) {
                    // Supprimer les lignes sélectionnées
                    this.grids[productId].api.applyTransaction({
                        remove: selectedNodes.map(function(node) {
                            return node.data;
                        })
                    });
                    
                    // Mettre à jour le tableau de données interne
                    var selectedRows = selectedNodes.map(function(node) {
                        return node.data;
                    });
                    
                    this.gridData[productId] = this.gridData[productId].filter(function(row) {
                        return selectedRows.indexOf(row) === -1;
                    });
                    
                    // S'assurer qu'il reste au moins une ligne
                    if (this.gridData[productId].length === 0) {
                        // Ajouter une ligne vide
                        var productWidth = document.getElementById('product-width-' + productId) ? 
                                          document.getElementById('product-width-' + productId).value : '0';
                        var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                                      document.getElementById('product-ref-ligne-' + productId).value : '';
                        
                        this.gridData[productId].push({
                            nbrColis: 0,
                            nbrElements: 0,
                            longueur: 0,
                            largeur: parseFloat(productWidth),
                            quantite: 0,
                            refLigne: refLigne
                        });
                        
                        // Mettre à jour la grid
                        this.grids[productId].api.setRowData(this.gridData[productId]);
                    }
                    
                    // Mise à jour du total de colis
                    this.updateTotalPackagesForProduct(productId);
                    
                    // Comparaison des quantités
                    this.compareQuantities();
                    
                    // Sauvegarder les modifications
                    setTimeout(function() {
                        FicheProduction.saveData(productId);
                    }, 100);
                    
                    console.log('Ligne(s) supprimée(s) avec succès');
                } else {
                    alert('Veuillez sélectionner une ligne à supprimer');
                }
            } catch (e) {
                console.error('Erreur lors de la suppression d\'une ligne:', e);
                alert('Erreur lors de la suppression: ' + e.message);
            }
        } else {
            console.error('Grid non trouvé ou API non disponible pour le produit ' + productId);
            alert('Erreur: Tableau non trouvé pour ce produit.');
        }
    },
    
    // Supprime toutes les lignes
    deleteAllRows: function(productId) {
        console.log('Suppression de toutes les lignes pour le produit', productId);
        
        if (this.grids[productId] && this.grids[productId].api) {
            try {
                if (confirm('Êtes-vous sûr de vouloir supprimer toutes les lignes ?')) {
                    // Récupérer la largeur du produit
                    var productWidth = document.getElementById('product-width-' + productId) ? 
                                      document.getElementById('product-width-' + productId).value : '0';
                    var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                                  document.getElementById('product-ref-ligne-' + productId).value : '';
                    
                    // Remplacer par une seule ligne vide
                    this.gridData[productId] = [{
                        nbrColis: 0,
                        nbrElements: 0,
                        longueur: 0,
                        largeur: parseFloat(productWidth),
                        quantite: 0,
                        refLigne: refLigne
                    }];
                    
                    // Mettre à jour la grid
                    this.grids[productId].api.setRowData(this.gridData[productId]);
                    
                    // Mise à jour du total de colis
                    this.updateTotalPackagesForProduct(productId);
                    
                    // Comparaison des quantités
                    this.compareQuantities();
                    
                    // Sauvegarder les modifications
                    setTimeout(function() {
                        FicheProduction.saveData(productId);
                    }, 100);
                    
                    console.log('Toutes les lignes ont été supprimées');
                }
            } catch (e) {
                console.error('Erreur lors de la suppression de toutes les lignes:', e);
                alert('Erreur lors de la suppression: ' + e.message);
            }
        } else {
            console.error('Grid non trouvé ou API non disponible pour le produit ' + productId);
            alert('Erreur: Tableau non trouvé pour ce produit.');
        }
    },
    
    // Met à jour le total de colis pour un produit spécifique
    updateTotalPackagesForProduct: function(productId) {
        try {
            if (!this.gridData[productId]) return;
            
            // Calculer le nombre total de colis pour ce produit
            var totalPackages = 0;
            this.gridData[productId].forEach(function(row) {
                totalPackages += parseInt(row.nbrColis) || 0;
            });
            
            // Mettre à jour la valeur cachée pour ce produit
            var productPackagesElement = document.getElementById('product-packages-' + productId);
            if (productPackagesElement) {
                productPackagesElement.value = totalPackages;
            }
            
            // Mettre à jour le total général des colis
            this.updateTotalPackages();
            
        } catch (e) {
            console.error('Erreur lors de la mise à jour du total de colis pour le produit:', e);
        }
    },
    
    // Met à jour le total général des colis
    updateTotalPackages: function() {
        try {
            // Calculer le nombre total de colis pour tous les produits
            var totalPackages = 0;
            var packageInputs = document.querySelectorAll('[id^="product-packages-"]');
            
            packageInputs.forEach(function(input) {
                totalPackages += parseInt(input.value) || 0;
            });
            
            // Mettre à jour l'affichage du total
            var totalPackagesElement = document.getElementById('total-packages');
            if (totalPackagesElement) {
                totalPackagesElement.textContent = totalPackages;
            }
        } catch (e) {
            console.error('Erreur lors de la mise à jour du total général des colis:', e);
        }
    },
    
    // Compare les quantités entre les deux tableaux (commande et grids)
compareQuantities: function() {
    try {
        // Pour chaque produit, calculer la quantité totale dans le grid
        for (var productId in this.gridData) {
            // S'assurer que le grid et ses données existent
            if (!this.grids[productId] || !this.gridData[productId]) {
                console.log('Grid ou données non trouvés pour le produit:', productId);
                continue;
            }
            
            // Vérifier que la grille a des données
            console.log('Données du grid pour ' + productId + ':', this.gridData[productId]);
            
            var gridQuantity = 0;
            
            // Additionner toutes les quantités du grid
            this.gridData[productId].forEach(function(row) {
                // Extraire et afficher chaque valeur pour le débogage
                var nbrColis = parseFloat(row.nbrColis) || 0;
                var nbrElements = parseFloat(row.nbrElements) || 0;
                var longueur = parseFloat(row.longueur) || 0;
                var largeur = parseFloat(row.largeur) || 0;
                
                console.log('Ligne:', 'colis='+nbrColis, 'elements='+nbrElements, 'long='+longueur, 'larg='+largeur);
                
                // Calculer la quantité pour cette ligne
                // Calculer la quantité pour cette ligne avec la même logique conditionnelle
                var rowQuantity = 0;
                if (longueur === 0 && largeur === 0) {
                    rowQuantity = nbrColis * nbrElements;
                } else if (longueur === 0) {
                    rowQuantity = nbrColis * nbrElements * largeur / 1000;
                } else if (largeur === 0) {
                    rowQuantity = nbrColis * nbrElements * longueur / 1000;
                } else {
                    rowQuantity = nbrColis * nbrElements * longueur / 1000 * largeur / 1000;
                };
                console.log('Quantité calculée pour cette ligne:', rowQuantity);
                
                // Ajouter au total
                gridQuantity += rowQuantity;
            });
            
            console.log('Quantité totale calculée pour le grid:', gridQuantity);
            
            // Récupérer la quantité TOTALE commandée
            var totalQuantitySelector = 'product-total-quantity-' + productId;
            var orderedQuantityElement = document.getElementById(totalQuantitySelector);
            
            if (orderedQuantityElement) {
                var rawValue = orderedQuantityElement.value;
                var orderedQuantity = parseFloat(rawValue) || 0;
                
                console.log('Quantité commandée (valeur brute):', rawValue);
                console.log('Quantité commandée (convertie):', orderedQuantity);
                
                // Récupérer l'élément pour afficher la comparaison
                var quantityCompareElement = document.getElementById('quantity-compare-' + productId);
                if (quantityCompareElement) {
                    // Calculer la différence
                    var difference = gridQuantity - orderedQuantity;
                    var percentDiff = orderedQuantity > 0 ? (difference / orderedQuantity) * 100 : 0;
                    
                    // Formater les nombres pour l'affichage
                    var gridQuantityDisplay = gridQuantity.toFixed(3);
                    var orderedQuantityDisplay = orderedQuantity.toFixed(3);
                    var differenceDisplay = difference.toFixed(3);
                    var percentDiffDisplay = percentDiff.toFixed(1);
                    
                    // Créer le message de comparaison
                    var message = 'Quantité colisage: ' + gridQuantityDisplay;
                    
                    // Ajouter la quantité commandée pour référence
                    message += ' / Commandé: ' + orderedQuantityDisplay;
                    
                    if (Math.abs(difference) > 0.001) {
                        var diffClass = '';
                        if (difference > 0) {
                            message += ' (+' + differenceDisplay + ', +' + percentDiffDisplay + '%)';
                            diffClass = 'quantity-over';
                        } else {
                            message += ' (' + differenceDisplay + ', ' + percentDiffDisplay + '%)';
                            diffClass = 'quantity-under';
                        }
                        
                        // Appliquer une classe en fonction de la différence
                        quantityCompareElement.className = 'quantity-compare ' + diffClass;
                    } else {
                        message += ' (identique)';
                        quantityCompareElement.className = 'quantity-compare quantity-match';
                    }
                    
                    // Mettre à jour le texte
                    quantityCompareElement.textContent = message;
                    
                    console.log('Comparaison finale:', message);
                }
            } else {
                console.error('Élément de quantité totale non trouvé pour le produit:', productId);
            }
        }
    } catch (e) {
        console.error('Erreur lors de la comparaison des quantités:', e);
        console.error('Détails:', e.message, e.stack);
    }
},
    
    // Ajoute les boutons d'action pour un grid
    addActionButtons: function(productId) {
        // Container pour les boutons
        var buttonsContainer = document.getElementById('buttons-' + productId);
        if (!buttonsContainer) {
            console.error('Conteneur de boutons non trouvé pour le produit ' + productId);
            return;
        }
        
        // Vider le conteneur de boutons
        buttonsContainer.innerHTML = '';
        
        // Bouton Ajouter
        var addButton = document.createElement('button');
        addButton.className = 'butAction';
        addButton.innerHTML = 'Ajouter';
        addButton.onclick = function() {
            FicheProduction.addRow(productId);
        };
        buttonsContainer.appendChild(addButton);
        
        // Bouton Supprimer
        var deleteButton = document.createElement('button');
        deleteButton.className = 'butActionDelete';
        deleteButton.innerHTML = 'Supprimer';
        deleteButton.onclick = function() {
            FicheProduction.deleteRow(productId);
        };
        buttonsContainer.appendChild(deleteButton);
        
        // Bouton Supprimer tout
        var deleteAllButton = document.createElement('button');
        deleteAllButton.className = 'butActionDelete';
        deleteAllButton.innerHTML = 'Supprimer tout';
        deleteAllButton.onclick = function() {
            FicheProduction.deleteAllRows(productId);
        };
        buttonsContainer.appendChild(deleteAllButton);
        
        console.log('Boutons ajoutés pour le produit', productId);
    },
    
    // Sauvegarde les données d'un grid
    saveData: function(productId) {
        console.log('Sauvegarde des données pour le produit', productId);
        
        if (this.grids[productId] && this.gridData[productId]) {
            try {
                // Vérifier que les données ne sont pas vides
                if (!this.gridData[productId] || this.gridData[productId].length === 0) {
                    console.error('Données vides, aucune sauvegarde effectuée');
                    return;
                }
                
                // Convertir les données en JSON
                var jsonData = JSON.stringify(this.gridData[productId]);
                
                // Récupérer le jeton CSRF et l'ID de commande
                var tokenElement = document.getElementById('token');
                var fk_commandeElement = document.getElementById('fk_commande');
                
                if (!tokenElement || !fk_commandeElement) {
                    console.error('Token ou ID de commande manquant dans la page');
                    return;
                }
                
                var token = tokenElement.value;
                var fk_commande = fk_commandeElement.value;
                
                if (!token || !fk_commande) {
                    console.error('Token ou ID de commande vides');
                    return;
                }
                
                // Créer et configurer la requête AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.pathname, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                // Gestion de la réponse
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    console.log('Sauvegarde réussie');
                                } else if (response.error) {
                                    console.error('Erreur lors de la sauvegarde:', response.error);
                                }
                            } catch (e) {
                                console.error('Erreur lors de l\'analyse de la réponse:', e.message);
                            }
                        } else {
                            console.error('Erreur HTTP lors de la sauvegarde:', xhr.status);
                            console.error('Réponse du serveur:', xhr.responseText);
                        }
                    }
                };
                
                // Préparer les données sous forme de chaîne pour x-www-form-urlencoded
                var params = 'action=save' + 
                            '&token=' + encodeURIComponent(token) +
                            '&productId=' + encodeURIComponent(productId) + 
                            '&fk_commande=' + encodeURIComponent(fk_commande) + 
                            '&colisage_data=' + encodeURIComponent(jsonData);
                
                // Envoyer la requête
                xhr.send(params);
                
            } catch (e) {
                console.error('Erreur lors de la sauvegarde des données:', e.message);
            }
        } else {
            console.error('Grid ou données non disponibles pour le produit', productId);
        }
    },
    
    // Charge les données d'un grid
    loadData: function(productId, data) {
        console.log('Chargement des données pour le produit', productId);
        
        if (this.grids[productId] && this.grids[productId].api) {
            try {
                // Parser les données JSON
                var parsedData = JSON.parse(data);
                
                // Vérifier que les données sont bien un tableau
                if (Array.isArray(parsedData)) {
                    // S'assurer que les données ne sont pas vides
                    if (parsedData.length === 0) {
                        // Ajouter une ligne vide
                        var productWidth = document.getElementById('product-width-' + productId) ? 
                                          document.getElementById('product-width-' + productId).value : '0';
                        var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                                      document.getElementById('product-ref-ligne-' + productId).value : '';
                        
                        parsedData.push({
                            nbrColis: 0,
                            nbrElements: 0,
                            longueur: 0,
                            largeur: parseFloat(productWidth),
                            quantite: 0,
                            refLigne: refLigne
                        });
                    }
                    
                    // Ajouter le champ refLigne s'il n'existe pas dans chaque ligne
                    parsedData.forEach(function(row) {
                        if (!row.hasOwnProperty('refLigne')) {
                            var refLigne = document.getElementById('product-ref-ligne-' + productId) ? 
                                          document.getElementById('product-ref-ligne-' + productId).value : '';
                            row.refLigne = refLigne;
                        }
                        
                        // Assurez-vous que les valeurs sont des nombres
                        row.nbrColis = parseFloat(row.nbrColis) || 0;
                        row.nbrElements = parseFloat(row.nbrElements) || 0;
                        row.longueur = parseFloat(row.longueur) || 0;
                        row.largeur = parseFloat(row.largeur) || 0;
                        
                        // Recalculer la quantité
                        var quantity = row.nbrColis * row.nbrElements * row.longueur / 1000 * row.largeur / 1000;
                        row.quantite = quantity.toFixed(3);
                    });
                    
                    // Mettre à jour les données internes
                    this.gridData[productId] = parsedData;
                    
                    // Mettre à jour la grid
                    this.grids[productId].api.setRowData(parsedData);
                    
                    // Mise à jour du total de colis
                    this.updateTotalPackagesForProduct(productId);
                    
                    // Comparaison des quantités
                    this.compareQuantities();
                    
                    console.log('Données chargées avec succès:', parsedData.length, 'lignes');
                } else {
                    console.error('Format de données invalide, un tableau était attendu');
                }
            } catch (e) {
                console.error('Erreur lors du chargement des données:', e.message);
            }
        } else {
            console.error('Grid non disponible pour le produit', productId);
        }
    }
};

// Initialisation du module quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation de FicheProduction avec AG Grid');
    FicheProduction.init();
});