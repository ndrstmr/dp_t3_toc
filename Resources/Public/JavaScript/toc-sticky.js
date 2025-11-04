/**
 * Table of Contents - Sticky Positioning Module (ES6)
 *
 * Provides sticky positioning using IntersectionObserver for:
 * - Fixed positioning without CSS position: sticky issues
 * - Dynamic frame wrapper creation
 * - Responsive width/position updates
 * - Performance optimized with requestAnimationFrame
 *
 * Exports:
 * - initSticky(): Initialize sticky positioning for all TOC elements
 * - cleanupSticky(): Cleanup observers and event listeners
 *
 * @module toc-sticky
 */

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
 * Initialize sticky positioning for TOC navigation elements
 *
 * Enhances TOC frames with wrapper elements and sets up fixed positioning
 * when sticky is enabled for sidebar layout.
 *
 * @export
 */
export function initSticky() {
    // Find all TOC nav elements with settings
    const tocNavElements = document.querySelectorAll('nav[data-toc-layout]');

    tocNavElements.forEach((nav) => {
        const layout = nav.getAttribute('data-toc-layout');
        const sticky = nav.getAttribute('data-toc-sticky') === '1' || nav.getAttribute('data-toc-sticky') === 'true';
        const scrollspy = nav.getAttribute('data-toc-scrollspy') === '1' || nav.getAttribute('data-toc-scrollspy') === 'true';

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
 * Cleanup sticky positioning instances
 *
 * Removes event listeners and disconnects observers.
 *
 * @export
 */
export function cleanupSticky() {
    const wrappers = document.querySelectorAll('.toc-sticky-wrapper');

    wrappers.forEach((wrapper) => {
        if (wrapper._cleanupFixed) {
            wrapper._cleanupFixed();
            delete wrapper._cleanupFixed;
        }
    });
}
