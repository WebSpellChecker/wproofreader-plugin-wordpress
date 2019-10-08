=== WProofreader ===
Contributors: webspellchecker
Tags:  Proofreader, multi-language proofreading, spelling check, grammar check, proofreading in Gutenberg, wproofreader, spell checker, spellcheck, proofread as you type, spelling mistakes, spelling errors, spellchecker, correct spellings, improve spelling, multi-language spellchecker, grammar checker, proofread posts, proofread pages, check grammar in gutenberg, spellcheck in gutenberg
Donate link: https://www.webspellchecker.com/
Requires at least: 4.4
Tested up to: 5.2.2
Stable tag: trunk
License: GPL-2.0+
Requires PHP: 5.6
License URI: http://www.gnu.org/licenses/gpl-2.0.txt


Check spelling and grammar on your site automatically with multilingual WProofreader plugin.  

== Description ==

The WProofreader plugin automatically checks spelling and grammar in multiple languages on websites, built with WordPress 4.x, and the new WordPress 5.x.

**The plugin works in two comfortable modes:**

- instant, underlining mistakes while you type, and 
- dialog, proofreading all the text at once in a separate pop-up window.

== Here are a few highlights of how the plugin can be useful for your websites: ==

= Multilingual =
The Pro version proofreads in 14 languages, plus spell checking in Finnish and Norwegian Bokmal. You can also extend the spell checking with any of 140+ additional languages.

6 languages are spell checked in the Free version.

= Grammar-Savvy =

The Pro version is able of checking of not only spelling, but grammar in 14 languages as well.

= Customizable =

The Pro version provides an option of adding specialized terminology, acronyms, proper names, etc. into Personal User Dictionaries, stored in the cloud and available for review and management from any browser, or to Global Custom Dictionaries, managed at the subscription admin level and available across all the websites under given license key.

In the Free version the users can create their Personal User Dictionaries as well. However, they will not be saved to cloud, so the dictionary can only be used from a single browser and device.  The Global Custom Dictionaries are not available in the Free version.

= Powerful =
The Pro version proofreads up to 10,000,000 words per year, and can be used in up to 5 websites.  

The free version checks spelling in to 5,000 words per day on one website. Once the limit is reached the proofreading stops until the next day.

= Modern =
The plugin seamlessly integrates with websites, built in WordPress 4.x, and the new WordPress 5.x with the Gutenberg editor.

The following browsers are supported: 

- Chrome
- Firefox 
- Safari
- MS Edge
- Internet Explorer 11

With all the content to post, the Proofreader plugin is a real time-saver, carefully checking texts right in your text editor. 

It might be particularly handy:

- for heavy-posters, with multiple articles to release daily
- for owners and keepers the  websites, which need to be maintained in several languages
- for editors working with content created by third parties
- for perfection enthusiasts eager to keep their texts flawless (we are also from this bunch:).

== Installation ==
= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'WProofreader'.
3. Activate WProofreader from your Plugins page.

= From WordPress.org =

1. Download WProofreader plugin.
2. Upload the 'wproofreader' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate WProofreader from your Plugins page.

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= What is License Key or how to obtain one? =

License Key is a special key that is required for migration to the Pro version of the WProofreader plugin. This key will be provided by WebSpellChecker after your purchase of a Pro license. Contact **info@webspellchecker.net** for more details.

= Can WProofreader plugin check the entire website? =
The plugin doesn’t check the whole website at a time. It is enabled only when the content on your website is in editing mode. Here is the list of content types which can be checked by the plugin: content of pages, content of posts, tag descriptions, category descriptions, WooCommerce and WP eCommerce product descriptions, any Custom Post Type, Meta description fields of Yoast SEO plugin.

= How can I enable the plugin in Custom Post Types? =
You need to add a special 'wproofreader_add_cpt' filter in your function.php. 
Example:
			
		function wproofreader_add_cpt_callback() {
			return  array(
				'my-custom-post-type'
            );
		}

		add_filter( 'wproofreader_add_cpt', 'wproofreader_add_cpt_callback' );
		
