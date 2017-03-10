(function($){
    
    var SCAYTElements = [];
    var availableEditors = [
        {
            option: webSpellChecker.options.excerpt_field,
            element: document.getElementById('excerpt'),
            styles: {
                display: 'block',
                margin: '12px 0 0',
                height: '4em',
                overflow: 'auto',
                padding: '2px 6px',
                linHeight: '1.4',
                resize: 'vertical',
                fontSize: '14px',
                fontFamily: 'inherit',
                borderRadius: '0',
                border: '1px solid #ddd',
                boxShadow: 'inset 0 1px 2px rgba( 0, 0, 0, 0.07 )',
                backgroundColor: '#fff',
                color: '#32373c',
                outline: 'none',
                transition: '0.05s border-color ease-in-out'
            }
        },
        {
            option: webSpellChecker.options.title_field,
            element: document.getElementById('title'),
            styles: {
                padding:'3px 8px',
                fontSize:'1.7em',
                fontFamily: 'inherit',
                color: '#32373c',
                outline:'none',
                margin:'0 0 3px',
                backgroundColor:'#fff',
                border: '1px solid #ddd'
            }
        }
    ];

    // ACF
    if (webSpellChecker.options.acf_fields == 'on') {
        var acfFields = $('.wsc_field');
        acfFields.each(function (inx, el) {
            var elementId = $(el).data('id');
            availableEditors.push({
                option: 'on',
                element: document.getElementById(elementId)
            });
        });
    }

    function initSCAYT(availableEditors) {
        var elements = [];
        var el = {};
        $(availableEditors).each(function () {
            if ('on' == this.option && this.element) {
                elements.push(
                    el = new SCAYT.SCAYT(
                        {
                            serviceProtocol: document.location.protocol.slice(0, -1),
                            serviceHost: "svc.webspellchecker.net",
                            servicePort: "80",
                            servicePath: "spellcheck31/script/ssrv.cgi",
                            shortcutsList: [
                                {
                                    shortcut: SCAYT.CTRL + 66,
                                    callback: function () {
                                        alert("ctrl+B from user");
                                    }
                                }
                            ],
                            undoDataSize: 10,
                            minWordLength: 4,
                            localization: "en",
                            elementsToIgnore: "style|script",
                            userDictionaryName: "MyUserDictionaryName",
                            customDictionaryIds: "ID1, ID2",
                            customerId: webSpellChecker.options.customer_id,
                            autoStartup: true,
                            contextMenuSections: 'undoredo|suggest|moresuggest|control|clipboard|options',
                            contextMenuCommands: 'undo|redo|ignore|ignoreall|addword|paste|cut|copy|options|languages|dictionaries|about|togglescayt|help|customItem1',
                            uiTabs: 'options,languages,dictionaries,about',
                            suggestionsCount: 3,
                            moreSuggestionsCount: 4,
                            container: this.element,
                            spellcheckLang: webSpellChecker.options.slang
                        }
                    )
                );
                el.setCssStyles(this.styles);
            }
        });
        return elements;
    }

    setTimeout(function () {
        // Yoast
        availableEditors.push(
            {
                option: webSpellChecker.options.yoast_title_field,
                element: document.getElementById('snippet-editor-title')
            },
            {
                option: webSpellChecker.options.yoast_description_field,
                element: document.getElementById('snippet-editor-meta-description')
            }
        );
        
        initSCAYT(availableEditors);
    }, 1500);

}(jQuery));