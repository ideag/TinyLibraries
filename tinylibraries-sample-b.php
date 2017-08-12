<?php
/**
 * TinyLibraries Sample B
 *
 * @package tinylibraries-sample-b
 */

/*
Plugin Name: TinyLibraries Sample B
Plugin URI: http://arunas.co
Description: Loads B library
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: tinylibraries
Libraries: butterbean
*/

add_action( 'admin_notices', 'tinylibraries_b_show' );
/**
 * Loads library B and displays a notice if the correct class is present;
 *
 * @return void
 */
function tinylibraries_b_show() {
	TinyLibraries()->require_library( 'butterbean' );
	if ( class_exists( 'ButterBean' ) ) {
		echo 'B library loaded<br />';
	}
}