= What other languages are available for WProofreader plugin? = 
By default the free version of our plugin is provided with 6 languages:

*	American English
*	British English
*	French
*	German
*	Italian
*	Spanish

The Pro version has an extended list of the default languages and supports the following 16 languages:

*	American English
*	British English
*	Brazilian Portuguese
*	Canadian English
*	Canadian French
*	Danish
*	Dutch
*	Finnish
*	French
*	German
*	Greek
*	Italian
*	Norwegian Bokmal
*	Portuguese
*	Spanish
*	Swedish

Also, with the Pro version, the default list of languages can be extended with more than 140+ [additional languages](https://webspellchecker.com/additional-dictionaries/).

= How can I create a personal dictionary? =
**WProofreader** plugin has a special functionality called User Dictionary. It allows creating personal dictionaries with custom words (complex words, acronyms, proper names etc.) and use it across you website. All words added to a personal dictionary will not be considered as misspellings. 

In order to create a new personal dictionary click the orange badge in bottom right corner of the text field and select Settings. Then go to Dictionaries, Name your new dictionary and click Create.   All new words will be added to a newly created dictionary.

You can create the global custom dictionaries to be used across all the websites under your subscription via your subscription management panel. Please see the detailed instructions [here](https://docs.webspellchecker.net/display/WebSpellCheckerCloud/Configuring+Cloud+Custom+Dictionary).

= Can I check grammar with this plugin as well? =

Yes, the Pro version checks grammar in 14 languages:

- American English
- British English
- Brazilian Portuguese
- Canadian English
- Canadian French
- Danish
- Dutch
- French
- German
- Greek
- Italian
- Portuguese
- Spanish 

= Is support provided with this plugin? =

Support service is provided by WebSpellChecker LLC technical team and available for Pro users. Contact us at **support@webspellchecker.net**. 

= What is the difference between Free and Pro versions? =
Please see the comparison of the functionality:

**Free version ($0)**

- Usage limitations: Up to 5,000 words processed per day.  Once the daily limit is reached, the tool will stop working.
- Allowed number of websites: 1 website
- Languages available  for spell checking (6 languages): American English, British English, French, German, Italian, Spanish.
- Languages available for grammar checking: Not available
- Personal user dictionaries:  No cloud backup. The dictionary is only available in one browser on a single device. The dictionary is not available for review and modification.
- Global custom dictionaries: Not available

**Pro version ($49 per year)**

- Usage limitations: Up to 10 million of words processed per year. No daily limits apply
- Allowed number of websites: Up to 5 websites
- Languages available  for spell checking (16 languages): American English, British English, Brazilian Portuguese, Canadian English, Canadian French, Danish, Dutch, Finnish, French, German, Greek, Italian, Norwegian Bokmal, Portuguese, Spanish, Swedish.
- Languages available for grammar checking (14 languages): American English, British English, Brazilian Portuguese, Canadian English, Canadian French, Danish, Dutch, French, German, Greek, Italian, Portuguese, Spanish, Swedish.
- Personal user dictionaries:  Stored in the cloud, available from any browser or device. The user can freely access, review and modify the list of words in the dictionary.
- Global custom dictionaries: The subscription admin can create global dictionaries that will be available across all the connected websites

= What is global custom dictionary and how I can use it? =

The global custom dictionary functionality is available in the Pro version only and is aimed at extending a list of words in the default dictionaries with your custom words and terms. It helps to improve the quality of spelling check services. Using a personal account on the webspellchecker.ner website. You may create a custom wordlist which includes specific terms, abbreviations, etc. These words won’t be considered as misspellings.

= What are the Terms of Service? =

Please see the complete [Terms of Service](https://webspellchecker.com/terms-of-service/) for the plugin here.

= Is my text secure? What is your Privacy Policy? =

We have an extensive Privacy Policy in place to protect your texts. Please see the details [here](https://webspellchecker.com/privacy-policy/).

= What browsers are supported? =

The plugin supports latest versions of Chrome, Firefox, Safari, and MS Edge, and Internet Explorer 11.

= Can I suggest a feature? =

Sure, please let us know how we can make the plugin more useful for you. Your ideas and comments are welcome at **support@webspellchecker.net**.


== Screenshots ==
1. WProofreader Checks Spelling and Grammar on Posts.
2. WProofreader Spelling Correction Box.
3. WProofreader Grammar Correction Box.
4. WProofreader Badge Button with Opened Menu.
5. WProofreader Badge Button with Active Proofread in Dialog Mode.
6. Proofread in Dialog Mode of WProofreader with Active Spelling Error.
7. Proofread in Dialog Mode of WProofreader with Active Grammar Error.
8. WProofreader Settings with Options for Check Spelling.
9. WProofreader Settings with Languages List.
10. WProofreader Settings with Personal Dictionary Management View.
11. WProofreader Checks Spelling and Grammar on Pages.
12. WProofreader Checks Spelling and Grammar on WooCommerce Products.
13. WProofreader Checks Spelling and Grammar on Gutenberg Editor of WordPress 5.
14. WProofreader Plugin Settings Pages.

== Changelog ==
= 2.5 - 2019-07-29 =
* Enabled spelling and grammar check in the Meta description fields of Yoast SEO plugin.
* Enabled support of Custom Post Types using the “wproofreader_add_cpt“ filter.

= 2.4 - 2019-04-15 =
* Added a new option “Disable WProofreader Badge” to the plugin settings which allows disabling an orange badge button. If disabled, the orange badge won’t appear in each editable box on the page. Users will see only underlined spelling and grammar errors that were detected by WProofreader. 
* Fixed the issue with “Uncaught TypeError” in the browser console which appeared after the update of the event system in the WProofreader core.

= 2.3 - 2019-02-25 =
* Fixed the issue: The Gutenberg editor doesn’t react on the text changes (e.g. replace word) that were made by WProofreader.
* Blocked saving of WProofreader span elements to database.
* Fixed the issue: After replacing a word in IE11 or MS Edge, the word remains underlined in the Gutenberg editor.
* Fixed the issue: After replacing a word in IE11 or MS Edge, the input event doesn’t work properly in the Gutenberg editor.

= 2.2 - 2019-02-12 =
* Resolved the issue with not working plugin under PHP version 7.1. and higher. Consequently eliminated PHP warnings “Invalid argument supplied for foreach()“.

= 2.1 - 2019-01-29 =
* Fixed the issue with not working grammar checking option in the Pro version.

= 2.0 - 2018-12-21 =
The new version of the WProofreader (WebSpellChecker Proofreader) plugin introduces the following updates:

* Upgraded to be fully compatible with latest versions of WordPress: WordPress 4.x, WordPress 5.x and  Gutenberg editor.
* Enabled two comfortable modes for proofreading: instant, underlining mistakes while you type, and dialog, proofreading all the text at once in a separate pop-up window.
* Improved engine to check not only spelling but grammar as well. Checking spelling for 6 languages in a Free version, checking both spelling and grammar for 14 in Pro + spell checking in Finnish and Norwegian Bokmal.
* Introduced new clean and comfy UI: intuitive suggestion boxes, spinner indicating the progress of proofreading, Maximize/Restore icon in a dialog mode, save changes notifications.
* Checks spelling and grammar on Pages, Posts, Tags, Categories, WooCommerce Product Descriptions, and WP eCommerce Product Descriptions including product tags and categories.
* Removed banner ad in the Free version.
* Extended the list of supported browsers: Chrome (the latest), Firefox (the latest), Safari (the latest), Internet Explorer 11, MS Edge (the latest).



= 1.1 = 
New version of the WebSpellChecker plugin introduces the following updates:

* Support of the Yoast SEO and ACF text fields
* Spell Checking in the Titles field
* Fixes for minor issues


= 1.0 = 
Initial Release of the WebSpellChecker plugin. The multi-language spell checking functionality is available for Visual Editor and excerpt all fields.

== Upgrade Notice ==

= 2.0 =
2.0 is a major update. Make a full site backup.
