=== Plugin Name ===
Contributors: tildapublishing
Donate link: https://wordpress.org/plugins/tilda-publishing/
Tags: blog, bulk, convert, crawl, data, import, importer, migrate, move, posts, publishing, tilda, export
Requires at least: 3.0.1
Tested up to: 4.9.6
Stable tag: 0.2.31
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export html page from Tilda.cc for import to your wordpress site into post or page.

== Description ==

Tilda Plugin for WordPress
It integrates content that has been created on Tilda.cc, with your site by WordPress.

Do you have a site on WordPress? Good news - you can now do on Tilda Publishing beautiful pages for your site.

Your site is a template that contains permanent elements: a cap, menus, homepage, net posts. But if you want to make
the page more effectively and add these elements are not present in your template, you can create a page on Tilda, and
then upload it to the site.

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

Q: How automate update page on worpress after publish page on Tilda?
A: Set on Tilda.cc next url for callback http://example.com/wp-admin/admin-ajax.php?action=nopriv_tilda_sync_event

Q: Where find official repository for this plugin?
A: Official repository on [GitHub page](https://github.com/greensun7/tilda-wordpress-plugin).

Q: Something is broken, or I have a great idea.
A: Please create an issue on the [GitHub page](https://github.com/greensun7/tilda-wordpress-plugin) or send email on team@tilda.cc. Creating a pull request with a fix is an even better option.


== Screenshots ==

1. After create page: screenshort3.jpg
2. Page where plugin on: screenshort2.jpg
3. Page with list project and pages from tilda.cc http://images.tildacdn.info/4af42b78-3494-4a85-ba10-15c2315af6f6/1.jpg

== Changelog ==

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
* sync updated css and js from project from manual sync (fix js or css error: unknow tXXX_init ... )

= 0.2.6 =
* sync updated css and js from project from webhook (fix js or css error: unknow tXXX_init ... )

= 0.2.5 =
* for any sync page load data from project (fix js or css error: unknow tXXX_init ... )

= 0.2.4 =
* add scroll if many pages in project of Tilda (fix css bug in list pages)

= 0.2.3 =
* add support PHP version 5.0-5.3 (bugfix: fatal error on activation plugin)

= 0.2.2 =
* bugfix ajax query for non-standart wordpress directory

= 0.2 =
* Rename plugin
* Order in folder with plugin
* remake to ajax query

= 0.1 =
* First version plugin

