=== Plugin Name ===
Contributors: tildapublishing
Donate link: https://wordpress.org/plugins/tilda-publishing/
Tags: blog, bulk, convert, crawl, data, import, importer, migrate, move, posts, publishing, tilda, export
Requires at least: 3.0.1
Tested up to: 6.0.1
Stable tag: 0.3.14
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export html page from Tilda.cc for import to your WordPress site into post or page.

== Description ==

Tilda Plugin for WordPress
It integrates web pages created on Tilda with your WordPress website.

Do you have a website on WordPress? Then we have good news for you – you can design beautiful pages on Tilda using all the built-in features and then easily integrate them with your website.

Your website is a template that contains permanent elements: header, menu, homepage, blog posts. If you want to create a more efficient page and add elements that are not available in your template, you can create a web page on Tilda, and then upload it to your website.

== Installation ==

* Download plugin from https://wordpress.org/plugins/tilda-publishing/
* Load the folder /wp-content/plugins folder to the plugin (tilda-wordpress-plugin)
* Go to the administration panel site
* Go to control plug-ins (Plugins/Installed)
* Find the plugin Tilda Publishing API and click "Activate"
* Get a public and a private key. To do this in your account tab, click the API and to generate keys. https://tilda.cc/identity/apikeys/
* Go to the plugin settings (Settings / Tilda Publishing)
* Enter the public and private keys generated at Tilda. Click "Save".


Before connecting the plug-in Tilda, create at least one project and one page.

* Click "Add New" (page or post)
* Enter a page title or record (Tilda otherwise not connected)
* If the list of projects and pages are not displayed, click the "Refresh List"
* In the list on the left select the project that contains the page you want to connect.
* In the list select the right page.
* After selecting the page, click "Save". Finish all the data pages are now on your server.

If you change the page to Tilda you need to click on your site from the page click "Sync."

== Frequently Asked Questions ==

Q: How do I automate page updates on WordPress after publishing my Tilda pages?
A: On Tilda, go to the Site Settings → Export → API Integration and set the following Webhook URL: http://example.com/wp-admin/admin-ajax.php?action=tilda_sync_event

Q: Where can I find the official repository for this plugin?
A: Please find the official repository on this [GitHub page](https://github.com/greensun7/tilda-wordpress-plugin).

Q: Something’s broken / I have a great idea, how can I contact you?
A: Please create an issue on the [GitHub page](https://github.com/greensun7/tilda-wordpress-plugin) or email team@tilda.cc. Creating a pull request is an even better option.


== Screenshots ==

1. After create page: screenshot-1.jpg
2. Page where plugin on: screenshot-2.jpg
3. Page with list project and pages from tilda.cc http://images.tildacdn.info/4af42b78-3494-4a85-ba10-15c2315af6f6/1.jpg

== Changelog ==

= 0.3.14 =
* refactoring
* use getprojectinfo instead of deprecated getprojectexport
* change tilda API server IP

= 0.3.13 =
* Update 0.3.13 - fix markup corruption on empty project_id

= 0.3.12 =
* Update 0.3.12 - Improved network reliability

= 0.3.11 =
* Update 0.3.11 - fix for nontilda pages

= 0.3.10 =
* Update 0.3.10 - update en_US locale

= 0.3.09 =
* Update 0.3.09 - fix unexpected array overwriting

= 0.3.08 =
* Update 0.3.08 - fix incorrect image path and improve en_US translations

= 0.3.07 =
* Update 0.3.07 - add en_US locale

= 0.3.06 =
* Update 0.3.06 - fix missing async loaded js script and images

= 0.3.05 =
* Update 0.3.05 - update webhook features according to API changes

= 0.3.04 =
* Update 0.3.04 - hot fix for 0.3.03

= 0.3.03 =
* Update 0.3.03 - minor fix warning on post meta without project_id

= 0.3.02 =
* Update 0.3.02 - fix ZeroBlock's gallery import

= 0.3.01 =
* Update 0.3.01 - modify storage for synced pages and new connect with Tilda.cc

= 0.2.33 =
* Update 0.2.33 - fix scripts

= 0.2.32 =
* Update 0.2.32 - fix forms in zero

= 0.2.31 =
* Update 0.2.31 - add version to css and js files

= 0.2.30 =
* Update 0.2.30 - fix if no pages in project

= 0.2.29 =
* fix custom types

= 0.2.28 =
* fix download images to local folder

= 0.2.27 =
* added a switch to turn off the styles in the list; added settings for custom types; add checking exist curl library or allow_url_fopen option and add option for save IMG on cdn.

= 0.2.26 =
* fix: resolve conflict with plugin modify meta box

= 0.2.25 =
* fix: bug in WP4.9

= 0.2.22 =
* remove_filter wpautop (crash svg and other html blocks)

= 0.2.21 =
* after sync not change status (draft->publish), thanks Anton Syuvaev; fix conflict with css from other version css and js

= 0.2.20 =
* micro fixing

= 0.2.19 =
* add css class [tilda-publishing] in tag body

= 0.2.18 =
* fix clean html for rss/seo plugin

= 0.2.17 =
* fix jQuery conflict with other js-framework

= 0.2.16 =
* add password protected on pages/posts

= 0.2.15.1 =
* add new secure version

= 0.2.15 =
* bugfix vulnerability

= 0.2.14 =
* bugfix refresh list (bug viewed in WP4.5.2)

= 0.2.13 =
* add check error
* add download image for socnet

= 0.2.12 =
* modify sync (updated Tilda API)

= 0.2.11 =
* add check writable for directory and small bug fixes

= 0.2.10 =
* add support upload without cURL library

= 0.2.9 =
* add english language

= 0.2.8 =
* add option in plugin settings for save text in post for other plugin (rss, ... etc)

= 0.2.7 =
* sync updated css and js from project from manual sync (fix js or css error: unknown tXXX_init ... )

= 0.2.6 =
* sync updated css and js from project from webhook (fix js or css error: unknown tXXX_init ... )

= 0.2.5 =
* for any sync page load data from project (fix js or css error: unknown tXXX_init ... )

= 0.2.4 =
* add scroll if many pages in project of Tilda (fix css bug in list pages)

= 0.2.3 =
* add support PHP version 5.0-5.3 (bugfix: fatal error on activation plugin)

= 0.2.2 =
* bugfix ajax query for non-standard WordPress directory

= 0.2 =
* Rename plugin
* Order in folder with plugin
* remake to ajax query

= 0.1 =
* First version plugin

