/**
 * =============================================================================
 * WEDEV UI - Clipboard Helper
 * =============================================================================
 * Vanilla JS clipboard utilities. Works with or without Alpine.js.
 * =============================================================================
 */

/**
 * Copy text to clipboard with visual feedback.
 *
 * @param {string} text - Text to copy
 * @param {HTMLElement|null} button - Optional button element for feedback
 * @returns {Promise<boolean>} Success status
 *
 * @example
 * // Basic usage
 * await clipboard.copy('Hello World');
 *
 * // With button feedback
 * const btn = document.querySelector('.copy-btn');
 * btn.addEventListener('click', () => clipboard.copy('text', btn));
 */
export const clipboard = {
    /**
     * Copy text to clipboard
     */
    async copy(text, button = null) {
        try {
            await navigator.clipboard.writeText(text);
            
            if (button) {
                this.showFeedback(button);
            }
            
            // Dispatch event for listeners
            window.dispatchEvent(new CustomEvent('wedev-copied', {
                detail: { text, success: true }
            }));
            
            return true;
        } catch (error) {
            console.error('Clipboard copy failed:', error);
            
            // Fallback for older browsers
            return this.fallbackCopy(text, button);
        }
    },

    /**
     * Fallback copy using execCommand (for older browsers)
     */
    fallbackCopy(text, button = null) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.cssText = 'position:fixed;top:-9999px;left:-9999px;';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            
            if (success && button) {
                this.showFeedback(button);
            }
            
            return success;
        } catch (error) {
            document.body.removeChild(textarea);
            return false;
        }
    },

    /**
     * Show visual feedback on copy button
     */
    showFeedback(button, duration = 2000) {
        const originalHtml = button.innerHTML;
        const originalClasses = button.className;
        
        // Success state
        button.innerHTML = '<span class="material-icons" style="font-size:inherit">check</span>';
        button.classList.remove('btn-outline-secondary', 'btn-secondary');
        button.classList.add('btn-success');
        
        // Restore after duration
        setTimeout(() => {
            button.innerHTML = originalHtml;
            button.className = originalClasses;
        }, duration);
    },

    /**
     * Initialize copy buttons with data-copy attribute
     * 
     * @example
     * <button data-copy="https://example.com">Copy URL</button>
     */
    initButtons(container = document) {
        container.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy]');
            if (btn) {
                e.preventDefault();
                this.copy(btn.dataset.copy, btn);
            }
        });
    }
};

// Expose globally
if (typeof window !== 'undefined') {
    window.WedevClipboard = clipboard;
}

export default clipboard;

