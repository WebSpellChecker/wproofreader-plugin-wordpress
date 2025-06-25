# WProofreader Plugin for WordPress Changelog

## 2.8.0 - 2025-06-25

* Fixed issue with user preferences not being remembered. A recent issue caused user-selected options (e.g., selected language, ignore settings, enabled check types) to reset on every page reload. Now, these preferences are stored.
* Compact badge enabled by default. Users can now click directly on the badge to launch the proofreading dialog. In earlier versions, this required hovering over the badge and selecting the “proofread in dialog” option.
* Improved suggestion mode in dialog. The dialog now opens in an extended view that shows all suggestions at once. Previously, users had to go through suggestions one by one.

## 2.7.1 - 2025-06-09
* Renamed WeDevs_Settings_API to WSC_Settings_API and WeDevs_Settings_API_Test to WSC_Settings to avoid naming conflicts with other plugins using similar settings API classes.

## 2.7.0 – 2025-02-18 
* Fixed issue with RTL (right-to-left) languages visibility in the Language dropdown on the plugin Settings page.
## 2.6.9 - 2024-02-27
* Updated plugin’s graphics and texts.

## 2.6.8 – 2023-05-17
* Enabled the global badge view instead of the standard one. It will add just one badge at the very bottom of the page instead of adding a small badge on each editable element within Gutenber editor. This badge can be disabled from the plugin settings page.
* Fixed issue when WProofreader doesn't start automatically in classic editor inside default Gutenberg editor.

## 2.6.7 – 2022-09-14
* Fixed warning in class-wsc-settings.php on line 180.

## 2.6.6 – 2022-07-29
* Fixed issue with the dialog mode.
* Updated text.

## 2.6.5 – 2021-09-28
* Enabled WProofreader badge by default. If needed, it can be disabled from the plugin's settings tab.
* Enabled the General section on the settings dialog for managing spelling autocorrection. By default, the spelling autocorrection is enabled. If needed, the user can disable it. To do so, please navigate to the badge (orange bubble located at the right bottom corner of the editable block) > click on the gear icon > toggle “Correct spelling automatically”.

## 2.6.4 - 2020-11-26
* Fixed a minor issue with the plugin permissions and an error shown on the settings page.

## 2.6.3 - 2020-11-20
* WProofreader is disabled in the table element in the Gutenberg editor to prevent breaking a table and overall glitching.

## 2.6.2 - 2020-09-22
* Fixed the integration issue that appeared in v2.6 with the Classic editor when Gutenberg is disabled.

## 2.6.1 - 2020-09-21
* Enabled error logging mechanism. Now errors will be displayed on the plugin settings page, which will help to troubleshoot issues faster.

## 2.6 - 2020-09-14
* Improved the integration with the Gutenberg editor. Now WProofreader will be automatically started in all editable elements of the editor without the necessity to click on each element to enable the spelling/grammar checking.
* The orange badge is disabled by default for all types of elements including the Gutenberg editor. There is a setting allowing to reactivate the badge.
* The dialog mode option for proofreading is disabled by default for the Gutenberg editor.


## 2.5 - 2019-07-29

* Enabled spelling and grammar check in the Meta description fields of [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) plugin.
* Enabled support of Custom Post Types using the 'wproofreader_add_cpt' filter. To do so, you need to add a special 'wproofreader_add_cpt' filter in your function.php. 

Example:

```
function wproofreader_add_cpt_callback() {
    return  array(
            'my-custom-post-type'
            );
}
add_filter( 'wproofreader_add_cpt', 'wproofreader_add_cpt_callback' );
```


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

