(function () {
    var WSC_DISABLE_AUTO_SEARCH_IN = [
        '.wp-block-table__cell-content',
        '.ui-autocomplete-input',
        '#wp-link-url',
        '#url',
        '#billing_phone',
        '#shipping_phone',
        '#siteurl',
        '#new_admin_email',
        '#home',
        '#billing_postcode',
        '#billing_email',
        '#shipping_postcode',
        '#mailserver_url',
        '#mailserver_login',
        '#ping_sites',
        '#permalink_structure',
        '.inline-edit-password-input'
    ];

    window.WEBSPELLCHECKER_CONFIG = {
        autoSearch: true,
        appType: 'wp_plugin',
        serviceProtocol: 'https',
        serviceHost: 'svc.webspellchecker.net',
        servicePath: 'spellcheck31/api',
        servicePort: '443',
        enableGrammar: (WSCProofreaderConfig.enableGrammar === 'true'),
        settingsSections: WSCProofreaderConfig.settingsSections,
        serviceId: WSCProofreaderConfig.key_for_proofreader,
        lang: WSCProofreaderConfig.slang,
        enableBadgeButton: (WSCProofreaderConfig.enableBadgeButton !== 'true'),
        actionItems: (WSCProofreaderConfig.disableBadgeButton === 'true') ? ['addWord', 'ignoreAll', 'settings', 'toggle', 'proofreadDialog'] : ['addWord', 'ignoreAll', 'settings', 'proofreadDialog'],
        disableAutoSearchIn: WSC_DISABLE_AUTO_SEARCH_IN,
        disableOptionsStorage: [],
        globalBadge: (WSCProofreaderConfig.globalBadge === 'true'),
        compactBadge: false,
        allSuggestionsMode: true,
        onLoad: function () {
            var self = this;

            this.subscribe('replaceProblem', function () {
                try {
                    var element = self.getContainerNode(),
                        event = document.createEvent('Event');

                    event.initEvent('input', true, false);
                    element.dispatchEvent(event);
                } catch (e) {
                }
            });

        },
        onBeforeAutoSearchInstanceCreate: function (activeElement, options) {
            var id = activeElement.element.id;
            return !(id && id.indexOf('url-input-control') === 0);
        }
    }
})();

