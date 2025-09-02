<?php
/**
 * Stock Meta class.
 * CRUD to modify the stored in DB data about the options associated to a stock.
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
	 * Get stock options data for a specific date and option type
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @return array|false Options data array or false if not found.
	 */
	public function get_stock_options_by_date( int $post_id, string $ticker, string $date, string $option_type ): array|false {
		$meta_key = $this->build_meta_key( $ticker, $date, $option_type );
		$data     = get_post_meta( $post_id, $meta_key, true );
		return ! empty( $data ) ? $data : false;
	}

	/**
	 * Get specific strike data for a stock option
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @param string $strike Strike price (e.g., '00310000').
	 * @return array|false Strike data array or false if not found.
	 */
	public function get_strike_data( int $post_id, string $ticker, string $date, string $option_type, string $strike ): array|false {
		$options_data = $this->get_stock_options_by_date( $post_id, $ticker, $date, $option_type );
		if ( $options_data && isset( $options_data[ $strike ] ) ) {
			return $options_data[ $strike ];
		}
		return false;
	}

	/**
	 * Add or update a strike for a stock option
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @param string $strike Strike price (e.g., '00310000').
	 * @param array  $strike_data Strike data array.
	 * @return bool|int Meta ID on success, false on failure.
	 */
	public function add_or_update_strike( int $post_id, string $ticker, string $date, string $option_type, string $strike, array $strike_data ): bool|int {
		$meta_key     = $this->build_meta_key( $ticker, $date, $option_type );
		$options_data = get_post_meta( $post_id, $meta_key, true );

		if ( empty( $options_data ) ) {
			$options_data = [];
		}

		$options_data[ $strike ] = $strike_data;
		return update_post_meta( $post_id, $meta_key, $options_data );
	}

	/**
	 * Delete a strike from a stock option
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @param string $strike Strike price (e.g., '00310000').
	 * @return bool True on success, false on failure.
	 */
	public function delete_strike( int $post_id, string $ticker, string $date, string $option_type, string $strike ): bool {
		$meta_key     = $this->build_meta_key( $ticker, $date, $option_type );
		$options_data = get_post_meta( $post_id, $meta_key, true );

		if ( empty( $options_data ) || ! isset( $options_data[ $strike ] ) ) {
			return false;
		}

		unset( $options_data[ $strike ] );

		// If no strikes left, delete the entire meta key
		if ( empty( $options_data ) ) {
			return delete_post_meta( $post_id, $meta_key );
		}

		// Otherwise update with the remaining strikes
		return (bool) update_post_meta( $post_id, $meta_key, $options_data );
	}

	/**
	 * Delete all options data for a specific date and option type
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @return bool True on success, false on failure.
	 */
	public function delete_options_by_date( int $post_id, string $ticker, string $date, string $option_type ): bool {
		$meta_key = $this->build_meta_key( $ticker, $date, $option_type );
		return delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Get all options meta keys for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array Array of meta keys.
	 */
	public function get_stock_options_keys( int $post_id ): array {
		global $wpdb;

		$ticker    = get_post_field( 'post_title', $post_id );
		$meta_keys = $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
			$post_id,
			$ticker . '%'
		) );

		return $meta_keys;
	}

	/**
	 * Get latest option data for a stock
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @return array|false Latest option data or false if none found.
	 */
	public function get_latest_option_data( int $post_id, string $ticker ): array|false {
		$options_keys = $this->get_stock_options_keys( $post_id );
		$latest_data  = false;
		$latest_time  = null;

		foreach ( $options_keys as $meta_key ) {
			// Only process keys that start with the ticker
			if ( strpos( $meta_key, $ticker ) !== 0 ) {
				continue;
			}

			$options_data = get_post_meta( $post_id, $meta_key, true );
			if ( ! empty( $options_data ) ) {
				// Check each strike for the latest timestamp
				foreach ( $options_data as $strike_data ) {
					if ( isset( $strike_data['last_update'] ) ) {
						if ( null === $latest_time || $strike_data['last_update'] > $latest_time ) {
							$latest_time = $strike_data['last_update'];
							$latest_data = $strike_data;
						}
					}
				}
			}
		}

		return $latest_data;
	}

	/**
	 * Build meta key from components
	 *
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @return string Formatted meta key (e.g., 'LMT250815C').
	 */
	private function build_meta_key( string $ticker, string $date, string $option_type ): string {
		return $ticker . $date . $option_type;
	}

	/**
	 * Parse meta key into components
	 *
	 * @param string $meta_key Meta key (e.g., 'LMT250815C').
	 * @return array|false Array with ticker, date, and option_type or false if invalid format.
	 */
	public function parse_meta_key( string $meta_key ): array|false {
		// Match pattern like 'LMT250815C'
		if ( preg_match( '/^([A-Z]+)(\d{6})([CP])$/', $meta_key, $matches ) ) {
			return [
				'ticker'      => $matches[1],
				'date'        => $matches[2],
				'option_type' => $matches[3],
			];
		}
		return false;
	}


	/**
	 * Get all PUTS meta data for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array Array of PUTS meta data.
	 */
	public function get_all_puts_meta( int $post_id ): array {
		$all_meta_keys = $this->get_stock_options_keys( $post_id );
		$puts_data     = [];

		foreach ( $all_meta_keys as $meta_key ) {
			$parsed_key = $this->parse_meta_key( $meta_key );
			if ( $parsed_key && 'P' === $parsed_key['option_type'] ) {
				$puts_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
			}
		}

		return $puts_data;
	}

	/**
	 * Get all CALLS meta data for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array Array of CALLS meta data.
	 */
	public function get_all_calls_meta( int $post_id ): array {
		$all_meta_keys = $this->get_stock_options_keys( $post_id );
		$calls_data    = [];

		foreach ( $all_meta_keys as $meta_key ) {
			$parsed_key = $this->parse_meta_key( $meta_key );
			if ( $parsed_key && 'C' === $parsed_key['option_type'] ) {
				$calls_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
			}
		}

		return $calls_data;
	}
}
