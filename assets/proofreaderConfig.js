var AppInstance;
window.WEBSPELLCHECKER_CONFIG = {
    autoSearch: true,
    serviceProtocol: 'https',
    serviceHost: 'svc.webspellchecker.net',
    servicePath: 'spellcheck31/script/ssrv.cgi',
    servicePort: '443',
    enableGrammar: WSCProofreaderConfig.enableGrammar,
    settingsSections: WSCProofreaderConfig.settingsSections,
    serviceId: WSCProofreaderConfig.key_for_proofreader,
    lang: WSCProofreaderConfig.slang,
    function(instance) {
        AppInstance = instance;
    },
};

console.log('loading');
