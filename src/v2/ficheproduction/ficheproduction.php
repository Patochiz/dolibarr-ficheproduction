document.querySelectorAll('.product-item');
                    
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
