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
        appType:'wp_plugin',
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
                    /// console.log(errorThrown);
                }
            });
        },
        error: function (e) {
            if(e.message){
                var message = '<strong>WProofreader:</strong> '+e.message;
                $('#wsc_proofreader').prepend('<div class="error"><p>'+message+'.</p><p><a target="_blank" href="https://webspellchecker.com/contact-us/">Contact us</a> for technical assistance.</p></div>');
            }
        }
    });
});
