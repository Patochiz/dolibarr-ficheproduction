/**
 * Script d'initialisation des spreadsheets pour le module Fiche de Production
 */

// Fonction pour initialiser les spreadsheets de tous les produits sur la page
function initSpreadsheets() {
    console.log('Initialisation des spreadsheets pour la fiche de production');
    
    // Récupérer tous les conteneurs de spreadsheet sur la page
    var containers = document.querySelectorAll('.ficheproduction-jspreadsheet-container');
    
    // Parcourir tous les conteneurs et initialiser chaque spreadsheet
    containers.forEach(function(container) {
        // Extraire l'ID du produit
        var productId = container.id.replace('spreadsheet-', '');
        
        if (productId) {
            console.log('Initialisation du spreadsheet pour le produit ' + productId);
            
            // Initialiser le spreadsheet
            FicheProduction.initSpreadsheet(productId);
            
            // Charger les données existantes si disponibles
            loadExistingData(productId);
        }
    });
}

// Fonction pour charger les données existantes pour un produit
function loadExistingData(productId) {
    // Récupérer l'ID de la commande
    var fk_commande = document.getElementById('fk_commande').value;
    
    if (!fk_commande || !productId) return;
    
    // Créer une requête AJAX pour charger les données
    var xhr = new XMLHttpRequest();
    
    // Utiliser le chemin actuel pour éviter les problèmes de chemin relatif
    xhr.open('GET', window.location.pathname + '?action=load_data&productId=' + productId + '&fk_commande=' + fk_commande, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data) {
                        FicheProduction.loadData(productId, response.data);
                    }
                } catch (e) {
                    console.error('Erreur lors de l\'analyse de la réponse JSON', e);
                }
            } else {
                console.error('Erreur lors du chargement des données', xhr.status);
            }
        }
    };
    
    xhr.send();
}

// Initialiser tous les spreadsheets lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les spreadsheets
    initSpreadsheets();
});