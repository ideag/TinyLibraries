<?php
/**
 * TinyLibraries Sample A
 *
 * @package tinylibraries-sample-a
 */

/*
Plugin Name: TinyLibraries Sample A
Plugin URI: http://arunas.co
Description: Loads A library
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: tinylibraries
Libraries: wp-background-processing
*/

add_action( 'plugins_loaded', 'tinylibraries_a_load' );
/**
 * Loads library A
 *
 * @return void
 */
function tinylibraries_a_load() {
	TinyLibraries()->require_library( 'wp-background-processing' );
}

add_action( 'admin_notices', 'tinylibraries_a_show' );
/**
 * Displays a notice if the correct class is present;
 *
 * @return void
 */
function tinylibraries_a_show() {
	if ( class_exists( 'WP_Background_Process' ) ) {
		echo 'A library loaded<br />';
	}
}
