var CheckEnableGrammar = (WSCProofreaderConfig.enableGrammar === 'true');
var disableBadgeButton = (WSCProofreaderConfig.disableBadgeButton === 'true');
actionItems = ['addWord', 'ignoreAll', 'settings', 'toggle', 'proofreadDialog'];
if (!disableBadgeButton) {
    actionItems = ['addWord', 'ignoreAll', 'settings', 'proofreadDialog'];
}
window.WEBSPELLCHECKER_CONFIG = {
    autoSearch: true,
    serviceProtocol: 'https',
    serviceHost: 'svc.webspellchecker.net',
    servicePath: 'spellcheck31/script/ssrv.cgi',
    servicePort: '443',
    enableGrammar: CheckEnableGrammar,
    settingsSections: WSCProofreaderConfig.settingsSections,
    serviceId: WSCProofreaderConfig.key_for_proofreader,
    lang: WSCProofreaderConfig.slang,
    enableBadgeButton: disableBadgeButton,
    actionItems: actionItems,
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
