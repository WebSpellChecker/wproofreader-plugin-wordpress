(function () {
    'use strict';

    function asBoolean(value, fallback) {
        if (value === true || value === 'true') return true;
        if (value === false || value === 'false') return false;
        return fallback;
    }

    function asArray(value) {
        return Array.isArray(value) ? value : [];
    }

    const serviceConfig = window.WSCServiceConfig || {};
    const proofreaderConfig = window.WSCProofreaderConfig || {};

    const isBadgeEnabled = asBoolean(proofreaderConfig.enableBadgeButton, true);
    const badgeActions = isBadgeEnabled
        ? ['addWord', 'ignoreAll', 'settings', 'toggle', 'proofreadDialog']
        : ['addWord', 'ignoreAll', 'settings', 'proofreadDialog'];

    const disableAutoSearchIn = [
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
        '.inline-edit-password-input',
    ];

    window.WEBSPELLCHECKER_CONFIG = {
        autoSearch: true,
        appType: 'wp_plugin',
        serviceProtocol: serviceConfig.serviceProtocol || 'https',
        serviceHost: serviceConfig.serviceHost || 'svc.webspellchecker.net',
        servicePath: serviceConfig.servicePath || 'api',
        servicePort: serviceConfig.servicePort || '443',
        enableGrammar: asBoolean(proofreaderConfig.enableGrammar, false),
        aiWritingAssistant: asBoolean(proofreaderConfig.aiWritingAssistant, false),
        settingsSections: asArray(proofreaderConfig.settingsSections),
        serviceId: proofreaderConfig.key_for_proofreader,
        lang: proofreaderConfig.slang,
        enableBadgeButton: isBadgeEnabled,
        actionItems: badgeActions,
        disableAutoSearchIn: disableAutoSearchIn,
        disableOptionsStorage: asArray(proofreaderConfig.disableOptionsStorage),
        disableDictionariesPreferences: asBoolean(proofreaderConfig.disableDictionariesPreferences, false),
        autocomplete: asBoolean(proofreaderConfig.autocomplete, false),
        globalBadge: asBoolean(proofreaderConfig.globalBadge, true),
        compactBadge: asBoolean(proofreaderConfig.compactBadge, false),
        allSuggestionsMode: true,
        onLoad: function () {
            const instance = this;

            this.subscribe('replaceProblem', function () {
                try {
                    const element = instance.getContainerNode();
                    element.dispatchEvent(new Event('input', { bubbles: true }));
                } catch (e) {
                    // Container may have been detached by the host editor — safe to ignore.
                }
            });
        },
        onBeforeAutoSearchInstanceCreate: function (activeElement) {
            const id = activeElement.element.id;
            return !(id && id.indexOf('url-input-control') === 0);
        },
    };

    if (Array.isArray(proofreaderConfig.generalOptions) && proofreaderConfig.generalOptions.length) {
        window.WEBSPELLCHECKER_CONFIG.generalOptions = proofreaderConfig.generalOptions;
    }
})();
