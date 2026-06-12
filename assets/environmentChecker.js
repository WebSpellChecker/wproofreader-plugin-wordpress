window.addEventListener("load", (event) => {
    var serviceConfig = window.WSCServiceConfig || {};
    var bundlePath = serviceConfig.bundleUrl || 'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js';

    function loadScript(doc, src) {
        // One bundle copy per document. This script runs both in the top frame
        // and inside the editor-canvas iframe, and the top copy also injects
        // into the iframe, so without the flag the bundle could load twice.
        var win = doc.defaultView;
        if (!win || win.__wscBundleRequested) {
            return;
        }
        win.__wscBundleRequested = true;

        const script = doc.createElement('script');
        const appendTo = doc.head || doc.documentElement;

        script.type = 'text/javascript';
        script.async = false;
        script.src = src;

        appendTo.appendChild(script);
    }

    if (!window.WEBSPELLCHECKER_CONFIG) {
        return;
    }

    window.gutenbergIframe = document.querySelector('[name=editor-canvas]');

    if (window.gutenbergIframe) {
        // The iframe document hosts the editable content and shows the badge;
        // the top frame keeps a badge-less SDK for classic metabox fields.
        const iframeConfig = window.gutenbergIframe.contentWindow.WEBSPELLCHECKER_CONFIG;
        if (iframeConfig && window.WEBSPELLCHECKER_CONFIG.globalBadge) {
            window.WEBSPELLCHECKER_CONFIG.globalBadge = false;
        }
        loadScript(window.gutenbergIframe.contentWindow.document, bundlePath);
        window.gutenbergIframe = null;
    }

    loadScript(document, bundlePath);
});
