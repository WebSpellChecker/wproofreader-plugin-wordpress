# WProofreader Plugin for WordPress Changelog

## 2.4 - 2019-04-15

* Added a new option “Disable WProofreader Badge” to the plugin settings which allows disabling an orange badge button. If disabled, the orange badge won’t appear in each editable box on the page. Users will see only underlined spelling and grammar errors that were detected by WProofreader. 
* Fixed the issue with “Uncaught TypeError” in the browser console which appeared after the update of the event system in the WProofreader core.

## 2.3 - 2019-02-25

* Fixed the issue: The Gutenberg editor doesn’t react on the text changes (e.g. replace word) that were made by WProofreader.
* Blocked saving of WProofreader span elements to database.
* Fixed the issue: After replacing a word in IE11 or MS Edge, the word remains underlined in the Gutenberg editor.
* Fixed the issue: After replacing a word in IE11 or MS Edge, the input event doesn’t work properly in the Gutenberg editor.

## 2.2 - 2019-02-12

* Resolved the [issue](https://wordpress.org/support/topic/description-says-spanish-is-supported-but-no-spanish/) with not working plugin under PHP version 7.1. and higher. Consequently eliminated PHP warnings “Invalid argument supplied for foreach()“. 

## 2.1 - 2019-01-29

* Fixed the issue with not working grammar checking option in the Pro version.

## 2.0 - 2018-12-21

The new version of the WProofreader (WebSpellChecker Proofreader) plugin introduces the following updates:

* Upgraded to be fully compatible with latest versions of WordPress: WordPress 4.x, WordPress 5.x and  Gutenberg editor.
* Enabled two comfortable modes for proofreading: instant, underlining mistakes while you type, and dialog, proofreading all the text at once in a separate pop-up window.
* Improved engine to check not only spelling but grammar as well. Checking spelling for 6 languages in a Free version, checking both spelling and grammar for 14 in Pro + spell checking in Finnish and Norwegian Bokmal.
* Introduced new clean and comfy UI: intuitive suggestion boxes, spinner indicating the progress of proofreading, Maximize/Restore icon in a dialog mode, save changes notifications.
* Checks spelling and grammar on Pages, Posts, Tags, Categories, WooCommerce Product Descriptions, and WP eCommerce Product Descriptions including product tags and categories.
* Removed banner ad in the Free version.
* Extended the list of supported browsers: Chrome (the latest), Firefox (the latest), Safari (the latest), Internet Explorer 11, MS Edge (the latest).

## 1.1

New version of the WebSpellChecker plugin introduces the following updates:

* Support of the Yoast SEO and ACF text fields
* Spell Checking in the Title fields
* Fixes for minor issues

## 1.0

Initial Release of the WebSpellChecker plugin. The multi-language spell checking functionality is available for Visual Editor and excerpt all fields.

