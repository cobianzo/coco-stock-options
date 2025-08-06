<?php
/**
 * Admin UI class
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Admin;

/**
 * Admin UI class
 */
class Admin_UI {

	/**
	 * Initialize the models
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		// All UI related hooks are now in CocoStockOptions\Models\Stock_UI
	}
}
