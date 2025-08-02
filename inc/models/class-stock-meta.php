<?php
/**
 * Stock Meta class
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Models;

/**
 * Stock Meta class
 */
class Stock_Meta {

	/**
	 * Save stock options data to post meta
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $meta_key Meta key (e.g., '250801C00310000').
	 * @param array  $options_data Options data array.
	 * @return bool|int Meta ID on success, false on failure.
	 */
	public function save_stock_options( $post_id, $meta_key, $options_data ) {
		return update_post_meta( $post_id, $meta_key, $options_data );
	}

	/**
	 * Get stock options data from post meta
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $meta_key Meta key.
	 * @return array|false Options data array or false if not found.
	 */
	public function get_stock_options( $post_id, $meta_key ) {
		$data = get_post_meta( $post_id, $meta_key, true );
		return ! empty( $data ) ? $data : false;
	}

	/**
	 * Delete stock options data
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $meta_key Meta key.
	 * @return bool True on success, false on failure.
	 */
	public function delete_stock_options( $post_id, $meta_key ) {
		return delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Get all options meta keys for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array Array of meta keys.
	 */
	public function get_stock_options_keys( $post_id ) {
		global $wpdb;

		$meta_keys = $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key REGEXP '^[0-9]{6}[CP][0-9]{8}$'",
			$post_id
		) );

		return $meta_keys;
	}

	/**
	 * Get latest option data for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array|false Latest option data or false if none found.
	 */
	public function get_latest_option_data( $post_id ) {
		$options_keys = $this->get_stock_options_keys( $post_id );
		$latest_data  = false;
		$latest_time  = null;

		foreach ( $options_keys as $meta_key ) {
			$options_data = $this->get_stock_options( $post_id, $meta_key );
			if ( $options_data && isset( $options_data['last_update'] ) ) {
				if ( null === $latest_time || $options_data['last_update'] > $latest_time ) {
					$latest_time = $options_data['last_update'];
					$latest_data = $options_data;
				}
			}
		}

		return $latest_data;
	}
}
