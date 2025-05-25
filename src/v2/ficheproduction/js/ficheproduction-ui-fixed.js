/**
 * FicheProduction v2.0 - Module UI (Version Corrigée)
 * Gestion de l'interface utilisateur avec enregistrement amélioré
 */

(function() {
    'use strict';

    // ============================================================================
    // GESTION DE L'INTERFACE UTILISATEUR
    // ============================================================================

    /**
     * Afficher une notification toast (FONCTION CRITIQUE)
     */
    function showToast(message, type = 'info', duration = 3000) {
        // Créer l'élément toast s'il n'existe pas
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                pointer-events: none;
            `;
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.style.cssText = `
            background: ${type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : type === 'success' ? '#28a745' : '#007bff'};
            color: ${type === 'warning' ? '#212529' : 'white'};
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            pointer-events: auto;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        toast.textContent = message;

        toastContainer.appendChild(toast);

        // Animation d'entrée
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);

        // Animation de sortie et suppression
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);

        debugLog(`🔔 Toast affiché: ${message} (${type})`);
    }

    /**
     * Afficher une boîte de dialogue de confirmation (FONCTION CRITIQUE)
     */
    function showConfirm(message, title = 'Confirmation') {
        return new Promise((resolve) => {
            // Créer l'overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            // Créer la boîte de dialogue
            const dialog = document.createElement('div');
            dialog.style.cssText = `
                background: white;
                border-radius: 8px;
                padding: 20px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            `;

            dialog.innerHTML = `
                <h3 style="margin: 0 0 15px 0; color: #333;">${title}</h3>
                <p style="margin: 0 0 20px 0; color: #666; line-height: 1.4;">${message}</p>
                <div style="text-align: right;">
                    <button id="confirm-cancel" style="margin-right: 10px; padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Annuler</button>
                    <button id="confirm-ok" style="padding: 8px 16px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">OK</button>
                </div>
            `;

            overlay.appendChild(dialog);
            document.body.appendChild(overlay);

            // Gestionnaires d'événements
            const okBtn = dialog.querySelector('#confirm-ok');
            const cancelBtn = dialog.querySelector('#confirm-cancel');

            function cleanup() {
                document.body.removeChild(overlay);
            }

            okBtn.addEventListener('click', () => {
                cleanup();
                resolve(true);
            });

            cancelBtn.addEventListener('click', () => {
                cleanup();
                resolve(false);
            });

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    cleanup();
                    resolve(false);
                }
            });

            debugLog(`🤔 Dialogue de confirmation affiché: ${message}`);
        });
    }

    /**
     * Afficher une boîte de dialogue de saisie
     */
    function showPrompt(message, defaultValue = '', title = 'Saisie') {
        return new Promise((resolve) => {
            // Créer l'overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            // Créer la boîte de dialogue
            const dialog = document.createElement('div');
            dialog.style.cssText = `
                background: white;
                border-radius: 8px;
                padding: 20px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            `;

            dialog.innerHTML = `
                <h3 style="margin: 0 0 15px 0; color: #333;">${title}</h3>
                <p style="margin: 0 0 15px 0; color: #666; line-height: 1.4;">${message}</p>
                <input type="text" id="prompt-input" value="${defaultValue}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; box-sizing: border-box;">
                <div style="text-align: right;">
                    <button id="prompt-cancel" style="margin-right: 10px; padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Annuler</button>
                    <button id="prompt-ok" style="padding: 8px 16px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">OK</button>
                </div>
            `;

            overlay.appendChild(dialog);
            document.body.appendChild(overlay);

            const input = dialog.querySelector('#prompt-input');
            const okBtn = dialog.querySelector('#prompt-ok');
            const cancelBtn = dialog.querySelector('#prompt-cancel');

            // Focus sur l'input
            setTimeout(() => {
                input.focus();
                input.select();
            }, 100);

            function cleanup() {
                document.body.removeChild(overlay);
            }

            function submitValue() {
                const value = input.value.trim();
                cleanup();
                resolve(value || null);
            }

            okBtn.addEventListener('click', submitValue);
            cancelBtn.addEventListener('click', () => {
                cleanup();
                resolve(null);
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    submitValue();
                } else if (e.key === 'Escape') {
                    cleanup();
                    resolve(null);
                }
            });

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    cleanup();
                    resolve(null);
                }
            });

            debugLog(`✏️ Dialogue de saisie affiché: ${message}`);
        });
    }

    /**
     * Afficher un message d'erreur
     */
    function showError(message, title = 'Erreur') {
        showToast(message, 'error', 5000);
        console.error(`[UI Error] ${title}: ${message}`);
    }

    /**
     * Afficher un message de succès
     */
    function showSuccess(message) {
        showToast(message, 'success', 3000);
        debugLog(`✅ Succès: ${message}`);
    }

    /**
     * Afficher un message d'avertissement
     */
    function showWarning(message) {
        showToast(message, 'warning', 4000);
        debugLog(`⚠️ Avertissement: ${message}`);
    }

    /**
     * Afficher ou masquer un loader
     */
    function showLoader(show = true, message = 'Chargement...') {
        let loader = document.getElementById('global-loader');
        
        if (show && !loader) {
            loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255,255,255,0.9);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
            `;
            
            loader.innerHTML = `
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 15px; color: #666;">${message}</p>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            
            document.body.appendChild(loader);
        } else if (!show && loader) {
            document.body.removeChild(loader);
        }
    }

    /**
     * Initialiser les composants UI de base
     */
    function initializeUIModule() {
        debugLog('🎨 Initialisation du module UI');
        
        // Ajouter les styles CSS de base si nécessaires
        const existingStyles = document.getElementById('ficheproduction-ui-styles');
        if (!existingStyles) {
            const styles = document.createElement('style');
            styles.id = 'ficheproduction-ui-styles';
            styles.textContent = `
                .ficheproduction-hidden { display: none !important; }
                .ficheproduction-fade-in { opacity: 0; transition: opacity 0.3s ease; }
                .ficheproduction-fade-in.show { opacity: 1; }
            `;
            document.head.appendChild(styles);
        }
        
        debugLog('✅ Module UI initialisé');
    }

    /**
     * Utilitaires UI
     */
    function fadeIn(element, duration = 300) {
        if (!element) return;
        
        element.style.opacity = '0';
        element.style.display = 'block';
        
        setTimeout(() => {
            element.style.transition = `opacity ${duration}ms ease`;
            element.style.opacity = '1';
        }, 10);
    }

    function fadeOut(element, duration = 300) {
        if (!element) return;
        
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '0';
        
        setTimeout(() => {
            element.style.display = 'none';
        }, duration);
    }

    // ============================================================================
    // REGISTRATION DU MODULE (VERSION AMÉLIORÉE)
    // ============================================================================

    const UIModule = {
        showToast: showToast, // FONCTION CRITIQUE
        showConfirm: showConfirm, // FONCTION CRITIQUE
        showPrompt: showPrompt,
        showError: showError,
        showSuccess: showSuccess,
        showWarning: showWarning,
        showLoader: showLoader,
        fadeIn: fadeIn,
        fadeOut: fadeOut,
        initialize: initializeUIModule
    };

    // Fonction d'enregistrement robuste
    function registerUIModule() {
        if (window.FicheProduction) {
            if (window.FicheProduction.registerModule) {
                // Utiliser le nouveau système d'enregistrement
                window.FicheProduction.registerModule('ui', UIModule);
            } else {
                // Fallback vers l'ancien système
                window.FicheProduction.ui = UIModule;
                debugLog('📦 Module UI enregistré (fallback) dans FicheProduction.ui');
            }
            
            // Vérification immédiate
            setTimeout(() => {
                if (window.FicheProduction.ui && window.FicheProduction.ui.showConfirm) {
                    debugLog('✅ showConfirm disponible dans le namespace');
                } else {
                    debugLog('❌ showConfirm toujours non disponible dans le namespace');
                    // Enregistrement forcé si nécessaire
                    window.FicheProduction.ui = UIModule;
                    debugLog('🔧 Enregistrement forcé du module UI');
                }
            }, 50);
        } else {
            debugLog('⏳ FicheProduction namespace pas encore disponible, réessai...');
            setTimeout(registerUIModule, 10);
        }
    }

    // Écouter l'événement de disponibilité du core
    if (window.addEventListener) {
        window.addEventListener('FicheProductionCoreReady', registerUIModule);
    }

    // Tenter l'enregistrement immédiat ou différé
    registerUIModule();

    // Export des fonctions pour compatibilité
    window.showToast = showToast;
    window.showConfirm = showConfirm;
    window.showPrompt = showPrompt;
    window.showError = showError;
    window.showSuccess = showSuccess;
    window.showWarning = showWarning;
    window.showLoader = showLoader;

    debugLog('📦 Module UI chargé et intégré (Version corrigée)');

})();