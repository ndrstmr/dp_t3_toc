/**
 * Table of Contents - Core Module (ES6)
 *
 * Main entry point that dynamically loads sticky and scrollspy modules as needed.
 * Uses ES6 dynamic imports for optimal performance - only loads what's required.
 *
 * Features:
 * - Lazy loading of sticky module (only when data-toc-sticky="true")
 * - Lazy loading of scrollspy module (only when data-toc-scrollspy="true")
 * - No dependencies - modules load independently
 * - TYPO3 AJAX content support
 * - Proper cleanup on page unload
 * - Uses relative imports (./toc-sticky.js) for frontend compatibility
 *
 * @module toc-core
 */

/**
 * Initialize TOC enhancements based on data attributes
 *
 * Scans the DOM for TOC navigation elements and dynamically imports
 * the required modules (sticky and/or scrollspy) based on their settings.
 */
async function init() {
    const tocElements = document.querySelectorAll('nav[data-toc-layout]');

    if (tocElements.length === 0) {
        return;
    }

    // Check if any element needs sticky
    const needsSticky = Array.from(tocElements).some(nav =>
        nav.getAttribute('data-toc-sticky') === '1' ||
        nav.getAttribute('data-toc-sticky') === 'true'
    );

    // Check if any element needs scrollspy
    const needsScrollspy = Array.from(tocElements).some(nav =>
        nav.getAttribute('data-toc-scrollspy') === '1' ||
        nav.getAttribute('data-toc-scrollspy') === 'true'
    );

    // Dynamic imports - only load what's needed
    // Using relative paths (import maps only work in TYPO3 backend)
    try {
        if (needsSticky) {
            const { initSticky } = await import('./toc-sticky.js');
            initSticky();
        }

        if (needsScrollspy) {
            const { initScrollspy } = await import('./toc-scrollspy.js');
            initScrollspy();
        }
    } catch (error) {
        console.error('TOC: Error loading modules', error);
    }
}

/**
 * Cleanup all TOC enhancements
 *
 * Calls cleanup functions from loaded modules.
 */
async function cleanup() {
    try {
        // Check which modules were loaded by checking if functions exist
        const tocElements = document.querySelectorAll('nav[data-toc-layout]');

        const hasSticky = Array.from(tocElements).some(nav =>
            nav.getAttribute('data-toc-sticky') === '1' ||
            nav.getAttribute('data-toc-sticky') === 'true'
        );

        const hasScrollspy = document.querySelectorAll('[data-toc-scrollspy="true"]').length > 0;

        if (hasSticky) {
            const { cleanupSticky } = await import('./toc-sticky.js');
            cleanupSticky();
        }

        if (hasScrollspy) {
            const { cleanupScrollspy } = await import('./toc-scrollspy.js');
            cleanupScrollspy();
        }
    } catch (error) {
        console.error('TOC: Error during cleanup', error);
    }
}

/**
 * Refresh TOC enhancements
 *
 * Useful when content is dynamically added/removed.
 * Cleans up existing instances and re-initializes.
 */
async function refresh() {
    await cleanup();
    await init();
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM already loaded
    init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', cleanup);

// Re-initialize on TYPO3 AJAX content load (if using AJAX navigation)
document.addEventListener('typo3:contentLoaded', init);

// Expose public API for manual control
window.TYPO3 = window.TYPO3 || {};
window.TYPO3.Toc = {
    init: init,
    cleanup: cleanup,
    refresh: refresh
};
