/**
 * FicheProduction v2.0 - UI Module
 * Gestion de l'interface utilisateur et des modales
 */

(function() {
    'use strict';

    // ============================================================================
    // MODULE UI
    // ============================================================================

    const UIModule = {
        
        /**
         * Initialisation du module UI
         */
        initialize() {
            debugLog('🎨 Initialisation du module UI');
            this.setupEventListeners();
            this.setupModalEvents();
            this.checkBrowserCompatibility();
        },

        /**
         * Configuration des écouteurs d'événements principaux
         */
        setupEventListeners() {
            // Affichage/masquage de la console de debug
            const header = document.querySelector('.header h1');
            if (header) {
                header.addEventListener('dblclick', () => {
                    this.toggleDebugConsole();
                });
            }

            // Gestion des raccourcis clavier
            document.addEventListener('keydown', (e) => {
                this.handleKeyboardShortcuts(e);
            });

            // Gestion du redimensionnement de fenêtre
            window.addEventListener('resize', this.debounce(() => {
                this.handleWindowResize();
            }, 250));
        },

        /**
         * Configuration des événements des modales
         */
        setupModalEvents() {
            // Fermer les modales en cliquant à l'extérieur
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal-overlay')) {
                    this.closeModal(e.target.id);
                }
            });

            // Fermer les modales avec Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
        },

        /**
         * Basculer l'affichage de la console de debug
         */
        toggleDebugConsole() {
            const debugConsole = document.getElementById('debugConsole');
            if (debugConsole) {
                const isVisible = debugConsole.style.display !== 'none';
                debugConsole.style.display = isVisible ? 'none' : 'block';
                debugLog(`🔧 Console de debug ${isVisible ? 'masquée' : 'affichée'}`);
            }
        },

        /**
         * Gérer les raccourcis clavier
         */
        handleKeyboardShortcuts(event) {
            // Ctrl+S pour sauvegarder
            if (event.ctrlKey && event.key === 's') {
                event.preventDefault();
                if (FicheProduction.ajax && FicheProduction.ajax.saveColisage) {
                    FicheProduction.ajax.saveColisage();
                }
            }

            // Ctrl+N pour nouveau colis
            if (event.ctrlKey && event.key === 'n') {
                event.preventDefault();
                if (FicheProduction.colis && FicheProduction.colis.addNewColis) {
                    FicheProduction.colis.addNewColis();
                }
            }

            // F5 pour actualiser l'inventaire
            if (event.key === 'F5') {
                event.preventDefault();
                if (FicheProduction.inventory && FicheProduction.inventory.render) {
                    FicheProduction.inventory.render();
                }
                if (FicheProduction.colis && FicheProduction.colis.renderOverview) {
                    FicheProduction.colis.renderOverview();
                }
            }
        },

        /**
         * Gérer le redimensionnement de fenêtre
         */
        handleWindowResize() {
            // Ajuster la hauteur des zones si nécessaire
            const inventoryZone = document.querySelector('.inventory-zone');
            const constructorZone = document.querySelector('.constructor-zone');
            
            if (inventoryZone && constructorZone) {
                // Calculer la hauteur optimale
                const windowHeight = window.innerHeight;
                const headerHeight = document.querySelector('.header')?.offsetHeight || 0;
                const availableHeight = windowHeight - headerHeight - 100; // marge
                
                const minHeight = Math.max(availableHeight * 0.6, 400);
                inventoryZone.style.minHeight = minHeight + 'px';
                constructorZone.style.minHeight = minHeight + 'px';
            }
        },

        /**
         * Afficher une modale de confirmation
         */
        showConfirm(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const messageEl = document.getElementById('confirmMessage');
                const okBtn = document.getElementById('confirmOk');
                const cancelBtn = document.getElementById('confirmCancel');

                if (!modal || !messageEl || !okBtn || !cancelBtn) {
                    debugLog('⚠️ Éléments de modale confirmation manquants, utilisation alert');
                    resolve(confirm(message));
                    return;
                }

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
        },

        /**
         * Afficher une modale de saisie
         */
        showPrompt(message, defaultValue = '') {
            return new Promise((resolve) => {
                const modal = document.getElementById('promptModal');
                const messageEl = document.getElementById('promptMessage');
                const inputEl = document.getElementById('promptInput');
                const okBtn = document.getElementById('promptOk');
                const cancelBtn = document.getElementById('promptCancel');

                if (!modal || !messageEl || !inputEl || !okBtn || !cancelBtn) {
                    debugLog('⚠️ Éléments de modale prompt manquants, utilisation prompt');
                    resolve(prompt(message, defaultValue));
                    return;
                }

                messageEl.textContent = message;
                inputEl.value = defaultValue;
                modal.classList.add('show');
                
                // Focus sur l'input
                setTimeout(() => {
                    inputEl.focus();
                    inputEl.select();
                }, 100);

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
        },

        /**
         * Afficher la progression de sauvegarde
         */
        showSaveProgress() {
            const modal = document.getElementById('saveModal');
            if (modal) {
                modal.classList.add('show');
            }
        },

        /**
         * Mettre à jour la progression de sauvegarde
         */
        updateSaveProgress(percentage, message) {
            const progressFill = document.getElementById('saveProgressFill');
            const statusMessage = document.getElementById('saveStatusMessage');
            
            if (progressFill) {
                progressFill.style.width = Math.min(percentage, 100) + '%';
            }
            
            if (statusMessage) {
                statusMessage.textContent = message;
            }
        },

        /**
         * Masquer la progression de sauvegarde
         */
        hideSaveProgress() {
            const modal = document.getElementById('saveModal');
            if (modal) {
                modal.classList.remove('show');
            }
        },

        /**
         * Fermer une modale spécifique
         */
        closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
            }
        },

        /**
         * Fermer toutes les modales
         */
        closeAllModals() {
            const modals = document.querySelectorAll('.modal-overlay.show');
            modals.forEach(modal => {
                modal.classList.remove('show');
            });
        },

        /**
         * Afficher un message d'erreur
         */
        showError(message, title = 'Erreur') {
            return this.showConfirm(`${title}\n\n${message}`);
        },

        /**
         * Afficher un message de succès
         */
        showSuccess(message, title = 'Succès') {
            return this.showConfirm(`${title}\n\n${message}`);
        },

        /**
         * Afficher un toast notification (notification légère)
         */
        showToast(message, type = 'info', duration = 3000) {
            // Créer le toast s'il n'existe pas
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    pointer-events: none;
                `;
                document.body.appendChild(toastContainer);
            }

            // Créer le toast
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.cssText = `
                background: ${this.getToastColor(type)};
                color: white;
                padding: 12px 20px;
                margin-bottom: 10px;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                transform: translateX(100%);
                transition: transform 0.3s ease;
                pointer-events: auto;
                max-width: 300px;
                word-wrap: break-word;
            `;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            // Animation d'entrée
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);

            // Suppression automatique
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);

            // Clic pour fermer
            toast.addEventListener('click', () => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            });
        },

        /**
         * Obtenir la couleur du toast selon le type
         */
        getToastColor(type) {
            switch (type) {
                case 'success': return '#4caf50';
                case 'error': return '#f44336';
                case 'warning': return '#ff9800';
                case 'info':
                default: return '#2196f3';
            }
        },

        /**
         * Afficher/masquer un indicateur de chargement
         */
        showLoading(message = 'Chargement...') {
            let loader = document.getElementById('global-loader');
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'global-loader';
                loader.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 18px;
                `;
                document.body.appendChild(loader);
            }
            
            loader.innerHTML = `
                <div style="text-align: center;">
                    <div style="width: 40px; height: 40px; border: 3px solid transparent; border-top: 3px solid white; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                    <div>${message}</div>
                </div>
            `;
            
            // Ajouter l'animation CSS si elle n'existe pas
            if (!document.getElementById('spinner-style')) {
                const style = document.createElement('style');
                style.id = 'spinner-style';
                style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
                document.head.appendChild(style);
            }
            
            loader.style.display = 'flex';
        },

        /**
         * Masquer l'indicateur de chargement
         */
        hideLoading() {
            const loader = document.getElementById('global-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        },

        /**
         * Fonction debounce pour limiter la fréquence d'exécution
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
         * Vérifier la compatibilité du navigateur
         */
        checkBrowserCompatibility() {
            const features = {
                flexbox: CSS.supports('display', 'flex'),
                grid: CSS.supports('display', 'grid'),
                fetch: typeof fetch !== 'undefined',
                promise: typeof Promise !== 'undefined',
                es6: typeof Symbol !== 'undefined'
            };
            
            const unsupported = Object.keys(features).filter(key => !features[key]);
            
            if (unsupported.length > 0) {
                debugLog(`⚠️ Fonctionnalités non supportées: ${unsupported.join(', ')}`);
                this.showToast('Certaines fonctionnalités peuvent ne pas fonctionner correctement dans ce navigateur.', 'warning', 5000);
                return false;
            }
            
            debugLog('✅ Navigateur compatible avec toutes les fonctionnalités');
            return true;
        },

        /**
         * Animer un élément
         */
        animateElement(element, animation, duration = 300) {
            if (!element) return Promise.resolve();
            
            return new Promise((resolve) => {
                const animationClasses = {
                    'fadeIn': 'opacity: 0; transition: opacity ' + duration + 'ms ease;',
                    'fadeOut': 'opacity: 1; transition: opacity ' + duration + 'ms ease;',
                    'slideIn': 'transform: translateY(-20px); opacity: 0; transition: all ' + duration + 'ms ease;',
                    'slideOut': 'transform: translateY(0); opacity: 1; transition: all ' + duration + 'ms ease;',
                    'bounce': 'animation: bounce 0.6s ease;'
                };
                
                if (animationClasses[animation]) {
                    element.style.cssText += animationClasses[animation];
                    
                    setTimeout(() => {
                        switch (animation) {
                            case 'fadeIn':
                            case 'slideIn':
                                element.style.opacity = '1';
                                element.style.transform = 'translateY(0)';
                                break;
                            case 'fadeOut':
                            case 'slideOut':
                                element.style.opacity = '0';
                                element.style.transform = 'translateY(-20px)';
                                break;
                        }
                        
                        setTimeout(resolve, duration);
                    }, 10);
                } else {
                    resolve();
                }
            });
        },

        /**
         * Mettre en surbrillance un élément temporairement
         */
        highlightElement(element, color = '#ffeb3b', duration = 1000) {
            if (!element) return;
            
            const originalBackground = element.style.backgroundColor;
            element.style.backgroundColor = color;
            element.style.transition = 'background-color 0.3s ease';
            
            setTimeout(() => {
                element.style.backgroundColor = originalBackground;
                setTimeout(() => {
                    element.style.transition = '';
                }, 300);
            }, duration);
        },

        /**
         * Faire défiler vers un élément
         */
        scrollToElement(element, offset = 0) {
            if (!element) return;
            
            const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
            const targetPosition = elementPosition - offset;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        },

        /**
         * Copier du texte dans le presse-papiers
         */
        async copyToClipboard(text) {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    this.showToast('Copié dans le presse-papiers', 'success', 2000);
                    return true;
                } else {
                    // Fallback pour navigateurs plus anciens
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.opacity = '0';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    if (successful) {
                        this.showToast('Copié dans le presse-papiers', 'success', 2000);
                        return true;
                    } else {
                        throw new Error('Commande copy non supportée');
                    }
                }
            } catch (err) {
                this.showToast('Erreur lors de la copie', 'error', 3000);
                debugLog('❌ Erreur copie presse-papiers: ' + err.message);
                return false;
            }
        }
    };

    // ============================================================================
    // EXPORT DU MODULE
    // ============================================================================

    // Ajouter le module au namespace principal
    if (window.FicheProduction) {
        window.FicheProduction.ui = UIModule;
        debugLog('📦 Module UI chargé et intégré');
    } else {
        console.warn('FicheProduction namespace not found. Module UI not integrated.');
    }

})();