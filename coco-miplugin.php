<?php
/**
 * Plugin Name: Coco Mi Plugin
 * Plugin URI: https://cobianzo.com/plugins/coco-miplugin/
 * Description: A boilerplate for a new plugin
 * Version: 1.0.0
 * Author: cobianzo
 * Author URI: https://cobianzo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coco-miplugin
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
