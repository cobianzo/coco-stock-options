<?php
/**
 * Enqueue class to handle scripts and styles in the admin area
 *
 * @package CocoStockOptions
 */

namespace CocoStockOptions\Admin;

/**
 * Admin Enqueue class for handling scripts and styles
 */
class AdminEnqueue {

	/**
	 * Constructor. Call hooks.
	 */
	public static function init(): void {

		// Admin scripts and styles
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_styles' ] );
	}

		/**
		 * Enqueue admin scripts
		 */
	public static function enqueue_admin_scripts(): void {
		$screen = get_current_screen();

		// Only enqueue on our plugin pages
		if ( $screen && ( strpos( $screen->id, 'coco-stock-options' ) !== false || $screen->post_type === 'stock' ) ) {
			$asset_file = include COCO_STOCK_OPTIONS_DIR . 'build/admin.asset.php';

			wp_enqueue_script(
				'coco-stock-options-admin',
				COCO_STOCK_OPTIONS_URL . 'build/admin.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);

			wp_localize_script(
				'coco-stock-options-admin',
				'cocoStockOptions',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cocostock_ajax_nonce' ),
				]
			);
		}
	}

			/**
			 * Enqueue admin styles
			 */
	public static function enqueue_admin_styles(): void {
		$screen = get_current_screen();

		// Only enqueue on our plugin pages
		if ( $screen && ( strpos( $screen->id, 'coco-stock-options' ) !== false || $screen->post_type === 'stock' ) ) {
			wp_enqueue_style(
				'coco-stock-options-admin',
				COCO_STOCK_OPTIONS_URL . 'build/index.css',
				[],
				COCO_STOCK_OPTIONS_VERSION
			);
		}
	}
}

AdminEnqueue::init();
