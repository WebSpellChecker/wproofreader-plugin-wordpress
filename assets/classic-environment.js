(function () {
    'use strict';

    const EDITOR_ID = 'content';
    // Own marker (set below) plus the attribute the SDK sets on its container.
    const OWN_INSTANCE_ATTRIBUTE = 'data-wsc-init';
    const SDK_INSTANCE_ATTRIBUTE = 'data-wpr-instance';
    const INIT_DELAY = 100;
    const INIT_TIMEOUT = 5000;
    const MAX_ATTEMPTS = 50;

    let attempts = 0;

    function editorInstance() {
        if (!window.tinymce || typeof window.tinymce.get !== 'function') {
            return null;
        }

        return window.tinymce.get(EDITOR_ID);
    }

    function editorIframe(editor) {
        const contentArea = editor.getContentAreaContainer && editor.getContentAreaContainer();

        return editor.iframeElement ||
            (contentArea && contentArea.querySelector('iframe'));
    }

    function hasInstance(iframe) {
        // The SDK marks the container (the iframe element) with data-wpr-instance;
        // the temporary own marker prevents duplicate concurrent initialization.
        if (iframe.hasAttribute(OWN_INSTANCE_ATTRIBUTE) || iframe.hasAttribute(SDK_INSTANCE_ATTRIBUTE)) {
            return true;
        }

        const body = iframe.contentDocument && iframe.contentDocument.body;

        return Boolean(body && (body.hasAttribute(OWN_INSTANCE_ATTRIBUTE) || body.hasAttribute(SDK_INSTANCE_ATTRIBUTE)));
    }

    function initializeClassicEditor() {
        attempts++;

        const editor = editorInstance();
        const iframe = editor && editorIframe(editor);

        if (!editor || !iframe || !window.WEBSPELLCHECKER || !window.WEBSPELLCHECKER_CONFIG) {
            if (attempts < MAX_ATTEMPTS) {
                window.setTimeout(initializeClassicEditor, INIT_DELAY);
            }

            return;
        }

        if (hasInstance(iframe)) {
            return;
        }

        iframe.setAttribute(OWN_INSTANCE_ATTRIBUTE, '1');

        // The SDK merges the global WEBSPELLCHECKER_CONFIG itself; pass only the container
        // (same convention as gutenberg-environment.js).
        try {
            window.WEBSPELLCHECKER.init({
                container: iframe,
            });

            window.setTimeout(() => {
                const body = iframe.contentDocument && iframe.contentDocument.body;
                const sdkInstanceCreated = iframe.hasAttribute(SDK_INSTANCE_ATTRIBUTE) ||
                    Boolean(body && body.hasAttribute(SDK_INSTANCE_ATTRIBUTE));

                if (!sdkInstanceCreated) {
                    iframe.removeAttribute(OWN_INSTANCE_ATTRIBUTE);
                }
            }, INIT_TIMEOUT);
        } catch (e) {
            iframe.removeAttribute(OWN_INSTANCE_ATTRIBUTE);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeClassicEditor);
    } else {
        initializeClassicEditor();
    }
})();
