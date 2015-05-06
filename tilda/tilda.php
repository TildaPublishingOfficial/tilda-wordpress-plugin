<?php
/**
 * Created by PhpStorm.
 * User: ALEX
 * Date: 09.04.15
 * Time: 19:16
 */

/*
Plugin Name: Tilda Publishing API
Description: Connect pages from Tilda Publishing
Version: 0.1
Author: Tilda Publishing / BroAgency
License: GPLv2 or later
Text Domain: api tilda
*/

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