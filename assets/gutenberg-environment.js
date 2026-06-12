(function () {
    'use strict';
    const SELECTORS = {
        RICH_TEXT: '.rich-text',
        MCE_CONTENT_BODY: '.mce-content-body',
        GUTENBERG_PAGE: 'block-editor-page',
        GUTENBERG_IFRAME: 'block-editor-iframe__body',
        TABLE_CELL: 'wp-block-table__cell-content'
    };

    // Own marker set right before init: keeps re-init idempotent even when the
    // SDK instance fails to start (e.g. service unreachable).
    const OWN_INSTANCE_ATTRIBUTE = 'data-wsc-init';
    // Attribute the SDK sets on a container once an instance is created.
    const SDK_INSTANCE_ATTRIBUTE = 'data-wpr-instance';

    const INIT_DELAY = 100;
    const INIT_TIMEOUT = 5000;
    const MAX_ATTEMPTS = 50;
    const OBSERVER_DEBOUNCE = 250;

    let attempts = 0;
    let observer = null;
    let observerTimer = null;
    const pendingRoots = new Set();

    const isGutenbergActive = () => {
        return document.body.classList.contains(SELECTORS.GUTENBERG_PAGE) ||
            document.body.classList.contains(SELECTORS.GUTENBERG_IFRAME);
    };

    function isContentEditable(element) {
        return element.isContentEditable;
    }

    function isInstanceCreated(element) {
        return element.hasAttribute(OWN_INSTANCE_ATTRIBUTE) ||
            element.hasAttribute(SDK_INSTANCE_ATTRIBUTE);
    }

    function isGutenbergTableCell(element) {
        return element.classList.contains(SELECTORS.TABLE_CELL);
    }

    const shouldIgnoreElement = (element) => {
        if (!isContentEditable(element)) {
            return true;
        }

        if (isInstanceCreated(element)) {
            return true;
        }

        if (isGutenbergTableCell(element)) {
            return true;
        }

        return false;
    };

    const createInstance = (element) => {
        element.setAttribute(OWN_INSTANCE_ATTRIBUTE, '1');

        try {
            WEBSPELLCHECKER.init({
                container: element,
            });

            // The SDK reports successful creation with data-wpr-instance. Do
            // not let our pending marker suppress retries forever otherwise.
            window.setTimeout(() => {
                if (!element.hasAttribute(SDK_INSTANCE_ATTRIBUTE)) {
                    element.removeAttribute(OWN_INSTANCE_ATTRIBUTE);
                }
            }, INIT_TIMEOUT);
        } catch (e) {
            // Allow a later retry if init threw synchronously.
            element.removeAttribute(OWN_INSTANCE_ATTRIBUTE);
        }
    };

    const initializeElements = (selector, root = document) => {
        let found = 0;
        const elements = [];

        if (root.nodeType === Node.ELEMENT_NODE && root.matches(selector)) {
            elements.push(root);
        }

        root.querySelectorAll(selector).forEach((element) => {
            elements.push(element);
        });

        elements.forEach((element) => {
            found++;

            if (shouldIgnoreElement(element)) {
                return;
            }

            createInstance(element);
        });

        return found;
    };

    const initializeAll = (root = document) => {
        return initializeElements(SELECTORS.RICH_TEXT, root) +
            initializeElements(SELECTORS.MCE_CONTENT_BODY, root);
    };

    // Blocks added after load (new paragraphs, async patterns) get instances too.
    const observeLateBlocks = () => {
        if (observer || typeof MutationObserver === 'undefined') {
            return;
        }

        observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        pendingRoots.add(node);
                    }
                });
            });

            if (pendingRoots.size === 0) {
                return;
            }

            if (observerTimer) {
                window.clearTimeout(observerTimer);
            }

            observerTimer = window.setTimeout(() => {
                pendingRoots.forEach((root) => {
                    if (root.isConnected) {
                        initializeAll(root);
                    }
                });
                pendingRoots.clear();
            }, OBSERVER_DEBOUNCE);
        });

        observer.observe(document.body, { childList: true, subtree: true });
    };

    const handleGutenbergReady = () => {
        attempts++;

        const found = initializeAll();

        if (found === 0 && attempts < MAX_ATTEMPTS) {
            window.setTimeout(handleGutenbergReady, INIT_DELAY);
            return;
        }

        observeLateBlocks();
    };

    const handleGutenbergReadyWithDelay = () => {
        setTimeout(handleGutenbergReady, INIT_DELAY);
    };

    window.webspellcheckerAlreadyLoaded = () => {
        if (!isGutenbergActive() || !window.WEBSPELLCHECKER_CONFIG?.globalBadge) {
            return;
        }

        handleGutenbergReadyWithDelay();
    };
})();
