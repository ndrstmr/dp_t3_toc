/**
 * Table of Contents - Scrollspy Module (ES6)
 *
 * Provides scrollspy functionality using IntersectionObserver:
 * - Auto-highlights active navigation items based on scroll position
 * - Custom implementation (no Bootstrap JS required)
 * - Performance optimized with IntersectionObserver API
 * - Supports nested navigation structures
 *
 * @module @ndrstmr/dp-t3-toc/toc-scrollspy
 */

/**
 * Configuration for scrollspy behavior
 */
const CONFIG = {
    SCROLL_SPY_ROOT_MARGIN: '0px 0px -40% 0px',
    SCROLL_SPY_THRESHOLDS: [0, 0.1, 0.25, 0.5, 0.75, 1],
    ACTIVE_OFFSET_PX: 16, // 1rem buffer from viewport top
    DEBUG_MODE: false
};

/**
 * Update active state based on visible sections
 *
 * @param {Array} targets - Navigation targets
 * @param {Set} visibleSections - Set of currently visible section IDs
 * @param {number} activeOffset - Vertical offset for active state calculation
 */
function updateActiveState(targets, visibleSections, activeOffset) {
    let activeSection = null;
    let closestDistance = Infinity;

    targets.forEach(item => {
        if (visibleSections.has(item.target.id)) {
            const rect = item.target.getBoundingClientRect();
            const distance = activeOffset - rect.top;

            // Check if section is in viewport and above active offset
            if (rect.top < window.innerHeight && rect.bottom > 0 && rect.top <= activeOffset) {
                if (distance >= 0 && distance < closestDistance) {
                    closestDistance = distance;
                    activeSection = item;
                }
            }
        }
    });

    // Remove active class from all items
    targets.forEach(item => {
        if (item.listItem) {
            item.listItem.classList.remove('active');
        }
    });

    // Add active class to closest section
    if (activeSection && activeSection.listItem) {
        activeSection.listItem.classList.add('active');
    }
}

/**
 * Set initial active state for first visible section
 *
 * @param {Array} targets - Navigation targets
 */
function setInitialActiveState(targets) {
    if (targets.length === 0) return;

    for (let i = 0; i < targets.length; i++) {
        const rect = targets[i].target.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            targets[i].listItem?.classList.add('active');
            return;
        }
    }
}

/**
 * Initialize scrollspy for TOC navigation elements
 *
 * Uses custom IntersectionObserver implementation for better control
 * and reliability compared to Bootstrap ScrollSpy.
 *
 * @export
 */
export function initScrollspy() {
    const debugMode = CONFIG.DEBUG_MODE;

    // Find all TOC elements with scrollspy enabled
    const tocElements = document.querySelectorAll('[data-toc-scrollspy="true"]');

    if (debugMode) {
        console.log('TOC Scrollspy: Found', tocElements.length, 'elements with data-toc-scrollspy="true"');
    }

    if (tocElements.length === 0) {
        if (debugMode) {
            console.warn('TOC Scrollspy: No elements found with data-toc-scrollspy="true"');
        }
        return;
    }

    tocElements.forEach((container) => {
        // Find the nav element within the container
        const navElement = container.querySelector('nav[id^="toc-"], nav[id^="kern-toc-"]');

        if (!navElement) {
            console.warn('TOC Scrollspy: No nav element found within container', container);
            return;
        }

        const navId = navElement.getAttribute('id');

        if (!navId) {
            console.warn('TOC Scrollspy: Nav element has no ID', navElement);
            return;
        }

        // Collect all links and their targets
        const navLinks = navElement.querySelectorAll('a[href^="#"]');
        const targets = [];

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const target = document.querySelector(href);
            const listItem = link.closest('.nav-item, .toc__item, .kern-toc__item');

            if (target && listItem) {
                targets.push({ link, target, listItem });
            } else if (debugMode) {
                console.warn('TOC Scrollspy: Target or listItem not found for', href);
            }
        });

        if (debugMode) {
            console.log('TOC Scrollspy: Nav', navId, 'has', targets.length, 'valid targets');
        }

        if (targets.length === 0) return;

        // Setup IntersectionObserver for tracking visible sections
        const visibleSections = new Set();

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    visibleSections.add(entry.target.id);
                } else {
                    visibleSections.delete(entry.target.id);
                }
            });

            updateActiveState(targets, visibleSections, CONFIG.ACTIVE_OFFSET_PX);
        }, {
            rootMargin: CONFIG.SCROLL_SPY_ROOT_MARGIN,
            threshold: CONFIG.SCROLL_SPY_THRESHOLDS
        });

        targets.forEach(item => observer.observe(item.target));

        // Set initial active state
        setInitialActiveState(targets);

        // Store observer for cleanup
        container._scrollSpyObserver = observer;

        if (debugMode) {
            console.log('TOC Scrollspy: Successfully initialized for', navId);
        }
    });
}

/**
 * Cleanup scrollspy instances
 *
 * Removes observers and cleans up stored references.
 *
 * @export
 */
export function cleanupScrollspy() {
    const tocElements = document.querySelectorAll('[data-toc-scrollspy="true"]');

    tocElements.forEach((container) => {
        if (container._scrollSpyObserver) {
            container._scrollSpyObserver.disconnect();
            delete container._scrollSpyObserver;
        }
    });
}

/**
 * Refresh scrollspy instances
 *
 * Useful when content is dynamically added/removed.
 * Re-initializes all scrollspy instances.
 *
 * @export
 */
export function refreshScrollspy() {
    // For custom IntersectionObserver implementation, we need to re-initialize
    cleanupScrollspy();
    initScrollspy();
}
