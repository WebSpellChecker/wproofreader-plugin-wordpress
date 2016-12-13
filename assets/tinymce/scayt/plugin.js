/**
 * SCAYT
 * plugin.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */
(function() {
	var dom = tinymce.DOM;
	var util = tinymce.util;
	var undoManager = tinymce.UndoManager;

	tinymce.PluginManager.requireLangPack('scayt');

	// Create SCAYT namespace in tinymce.plugins
	tinymce.createNS('tinymce.plugins.SCAYT');

	tinymce.plugins.SCAYT = (function() {
		var state = {},
			instances = {},
			suggestions = [],
			loadingHelper = {
				loadOrder: []
			},
			warningCounter = 0,

			// look at multiload app realization
			scriptLoader = new tinymce.dom.ScriptLoader(),

			dataAttributeName = 'data-scayt-word',
			misspelledWordClass = 'scayt-misspell-word',
			backCompatibilityMap = {
				'scayt_context_commands'		: 'scayt_contextCommands',
				'scayt_slang'					: 'scayt_sLang',
				'scayt_max_suggestion'			: 'scayt_maxSuggestions',
				'scayt_custom_dic_ids'			: 'scayt_customDictionaryIds',
				'scayt_user_dic_name'			: 'scayt_userDictionaryName',
				'scayt_ui_tabs'					: 'scayt_uiTabs',
				'scayt_service_protocol'		: 'scayt_serviceProtocol',
				'scayt_service_host'			: 'scayt_serviceHost',
				'scayt_service_port'			: 'scayt_servicePort',
				'scayt_service_path'			: 'scayt_servicePath',
				'scayt_context_moresuggestions'	: 'scayt_moreSuggestions',
				'scayt_customer_id'				: 'scayt_customerId',
				'scayt_custom_url'				: 'scayt_srcUrl',
				'scayt_auto_startup'			: 'scayt_autoStartup',
				'scayt_context_menu_items_order': 'scayt_contextMenuItemsOrder'
			};

		var replaceOldOptionsNames = function(config) {
			for(var key in config) {
				if(key in backCompatibilityMap) {
					config[backCompatibilityMap[key]] = config[key];
					delete config[key];
				}
			}
		};

		var loadScaytLibrary = function(editor, callback) {
			var protocol = document.location.protocol;
			var baseUrl = editor.getParam('scayt_srcUrl');
			var date = new Date();
			var	timestamp = date.getTime();
			var	baseUrlWithTimestamp;
			// Default to 'http' for unknown.
			protocol = protocol.search(/https?:/) !== -1 ? protocol : 'http:';
			baseUrl = baseUrl.search(/^\/\//) === 0 ? protocol + baseUrl : baseUrl;
			baseUrlWithTimestamp = baseUrl + "?" + timestamp;

			if(typeof window.SCAYT === 'undefined' || typeof window.SCAYT.TINYMCE !== 'function') {
				// add onLoad callbacks for editors while SCAYT is loading
				loadingHelper[editor.id] = callback;
				loadingHelper.loadOrder.push(editor.id);

				scriptLoader.add(baseUrlWithTimestamp);
				scriptLoader.loadQueue(function(success) {
					var editorName;

					for(var i = 0; i < loadingHelper.loadOrder.length; i++) {
						editorName = loadingHelper.loadOrder[i];

						tinymce.plugins.SCAYT.fireOnce(tinymce, 'onScaytReady');

						if(typeof loadingHelper[editorName] === 'function') {
							loadingHelper[editorName](tinymce.editors[editorName]);
						}

						delete loadingHelper[editorName];
					}
					loadingHelper.loadOrder = [];
				});
			} else if(window.SCAYT && typeof window.SCAYT.TINYMCE === 'function') {
				tinymce.plugins.SCAYT.fireOnce(tinymce, 'onScaytReady');

				if(!tinymce.plugins.SCAYT.getScayt(tinymce.editors[editor.id])) {
					if(typeof callback === 'function') {
						callback(editor);
					}
				}
			}
		};

		var createScayt = function(editor) {
			loadScaytLibrary(editor, function(_editor) {
                            
				var _scaytInstanceOptions = {
					debug 				: _editor.getParam('scayt_debug', false),
					lang 				: _editor.getParam('scayt_sLang'),
					container 			: (_editor.getContentAreaContainer() && _editor.getContentAreaContainer().children[0]) ? _editor.getContentAreaContainer().children[0] : _editor.getElement(),
					customDictionary	: _editor.getParam('scayt_customDictionaryIds'),
					userDictionaryName 	: _editor.getParam('scayt_userDictionaryName'),
					localization		: _editor.langCode,
					customer_id			: _editor.getParam('scayt_customerId'),
					data_attribute_name : dataAttributeName,
					misspelled_word_class : misspelledWordClass,
					ignoreElementsRegex : _editor.getParam('scayt_elementsToIgnore'),
					minWordLength 		: _editor.getParam('scayt_minWordLength')
				};

				// fixed #79459 Focus jumping after SCAYT load
				if(tinymce.focusedEditor === editor){
					_scaytInstanceOptions['focused'] = true;
				}
				if(_editor.getParam('scayt_serviceProtocol')) {
					_scaytInstanceOptions['service_protocol'] = _editor.getParam('scayt_serviceProtocol');
				}

				if(_editor.getParam('scayt_serviceHost')) {
					_scaytInstanceOptions['service_host'] = _editor.getParam('scayt_serviceHost');
				}

				if(_editor.getParam('scayt_servicePort')) {
					_scaytInstanceOptions['service_port'] = _editor.getParam('scayt_servicePort');
				}

				if(_editor.getParam('scayt_servicePath')) {
					_scaytInstanceOptions['service_path'] = _editor.getParam('scayt_servicePath');
				}

				var scaytInstance = new SCAYT.TINYMCE(_scaytInstanceOptions,
					function() {
						// success callback
					},
					function() {
						// error callback
					}),
					wordsPrefix = 'word_';

				scaytInstance.subscribe('suggestionListSend', function(data) {
					var wordsCollection = {},
						suggestionList = [];

					for(var i = 0; i < data.suggestionList.length; i++) {
						if (!wordsCollection[wordsPrefix + data.suggestionList[i]]) {
							wordsCollection[wordsPrefix + data.suggestionList[i]] = data.suggestionList[i];
							suggestionList.push(data.suggestionList[i]);
						}
					}

					suggestions = suggestionList;
				});

				instances[_editor.id] = scaytInstance;
			});
		};
		var destroyScayt = function(editor) {
			var scaytInstance = instances[editor.id];

			if(scaytInstance) {
				scaytInstance.destroy();
			}

			delete instances[editor.id];
		};
		// backward compatibility if version of scayt app < 4.8.3
		var reloadMarkup = function(scaytInstance) {
			if(scaytInstance){
				if(scaytInstance.reloadMarkup) {
					scaytInstance.reloadMarkup();
				} else {
					if(warningCounter < 5){
						console.warn('Note: You are using latest version of SCAYT plug-in. It is recommended to upgrade WebSpellChecker.net application to version v4.8.3.' +
							'Contact us by e-mail at support@webspellchecker.net.');
						warningCounter += 1;
					}
					scaytInstance.fire('startSpellCheck');
				}
			}
		};
		return {
			reloadMarkup: function(scaytInstance){
				return reloadMarkup(scaytInstance);
			},
			create: function(editor) {
				return createScayt(editor);
			},
			destroy: function(editor) {
				return destroyScayt(editor);
			},
			getScayt: function(editor) {
				return instances[editor.id] || false;
			},
			getSuggestions: function() {
				return suggestions;
			},
			getState: function(editor) {
				var editorName = (typeof editor === 'object') ? editor.id : editor;
				return state[editorName];
			},
			setState: function(editor, status) {
				var editorName = (typeof editor === 'object') ? editor.id : editor;
				status = status || false;
				return state[editorName] = status;
			},
			getOptions: function(editor) {
				var scaytInstance = this.getScayt(editor);
				return scaytInstance.getApplicationConfig();
			},
			getCurrentLanguage: function(editor) {
				var scaytInstance = this.getScayt(editor);
				return scaytInstance.getLang();
			},
			getLanguages: function(editor) {
				var scaytInstance = this.getScayt(editor);
				return scaytInstance.getLangList();
			},
			getUserDictionaryName: function(editor) {
				var scaytInstance = this.getScayt(editor);
				return scaytInstance.getUserDictionaryName();
			},
			getVersion: function(editor) {
				var scaytInstance = this.getScayt(editor);
				return scaytInstance.getVersion();
			},
			removeMarkupFromString: function(editor, str) {
				var scaytInstance = this.getScayt(editor);

				if(scaytInstance) {
					return scaytInstance.removeMarkupFromString(str);
				}

				return str;
			},
			replaceOldOptionsNames: function(config) {
				replaceOldOptionsNames(config);
			},
			fireOnce: function(obj, event) {
				if(!obj['uniqueEvtKey_' + event]) {
					obj['uniqueEvtKey_' + event] = true;
					obj.fire(event);
				}
			},
			executeOnce: function(editor, event, handler) {
				var _handler = function() {
					editor.off(event, _handler);
					if(typeof handler === 'function') {
						handler();
					}
				};

				editor.on(event, _handler);
			},
			misspelledWordClass: (function() {
				return misspelledWordClass;
			}())
		};
	}());

	tinymce.PluginManager.add('scayt', function(editor, url) {
		"use strict";

		// Default items on context menu
		editor.settings.contextmenu = editor.settings.contextmenu || 'scayt link image inserttable | cell row column deletetable';

		var options = {
			definitionDefaultOption: {
				scayt_srcUrl: {
					type: 'string',
					'default': '//svc.webspellchecker.net/spellcheck31/lf/scayt3/tinymce/tinymcescayt.js'
				},
				scayt_sLang: {
					type: 'string',
					'default': 'en_US'
				},
				scayt_customerId: {
					type: 'string',
					'default': '1:wiN6M-YQYOz2-PTPoa2-3yaA92-PmWom-3CEx53-jHqwR3-NYK6b-XR5Uh1-M7YAp4'
				},
				scayt_autoStartup: {
					type: 'boolean',
					'default': false
				},
				scayt_maxSuggestions: {
					type: 'number',
					'default': 5
				},
				scayt_minWordLength: {
					type: 'number',
					'default': 4
				},
				scayt_moreSuggestions: {
					type: 'string',
					'default': 'on',
					allowable: {
						on: true,
						off: true
					}
				},
				scayt_contextCommands: {
					type: 'string',
					'default': 'ignore,ignoreall,add'
				},
				scayt_contextMenuItemsOrder: {
					type: 'string',
					'default': 'suggest|moresuggest|control'
				},
				scayt_uiTabs: {
					type: 'string',
					'default': '1,1,1'
				},
				scayt_customDictionaryIds: {
					type: 'string',
					'default': ''
				},
				scayt_userDictionaryName: {
					type: 'string',
					'default': null
				},
				scayt_serviceProtocol: {
					type: 'string',
					'default': null
				},
				scayt_serviceHost: {
					type: 'string',
					'default': null
				},
				scayt_servicePort: {
					type: 'string',
					'default': null
				},
				scayt_servicePath: {
					type: 'string',
					'default': null
				}
			},
			parseOptions: function(editor) {
				var self = this,
					optionDefinition = self.definitionDefaultOption,
					settings = editor.settings,
					_SCAYT = tinymce.plugins.SCAYT,
					getParameter = utils.getParameter,
					isAllowable = utils.isAllowable;

				settings.scayt_autoStartup = getParameter('scayt_autoStartup');
				settings.scayt_moreSuggestions = isAllowable("scayt_moreSuggestions") ? getParameter("scayt_moreSuggestions") : "off";
				settings.scayt_maxSuggestions = utils.isNegative(getParameter('scayt_maxSuggestions')) ? optionDefinition['scayt_maxSuggestions']['default'] : getParameter('scayt_maxSuggestions');
				settings.scayt_minWordLength = getParameter('scayt_minWordLength') < 1 ? optionDefinition['scayt_minWordLength']['default'] : getParameter('scayt_minWordLength');
				settings.scayt_srcUrl = getParameter('scayt_srcUrl');
				settings.scayt_sLang = getParameter('scayt_sLang');

				settings.scayt_customerId = (function( customerId, url ) {

					url = utils.getLocationInfo( url );
					var defUrl = utils.getLocationInfo( optionDefinition['scayt_srcUrl']['default'] );

					var cusId = customerId && ( typeof customerId  === optionDefinition['scayt_customerId']['type'] ) && ( customerId.length >= 50 );
					defUrl = ( url.host === defUrl.host ) && ( url.pathname === defUrl.pathname );

					if ( cusId && !defUrl) {
						return customerId
					}

					if ( !defUrl ) {
						return customerId
					}

					if ( !(cusId && defUrl) ) {
						return optionDefinition['scayt_customerId']['default'];
					}

					return customerId

				})( editor.getParam('scayt_customerId'),  settings.scayt_srcUrl)

				settings.scayt_customDictionaryIds = getParameter('scayt_customDictionaryIds');
				settings.scayt_userDictionaryName = getParameter('scayt_userDictionaryName');
				settings.scayt_serviceProtocol = getParameter('scayt_serviceProtocol');
				settings.scayt_serviceHost = getParameter('scayt_serviceHost');
				settings.scayt_servicePort = getParameter('scayt_servicePort');
				settings.scayt_servicePath = getParameter('scayt_servicePath');

				settings.scayt_contextCommands = getParameter('scayt_contextCommands');
				if(settings.scayt_contextCommands === 'all') {
					settings.scayt_contextCommands = optionDefinition['scayt_contextCommands']['default'];
				} else if(settings.scayt_contextCommands === 'off') {
					settings.scayt_contextCommands = '';
				} else {
					settings.scayt_contextCommands = settings.scayt_contextCommands.replace(/\s?[\|]+/gi, ' ');
				}

				settings.scayt_contextMenuItemsOrder = getParameter('scayt_contextMenuItemsOrder');
				settings.scayt_contextMenuItemsOrder = settings.scayt_contextMenuItemsOrder.replace(/\s?[\|]+/g, ' ');

				// prepare uiTabs parameter
				settings.scayt_uiTabs = getParameter('scayt_uiTabs').split(',');
				// lets validate our scayt_uiTabs option : now it should contain comma separated '0' or '1' symbols
				if(settings.scayt_uiTabs.length != 3 || !utils.validateArray(settings.scayt_uiTabs, function(value) {
					return value == 0 || value == 1;
				})) {
					settings.scayt_uiTabs = optionDefinition.scayt_uiTabs['default'].split(',');
				}

				_SCAYT.setState(editor, settings.scayt_autoStartup);
			},
			init: function(editor) {
				var ed = editor;

				// preprocess settings for backward compatibility
				tinymce.plugins.SCAYT.replaceOldOptionsNames(editor.settings);
				this.parseOptions(ed);

				this.bindEvents(ed);
			},
			bindEvents: function(editor) {
				var self = this,
					ed = editor,
					_SCAYT = tinymce.plugins.SCAYT,
					scaytPlugin = editor.plugins.scayt,
					inlineMode = editor.inline;

				//@TODO: find solution for inline support
				// var bindInlineModeEvents = function(editor) {
				// 	_SCAYT.executeOnce(ed, 'focus', contentDomReady);
				// 	_SCAYT.executeOnce(ed, 'blur', scaytDestroy);
				//};

				//@TODO: find solution for inline support
				var contentDomReady = function(editor) {
					var button = toolbarButton.scaytButton;

					if(!editor.settings.readonly) {
						// The event are fired when editable iframe node was reinited so we should restart our service
						if(_SCAYT.getState(editor) === true) {
							_SCAYT.create(editor);
							if(button) {
								button.active(true);
							}
							contextMenu.create(editor);
						}
					} else {
						if(button) {
							button.disabled(true);
						}
					}
				};

				var scaytDestroy = function(editor) {
					var scaytInstance = _SCAYT.getScayt(ed),
						button = toolbarButton.scaytButton;

					if(scaytInstance) {
						_SCAYT.destroy(editor);
						if(button) {
							button.active(false);
						}
						contextMenu.destroy(editor);
					}
				};

				if(inlineMode) {
					//@TODO: find solution for inline support
					// bindInlineModeEvents(editor);
					ed.on('focusin', function(e) {
						var button = toolbarButton.scaytButton;

						if(button) {
							button.disabled(true);
						}
					});
				} else {
					// Initialization the tinymce editor
					ed.on('init', function(e) {
						var editor = e.target;

						contentDomReady(editor);
					});
				}

				ed.on('remove', function() {
					return _SCAYT.destroy(ed);
				});

				ed.on('SetContent', function(data) {
					var scaytInstance = _SCAYT.getScayt(ed);

					if(scaytInstance && _SCAYT.getState(ed)) {
						if(data['content']) {
							data['content'] = _SCAYT.removeMarkupFromString(ed, data['content']);
						}

						_SCAYT.reloadMarkup(scaytInstance);
					}
				});

				ed.on('GetContent', function(data) {
					var scaytInstance = _SCAYT.getScayt(ed);

					data.preventDefault();

					if(_SCAYT.getState(ed)) {
						if(data['content']) {
							data['content'] = _SCAYT.removeMarkupFromString(ed, data['content']);
						}
					}
				});


				// There is no 'PastePostProcess' event in 4.0.5 tiny. So we need to complitely remove our markup from selection node
				ed.on('PastePreProcess', function(data) {
					var scaytInstance = _SCAYT.getScayt(ed);

					if(_SCAYT.getState(ed)) {
						// Lets remove our possible markup from pasted text
						data['content'] = _SCAYT.removeMarkupFromString(ed, data['content']);

						setTimeout(function() {
							scaytInstance.removeMarkupInSelectionNode();
							_SCAYT.reloadMarkup(scaytInstance);
						}, 0);
					}
				});

				ed.on('NodeChange', function(e) {
					var temp = [],
						className = tinymce.plugins.SCAYT.misspelledWordClass;

					if(e.element && e.element.nodeName == 'SPAN' && e.element.className == className) {

						tinymce.each(e.parents, function(item) {
							if(!(item.nodeName == 'SPAN' && item.className == className)) {
								temp.push(item);
							}
						});

						e.element = e.element.parentNode;
						e.parents = temp;
					}
				});

				// Remove SCAYT markup with undo level
				editor.on('BeforeAddUndo', function(e) {
					if(e.lastLevel && e.lastLevel.content) {
						e.lastLevel.content = _SCAYT.removeMarkupFromString(editor, e.lastLevel.content);
					}
					e.level.content = _SCAYT.removeMarkupFromString(editor, e.level.content);
				});

				editor.on('BeforeExecCommand', function(e) {
					var scaytInstance = _SCAYT.getScayt(editor),
						forceBookmark = false,
						removeMarkupInsideSelection = true;

					if(	e.command === 'Cut' || e.command === 'Bold' || e.command === 'Underline' ||
						e.command === 'Italic' || e.command === 'Subscript' || e.command === 'Superscript' ||
						e.command === 'mceToggleFormat' ) {

						if(scaytInstance) {
							if(e.command === 'Cut') {
								removeMarkupInsideSelection = false;
								// We need to force bookmark before we remove our markup.
								// Otherwise we will get issues with cutting text via context menu.
								forceBookmark = true;
							}
							scaytInstance.removeMarkupInSelectionNode({
								removeInside: removeMarkupInsideSelection,
								forceBookmark: forceBookmark
							});

							setTimeout(function() {
								_SCAYT.reloadMarkup(scaytInstance);
							}, 0);
						}
					}
				});
			}
		};

		var toolbarButton = {
			scaytButton: null,
			createToolBarButton: function(editor) {
				var self = this;

				editor.addButton('scayt', {
					type: 'splitbutton',
					role: 'splitbutton',
					tooltip: 'SpellCheckAsYouType',
					disabled: false,
					menu: self.renderItemMenu(editor),
					icon: 'spellchecker',
					onpostrender: function() {
						self.scaytButton = this;
					},
					onshow: function(data) {
						self.findOutStatusMenu(data.control, this);
					},
					onclick: function(data) {
						self.toggleStateMenuSelect(editor);
					}
				});
			},
			renderItemMenu: function(editor) {
				var items = [],
					each = tinymce.each,
					ed = editor,
					settings = ed.settings,
					showUITab = settings.scayt_uiTabs;

				each(definitionDialog.menu, function(menuItem, index) {
					if(menuItem.identification && !!parseInt(showUITab[index])) {
						menuItem.menuItemIndex = items.length;
						items.push(menuItem);
					} else if(menuItem.identification == 'about') {
						menuItem.menuItemIndex = (items.length === 0) ?  0 : items.length;
						items.push(menuItem);
					}

				});

				return items;
			},
			toggleStateMenuSelect: function(editor) {
				var _SCAYT = tinymce.plugins.SCAYT,
					scaytPluginState = _SCAYT.setState(editor, !_SCAYT.getState(editor)),
					button = this.scaytButton,
					menu = this.scaytButton.menu;

				if(scaytPluginState) {
					_SCAYT.create(editor);
					button.active(true);
					contextMenu.create(editor);
				} else {
					_SCAYT.destroy(editor);
					button.active(false);
					contextMenu.destroy(editor);
				}

				this.findOutStatusMenu(menu, button);
			},
			findOutStatusMenu: function(aControl, aMenu) {
				var items = aControl ? aControl._items : {},
					stateMenu = aMenu.active() ? false : true;

				tinymce.each(items, function(aElement) {
					aElement.disabled(stateMenu);
				});
				return !stateMenu;
			},
			init: function(editor) {
				var _SCAYT = tinymce.plugins.SCAYT;

				options.init(editor);
				this.createToolBarButton(editor);
			}
		};

		var contextMenu = {
			originalMenu: undefined,
			contextmenu: null,
			menu: null,
			items: [],
			pos: null,
			createScaytMenuItem: function() {
				/* 	this method creates contextmenu item in original menu
					 and will be always invisible
					 Created to save link of original tinymce contextmenu.
			 	*/
				editor.addMenuItem('scayt', {
					context: 'scayt3t',
					text: 'SCAYT',
					onPostRender: function() {
						this.visible(false);
						contextMenu.originalMenu = this._parent;
					}
				});
			},
			destroy: function(editor) {
				var self = this,
					ed = editor;

				if(self.menu != null) {
					self.menu.remove();
				}
				self.menu = null;
			},
			create: function(editor) {
				var self = this,
					_SCAYT = tinymce.plugins.SCAYT,
					ed = editor,
					settings = ed.settings,
					command = '',
					scaytInstance = null,
					word = null,
					wrapMenu = null,
					allSuggestions,
					suggestions,
					moreSuggestions,
					needMoreSuggestions,
					maxSuggestions = settings.scayt_maxSuggestions;

				ed.on('contextmenu', function(e) {
					e.preventDefault();

					if(!!editor.settings.readonly) {
						return;
					}

					scaytInstance = _SCAYT.getScayt(ed);
					word = utils.getSelectionWord(scaytInstance);

					if(!word) {
						return;
					}

					scaytInstance.fire("getSuggestionsList", {lang: _SCAYT.getCurrentLanguage(ed), word: word});


					// Get all suggestions
					allSuggestions = _SCAYT.getSuggestions();
					// Split suggestions items into 'Suggestions' and 'More suggestions' menu items
					suggestions = allSuggestions.slice(0, maxSuggestions);
					moreSuggestions = allSuggestions.slice(maxSuggestions, 15);

					// Do we need 'More suggestions' menu item ?
					if(settings.scayt_moreSuggestions === 'on' && moreSuggestions.length > 0 && moreSuggestions[0] != 'no_any_suggestions') {
						needMoreSuggestions = true;
					} else {
						needMoreSuggestions = false;
					}

					utils.generateMenuItemsForSuggestion(suggestions);
					utils.generateMoreSuggestionsItem(moreSuggestions);
					utils.registerControlItems();

					var control = settings.scayt_contextCommands + ' | aboutscayt |',
						suggest = (suggestions.length > 0 ? suggestions.join(' ') : (needMoreSuggestions || allSuggestions.length > 0 ? '' : 'no_any_suggestions')) + ' | ',
						moresuggest = needMoreSuggestions ? (' moreSuggestions' + ' | ') : '';

					self.contextmenu = settings.scayt_contextMenuItemsOrder.replace('control', control).replace('moresuggest', moresuggest).replace('suggest', suggest);
					self.contextmenu = editor.settings.contextmenu.replace('scayt', self.contextmenu);

					// Render menu
					self.items = [];

					tinymce.each(self.contextmenu.split(/[ ,]/), function(name) {
						var item = ed.menuItems[name];

						if(name == '|') {
							item = {text: name};
						}

						if(item) {
							item.shortcut = ''; // Hide shortcuts
							self.items.push(item);
						}
					});

					for(var i = 0; i < self.items.length; i++) {
						if(self.items[i].text == '|') {
							if(i === 0 || i == self.items.length - 1) {
								self.items.splice(i, 1);
							}
						}
					}

					self.menu = new tinymce.ui.Menu({
						items: self.items,
						context: 'scayt',
						onCancel: function() {
							if(self.menu) {
								self.menu.hide();
								self.menu = null;
							}
						}
					});

					contextMenu.originalMenu.hide();
					self.menu.renderTo(document.body);

					var menuEl = self.menu.getEl();

					if(!tinymce.isIE) {
						menuEl.style.maxHeight = 'inherit';
						menuEl.style.position = 'absolute';
						menuEl.style.overflow = 'visible';
					} else {
						menuEl.style.height = 'auto';

						if(window.JSON) {
							menuEl.style.maxHeight = 'inherit';
							menuEl.style.overflow = 'inherit';
						}
					}

					wrapMenu = '#' + self.menu._id;

					scaytInstance.showBanner(wrapMenu);
					self.menu.show();

					// Position menu
					self.pos = {x: e.pageX, y: e.pageY};

					if(!ed.inline) {
						self.pos = tinymce.DOM.getPos(ed.getContentAreaContainer());
						self.pos.x += e.clientX;
						self.pos.y += e.clientY;
					}

					self.menu.moveTo(self.pos.x, self.pos.y);

					self.menu.on('cancel', function() {
						this.remove();
						this.menu = null;
					});

					return false;
				});
			}
		};

		var utils = {
			getKeys: Object.keys || function( obj ) {
				var buffer = [], key;

					for( key in obj ) {
						if( obj.hasOwnProperty( key ) ) {
							buffer.push( key );
						}
					}

				return buffer;
			},
			getLocationInfo: function(path) {

				// path: 'file:///D:/Dev/WSC/SCAYTv3/apps/ckscayt/' or 'https://www.google.com.ua'
				var a = document.createElement('a');
				a.href = path;

				return {
					protocol: a.protocol.replace(/:/, ''),
					host: a.host.split(':')[0],
					port: a.port == "0" ? "80" : a.port, // Safari 5 always return '0' when port implicitly equals '80'
					pathname: a.pathname.replace(/^\//, '')
				};
			},
			isInteger: function(num) {
				return (num ^ 0) === num;
			},
			validateArray: function(array, comparison) {
				var result = true;

				for(var i = 0, len = array.length; i < len; i++) {
					result = result && comparison(array[i]);
					if(!result) break;
				}

				return result;
			},
			isNegative: function(num) {
				return num < 0 ? true : false;
			},
			getParameter: function(optionName) {
				var optionDefinition = options.definitionDefaultOption;

				optionName = optionName + '';

				if(!optionDefinition[optionName]) {
					return editor.getParam(optionName);
				}

				var checkType = function(name) {
					return (typeof editor.getParam(name) === optionDefinition[name]['type']);
				};

				return checkType(optionName) ? editor.getParam(optionName) : optionDefinition[optionName]['default'];
			},
			isAllowable: function(optionName) {
				var optionDefinition = options.definitionDefaultOption;

				return  (utils.getParameter(optionName) in optionDefinition[optionName]["allowable"]);
			},
			getLang: function(name, defaultVal) {
				var self = this;
				return editor.editorManager.i18n.translate(name) !== name ? editor.editorManager.i18n.translate(name) : defaultVal || name;
			},
			getTranslate: function(aString) {
				return editor.editorManager.i18n.translate(aString);
			},
			getSelectionWord: function(scaytInstance) {
				var selectionNode,
					word = false;

				if(scaytInstance) {
					selectionNode = scaytInstance.getSelectionNode();

					if(selectionNode) {
						word = selectionNode.getAttribute(scaytInstance.getNodeAttribute());
					} else {
						word = selectionNode;
					}
				}

				return word;
			},
			registerMenuItem: function(name, itemDefinition) {
				editor.addMenuItem(name, itemDefinition);
			},
			// TODO: refactor this code
			addUndoLevelAfterReplaceCommand: function(editor) {
				var self = this,
					undo = editor.undoManager;

				undo.add();
			},
			generateReplaceCommand: function(scaytInstance, suggestion) {
				var replacement = suggestion,
					self = this;

				return function() {
					editor.focus(); // ~ fix bug with focus
					scaytInstance.replaceSelectionNode({word: replacement});
					self.addUndoLevelAfterReplaceCommand(editor);
				};
			},
			generateMenuItemsForSuggestion: function(suggestions /*Array*/) {
				var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor);

				if(suggestions.length === 0 || suggestions[0] === 'no_any_suggestions') {
					this.registerMenuItem('no_any_suggestions', {
						text: utils.getLang('no_any_suggestions', 'No suggestions'),
						onclick: function() {
							return false;
						}
					});
				} else {
					for(var i = 0; i < suggestions.length; i++) {
						this.registerMenuItem(suggestions[i], {
							text: suggestions[i],
							onclick: this.generateReplaceCommand(scaytInstance, suggestions[i])
						});
					}
				}
			},
			generateSubMenuForMoreSuggestions: function(suggestions /*Array*/) {
				var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor),
					menuList = [];

				for(var i = 0; i < suggestions.length; i++) {
					menuList.push({
						text: suggestions[i],
						onclick: this.generateReplaceCommand(scaytInstance, suggestions[i])
					});
				}

				return menuList;
			},
			generateMoreSuggestionsItem: function(suggestions /*Array*/) {
				var self = this,
					moreSuggestionsSubMenu,
					listItem;

				moreSuggestionsSubMenu = self.generateSubMenuForMoreSuggestions(suggestions);

				if(moreSuggestionsSubMenu[0] && moreSuggestionsSubMenu[0].text) {
					listItem = moreSuggestionsSubMenu[0].text == 'no_any_suggestions' ? null : moreSuggestionsSubMenu;
				}

				if(listItem == null) {
					return;
				}

				editor.addMenuItem('moreSuggestions', {
					text: utils.getLang('cm_more_suggestions', 'More suggestions'),
					menu: listItem
				});
			},
			registerControlItems: function() {
				editor.addMenuItem('ignore', {
					text: utils.getLang('cm_ignore_word','Ignore'),
					onclick: function() {
						var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor);

						editor.focus(); // ~ fix bug with focus
						setTimeout(function() {
							scaytInstance.ignoreWord();
						}, 0);
					}
				});

				editor.addMenuItem('ignoreall', {
					text: utils.getLang('cm_ignore_all','Ignore all'),
					onclick: function() {
						var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor);

						editor.focus(); // ~ fix bug with focus
						setTimeout(function() {
							scaytInstance.ignoreAllWords();
						}, 0);
					}
				});

				editor.addMenuItem('add', {
					text: utils.getLang('cm_add_word','Add word'),
					onclick: function() {
						var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor);

						editor.focus(); // ~ fix bug with focus
						setTimeout(function() {
							scaytInstance.addWordToUserDictionary();
						}, 0);
					}
				});

				editor.addMenuItem('aboutscayt', {
					text: utils.getLang('cm_about','About SCAYT'),
					onclick: function(data) {
						var scayt_uiTabs = editor.settings.scayt_uiTabs,
							aboutTabIndex = scayt_uiTabs.length;

						tinymce.each(scayt_uiTabs, function(item) {
							if(parseInt(item) == 0) {
								aboutTabIndex--;
							}
						});

						definitionDialog.openDialog(editor, data, aboutTabIndex);
					}
				});

				editor.addMenuItem('no_any_suggestions', {
					text: utils.getLang('no_any_suggestions', 'No suggestions'),
					onPostRender: function(data) {
						data.control.disabled(true);
					},
					onclick: function() {
						return;
					}
				});
			},
			registerLanguages: (function() {
				var langList = {},
					codeList = {},
					getToName,
					getToCode,
					makeLanguageList;

				getToName = function(aLangCode) {
					var langCode = typeof aLangCode === 'string' ? aLangCode : '"' + aLangCode + '"';

					if(codeList.hasOwnProperty(langCode)) {
						return codeList[langCode];
					}
				};
				getToCode = function(aLangName) {
					var langName = typeof aLangName === 'string' ? aLangName : '"' + aLangName + '"';

					for(var code in codeList) {
						if(codeList[code] == langName) {
							return code;
						}
					}
				};
				makeLanguageList = function(aLangList) {
					langList = aLangList;

					for(var langGroup in langList) {
						if(langList.hasOwnProperty(langGroup)) {
							for(var langCode in langList[langGroup]) {
								codeList[langCode] = langList[langGroup][langCode];
							}
						}
					}
					return codeList;
				};
				return {
					getToName: getToName,
					getToCode: getToCode,
					makeLanguageList: makeLanguageList
				};
			})(),
			replaceHostNullToNativeNull: function(data) {
				/* Fixed bug in IE8.Instanceof host "null" throw Object expected*/
				if(document.all && document.querySelector && !document.addEventListener){
					for (var k in data){
						data[k] = (data[k] === null)? null : data[k];
					}
				}
			}
		};

		var definitionDialog = {
			dictionaryButtons: [],
			definitionDataControl: {
				nodes: {},
				setInstances: function(data, name) {
					return this.nodes[name] = data;
				},
				getInstances: function(name) {
					return this.nodes[name];
				}
			},
			langState: {
				isChanged: function() {
					return (this.selectLang === null || this.currentLang === this.selectLang) ? false : true;
				},
				currentLang: null,
				selectLang: null,
				id: 'lang'
			},
			getChangedOptions: function() {
				var optionsList = this,
					changedOptions = {};

				for(var item in optionsList) {
					if(optionsList.hasOwnProperty(item)) {
						changedOptions[item] = optionsList[item];
					}
				}

				if(definitionDialog.langState.isChanged()) {
					changedOptions[definitionDialog.langState.id] = definitionDialog.langState.selectLang;
				}

				return changedOptions;
			},
			menu: [
				{
					text: utils.getLang('tb_menu_options','SCAYT Options'),
					identification: 'options',
					menuItemIndex: 0,
					onclick: function(data) {
						utils.replaceHostNullToNativeNull(data);
						definitionDialog.openDialog(editor, data, this.settings.menuItemIndex);
					}
				},
				{
					text: utils.getLang('tb_menu_languages','SCAYT Languages'),
					identification: 'languages',
					menuItemIndex: 1,
					onclick: function(data) {
						utils.replaceHostNullToNativeNull(data);
						definitionDialog.openDialog(editor, data, this.settings.menuItemIndex);
					}
				},
				{
					text: utils.getLang('tb_menu_dictionaries','SCAYT Dictionaries'),
					identification: 'dictionary',
					menuItemIndex: 2,
					onclick: function(data) {
						utils.replaceHostNullToNativeNull(data);
						definitionDialog.openDialog(editor, data, this.settings.menuItemIndex);
					}
				},
				{
					text: utils.getLang('tb_menu_about','About SCAYT'),
					identification: 'about',
					menuItemIndex: 3,
					onclick: function(data) {
						utils.replaceHostNullToNativeNull(data);
						definitionDialog.openDialog(editor, data, this.settings.menuItemIndex);
					}
				}
			],
			generateOptionsCheckbox: function(aOptionsList) {
				var createCheckbox, optionList = aOptionsList || {};

				createCheckbox = [
					{checked: false, name: "ignore-all-caps-words", text: utils.getLang('label_allCaps','Ignore All-Caps Words')},
					{checked: false, name: 'ignore-domain-names', text: utils.getLang('label_ignoreDomainNames','Ignore Domain Names')},
					{checked: false, name: 'ignore-words-with-mixed-cases', text: utils.getLang('label_mixedCase','Ignore Mixed-Case Words')},
					{checked: false, name: 'ignore-words-with-numbers', text: utils.getLang('label_mixedWithDigits','Ignore Words with Numbers')}
				];

				for(var i = 0; i < createCheckbox.length; i++) {
					createCheckbox[i].checked = optionList[createCheckbox[i].name];
				}

				return createCheckbox;
			},
			generateLanguagesCheckbox: function(aLanguages, aCurrentLanguage) {
				var createCheckbox = [],
					renderLang,
					languageList = aLanguages || {},
					currentLang = aCurrentLanguage || 'en_US',
					reverseKeys =[],
					newLanguageList = {},
					getKeys = utils.getKeys;

				this.langState.currentLang = currentLang;
				languageList = utils.registerLanguages.makeLanguageList(languageList);

				for (var key in languageList) {
					if (languageList.hasOwnProperty(key) && (typeof languageList[key] === 'string' && languageList[key] != '')){
						newLanguageList[languageList[key]] = key;
					}
				}
				reverseKeys =  getKeys(newLanguageList).sort();
				languageList = {};

				for(var i = 0; i < reverseKeys.length; i += 1){
					languageList[reverseKeys[i]] =  newLanguageList[reverseKeys[i]];
				}
				for(var langName in languageList) {
					createCheckbox.push({
						checked: languageList[langName] === currentLang ? true : false,
						name: languageList[langName],
						role: languageList[langName],
						text: langName
					});
				}

				return createCheckbox;
			},
			dividedLanguagesCheckbox:function(checkBoxes){
				var dividedArr = [[],[]],
					getKeys = utils.getKeys,
					keys =  getKeys(checkBoxes),
					length = keys.length,
					arrNum = 0,
					keyNum = 0;

				for(var key in checkBoxes){
					if(checkBoxes.hasOwnProperty(key)){
						keyNum = parseInt(key);
						arrNum = (keyNum < Math.ceil(length / 2))? 0 : 1 ;
						dividedArr[arrNum].push(checkBoxes[key]);
					}
				}

				return dividedArr;
			},
			generateDictionaryButtons: function() {
				var buttons = [
					{disabled: true, name: "Create", role: 'Create', text: utils.getLang('dic_create','Create')},
					{disabled: true, name: 'Restore', role: 'Restore', text: utils.getLang('dic_restore','Restore')},
					{disabled: true, name: 'Rename', role: 'Rename', text: utils.getLang('dic_rename','Rename')},
					{disabled: true, name: 'Remove', role: 'Remove', text: utils.getLang('dic_remove','Remove')}
				];
				return buttons;
			},
			setDictionaryButtons: function() {
				var self = this;
				self.removeDictionaryButtons();
				self.definitionDataControl.getInstances('dic_buttons').append(self.getDictionaryButtons());
			},
			getDictionaryButtons: function() {
				var self = this;
				return self.dictionaryButtons;
			},
			removeDictionaryButtons: function() {
				var self = this,
					items = self.definitionDataControl.getInstances('dic_buttons').items();

				for(var i = items.length - 1; i >= 0; i--) {
					items[i].remove();
				}
			},
			toggleDictionaryButtons: function(aIds) {
				var self = this,
					items = self.generateDictionaryButtons(),
					showItems = aIds.split('|') || '';

				for(var i = 0; i < showItems.length; i++) {
					for(var j = 0; j < items.length; j++) {
						if(items[j].name == showItems[i]) {
							items[j]['disabled'] = false;
						}
					}
				}

				self.dictionaryButtons = items;
			},
			initDictionaryNameAndButtons: function(dicName) {
				var self = this,
					dictionaryValue = '',
					dictionaryButtonLocation = [
						"Create|Restore",
						"Rename|Remove"
					];

				if(dicName !== undefined && dicName !== "null" && dicName !== "") {
					dictionaryValue = dicName;
					self.toggleDictionaryButtons(dictionaryButtonLocation[1]);
				} else {
					dictionaryValue = '';
					self.toggleDictionaryButtons(dictionaryButtonLocation[0]);
				}

				return dictionaryValue;
			},
			handlerDictionaryButtons: function(data) {
				var self = this;

				if(data && data.target.tagName === 'BUTTON' && data.control.disabled() !== true) {
					var cmd = data.control.aria('role').toLowerCase();
					self.manageCommandButtons[cmd]();
				}
			},
			dicEmptyMessage: function() {
				return !document.getElementById('messageBox').innerHTML;
			},
			dicSuccessMessage: function(message) {
				if(message) {
					document.getElementById('messageBox').style.color = "green";
					document.getElementById('messageBox').innerHTML = message;
				}
				return '';
			},
			dicErrorMessage:  function(message) {
				if(message) {
					document.getElementById('messageBox').style.color = "red";
					document.getElementById('messageBox').innerHTML = message;
				}
				return '';
			},
			removeMessage: function() {
				var self = this;
				self.dicEmptyMessage();
			},
			manageCommandButtons: {
				create: function() {
					var self = this;
					var _SCAYT = tinymce.plugins.SCAYT;
					var scayt_control = _SCAYT.getScayt(editor);
					var err_massage = utils.getLang('dic_err_dic_create', 'The Dictionary %s cannot be created');
					var suc_massage = utils.getLang('dic_succ_dic_create', 'The Dictionary %s has been successfully created');

					scayt_control.createUserDictionary(definitionDialog.getDictionaryName(), function(response) {

						definitionDialog.initDictionaryNameAndButtons(response.name);
						if(!response.error) {
							definitionDialog.setDictionaryButtons();
							suc_massage = suc_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.dicSuccessMessage(suc_massage);
						} else {
							definitionDialog.setDictionaryButtons();
							err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.dicErrorMessage( err_massage );
						}

					}, function(error) {
						err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
						definitionDialog.dicErrorMessage( err_massage );
					});
				},
				restore: function() {
					var self = this;
					var _SCAYT = tinymce.plugins.SCAYT;
					var scayt_control = _SCAYT.getScayt(editor);
					var err_massage = utils.getLang('dic_err_dic_restore', 'The Dictionary %s cannot be restored');
					var suc_massage = utils.getLang('dic_succ_dic_restore', 'The Dictionary %s has been successfully restored');

					scayt_control.restoreUserDictionary(definitionDialog.getDictionaryName(), function(response) {

						definitionDialog.initDictionaryNameAndButtons(response.name);
						if(!response.error) {
							definitionDialog.setDictionaryButtons();
							suc_massage = suc_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.dicSuccessMessage(suc_massage);
						} else {
							definitionDialog.setDictionaryButtons();
							err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.dicErrorMessage( err_massage );
						}
					}, function(error) {
						err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
						definitionDialog.dicErrorMessage( err_massage );
					});
				},
				rename: function() {
					var self = this;
					var _SCAYT = tinymce.plugins.SCAYT;
					var scayt_control = _SCAYT.getScayt(editor);
					var err_massage = utils.getLang('dic_err_dic_rename', "The Dictionary %s cannot be renamed");
					var suc_massage = utils.getLang('dic_succ_dic_rename', 'The Dictionary %s has been successfully renamed');

					scayt_control.renameUserDictionary(definitionDialog.getDictionaryName(), function(response) {

						definitionDialog.initDictionaryNameAndButtons(response.name);
						if(!response.error) {
							definitionDialog.setDictionaryButtons();
							suc_massage = suc_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.setDictionaryName(definitionDialog.getDictionaryName());
							definitionDialog.dicSuccessMessage(suc_massage);
						} else {
							err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
							definitionDialog.dicErrorMessage( err_massage );
						}

					}, function(error) {
						err_massage = err_massage.replace("%s" , ('"'+definitionDialog.getDictionaryName()+'"') );
						definitionDialog.setDictionaryName(definitionDialog.getDictionaryName());
						definitionDialog.dicErrorMessage( err_massage );
					});
				},
				remove: function() {
					var self = this;
					var _SCAYT = tinymce.plugins.SCAYT;
					var scayt_control = _SCAYT.getScayt(editor);
					var err_massage = utils.getLang('dic_err_dic_remove', 'The Dictionary %s cannot be removed');
					var suc_massage = utils.getLang('dic_succ_dic_remove', 'The Dictionary %s has been successfully removed');

					scayt_control.removeUserDictionary(definitionDialog.getDictionaryName(), function(response) {

						definitionDialog.resetDictionaryName();
						definitionDialog.initDictionaryNameAndButtons(definitionDialog.getDictionaryName());

						if(!response.error) {
							definitionDialog.setDictionaryButtons();
							suc_massage = suc_massage.replace("%s" , ('"'+ response.name +'"') );
							definitionDialog.dicSuccessMessage(suc_massage);
						} else {
							err_massage = err_massage.replace("%s" , ('"'+ response.name +'"') );
							definitionDialog.dicErrorMessage( err_massage );
						}

					}, function(error) {
						err_massage = err_massage.replace("%s" , ('"'+ definitionDialog.getDictionaryName() +'"') );
						definitionDialog.dicErrorMessage( err_massage );
					});
				}
			},
			getDictionaryName: function() {
				var self = this;
				return self.definitionDataControl.getInstances('dic_textarea').value();
			},
			setDictionaryName: function(dictionaryName) {
				var self = this;
				dictionaryName = dictionaryName || '';
				return self.initDictionaryNameAndButtons(dictionaryName);
			},
			resetDictionaryName: function(name) {
				var self = this;
				name = name || '';
				return self.definitionDataControl.getInstances('dic_textarea').value(name);
			},
			definitionWindowDialog: function(editor) {
				var self = this,
					items = [],
					each = tinymce.each,
					tabs = null,
					ed = editor,
					_SCAYT = tinymce.plugins.SCAYT,
					settings = ed.settings,
					showUITab = settings.scayt_uiTabs,
					LanguagesCheckbox = definitionDialog.generateLanguagesCheckbox(_SCAYT.getLanguages(ed), _SCAYT.getCurrentLanguage(ed)),
					arrayDividedLanguagesCheckbox = definitionDialog.dividedLanguagesCheckbox(LanguagesCheckbox);

				tabs = [
					{
						title: utils.getLang('tab_options','Options'),
						type: 'form',
						role: 'scayt_options',
						identification: 'options',
						minWidth: 450,
						minHeight: 300,
						defaults: {
							type: 'checkbox',
							role: 'checkbox',
							style: 'overflow: hidden; cursor: pointer;'
						},
						items: definitionDialog.generateOptionsCheckbox(_SCAYT.getOptions(ed))
					},
					{
						title: utils.getLang('tab_languages','Languages'),
						type: 'form',
						pack: 'start',
						role: 'scayt_language',
						identification: 'language',
						items: [
							{
								type: 'container',
								role: 'container',
								layout: 'grid',
								id: 'langHolder',
								name: 'langHolder',
								packV: 'start',
								columns: 2,
								spacing: 10,
								alignH: ['50%', '50%'],
								items: [
									{
										type: 'container',
										id: 'leftCol',
										name: 'leftCol',
										layout: 'grid',
										role: 'container',
										columns: 1,
										spacing: 10,
										defaults: {
											type: 'radio',
											role: 'radio',
											label: ' ',
											style: 'overflow: hidden; cursor: pointer;'
										},
										items : arrayDividedLanguagesCheckbox[0]
									},
									{
										type: 'container',
										id: 'rightCol',
										name: 'rightCol',
										layout: 'grid',
										role: 'container',
										columns: 1,
										spacing: 10,
										defaults: {
											type: 'radio',
											role: 'radio',
											label: ' ',
											style: 'overflow: hidden; cursor: pointer;'
										},
										items : arrayDividedLanguagesCheckbox[1]
									}
								],


								onClick: function(data) {
									var control = data.control,
										langHolder,
										itemsLeft, itemsRight, items,
										leftCol, rightCol;

									if(data && control.aria('role') != "container") {

										langHolder = control.parents('#langHolder')[0];
										leftCol = langHolder.find('#leftCol')[0];
										rightCol = langHolder.find('#rightCol')[0];

										itemsLeft = leftCol.toJSON();
										itemsRight = rightCol.toJSON();

										items = tinymce.extend(itemsLeft, itemsRight);

										for(var item in items) {
											items[item] = false;
										}

										items[control.aria('role')] = true;
										definitionDialog.langState.selectLang = control.aria('role');

										leftCol.fromJSON(items);
										rightCol.fromJSON(items);

									}

								}
							}
						]
					},
					{
						title: utils.getLang('tab_dictionaries','User dictionaries'),
						type: 'form',
						pack: 'start',
						role: 'scayt_dictionary',
						identification: 'dictionary',
						items: [
							{
								label: utils.getLang('dic_dictionary_name','Dictionary Name'),
								type: 'textbox',
								role: 'dic_textbox',
								value: definitionDialog.setDictionaryName(_SCAYT.getUserDictionaryName(ed)),
								onPostRender: function(data) {
									definitionDialog.definitionDataControl.setInstances(data.control, 'dic_textarea');
								}
							},
							{
								type: 'container',
								role: 'container',
								defaults: {
									type: 'button',
									role: 'button',
									style: 'margin-right: 6px; z-index:2;'
								},
								items: definitionDialog.getDictionaryButtons(),
								onPostRender: function(data) {
									definitionDialog.definitionDataControl.setInstances(data.control, 'dic_buttons');
								},
								onClick: function(data) {
									definitionDialog.handlerDictionaryButtons(data);
								}
							},
							{
								type: 'fieldset',
								role: 'fieldset',
								style: 'border: none;padding-top: 20px;',
								html: '<div id="infoBlock" style="white-space: normal;text-align: justify;"><div id="messageBox"></div>'+
											utils.getLang('dic_about_info','Initially a User Dictionary is stored in a cookie. However, cookies are limited in size. When a User Dictionary grows to a point where it cannot be stored in a cookie, the dictionary may be stored on our server. To store your personal dictionary on our server, you should specify a name for it. If you already have a stored dictionary, please type its name and click the Restore button.')
									+ '</div>'
							}

						]
					},
					{
						title: utils.getLang('tab_about','About SCAYT'),
						type: 'form',
						role: 'scayt_about',
						identification: 'about',
						items: [
							{
								type: 'container',
								role: 'container',
								style: 'padding: 10px; min-width: 300px;',
								html: '<div style="white-space: normal;">'+ self.aboutTemplate() +'</div>'
							}
						]
					}
				];

				// Create tab list with depends on scayt_uiTabs settings
				each(tabs, function(tab, index) {
					if(tab.identification && !!parseInt(showUITab[index])) {
						items.push(tab);
					} else if(tab.identification == 'about') {
						items.push(tab);
					}
				});

				return items;
			},
			openDialog: function(aEditor, aData, aIdOpenTab) {
				var self = this,
					openTabWithId = aIdOpenTab || 0;

				return aEditor.windowManager.open({
					title: utils.getLang('title','SpellCheckAsYouType'),
					data: aData,
					minWidth: 450,
					minHeight: 300,
					resizable: 'true',
					role: 'tabpanel',
					bodyType: 'tabpanel',
					onPostRender: function(data) {
						var self = this,
							titleContainerId = this._id + '-head',
							titleContainer = document.getElementById(titleContainerId),
							title = titleContainer.getElementsByTagName('DIV');

						// Create small font for header dialog window
						for(var i = title.length - 1; i >= 0; i--) {
							if(title[i].className == 'mce-title') {
								title[i].style.fontSize = '15px';
							}
							continue;
						}

						// Opening tab depends on the selected menu item
						//if(openTabWithId !== 0) {
							self.items()[0].activateTab(openTabWithId);
						//};

						self.focus();

					},
					onSubmit: function(data) {
						var scaytInstance = tinymce.plugins.SCAYT.getScayt(editor);
						var changedOptions = definitionDialog.getChangedOptions.call(data.data);

						scaytInstance.commitOption({changedOptions: changedOptions});
					},
					body: self.definitionWindowDialog(editor)
				});
			},
			aboutTemplate: function() {
				var _SCAYT = tinymce.plugins.SCAYT,
					scaytInstance = _SCAYT.getScayt(editor);

				return '<div id="scayt_about" style="padding: 15px;"><a href="http://webspellchecker.net" target="_blank" alt="WebSpellChecker.net"><img title="WebSpellChecker.net" src="' + scaytInstance.getLogo(editor) + '" style="padding-bottom: 15px;" /></a><br />' + scaytInstance.getLocal('version') + _SCAYT.getVersion(editor) + ' <br /><br /> '+ scaytInstance.getLocal('about_throw_copy') + '</div>';
			}
		};

		toolbarButton.init(editor);
		contextMenu.createScaytMenuItem();
	});

	// Handle 'onScaytReady' callback
	tinymce.on('onScaytReady', function(editor) {
		// override editor dirty checking behaviour
		tinymce.EditorManager.Editor.prototype.isDirty = function() {
			var scaytInstance = tinymce.plugins.SCAYT.getScayt(this),
				startContent, getContent;

			if(scaytInstance) {
				startContent = tinymce.trim(scaytInstance.removeMarkupFromString(this.startContent));
				getContent = tinymce.trim(scaytInstance.removeMarkupFromString(this.getContent({format : 'raw', no_events : 1})));
			} else {
				startContent = tinymce.trim(this.startContent);
				getContent = tinymce.trim(this.getContent({format : 'raw', no_events : 1}));
			}

			return (getContent != startContent) && !this.isNotDirty;
		};
	});
}());