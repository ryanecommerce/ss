=== Plugin Name ===
Contributors: rpayne7264
Tags: mediawiki, wiki, wiki embed
Requires at least: 3.0
Tested up to: 4.9.8
Stable tag: 1.2.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

RDP Wiki Embed lets you embed content from MediaWiki sites.

== Description ==

RDP Wiki Embed will pull content from any MediaWiki website (such as wikipedia.org) and embed it in pages and posts. It strips and reformats the content, allowing you to supply some arguments to dictate how this works.

RDP Wiki Embed can also look for all links to wiki sites listed in the Security section and force the content on the current page to be replaced with the content found at the wiki site when the link is clicked. Visitors will be able to read wiki content without leaving your site.

Works automatically with [RDP PediaPress Embed] (https://wordpress.org/plugins/rdp-pediapress-embed/)

= Support =

Posting to the WordPress.org Support Forum does not send me notifications of new issues. Therefore, please send support requests using the [contact form on my web site.](http://www.robert-d-payne.com/contact/)


= Sponsor =

This plug-in brought to you through the generous funding of [Laboratory Informatics Institute, Inc.](http://www.limsinstitute.org/)


== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'RDP Wiki Embed'
3. Click the Install Now link.
3. Activate RDP Wiki Embed once it is installed.


= From WordPress.org =

1. Download RDP Wiki Embed zip file.
2. Upload the 'rdp-wiki-embed' directory from the zip file to your '/wp-content/plug-ins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate RDP Wiki Embed from your Plugins page.


= After Activation - Go to 'Settings' > 'RDP Wiki' and: =

1. Set configurations as desired.
2. Click 'Save Changes' button.

== Frequently Asked Questions ==

= I am using RDP Wiki Embed on a site that also hosts the wiki pages I want to embed. How do I prevent my site menu links from being re-written when using *Overwrite* mode? =

Add this CSS class to each menu item: no-wiki-overwrite

You made need to go to *Screen Options* at the top of the *Menus* page and tick the *CSS Classes* checkbox, before you can add the class to your menu items.

== Usage ==

Use the shortcode [rdp-wiki-embed] for embedding MediaWiki content. The following arguments are accepted:

* url: (required) the web address of the wiki article that you want to embed on this page
* toc_show: 0 (zero) to hide table of contents (TOC) or 1 to show
* edit_show: 0 (zero) to hide edit links or 1 to show 
* infobox_show: 0 (zero) to hide info boxes or 1 to show 
* unreferenced_show: 0 (zero) to hide "unreferenced" warning boxes  or 1 to show 
* wiki_update: number of minutes content of the wiki page will be stored on your site, before it is refreshed 
* wiki_links: behavior after clicking a link to wiki content - **default** or **overwrite**
* wiki_links_open_new: 0 (zero) to open wiki links in same window or 1 to open in new window 
* global_content_replace: 1 to apply embed overwrite behavior to all wiki links on the site or 0 (zero)  
* global_content_replace_template: page template to use for replaced content
* source_show: 0 (zero) to hide attribution or 1 to show 
* pre_source: text for source label



= Examples =

Basic uasge:

[rdp-wiki-embed url='http://en.wikipedia.org']


Display table-of-contents and info boxes, but hide edit links and 'unreferenced' warning boxes:

[rdp-wiki-embed url='http://en.wikipedia.org' toc_show='1' edit_show='0' infobox_show='1' unreferenced_show='0']


= About Overwrite and Global Content Replace =

Global content replace requires Overwrite mode to be enabled. When content is being replaced in Overwrite mode, the Default Shortcode Settings on the plug-in's settings page will be applied to content that is fetched from wiki sites.


== Screenshots ==

1. Settings page
2. Media button to launch shortcode embed helper form
3. Shortcode embed helper form


== Change Log ==

= 1.2.13 =
* Updated content parsing to remove jump links

= 1.2.12 =
* Updated content parsing to preserve line breaks in HTML

= 1.2.11 =
* Updated link overwrite script to skip menu items with the class **no-wiki-overwrite**

= 1.2.10 =
* Modified **RDP_WIKI_EMBED_CONTENT->content_get()** to use in-memory-only cookies

= 1.2.9 =
* Addressed issue where **No Caching** setting was ignored

= 1.2.8 =
* Revert shortcode to always render the book TOC button

= 1.2.7 =
* Modified shortcode to prevent rendering of the TOC button if toc_show = 0

= 1.2.6 =
* Re-worked php code and JS code to skip file pages, to leave download links for files and images intact

= 1.2.5 =
* Modified code to ignore headline anchors, where the class attribute contains **mw-headline**, when adding **rdp-we-** prefix to the id attributes of HTML elements coming in from MediaWiki sites

= 1.2.4 =
* Added check for array to RDP_WIKI_EMBED_CONTENT->scrub();

= 1.2.3 =
* Modified code to ignore cite anchors when adding **rdp-we-** prefix to the id attributes of HTML elements coming in from MediaWiki sites
* Moved code that adds **rdp-we-** prefix to the id attributes of HTML elements to **RDP_WIKI_EMBED_CONTENT->preRender()**, from **RDP_WIKI_EMBED_CONTENT->scrub()**
* Modified code section that identifies links to external files
* Added **RDP_WIKI_EMBED_UTILITIES::leadingslashit()**
* Added **RDP_WIKI_EMBED_UTILITIES::unleadingslashit()**
* Modified code section that tries to convert relative image URLs to absolute URLs

= 1.2.2 =
* Modified code to make a second call to RDP_WIKI_EMBED->enqueueScripts(), to ensure that any overriding shortcode atts are honored
* Modified **RDP_WIKI_EMBED->contentFilter()** for simplicity
* Removed **RDP_WIKI_EMBED->getPageShortcode()**
* Fixed bug in **RDP_WIKI_EMBED->handleTemplateSelection()** related to obtaining default settings
* Added *math* to the default element remove list in RDP_WIKI_EMBED_CONTENT->scrub()
* Added filter **rdp_we_scrub_remove_elements_filter**
* Added filter **rdp_we_prerender_remove_elements_filter**

= 1.2.1 =
* Slight modification to overwrite functionality

= 1.2.0 =
* Refactored code to leave download links for files and images intact
* Refactored code to add **rdp-we-** prefix to the id attributes of HTML elements coming in from MediaWiki sites
* Refactored overwrite code to use same shortcode attributes specified in a page's original RDP Wiki Embed shortcode
* Added WP_CRON job to clear expired content from cache

= 1.1.3 =
* Refactored code to properly parse source URL for data-href link attribute
* Added backup routine to look for rdp_we_resource query variable

= 1.1.2 =
* Security update

= 1.1.1 =
* Updated CSS to show formulas

= 1.1.0 =
* Fix no-cache option setting
* Add option setting to show wiki admin links
* Add option setting to show wiki footer


= 1.0.0 =
* Initial RC

== Upgrade Notice ==

= 1.2.3 =
* Fixes issue with cite number links not working
* Refines handling of links to external files

= 1.2.0 =
* Fixes issues with links to downloadable files
* Fixes CSS collisions
* Adds CRON job to clear expired content from cache

== Other Notes ==


== Action Hook Reference: ==

= rdp_we_scripts_enqueued =

* Param: none
* Fires after enqueuing plug-in-specific frontend scripts


= rdp_we_styles_enqueued =

* Param: none
* Fires after enqueuing plug-in-specific frontend styles


== Filter Reference ==

= rdp_we_scrub_remove_elements_filter =

* Param: Array of HTML elements to remove from the raw wiki content, before being cached
* Return: Array of HTML elements to remove from the raw wiki content, before being cached


= rdp_we_prerender_remove_elements_filter =

* Param: Array of HTML elements to remove from the wiki content, before rendering to browser
* Return: Array of HTML elements to remove from the wiki content, before rendering to browser