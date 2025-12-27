/**
 * =============================================================================
 * WEDEV Sortable Extension
 * =============================================================================
 * Generic sortable list functionality with SortableJS fallback.
 * =============================================================================
 */

window.WedevSortable = (function() {
    'use strict';

    /**
     * Initialize a sortable container.
     * 
     * @param {HTMLElement} container - Container element
     * @param {Object} options - Configuration
     * @param {string} options.itemSelector - Selector for sortable items (default: '[data-id]')
     * @param {string} options.handleSelector - Selector for drag handle (optional)
     * @param {string} options.orderInput - Selector for hidden order input (optional)
     * @param {Function} options.onSort - Callback after sorting
     */
    function init(container, options = {}) {
        if (!container || container.dataset.wedevSortable === '1') return;
        container.dataset.wedevSortable = '1';

        const config = {
            itemSelector: options.itemSelector || '[data-id], [data-index]',
            handleSelector: options.handleSelector || null,
            orderInput: options.orderInput || '.wedev-sortable-order, .acf-order',
            onSort: options.onSort || null,
            ghostClass: options.ghostClass || 'opacity-50',
            animation: options.animation || 150
        };

        // Try SortableJS first
        if (typeof Sortable !== 'undefined') {
            initWithSortableJS(container, config);
        } else {
            // Native fallback
            initNative(container, config);
        }
    }

    /**
     * Initialize with SortableJS library.
     */
    function initWithSortableJS(container, config) {
        const sortableOptions = {
            animation: config.animation,
            ghostClass: config.ghostClass,
            onEnd: () => {
                updateOrder(container, config);
                if (config.onSort) config.onSort(getOrder(container, config));
            }
        };

        if (config.handleSelector) {
            sortableOptions.handle = config.handleSelector;
        }

        new Sortable(container, sortableOptions);
    }

    /**
     * Initialize with native HTML5 drag and drop.
     */
    function initNative(container, config) {
        const items = container.querySelectorAll(config.itemSelector);
        let draggedItem = null;

        items.forEach((item) => {
            // Make draggable
            if (config.handleSelector) {
                const handle = item.querySelector(config.handleSelector);
                if (handle) {
                    handle.style.cursor = 'grab';
                    handle.addEventListener('mousedown', () => item.setAttribute('draggable', 'true'));
                    handle.addEventListener('mouseup', () => item.setAttribute('draggable', 'false'));
                }
            } else {
                item.setAttribute('draggable', 'true');
            }

            item.addEventListener('dragstart', (e) => {
                draggedItem = item;
                item.classList.add(config.ghostClass);
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove(config.ghostClass);
                draggedItem = null;
                updateOrder(container, config);
                if (config.onSort) config.onSort(getOrder(container, config));
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!draggedItem || draggedItem === item) return;

                const rect = item.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;

                if (e.clientY < midY) {
                    container.insertBefore(draggedItem, item);
                } else {
                    container.insertBefore(draggedItem, item.nextSibling);
                }
            });
        });
    }

    /**
     * Get current order of items.
     * 
     * @param {HTMLElement} container
     * @param {Object} config
     * @returns {string[]} Array of item IDs
     */
    function getOrder(container, config = {}) {
        const selector = config.itemSelector || '[data-id], [data-index]';
        const items = container.querySelectorAll(selector);
        const order = [];

        items.forEach((item) => {
            const id = item.dataset.id || item.dataset.index;
            if (id) order.push(id);
        });

        return order;
    }

    /**
     * Update hidden order input.
     */
    function updateOrder(container, config) {
        const orderInput = container.querySelector(config.orderInput) ||
                           container.closest('form')?.querySelector(config.orderInput);
        
        if (orderInput) {
            orderInput.value = JSON.stringify(getOrder(container, config));
        }
    }

    /**
     * Auto-initialize all sortable containers.
     */
    function initAll(containerSelector = '.wedev-sortable, .acf-sortable') {
        document.querySelectorAll(containerSelector).forEach((el) => {
            init(el, {
                handleSelector: el.dataset.handle || null,
                orderInput: el.dataset.orderInput || null
            });
        });
    }

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================
    
    return {
        init,
        initAll,
        getOrder,
        updateOrder
    };
})();

// Attach to Wedev namespace if available
if (typeof window.Wedev !== 'undefined') {
    window.Wedev.Sortable = window.WedevSortable;
}

console.log('WEDEV Sortable Extension loaded');

