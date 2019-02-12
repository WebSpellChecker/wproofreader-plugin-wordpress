jQuery(document).ready(function ($) {
    var CheckEnableGrammar = (ProofreaderInstance.enableGrammar === 'true');
    var AppInstance;
    AppInstance = WEBSPELLCHECKER.initWebApi({
        autoSearch: true,
        serviceProtocol: 'https',
        serviceHost: 'svc.webspellchecker.net',
        servicePath: 'spellcheck31/script/ssrv.cgi',
        servicePort: '443',
        enableGrammar: CheckEnableGrammar,
        serviceId: ProofreaderInstance.key_for_proofreader,
        lang: ProofreaderInstance.slang,
        function(instance) {
            AppInstance = instance;
        },
    });
    AppInstance.getInfo({
        success: function (result) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "get_proofreader_info_callback",
                    security: ProofreaderInstance.ajax_nonce,
                    getInfoResult: result
                },
                success: function (data) {
                    $('#wsc_proofreader select ').html(data);
                },
                error: function (errorThrown) {
                    console.log(errorThrown);
                }
            });
        },
        error: function (res) {
            $('#wsc_proofreader').prepend('<span class="description" style="font-size:15px;color: red;"> Oooops! Something went wrong. Open browser console for details. For technical assistance contact us at <a href="mailto:support@webspellchecker.net">support@webspellchecker.net</a></span>');
        }
    });
});
