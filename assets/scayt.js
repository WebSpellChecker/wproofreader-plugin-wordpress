//var input = document.getElementById('title');
var textarea = document.getElementById('content');


//var inputSCAYT = new SCAYT.SCAYT({
//    //DOM node with text or its selector.
//    container: input,
//    //The parameter allows to specify protocol for WSC service (entry point is ssrv.cgi) full path.
//    serviceProtocol: "http",
//    //The parameter allows to specify host for WSC service (entry point is ssrv.cgi) full path.
//    serviceHost: "svc.webspellchecker.net",
//    //The parameter allows to specify default port for WSC service (entry point is ssrv.cgi) full path.
//    servicePort: "80",
//    //The parameter allows to specify path for WSC service (entry point is ssrv.cgi) full path.
//    servicePath: "spellcheck31/script/ssrv.cgi",
//    //The parameter allows to specify user defined menu items with custom behavior.
//    additionalMenuItems: {
//        "customItem1": {
//            itemTitle: "Custom item in clipboard section",
//            order: 0,
//            group: "clipboard",
//            extraClass: "some-class-1",
//            onClick: function() {
//                alert("Thank you! Context menu will be closed");
//                return false;
//            }
//        }
//    },
//    //The parameter sets a shortcuts list. It is an array containing objects with a shortcut and callback function.
//    shortcutsList: [
//        {
//            shortcut: SCAYT.CTRL + 66,
//            callback: function() {
//                alert("ctrl+B from user");
//            }
//        }
//    ],
//    //The parameter sets length of the Undo array.
//    undoDataSize: 10,
//    //The parameter allows to specify a desired theme for UI.
//    theme: "classic",
//    //The parameter defines minimum length of the letters that will be collected from
//    // container's text for spell checking. Possible value is any positive number.
//    minWordLength: 3,
//    //The parameter sets localization. If no parameter is specified by a user,
//    //it will be detected automatically from a browser settings.
//    //If the parameter can't be automatically detected, a default value will be used.
//    localization: "en",
//    //The parameter specifies the names of tags which will be skipped while spell checking.
//    //It is a string containing tag names separated by commas (",").
//    //Please note that the "style" and "script" tags will be added to a specified tag list.
//    elementsToIgnore: "style|script",
//    //The parameter activates a User Dictionary in SCAYT.
//    userDictionaryName: "MyUserDictionaryName",
//    //The parameter links SCAYT to custom dictionaries.
//    //Here is a string containing dictionary IDs separated by commas (",").
//    customDictionaryIds: "ID1, ID2",
//    //The parameter sets the customer ID for SCAYT.
//    //It used for a migration from free, ad-supported version to paid, ad-free version.
//    customerId: "encrypted-customer-id",
//    // The parameter turns on/off SCAYT on the autostartup.
//    //If "true", turns on SCAYT automatically after loading the SCAYT application.
//    autoStartup: true,
//    //The parameter defines the existence and the order of menu items inside of their sections.
//    //E.g. if you remove the "Control" section, then all its items (Ignore, Ignore All, Add Word)
//    //will not be included to the view. Please note that the "About" menu item
//    //and its tab aren’t allowed to be removed by default.
//    ///Such a customization can be provided for an additional fee and for more details,
//    //please contact support@webspellchecker.
//    contextMenuSections:
//        'undoredo|suggest|moresuggest|control|clipboard|options',
//    //The parameter defines existance and order of menu items inside of their sections.
//    contextMenuCommands:
//        'undo|redo|ignore|ignoreall|addword|paste|cut|copy|options|languages|dictionaries|togglescayt|help|customItem1',
//    //The parameter defines the number of SCAYT suggestions to show in the context menu.
//    //Possible values are: "0" (zero) – No suggestions are shown in the context menu.
//    //All suggestions will be listed in the "More Suggestions" sub-menu.
//    //Positive number – The maximum number of suggestions to show in the context menu.
//    //Other suggestions will be shown in the "More Suggestions" sub-menu.
//    //Negative number – 5 suggestions are shown in the context menu.
//    //All other suggestions will be listed in the "More Suggestions" sub-menu.
//    suggestionsCount: 3,
//    //The parameter enables/disables the "More Suggestions" sub-menu in the context menu.
//    //Possible values are: "0" (zero) – No suggestions are shown in the "More Suggestions" sub-menu.
//    //Positive number – The maximum number of suggestions to show in "More Suggestions" sub-menu.
//    //Negative number – 10 suggestions are shown in "More Suggestions" sub-menu.
//    moreSuggestionsCount: 4,
//    //The parameter sets the default spell checking language for SCAYT.  Possible values are:
//    // 'en_US', 'en_GB', 'pt_BR', 'da_DK', 'nl_NL', 'en_CA', 'fi_FI', 'fr_FR', 'fr_CA',
//    // 'de_DE', 'el_GR', 'it_IT', 'nb_NO', 'pt_PT', 'es_ES', 'sv_SE'.
//    spellcheckLang: "en_US",
//    //The parameter customizes the SCAYT dialog to show particular tabs.
//    //Please note that the "About" menu item and its tab aren’t allowed to be removed by default.
//    //Such a customization can be provided for an additional fee and for more details, please contact support@webspellchecker.
//    uiTabs: 'options,languages,dictionaries,about',
//    //This is the SCAYT onLoad callback.
//    onLoad: function() {
//        // "this" refers to SCAYT instance
//        this.setDisabled(true);
//    }
//});


var textareaSCAYT = new SCAYT.SCAYT({
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
            onClick: function() {
                alert("Thank you! Context menu will be closed");
                return false;
            }
        }
    },
    shortcutsList: [
        {
            shortcut: SCAYT.CTRL + 66,
            callback: function() {
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
    spellcheckLang: "en_US",
    container: textarea,
    onLoad: function() {
        // "this" refers to SCAYT instance
        this.setDisabled(true);
    }
});