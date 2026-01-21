/**
 * WePresta ACF - Advanced Custom Fields for PrestaShop
 *
 * @author    WePresta
 * @copyright 2024-2025 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * Module Starter - JavaScript Front-Office
 */

(function() {
    'use strict';

    /**
     * Module Starter Frontend
     */
    const WeprestaAcf = {
        /**
         * Configuration
         */
        config: {
            debug: false,
            selectors: {
                container: '.wepresta_acf-container',
                btn: '.wepresta_acf-btn',
            }
        },

        /**
         * Initialisation
         */
        init() {
            if (this.config.debug) {
                console.log('[WeprestaAcf] Initializing...');
            }

            this.bindEvents();
            this.onReady();
        },

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Exemple: tracking des clics sur les boutons
            document.querySelectorAll(this.config.selectors.btn).forEach(btn => {
                btn.addEventListener('click', this.onButtonClick.bind(this));
            });
        },

        /**
         * Callback au chargement
         */
        onReady() {
            // Actions au chargement de la page
            const containers = document.querySelectorAll(this.config.selectors.container);
            
            if (containers.length > 0 && this.config.debug) {
                console.log('[WeprestaAcf] Found', containers.length, 'container(s)');
            }
        },

        /**
         * Gestionnaire de clic sur bouton
         * @param {Event} event
         */
        onButtonClick(event) {
            if (this.config.debug) {
                console.log('[WeprestaAcf] Button clicked', event.target);
            }

            // Exemple d'animation
            event.target.classList.add('is-loading');
        },

        /**
         * Méthode utilitaire: requête AJAX
         * @param {string} url
         * @param {Object} data
         * @returns {Promise}
         */
        async ajax(url, data = {}) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return await response.json();
            } catch (error) {
                console.error('[WeprestaAcf] AJAX Error:', error);
                throw error;
            }
        }
    };

    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => WeprestaAcf.init());
    } else {
        WeprestaAcf.init();
    }

    // Export global (optionnel)
    window.WeprestaAcf = WeprestaAcf;

})();
