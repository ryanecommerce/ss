=== Plugin Name ===
Contributors: jeroenpeters1986
Requires at least: 3.0.1
Tested up to: 5.0
Stable tag: 1.9.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=T284NKYDFC8PE&lc=US&item_name=wordpress%2dplugin&item_number=name%2ddirectory&currency_code=USD
Tags: glossary, index, name, directory, names, NameDirectory, Name Directory, telephonebook, glossaries, directories, dictionary, dictorionaries

Name directory (glossary) with lots of options. Very easy to add to your site with a simple shortcode. It can have multiple directories. Supports multibyte characters (Chinese, Russian, Arabic, Danish/Nordic alphabet, etc).

== Description ==

This plugin adds name/term directories to your WordPress installation. They are like glossaries. The output on your website is like a glossary/index. I recommend you to take a look at the screenshots, they illustrate more than words.

The Name Directory plugin was orginally developed for [ParkietenVilla.nl](http://www.parkietenvilla.nl/namenlijst/) to show a directory of names to name your budgies.

A name directory is a directory that contains entries with the following properties:

 - name
 - description
 - submitter

You can create multiple directories with this plugin. Every directory can be embedded with a very simple shortcode which you can just copy-and-paste in your own pages and posts. Every directory has a few configuration options that customize the layout and functionality of the directory: 

 - Show/Hide title
 - Show/Hide description
 - Show/Hide suggestion form
 - Show/Hide submitter name
 - Show/Hide search function (searches names/titles and description)
 - Show/Hide a horizontal rule between the entries
 - Show/Hide all entries when the user has not chosen an index-letter yet
 - Show/Hide the newest entries (and choose an amount of newest entries to show)
 - Choose the amount of columns to display
 - Whether to jump to the name directory when a visitor is using the search box (for onepage websites)
 - When you embed a directory, you can configure it to start with a letter of your choosing. E.g.: start on letter J.

The administration view of this plugin has the familiar look and feel of (the rest of) the WordPress Administration panel. I have done my best to enable some AJAX-features in the administration panel, so you can work efficiently while adding new entries.

Since v1.7 and v1.8, import and export functionality is also supported through .csv-files.

Current supported languages:

 - English
 - Dutch
 - French (Thanks to: [Patrick BARDET](http://www.web-studio-creation.fr))
 - Russian (Thanks to: [Rig Kruger](http://rodichi.org))
 - Norwegian (Thank to: Mikael)
 - Arabic (Thanks to: [Ahmad Salim](http://www.ams.ly))

Do you want Name Directory to be available in your language? Please help us translate. You can translate directly by going to https://translate.wordpress.org/projects/wp-plugins/name-directory, login with your WordPress account and click Select your language and click 'Contribute Translation'.

== Installation ==

= Displaying a directory on your site =

1. Go to the Name Directory settings page
1. Hover over the directory you want to add to the page.
1. A few options should show now, like Delete, Manage and Shortcode (see screenshot https://ps.w.org/name-directory/assets/screenshot-2.png).
1. Click 'Shortcode', a little textbox will show now.
1. Copy-and-paste the content of the textbox into the page you want the plugin to show up.
1. Save and view the page to see the result.

= Installing the plugin =
Installation is very easy. You can just download this plugin through the Plugin Finder in your WordPress Administration Panel.

If you download the zip-file, installation isn't that difficult either:

1. Unzip the file which results into a directory called `name-directory`
1. Upload that directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a new name directory and add some names
1. Copy the shortcode and paste it into a post or page to show it on your website


== Frequently Asked Questions ==

= What does the shortcode look like? =

The shortcode for this Name Directory plugin is like this:
`[namedirectory dir="1"]`

The `1` in this example is the internal ID of the directory, the rest of the shortcode should always look like this.

= I created a directory, how do I show it on my site? =

1. Go to the Name Directory settings page
1. Hover over the directory you want to add to the page.
1. A few options should show now, like Delete, Manage and Shortcode - see screenshot https://ps.w.org/name-directory/assets/screenshot-2.png
1. Click 'Shortcode', a little textbox will show now.
1. Copy-and-paste the content of the textbox into the page you want the plugin to show up.
1. Save and view the page to see the result.

= Can you install this plugin or directory for me? =

Maybe, send me an email to see if we can work this out.

= Is there a bulk-add or import in this plugin? =

Since v1.7, yes there is! You can import a .csv-file into a directory.

1. Go to the Name Directory settings page
1. Hover over the directory you want to import names into.
1. A few options should show now, like Delete, Manage and Import - see screenshot for a settings-example https://ps.w.org/name-directory/assets/screenshot-2.png
1. Click 'Import'
1. Select your .csv-file
1. Upload

You can add names, descriptions and submitter entries, just the first column (name) is required. Good to know: the first row is always ignored (they should be headers).
You can download an example file at http://ps.w.org/name-directory/assets/name-directory-import-example.csv
If you need any help, contact me on the forums.

= Can I export my directory? =

Since v1.8, yes you can! This export is also compatible with the import-functionality offcourse. If you want to export, use the following steps:

1. Go to the Name Directory settings page
1. Hover over the directory you want to export.
1. A few options should show now, like Delete, Manage, Import and also Export - see screenshot for a settings-example https://ps.w.org/name-directory/assets/screenshot-2.png
1. Click 'Export'
1. On the new page click the button and your .csv-file will be downloaded


= Can I make the text bigger or another color? =

Yes you can, with CSS. If you know your way around CSS you might already know that you can style elements by their class name or HTML structure.
This plugin was written with styling / CSS in mind. Using the HTML inspector of your favourite browser you should be able to discover the classnames, but here are a few popular classes:

* `.name_directory_index`: Index links (the letters A-Z)
* `.name_directory_name_box > strong`: Name / Entry title
* `.name_directory_name_box > div`: Name / Entry descriptiong
* `.name_directory_total`: Total count of names / entries
* `.name_directory_index > form`: Search form
* `#name-directory-search-input-box`: Search input box
* `#name-directory-search-input-button`: Search button
* `.name_directory_submit_bottom_link`: Link to submit form
* `.name_directory_active`: Currently active character on index
* `.name_directory_empty`: Indicates that there a no entries for character

= How can I contact you? =

You can through this plugin information page or in the Support forums.

== Screenshots ==

1. The output of a name directory on a standard WordPress website. It's a full-featured name directory (search form, index links, descriptions and submit button)
2. Overview of all the name directories in the WordPress Administration screen for this plugin
3. List of all names in the selected directory and the 'Add name' form
4. Settings screen for a name directory
5. Where to find the Name Directory plugin settings page
6. Another example of the plugin on a website

== Changelog ==

= 1.9.7 =
 * PHP 5.3 compatibility
 * WordPress 5.0 compatible

= 1.9.6 =
 * Cleaned up code to prevent errors in debug-mode

= 1.9.5 =
 * Startswith character can now be non-latin

= 1.9.4 =
 * Confirm-delete message did show an alert, but no text

= 1.9.3 =
 * Added a confirm-delete message when deleting a directory.

= 1.9.2 =
 * Sorted index characters when they were not shown by default: A-Z

= 1.9.1 =
 * Compatible with WordPress 4.9.1
 * Ability to limit the amount of words in the description, shown on the frontend of the website. You can manage this in the directory settings. Names with a description which exceeds the setting, are limited with a "Show more" link.
 * Optimized code, rewritten the directory options screen to prevent lots of duplicate code and make it easier for myself to maintain
 * Added some more descriptions to what the directory settings do
 * Updated screenshots

= 1.9.0 =
 * You can now use words that start with non-latin characters (like Chinese, Arabic, Nordic/Danish, etc)
 * Updated Readme, which includes the Description, Changelog and the Frequently Asked Questions
 * Updated php code to follow development guidelines

= 1.8.2 =
 * Compatible with WordPress 4.9 final
 * Update php code to follow development guidelines

= 1.8.1 =
 * Compatible with WordPress 4.9b3
 * Removed old translation file, since Name Directory uses the WordPress translation platform
 * Moved an admin `add_action` hook
 * Updated Export to work with Javascript, possible denying Internet Explorer 9 (and lower) users export
 * Fixed compatibility with AJAX Front-end plugins
 * WordPress moves all divs with class 'updated' to the top of the page, which isn't very handy for the Add-Name form, which is on the bottom of the page. Created a workaround, all success-messages will be displayed above the add-form again.

= 1.8 =
 * Added export function, export your name directory to .csv file. Offcourse, it's compatible with the upload function

= 1.7.15 =
 * WND-46: Created a new option the admin for jumping to the searchbox on the front-end, useful on long page or onepage website
 * Support bumped to WordPress 4.8.2

= 1.7.14 =
 * WND-45: Better multisite support

= 1.7.13 =
 * WND-44: Front-end submit form now requires at least a name

= 1.7.12 =
 * WND-42: Option to use latest was broken, treated as L

= 1.7.11 =
 * WND-41: Verified translation from translate.wordpress.org
 * WND-40: Fixed bug there selecting only names that start with numbers, everything was shown

= 1.7.10 =
 * WND-39: Added `name_directory_active` class on index to indicate which character was activated
 * WND-39: Added `name_directory_empty` class on index to indicate there are no entries for a character
 * Started working with the Stable tag: https://wordpress.org/plugins/about/svn/#task-3
 * Removed translations so we can benefit from translate.wordpress.org :)
 * Do you want to help me translate this plugin in your own language? Let me know in the support forums! I will credit you!

= 1.7.9 =
 * WND-38: Database structure adjust

= 1.7.8 =
 * WND-35: Fixed CVS import
 * WND-36: Tested WP 4.4.2

= 1.7.7 =
 * WND-24: Fixed Possible XSS vulnerability

= 1.7.6 =
 * WordPress 4.3 compatible
 * Added Arabic translation (Thanks Ahmad from http://www.ams.ly)
 * Updated some PHP-code
 * Prevent possible function collisions
 
= 1.7.5 =
 * Updated Norwegian translation (Thanks Mikael!)
 * Some improvements for English language
 * Synced .pot and .po files

= 1.7.4 =
 * WND-25: Send e-mailnotification to WordPress admin when a new name is submitted
 * Generated new .pot file and synced all .po files

= 1.7.3 =
 * Ordering enhancements
 * Generated new .pot file and synced all .po files

= 1.7.2 =
 * WND-32: Show X latest (most recent) names
 * Updated Dutch Translation

= 1.7.1 =
 * Added Norwegian translation thanks to Mikael
 * WND-31: Search for searchterm in description (but only if show_description is enabled)
 * Moved common code to helpers, preparing for better code

= 1.7 =
 * WND-11: Import names and descriptions by csv-upload, find this option at the manage-screen
 * WND-24: Toggle published-status for name (easily show or hide names)
 * Name in WordPress settings menu is now "Name Directory" instead of "Name Directory Plugin"
 * Extended FAQ
 * Code improvements
 * Updated Dutch Translation

= 1.6.16 =
 * WND-26 & WND-28: Honour the Show Description setting in frontend

= 1.6.15 =
 * Added little spacers in the admin on the Manage names screen
 * Every name on the front-end got an anchor name

= 1.6.14 =
 * Added new translation file
 * Updated Dutch translation

= 1.6.13 =
 * WND-23: New option to only show letters on the index when there are entries, so A B D E when there is no entry with C
 * Fixed small legacy db-convert bug
 * Gave the admin panel for directory settings some space

= 1.6.12 =
 * Expanded FAQ
 * Updated documentation / edited screenshots
 * Updated information displayed at the WordPress Plugin Repository page

= 1.6.11 =
 * Search URL's didn't function properly
 * Search argument didn't work together (selected name and input filter)
 * function didn't work when WordPress was running without SEO tools
 * URL improvements (also tested with Yoast SEO plugin)

= 1.6.10 =
 * URLencoded the # sign, so entries starting with a number will show up

= 1.6.9 =
 * WND-21: Checked translation strings. Also edited two fussy strings in the Dutch translation
 * WND-22: Fixed wp-admin paths for WP Multisite users

= 1.6.8 =
 * WND-17: Added option which let's the user choose a default starting-character when displaying the name directory. For example: use [namedirectory dir="X" start_with="j"] to start with the letter J.
 * WordPress 4.0 compatibility
 * Added Icon to the installer gallery

= 1.6.7 =
 * Updated Russion Translation (Thanks to: Rig Kruger http://rodichi.org)

= 1.6.6 =
 * Fixed small display bug

= 1.6.5 =
 * Showed submitted name

= 1.6.4 =
 * Updated French translations
 * Fixed too-many-slashes issue

= 1.6.3 =
 * Updated Dutch translations
 * Fixed display bug. 
 * The All-link is hidden when you a visitor HAS to choose a letter from the index

= 1.6 =
* Added option 'Show all names by default', this can be disabled to hide all entries if a user hasn't chosen a letter from the index.

= 1.5.2 =
* Fixed bug in CREATE TABLE and backlink in form, thank you very much MerlIAV for the patch!

= 1.5.1 =
* Fixed bug that prevent saving searchform preference in admin

= 1.5 =
* Added search box on front-end (You can enable this in the name-directory settings)
* Added support for four-column layout
* Added Russion Translation (Translated by: Rig Kruger http://rodichi.org)

= 1.4.3 =
* Fixed bug which allowed non-published items to be shown

= 1.4.2 = 
* Fixed support for Chinese characters
* Added French Translation (Translated by: Patrick BARDET http://www.web-studio-creation.fr)

= 1.4.1 = 
* Fixed sorting issue at the frontend

= 1.4 = 
* WND-19: Added support for HTML in the name description

= 1.3 =
* Name lists can now have multiple columns at the frontend
* Added css in a separate file
* Added database upgrade module

= 1.2.1 =
* Plugin url's are now compatible with third party SEO modules

= 1.2 =
* Added support for submission form on the front-end
* Added possibility for admin to filter on published/unpublished names
* Rearranged directory overview for admin, overview now shows totals for published/unpublished

= 1.1 =
* Added double name detection

= 1.0 =
* First major public release

= 0.5 =
* First version for private use

