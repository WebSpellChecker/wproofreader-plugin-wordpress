var CheckEnableGrammar = (WSCProofreaderConfig.enableGrammar === 'true');
var disableBadgeButton = (WSCProofreaderConfig.disableBadgeButton === 'true');
actionItems = ['addWord', 'ignoreAll', 'settings', 'toggle', 'proofreadDialog'];
if (!disableBadgeButton) {
    actionItems = ['addWord', 'ignoreAll', 'settings', 'proofreadDialog'];
}

window.WEBSPELLCHECKER_CONFIG = {
    autoSearch: true,
    appType:'wp_plugin',
    serviceProtocol: 'https',
    serviceHost: 'svc.webspellchecker.net',
    servicePath: 'spellcheck31/script/ssrv.cgi',
    servicePort: '443',
    enableGrammar: CheckEnableGrammar,
    settingsSections: WSCProofreaderConfig.settingsSections,
    serviceId: WSCProofreaderConfig.key_for_proofreader,
    lang: WSCProofreaderConfig.slang,
    globalBadge: true,
    badgeOffsetX: 300,
    badgeOffsetY: 34,
    enableBadgeButton: disableBadgeButton,
    actionItems: actionItems,
    disableAutoSearchIn: ['.wp-block-table__cell-content','.ui-autocomplete-input','#wp-link-url'],
    onLoad:
        function () {
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

        }
}
;
