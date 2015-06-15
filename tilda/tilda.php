<?php
/**
 * Created by PhpStorm.
 * User: ALEX
 * Date: 09.04.15
 * Time: 19:16
 */

/*
Plugin Name: Tilda Publishing
Description: Tilda позволяет делать яркую подачу материала, качественную верстку и эффектную типографику, близкую к журнальной. Каким бы ни был ваш контент — Tilda знает, как его показать. С чего начать: 1) Нажмите ссылку «Активировать» слева от этого описания; 2) <a href="http://www.tilda.cc/" target="_blank">Зарегистрируйтесь</a>, чтобы получить API-ключ; 3) Перейдите на страницу настройки Tilda Publishing и введите свой API-ключ. Читайте подробную инструкцию по подключению.
Version: 0.1
Author: Tilda Publishing / BroAgency
License: GPLv2 or later
Text Domain: api tilda
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
error_reporting(0);
 
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'TILDA_VERSION', '0.1' );
define( 'TILDA_MINIMUM_WP_VERSION', '3.1' );
define( 'TILDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TILDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TILDA_DELETE_LIMIT', 100000 );

register_activation_hook( __FILE__, array( 'Tilda', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Tilda', 'plugin_deactivation' ) );

require_once( TILDA_PLUGIN_DIR . 'class.tilda.php' );

add_action( 'init', array( 'Tilda', 'init' ) );


if ( is_admin() ) {
    require_once( TILDA_PLUGIN_DIR . 'class.tilda-admin.php' );
    add_action( 'init', array( 'Tilda_Admin', 'init' ) );
}