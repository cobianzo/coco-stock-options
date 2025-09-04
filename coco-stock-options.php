<?php
/**
 * Plugin Name: Coco Stock Options API
 * Plugin URI: https://cobianzo.com/plugins/coco-stock-options/
 * Description: This plugin defines CPT for the required stocks and scans in a cron job the values for the future options for every stock from the API of `https://cdn.cboe.com/api/global/delayed_quotes/options/<stock-symbol>.json`, and formats the result to save it as post meta and expose it in our own REST API under our endpoint `wp-json/coco/puts/LMT?date=250815&strike=00310000`
 * Version: 1.0.2
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
define( 'COCO_STOCK_OPTIONS_VERSION', '1.0.2' );
define( 'COCO_STOCK_OPTIONS_FILE', __FILE__ );
define( 'COCO_STOCK_OPTIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'COCO_STOCK_OPTIONS_URL', plugin_dir_url( __FILE__ ) );
define( 'COCO_STOCK_OPTIONS_BUILD_DIR', COCO_STOCK_OPTIONS_DIR . 'build/' );
define( 'COCO_STOCK_OPTIONS_BUILD_URL', COCO_STOCK_OPTIONS_URL . 'build/' );

/**
 * Initialize plugin
 *
 * @return void
 */
function coco_mp_init(): void {

	// includes
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/admin/class-admin-enqueue.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/admin/class-admin-page.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/admin/class-admin-ui.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/api/class-api-parameter-validator.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/api/class-options-data-helper.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/api/class-wordpress-api.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/cboe/class-cboe-connection.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/cboe/class-sync-cboe-data.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/cron/class-buffer-manager.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/cron/class-cron-job.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/cron/class-garbage-cleaner.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/models/class-stock-cpt.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/models/class-stock-meta.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/blocks/class-stock-block-ui.php';
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/class-coco-stock-options.php';

	// Include blocks system
	require_once COCO_STOCK_OPTIONS_DIR . 'inc/class-blocks-manager.php';

	// Initialize the main plugin
	\CocoStockOptions\CocoStockOptions::get_instance();

	// Initialize blocks system
	\CocoStockOptions\BlocksManager::get_instance();
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
if ( ! function_exists( 'dd' ) ) {
	function dd( mixed $var ): void {
		echo '<pre>';
		var_dump( $var );
		echo '</pre>';
	}
}

/**
 * Debug function to dump variables and die
 *
 * @param mixed $var Variable to dump
 * @return void
 */
if ( ! function_exists( 'ddie' ) ) {
	function ddie( mixed $var = '' ): void {
		dd( $var );
		wp_die();
	}
}
// phpcs:enable
