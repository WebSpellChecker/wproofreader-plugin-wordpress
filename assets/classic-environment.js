(function () {
    'use strict';

    const EDITOR_ID = 'content';
    const INSTANCE_ATTRIBUTE = 'data-wsc-instance';
    const INIT_DELAY = 100;
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
        const body = iframe.contentDocument && iframe.contentDocument.body;

        return Boolean(body && body.hasAttribute(INSTANCE_ATTRIBUTE));
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

        window.WEBSPELLCHECKER.init(Object.assign({}, window.WEBSPELLCHECKER_CONFIG, {
            container: iframe,
        }));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeClassicEditor);
    } else {
        initializeClassicEditor();
    }
})();
