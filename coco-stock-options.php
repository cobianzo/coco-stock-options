<?php
/**
 * Plugin Name: Coco Stock Options API plugin
 * Plugin URI: https://cobianzo.com/plugins/coco-stock-options/
 * Description: This plugin defines CPT for the required stocks and scans in a cron job the values for the future options for every stock from the API of `https://cdn.cboe.com/api/global/delayed_quotes/options/<stock-symbol>.json`, and formats the result to save it as post meta and expose it in our own REST API under our endpoint `wp-json/coco/puts/lmt?date=250815&strike=350`
 * Version: 1.0.0
 * Author: cobianzo
 * Author URI: https://cobianzo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coco-stock-options
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package CocoMiPlugin
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 */
define( 'COCO_MP_VERSION', '1.0.0' );
define( 'COCO_MP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COCO_MP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize plugin
 *
 * @return void
 */
function coco_mp_init(): void {

	// includes
	require_once COCO_MP_PLUGIN_DIR . 'inc/class-admin-enqueue.php';
}

// Initialize the plugin
coco_mp_init();

// Debugging functions.
// phpcs:disable
/**
 * Debug function to dump variables
 *
 * @param mixed $var Variable to dump
 * @return void
 */
function dd( mixed $var ): void {
	echo '<pre>';
	var_dump( $var );
	echo '</pre>';
}

/**
 * Debug function to dump variables and die
 *
 * @param mixed $var Variable to dump
 * @return never
 */
function ddie( mixed $var = '' ): never {
	dd( $var );
	wp_die();
}
// phpcs:enable
