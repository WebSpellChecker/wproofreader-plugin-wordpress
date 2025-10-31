/**
 * WProofreader Settings Page
 * Initializes the spell checker instance and fetches languages.
 */
jQuery(function ($) {
    'use strict';

    // Guards
    if (typeof WEBSPELLCHECKER === 'undefined' || typeof ProofreaderInstance === 'undefined') return;

    const $root = $('#wsc_proofreader');
    let $select = $root.find('select[name="wsc_proofreader[slang]"]');
    if (!$select.length) return;

    const enableGrammar = (ProofreaderInstance.enableGrammar === 'true');

    // Init WebSpellChecker
    const app = WEBSPELLCHECKER.initWebApi({
        autoSearch: true,
        serviceProtocol: 'https',
        serviceHost: 'svc.webspellchecker.net',
        servicePath: 'spellcheck31/api',
        servicePort: '443',
        enableGrammar: enableGrammar,
        serviceId: ProofreaderInstance.key_for_proofreader,
        lang: ProofreaderInstance.slang,
        appType: 'wp_plugin'
    });

    // Get info → send to WP → render languages
    app.getInfo({
        success(result) {
            $.ajax({
                type: 'POST',
                url: window.ajaxurl,
                data: {
                    action: 'get_proofreader_info_callback',
                    security: ProofreaderInstance.ajax_nonce,
                    getInfoResult: result
                }
            })
                .done((res) => {
                    // Support both: raw HTML string OR {success:true, data:"..."} OR {success:true, data:{html:"..."}}
                    const html =
                        (typeof res === 'string') ? res :
                            (res && res.success && typeof res.data?.html === 'string') ? res.data.html :
                                (res && res.success && typeof res.data === 'string') ? res.data : '';

                    if (!html) {
                        displayError('Failed to load language list. Invalid response format.');
                        return;
                    }

                    // If a full <select> returned — replace; otherwise swap <option>s
                    if (/<\s*select[^>]*>/i.test(html)) {
                        $select.replaceWith(html);
                        $select = $root.find('select[name="wsc_proofreader[slang]"]');
                    } else {
                        $select.html(html);
                    }
                })
                .fail((jqXHR, textStatus) => {
                    let msg = 'Failed to load language list.';
                    if (jqXHR?.responseJSON?.data?.message) {
                        msg = jqXHR.responseJSON.data.message;
                    } else if (textStatus) {
                        msg += ` (${textStatus})`;
                    }
                    displayError(msg);
                });
        },
        error(err) {
            displayError(err?.message || 'Unexpected initialization error.');
        }
    });

    function displayError(message) {
        const html =
            '<div class="error">' +
            `<p><strong>WProofreader:</strong> ${escapeHtml(message)}</p>` +
            '<p><a target="_blank" href="https://webspellchecker.com/contact-us/">Contact us</a> for technical assistance.</p>' +
            '</div>';
        $root.prepend(html);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
