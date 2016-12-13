(function($){
    var SCAYTElements = [];
    var availableEditors = [
        {
            option: webSpellChecker.options.excerpt_field,
            element: document.getElementById('excerpt')
        },
        {
            option: webSpellChecker.options.title_field,
            element: document.getElementById('title')
        }
    ];

    function initSCAYT(availableEditors) {
        var elements = [];
        $(availableEditors).each(function () {
            if ('on' == this.option && this.element) {
                elements.push(
                    new SCAYT.SCAYT(
                        {
                            serviceProtocol: "http",
                            serviceHost: "svc.webspellchecker.net",
                            servicePort: "80",
                            servicePath: "spellcheck31/script/ssrv.cgi",
                            additionalMenuItems: {
                                "customItem1": {
                                    itemTitle: "Custom item in clipboard section",
                                    order: 0,
                                    group: "clipboard",
                                    extraClass: "some-class-1",
                                    onClick: function () {
                                        alert("Thank you! Context menu will be closed");
                                        return false;
                                    }
                                }
                            },
                            shortcutsList: [
                                {
                                    shortcut: SCAYT.CTRL + 66,
                                    callback: function () {
                                        alert("ctrl+B from user");
                                    }
                                }
                            ],
                            undoDataSize: 10,
                            theme: "classic",
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
                            spellcheckLang: webSpellChecker.options.slang,
                            onLoad: function () {
                                this.setDisabled(true);
                            }
                        }
                    )
                );
            }
        });
        return elements;
    }

    SCAYTElements = initSCAYT(availableEditors);

}(jQuery));