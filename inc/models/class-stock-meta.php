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
	 * Get all options data for a specific field across all strikes
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $ticker Stock ticker symbol (e.g., 'LMT').
	 * @param string $date Date in format YYMMDD (e.g., '250815').
	 * @param string $option_type Option type ('C' for Call, 'P' for Put).
	 * @param string $field Field name to extract (e.g., 'bid', 'ask').
	 * @return array Associative array with strike as key and field value as value.
	 */
	public function get_field_for_all_strikes( int $post_id, string $ticker, string $date, string $option_type, string $field ): array {
		$options_data = $this->get_stock_options_by_date( $post_id, $ticker, $date, $option_type );
		$result       = [];

		if ( $options_data ) {
			foreach ( $options_data as $strike => $strike_data ) {
				if ( isset( $strike_data[ $field ] ) ) {
					$result[ $strike ] = $strike_data[ $field ];
				}
			}
		}

		return $result;
	}

	/**
	 * Get stock options data from post meta
	 *
	 * This method is provided for backward compatibility with other parts of the codebase
	 * that might be expecting the old method signature.
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $meta_key Meta key. This could be in various formats depending on the caller:
	 *                         - Full meta key (e.g., 'LMT250815C')
	 *                         - Date + option type (e.g., '250815C')
	 *                         - Date + option type + strike (e.g., '250815C00310000')
	 * @return array|false Options data array or false if not found.
	 */
	public function get_stock_options( int $post_id, string $meta_key ): array|false {
		// Get the data directly if it's a full meta key
		$data = get_post_meta( $post_id, $meta_key, true );
		if ( ! empty( $data ) ) {
			return $data;
		}

		// If the meta_key is in format 'YYMMDDX' (date + option type)
		// Try to get the ticker from the post and build the full meta key
		if ( preg_match( '/^\d{6}[CP]$/', $meta_key ) ) {
			$ticker = get_post_field( 'post_title', $post_id );
			if ( $ticker ) {
				$full_meta_key = $ticker . $meta_key;
				$data          = get_post_meta( $post_id, $full_meta_key, true );
				if ( ! empty( $data ) ) {
					return $data;
				}
			}
		}

		// If the meta_key includes a strike price (e.g., '250815C00310000')
		// Extract the date and option type, then try to get the data
		if ( preg_match( '/^(\d{6})([CP])(\d{8})$/', $meta_key, $matches ) ) {
			$date        = $matches[1];
			$option_type = $matches[2];
			$strike      = $matches[3];
			
			$ticker = get_post_field( 'post_title', $post_id );
			if ( $ticker ) {
				$full_meta_key = $ticker . $date . $option_type;
				$data          = get_post_meta( $post_id, $full_meta_key, true );
				if ( ! empty( $data ) && isset( $data[ $strike ] ) ) {
					// Return just the specific strike data if requested
					return $data[ $strike ];
				} elseif ( ! empty( $data ) ) {
					// Return all strikes if the specific one wasn't found
					return $data;
				}
			}
		}

		return false;
	}
}
