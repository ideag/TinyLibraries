<?php
/**
 * TinyLibraries Sample AB
 *
 * @package tinylibraries-sample-ab
 */

/*
Plugin Name: TinyLibraries Sample AB
Plugin URI: http://arunas.co
Description: Loads A and B libraries
Version: 0.1.0
Author: Arūnas Liuiza
Author URI: http://arunas.co
Text Domain: tinylibraries
Libraries: wp-background-processing,butterbean
*/

add_action( 'init', 'tinylibraries_ab_load' );
/**
 * Loads library A
 *
 * @return void
 */
function tinylibraries_ab_load() {
	TinyLibraries()->require_library( 'wp-background-processing' );
}

add_action( 'admin_init', 'tinylibraries_ab_init' );
/**
 * Loads library B
 *
 * @return void
 */
function tinylibraries_ab_init() {
	TinyLibraries()->require_library( 'butterbean' );
}

add_action( 'admin_notices', 'tinylibraries_ab_show' );
/**
 * Displays a notice if the correct class is present;
 *
 * @return void
 */
function tinylibraries_ab_show() {
	if ( class_exists( 'WP_Background_Process' ) ) {
		echo 'A library loaded<br />';
	}
	if ( class_exists( 'ButterBean' ) ) {
		echo 'B library loaded<br />';
	}
}
