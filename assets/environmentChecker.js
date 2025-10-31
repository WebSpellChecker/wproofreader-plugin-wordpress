window.addEventListener("load", (event) => {
    var  bundlePath = 'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js';

    function loadScript(doc, src) {
        const script = doc.createElement('script');
        const appendTo = doc.head || doc.documentElement;

        script.type = 'text/javascript';
        script.charset = 'UTF-8';
        script.async = false;
        script.src = src;

        appendTo.appendChild(script);
    }

    window.gutenbergIframe = document.querySelector('[name=editor-canvas]');
    window.WEBSPELLCHECKER_CONFIG.globalBadge= true;

    if (window.gutenbergIframe) {
        window.WEBSPELLCHECKER_CONFIG.globalBadge = false;
        loadScript(window.gutenbergIframe.contentWindow.document, bundlePath);
        window.gutenbergIframe = null;
    }

    loadScript(document, bundlePath);
});