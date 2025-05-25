/**
 * FicheProduction v2.0 - Utils Module
 * Fonctions utilitaires et helpers
 */

(function() {
    'use strict';

    // ============================================================================
    // MODULE UTILS
    // ============================================================================

    const UtilsModule = {
        
        /**
         * Initialisation du module utils
         */
        initialize() {
            debugLog('🔧 Initialisation du module Utils');
        },

        /**
         * Formater un poids avec unité
         */
        formatWeight(weight, unit = 'kg') {
            return parseFloat(weight).toFixed(1) + ' ' + unit;
        },

        /**
         * Formater des dimensions
         */
        formatDimensions(length, width, unit = 'mm') {
            return `${length}×${width} ${unit}`;
        },

        /**
         * Générer un ID unique pour les colis
         */
        generateColisId() {
            const existingIds = FicheProduction.data.colis().map(c => c.id);
            return Math.max(...existingIds, 0) + 1;
        },

        /**
         * Générer un numéro unique pour les colis
         */
        generateColisNumber() {
            const existingNumbers = FicheProduction.data.colis().map(c => c.number);
            return Math.max(...existingNumbers, 0) + 1;
        },

        /**
         * Générer un ID unique pour les produits
         */
        generateProductId() {
            const existingIds = FicheProduction.data.products().map(p => p.id);
            return Math.max(...existingIds, 10000) + 1;
        },

        /**
         * Calculer le pourcentage d'utilisation d'un produit
         */
        calculateUsagePercentage(product) {
            if (product.total === 0) return 0;
            return (product.used / product.total) * 100;
        },

        /**
         * Déterminer le statut d'un produit
         */
        getProductStatus(product) {
            const available = product.total - product.used;
            if (available === 0) return 'exhausted';
            if (product.used > 0) return 'partial';
            return 'available';
        },

        /**
         * Déterminer le statut d'un colis selon le poids
         */
        getColisStatus(colis) {
            const percentage = (colis.totalWeight / colis.maxWeight) * 100;
            if (percentage > 100) return 'error';
            if (percentage > 90) return 'warning';
            return 'ok';
        },

        /**
         * Obtenir l'icône de statut pour un colis
         */
        getColisStatusIcon(colis) {
            const status = this.getColisStatus(colis);
            switch(status) {
                case 'error': return '❌';
                case 'warning': return '⚠️';
                default: return '✅';
            }
        },

        /**
         * Valider les données d'un colis libre
         */
        validateColisLibreData(items) {
            const errors = [];
            
            items.forEach((item, index) => {
                if (!item.name || item.name.trim() === '') {
                    errors.push(`Élément ${index + 1}: Le nom est requis`);
                }
                if (isNaN(item.weight) || item.weight < 0) {
                    errors.push(`Élément ${index + 1}: Le poids doit être un nombre positif`);
                }
                if (isNaN(item.quantity) || item.quantity < 1) {
                    errors.push(`Élément ${index + 1}: La quantité doit être un entier positif`);
                }
            });
            
            return errors;
        },

        /**
         * Débouncer une fonction
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Escaper du HTML pour éviter les injections
         */
        escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        /**
         * Copier un objet en profondeur
         */
        deepClone(obj) {
            if (obj === null || typeof obj !== "object") return obj;
            if (obj instanceof Date) return new Date(obj.getTime());
            if (obj instanceof Array) return obj.map(item => this.deepClone(item));
            if (typeof obj === "object") {
                const copy = {};
                Object.keys(obj).forEach(key => {
                    copy[key] = this.deepClone(obj[key]);
                });
                return copy;
            }
        },

        /**
         * Fonction d'impression améliorée
         */
        preparePrint() {
            debugLog('🖨️ Préparation de l\'impression');
            
            const originalTitle = document.title;
            const orderId = FicheProduction.config.orderId();
            
            // Mettre à jour le titre pour l'impression
            document.title = `Fiche de Production - Commande ${orderId}`;
            
            // Cacher la console de debug pour l'impression
            const debugConsole = document.getElementById('debugConsole');
            const originalDisplay = debugConsole ? debugConsole.style.display : null;
            if (debugConsole) {
                debugConsole.style.display = 'none';
            }
            
            // Lancer l'impression
            window.print();
            
            // Restaurer après impression
            setTimeout(() => {
                document.title = originalTitle;
                if (debugConsole && originalDisplay !== null) {
                    debugConsole.style.display = originalDisplay;
                }
                debugLog('✅ Impression terminée');
            }, 1000);
        },

        /**
         * Calculer les totaux de colisage
         */
        calculateTotals() {
            const colis = FicheProduction.data.colis();
            
            let totalPackages = 0;
            let totalWeight = 0;
            
            colis.forEach(c => {
                totalPackages += c.multiple;
                totalWeight += c.totalWeight * c.multiple;
            });
            
            return {
                packages: totalPackages,
                weight: totalWeight
            };
        },

        /**
         * Mettre à jour l'affichage des totaux
         */
        updateSummaryTotals() {
            const totals = this.calculateTotals();
            
            // Mettre à jour l'affichage
            const totalPackagesElement = document.getElementById('total-packages');
            const totalWeightElement = document.getElementById('total-weight');
            
            if (totalPackagesElement) {
                totalPackagesElement.textContent = totals.packages;
            }
            
            if (totalWeightElement) {
                totalWeightElement.textContent = this.formatWeight(totals.weight, '');
            }
            
            debugLog(`📊 Totaux mis à jour: ${totals.packages} colis, ${this.formatWeight(totals.weight)}`);
        },

        /**
         * Vérifier la compatibilité du navigateur
         */
        checkBrowserCompatibility() {
            const features = {
                dragAndDrop: 'draggable' in document.createElement('div'),
                localStorage: typeof(Storage) !== "undefined",
                fetch: typeof fetch !== 'undefined',
                promise: typeof Promise !== 'undefined'
            };
            
            const unsupported = Object.keys(features).filter(key => !features[key]);
            
            if (unsupported.length > 0) {
                debugLog(`⚠️ Fonctionnalités non supportées: ${unsupported.join(', ')}`);
                return false;
            }
            
            debugLog('✅ Navigateur compatible');
            return true;
        },

        /**
         * Formater une durée en millisecondes
         */
        formatDuration(ms) {
            if (ms < 1000) return `${ms}ms`;
            if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`;
            return `${Math.floor(ms / 60000)}m ${Math.floor((ms % 60000) / 1000)}s`;
        },

        /**
         * Mesurer le temps d'exécution d'une fonction
         */
        measureTime(func, name = 'Function') {
            const start = performance.now();
            const result = func();
            const end = performance.now();
            const duration = end - start;
            
            debugLog(`⏱️ ${name}: ${this.formatDuration(duration)}`);
            
            return result;
        }
    };

    // ============================================================================
    // EXPORT DU MODULE
    // ============================================================================

    // Ajouter le module au namespace principal
    if (window.FicheProduction) {
        window.FicheProduction.utils = UtilsModule;
        debugLog('📦 Module Utils chargé et intégré');
    } else {
        console.warn('FicheProduction namespace not found. Module Utils not integrated.');
    }

})();