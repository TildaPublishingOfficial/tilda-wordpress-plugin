<?php
/*
Plugin Name: Tilda Publishing
Description: Tilda позволяет делать яркую подачу материала, качественную верстку и эффектную типографику, близкую к журнальной. Каким бы ни был ваш контент — Tilda знает, как его показать. С чего начать: 1) Нажмите ссылку «Активировать» слева от этого описания; 2) <a href="http://www.tilda.cc/" target="_blank">Зарегистрируйтесь</a>, чтобы получить API-ключ; 3) Перейдите на страницу настройки Tilda Publishing и введите свой API-ключ. Читайте подробную инструкцию по подключению.
Version: 0.2.31
Author: Tilda Publishing
License: GPLv2 or later
Text Domain: api tilda

Update 0.2.31 - add version to css and js files

Update 0.2.30 - fix if no pages in project

Update 0.2.29 - fix custom types

Update 0.2.28 - fix upload images to local directory

Update 0.2.27 - added a switch to turn off the styles in the list; added settings for custom types; add checking exist curl library or allow_url_fopen option.

Update 0.2.26 - fix: resolve conflict with plugin modify meta box

Update 0.2.25 - fix: bug in WP4.9

Update 0.2.23 - fix: wpautop and add check rule

Update 0.2.22 - remove_filter wpautop

Update 0.2.21 - after sync not change status (draft->publish), thanks Anton Syuvaev; fix conflict with css from other version css and js

Update 0.2.20 - micro fixing

Update 0.2.19 - add css class [tilda-publishing] in tag body

Update 0.2.18 - fix clean html for rss/seo plugin

Update 0.2.17 - fix jQuery conflict with other js-framework

Update 0.2.16 - add password protected on pages/posts

Update 0.2.15.1 - Add new secure server

Update 0.2.15 - Bugfix vulnerability

Update 0.2.14 - Bugfix refresh list (bug viewed in WP4.5.2)

Update 0.2.13 - Add check error, add download image for socnet and other bugfix

Update 0.2.12 - modify sync (updated Tilda API)

Update 0.2.11 - bugfix synchronization

Update 0.2.10 - add support upload without curl library

Update 0.2.9 - add english language
*/


/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Turn off all error reporting
//error_reporting(0);

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'TILDA_VERSION', '0.2.31' );
define( 'TILDA_MINIMUM_WP_VERSION', '3.1' );
define( 'TILDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TILDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TILDA_DELETE_LIMIT', 100000 );

require_once( TILDA_PLUGIN_DIR . 'class.tilda.php' );

register_activation_hook( __FILE__, array( 'Tilda', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Tilda', 'plugin_deactivation' ) );

add_action( 'init', array( 'Tilda', 'init' ) );


if ( is_admin() ) {
    require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
    add_action( 'init', array( 'Tilda_Admin', 'init' ) );
}