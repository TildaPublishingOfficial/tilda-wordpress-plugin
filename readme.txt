=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://wordpress.org/plugins/tilda-publishing/
Tags: publishing, tilda, export
Requires at least: 3.0.1
Tested up to: 4.1
Stable tag: 0.2.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export html page from Tilda.cc to your wordpress site into post or page.

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

Something is broken, or I have a great idea.
Please create an issue on the [GitHub page](https://github.com/greensun7/tilda-wordpress-plugin). Creating a pull request with a fix is an even better option.

Q: How automate update page on worpress after publish page on Tilda?
A: Set on Tilda.cc next url for callback http://example.com/wp-admin/admin-ajax.php?action=nopriv_tilda_sync_event

== Screenshots ==

1. Agter create page: http://images.tildacdn.info/61517a4c-5abd-4f01-bb2a-9049506e9367/3.jpg
2. Page where plugin on: http://images.tildacdn.info/85a010fe-a741-4cad-adfc-b38a0204aa5c/2.jpg
3. Page with list project and pages from tilda.cc http://images.tildacdn.info/4af42b78-3494-4a85-ba10-15c2315af6f6/1.jpg

== Changelog ==

= 0.1 =
* First version plugin

= 0.2 =
* Rename plugin
* Order in folder with plugin
* remake to ajax query

= 0.2.2 =
* bugfix ajax query for non-standart wordpress directory

= 0.2.3 =
* add support PHP version 5.0-5.3 (bugfix: fatal error on activation plugin)

= 0.2.4 =
* add scroll if many pages in project of Tilda (fix css bug in list pages)

= 0.2.5 =
* for any sync page load data from project (fix js or css error: unknow tXXX_init ... )

= 0.2.6 =
* sync updated css and js from project from webhook (fix js or css error: unknow tXXX_init ... )

= 0.2.7 =
* sync updated css and js from project from manual sync (fix js or css error: unknow tXXX_init ... )

= 0.2.8 =
* add option in plugin settings for save text in post for other plugin (rss, ... etc)

= 0.2.9 =
* add english language

= 0.2.10 =
* add support upload without cURL library

= 0.2.11 =
* add check writable for directory and small bug fixes
