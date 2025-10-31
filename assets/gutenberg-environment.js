(function () {
    'use strict';
    const SELECTORS = {
        RICH_TEXT: '.rich-text',
        MCE_CONTENT_BODY: '.mce-content-body',
        GUTENBERG_PAGE: 'block-editor-page',
        GUTENBERG_IFRAME: 'block-editor-iframe__body',
        TABLE_CELL: 'wp-block-table__cell-content'
    };

    const INSTANCE_ATTRIBUTE = 'data-wsc-instance';
    const INIT_DELAY = 100;

    const isGutenbergActive = () => {
        return document.body.classList.contains(SELECTORS.GUTENBERG_PAGE) ||
               document.body.classList.contains(SELECTORS.GUTENBERG_IFRAME);
    };

    function isContentEditable(element) {
        return element.isContentEditable;
    }
    function isInstanceCreated(element) {
        return element.hasAttribute(INSTANCE_ATTRIBUTE);
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
        WEBSPELLCHECKER.init({
            container: element,
        });
    };

    const initializeElements = (selector) => {
        document.querySelectorAll(selector).forEach((element) => {
            if (shouldIgnoreElement(element)) {
                return;
            }

            createInstance(element);
        });
    };

    const handleGutenbergReady = () => {
        initializeElements(SELECTORS.RICH_TEXT);
        initializeElements(SELECTORS.MCE_CONTENT_BODY);
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