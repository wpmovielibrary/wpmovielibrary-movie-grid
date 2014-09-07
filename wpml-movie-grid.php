<?php
/**
 * WPMovieLibrary-Movie-Grid
 *
 * Add a grid view for WPMovieLibrary movies
 *
 * @package   WPMovieLibrary-Movie-Grid
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 CaerCam.org
 *
 * @wordpress-plugin
 * Plugin Name: WPMovieLibrary-Movie-Grid
 * Plugin URI:  http://wpmovielibrary.com/extensions/wpmovielibrary-movie-grid/
 * Description: Add a grid view for WPMovieLibrary movies
 * Version:     1.0
 * Author:      Charlie MERLAND
 * Author URI:  http://www.caercam.org/
 * Text Domain: wpml-movie-grid
 * License:     GPL-3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/CaerCam/wpmovielibrary-movie-grid/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPMLMG_NAME',                   'WPMovieLibrary-Movie-Grid' );
define( 'WPMLMG_VERSION',                '1.0' );
define( 'WPMLMG_SLUG',                   'wpml-movie-grid' );
define( 'WPMLMG_URL',                    plugins_url( basename( __DIR__ ) ) );
define( 'WPMLMG_PATH',                   plugin_dir_path( __FILE__ ) );
define( 'WPMLMG_REQUIRED_PHP_VERSION',   '5.3' );
define( 'WPMLMG_REQUIRED_WP_VERSION',    '3.6' );
define( 'WPMLMG_REQUIRED_WPML_VERSION',  '1.2' );


/**
 * Determine whether WPML is active or not.
 *
 * @since    1.0
 *
 * @return   boolean
 */
function is_wpml_active() {

	return defined( 'WPML_VERSION' );
}

/**
 * Checks if the system requirements are met
 * 
 * @since    1.0
 * 
 * @return   bool    True if system requirements are met, false if not
 */
function wpmlmg_requirements_met() {

	global $wp_version;

	if ( version_compare( PHP_VERSION, WPMLMG_REQUIRED_PHP_VERSION, '<=' ) )
		return false;

	if ( version_compare( $wp_version, WPMLMG_REQUIRED_WP_VERSION, '<=' ) )
		return false;

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0
 */
function wpmlmg_requirements_error() {

	global $wp_version;

	require_once WPMLMG_PATH . '/views/requirements-error.php';
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0.1
 */
function wpmlmg_l10n() {

	$domain = 'wpmovielibrary-movie-grid';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, WPMLMG_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );
	load_plugin_textdomain( $domain, FALSE, basename( __DIR__ ) . '/languages/' );
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the
 * plugin requirements are met. Otherwise older PHP installations could crash
 * when trying to parse it.
 */
if ( wpmlmg_requirements_met() ) {

	require_once( WPMLMG_PATH . 'includes/class-module.php' );
	require_once( WPMLMG_PATH . 'class-wpml-movie-grid.php' );

	if ( class_exists( 'WPMovieLibrary_Movie_Grid' ) ) {
		$GLOBALS['wpmlmg'] = new WPMovieLibrary_Movie_Grid();
		register_activation_hook(   __FILE__, array( $GLOBALS['wpmltr'], 'activate' ) );
		register_deactivation_hook( __FILE__, array( $GLOBALS['wpmltr'], 'deactivate' ) );
	}

	WPMovieLibrary_Trailers::require_wpml_first();

	if ( is_admin() ) {
		
	}
}
else {
	add_action( 'init', 'wpmlmg_l10n' );
	add_action( 'admin_notices', 'wpmlmg_requirements_error' );
}
