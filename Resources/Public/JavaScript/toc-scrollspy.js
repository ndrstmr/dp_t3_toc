/**
 * Table of Contents - Frame Enhancement & Bootstrap 5 Scrollspy
 *
 * State-of-the-art implementation with:
 * - Dynamic frame class and attribute manipulation
 * - Sticky wrapper for frameless elements
 * - Automatic scrollspy initialization
 * - Proper cleanup on page unload
 * - Support for dynamic content
 * - Performance optimized
 */

(function() {
    'use strict';

    /**
     * Setup fixed positioning using IntersectionObserver (modern, performant)
     *
     * @param {HTMLElement} wrapper - The wrapper element that will become fixed
     * @param {HTMLElement} placeholder - Placeholder to maintain layout
     * @param {HTMLElement} sentinel - Sentinel at original position (already in DOM)
     */
    function setupFixedPositioning(wrapper, placeholder, sentinel) {
        // Store state
        const state = {
            wrapper: wrapper,
            placeholder: placeholder,
            sentinel: sentinel,
            isFixed: false,
            width: 0,
            left: 0
        };

        // Calculate dimensions
        function updateDimensions() {
            const rect = state.isFixed ? placeholder.getBoundingClientRect() : wrapper.getBoundingClientRect();
            state.width = rect.width;
            state.left = rect.left;
        }

        // Apply fixed state
        function makeFixed() {
            if (state.isFixed) return;

            // CRITICAL: Set placeholder height BEFORE making fixed to prevent layout shift
            const wrapperHeight = wrapper.offsetHeight;
            placeholder.style.height = wrapperHeight + 'px';

            // Calculate dimensions
            updateDimensions();

            // Use requestAnimationFrame to batch DOM changes and reduce jank
            requestAnimationFrame(() => {
                wrapper.classList.add('is-fixed');
                wrapper.style.width = state.width + 'px';
                wrapper.style.left = state.left + 'px';
                placeholder.classList.add('is-active');
            });

            state.isFixed = true;
        }

        // Remove fixed state
        function makeStatic() {
            if (!state.isFixed) return;

            // Use requestAnimationFrame to batch DOM changes
            requestAnimationFrame(() => {
                wrapper.classList.remove('is-fixed');
                wrapper.style.width = '';
                wrapper.style.left = '';
                placeholder.classList.remove('is-active');
                placeholder.style.height = '';
            });

            state.isFixed = false;
        }

        // IntersectionObserver to detect when sentinel leaves viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Sentinel is visible = TOC should be static
                    makeStatic();
                } else if (entry.boundingClientRect.top < 0) {
                    // Sentinel scrolled above viewport = TOC should be fixed
                    makeFixed();
                }
            });
        }, {
            threshold: 0,
            rootMargin: '0px'
        });

        observer.observe(sentinel);

        // Handle resize with debounce
        let resizeTimeout;
        function onResize() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (state.isFixed) {
                    updateDimensions();
                    wrapper.style.width = state.width + 'px';
                    wrapper.style.left = state.left + 'px';
                }
            }, 150);
        }

        window.addEventListener('resize', onResize);

        // Initial dimensions
        updateDimensions();

        // Store cleanup function
        wrapper._cleanupFixed = function() {
            observer.disconnect();
            window.removeEventListener('resize', onResize);
            if (sentinel.parentNode) {
                sentinel.parentNode.removeChild(sentinel);
            }
        };
    }

    /**
     * Enhance TOC frames with wrapper and setup fixed positioning
     */
    function enhanceTocFrames() {
        // Find all TOC nav elements with settings
        const tocNavElements = document.querySelectorAll('nav[data-toc-layout]');

        tocNavElements.forEach((nav) => {
            const layout = nav.getAttribute('data-toc-layout');
            const sticky = nav.getAttribute('data-toc-sticky') === '1' || nav.getAttribute('data-toc-sticky') === 'true';
            const scrollspy = nav.getAttribute('data-toc-scrollspy') === '1' || nav.getAttribute('data-toc-scrollspy') === 'true';
            const uid = nav.getAttribute('data-toc-uid');

            // Only apply fixed positioning to sidebar layout with sticky
            if (layout !== 'sidebar' || !sticky) {
                return;
            }

            // Find parent frame (bootstrap_package frame structure)
            const frame = nav.closest('.frame');

            if (frame) {
                // Check if frame is already wrapped (avoid double-wrapping)
                if (!frame.parentElement.classList.contains('toc-sticky-wrapper')) {
                    // Create sentinel FIRST at original position (before any DOM manipulation)
                    // Sentinel stays in normal document flow to track scroll position
                    const sentinel = document.createElement('div');
                    sentinel.className = 'toc-sticky-sentinel';
                    sentinel.style.height = '1px';
                    sentinel.style.pointerEvents = 'none';
                    sentinel.style.visibility = 'hidden';
                    sentinel.style.margin = '0';
                    sentinel.style.padding = '0';

                    // Insert sentinel at original position
                    frame.parentNode.insertBefore(sentinel, frame);

                    // Create wrapper for fixed positioning
                    const wrapper = document.createElement('div');
                    wrapper.className = 'toc-sticky-wrapper';
                    wrapper.setAttribute('data-toc-layout', layout);
                    if (sticky) wrapper.setAttribute('data-toc-sticky', 'true');
                    if (scrollspy) wrapper.setAttribute('data-toc-scrollspy', 'true');

                    // Create placeholder to maintain layout
                    const placeholder = document.createElement('div');
                    placeholder.className = 'toc-sticky-placeholder';

                    // Insert placeholder after sentinel, then wrapper, then move frame into wrapper
                    frame.parentNode.insertBefore(placeholder, sentinel.nextSibling);
                    frame.parentNode.insertBefore(wrapper, placeholder);
                    wrapper.appendChild(frame);

                    // Setup IntersectionObserver-based fixed positioning
                    setupFixedPositioning(wrapper, placeholder, sentinel);
                }
            } else {
                // No frame: wrap nav directly
                // Create sentinel FIRST at original position
                // Sentinel stays in normal document flow to track scroll position
                const sentinel = document.createElement('div');
                sentinel.className = 'toc-sticky-sentinel';
                sentinel.style.height = '1px';
                sentinel.style.pointerEvents = 'none';
                sentinel.style.visibility = 'hidden';
                sentinel.style.margin = '0';
                sentinel.style.padding = '0';

                // Insert sentinel at original position
                nav.parentNode.insertBefore(sentinel, nav);

                const wrapper = document.createElement('div');
                wrapper.className = 'toc-sticky-wrapper';
                wrapper.setAttribute('data-toc-layout', layout);
                if (sticky) wrapper.setAttribute('data-toc-sticky', 'true');
                if (scrollspy) wrapper.setAttribute('data-toc-scrollspy', 'true');

                // Create placeholder
                const placeholder = document.createElement('div');
                placeholder.className = 'toc-sticky-placeholder';

                // Insert placeholder after sentinel, wrapper after placeholder, nav into wrapper
                nav.parentNode.insertBefore(placeholder, sentinel.nextSibling);
                nav.parentNode.insertBefore(wrapper, placeholder);
                wrapper.appendChild(nav);

                // Setup IntersectionObserver-based fixed positioning
                setupFixedPositioning(wrapper, placeholder, sentinel);
            }
        });
    }

    /**
     * Initialize scrollspy for TOC elements
     * Uses custom IntersectionObserver implementation (like Kickstarter)
     * instead of Bootstrap ScrollSpy for better control and reliability
     */
    function initializeScrollspy() {
        const debugMode = false; // Always enable debug for now

        // Configuration
        const CONFIG = {
            SCROLL_SPY_ROOT_MARGIN: '0px 0px -40% 0px',
            SCROLL_SPY_THRESHOLDS: [0, 0.1, 0.25, 0.5, 0.75, 1],
            ACTIVE_OFFSET_PX: 16 // 1rem buffer from viewport top
        };

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
     * Update active state based on visible sections
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
     * Cleanup scrollspy instances
     */
    function cleanupScrollspy() {
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
     * Useful when content is dynamically added/removed
     */
    function refreshScrollspy() {
        // For custom IntersectionObserver implementation, we need to re-initialize
        cleanupScrollspy();
        initializeScrollspy();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            enhanceTocFrames();
            initializeScrollspy();
        });
    } else {
        // DOM already loaded
        enhanceTocFrames();
        initializeScrollspy();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanupScrollspy);

    // Re-initialize on TYPO3 AJAX content load (if using AJAX navigation)
    document.addEventListener('typo3:contentLoaded', function() {
        enhanceTocFrames();
        initializeScrollspy();
    });

    // Expose public API for manual control
    window.TYPO3 = window.TYPO3 || {};
    window.TYPO3.TocScrollspy = {
        enhance: enhanceTocFrames,
        init: initializeScrollspy,
        cleanup: cleanupScrollspy,
        refresh: refreshScrollspy
    };

})();
