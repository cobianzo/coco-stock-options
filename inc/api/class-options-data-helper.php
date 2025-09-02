<?php
/**
 * Options Data Helper class
 *
 * Provides utility methods for manipulating options data following single responsibility principle.
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Api;

/**
 * Options Data Helper class
 *
 * Responsible for data manipulation and filtering operations on options data
 */
class OptionsDataHelper {

	/**
	 * Filter options data to exclude entries with bid = 0
	 *
	 * Recursively filters nested arrays to remove any option entries where the 'bid' field equals 0.
	 * Preserves the original data structure while excluding unwanted entries.
	 *
	 * @param array $options_data Array of options data.
	 * @return array Filtered options data.
	 */
	public function filter_options_by_bid( array $options_data ): array {
		$filtered_data = [];

		foreach ( $options_data as $option_key => $option_group ) {
			if ( ! is_array( $option_group ) ) {
				// If it's not an array, keep as is
				$filtered_data[ $option_key ] = $option_group;
				continue;
			}

			// Filter individual options within the group
			$filtered_group = [];
			foreach ( $option_group as $strike_key => $option_data ) {
				if ( ! is_array( $option_data ) ) {
					// If not an array, keep as is
					$filtered_group[ $strike_key ] = $option_data;
					continue;
				}

				// Check if this option has bid = 0 and should be excluded
				if ( isset( $option_data['bid'] ) && 0.0 === (float) $option_data['bid'] ) {
					// Skip this option (exclude it)
					continue;
				}

				// Keep this option
				$filtered_group[ $strike_key ] = $option_data;
			}

			// Only add the option group if it has any remaining options
			if ( ! empty( $filtered_group ) ) {
				$filtered_data[ $option_key ] = $filtered_group;
			}
		}

		return $filtered_data;
	}

	/**
	 * Format strike price for meta key
	 *
	 * Converts strike price to the format used in WordPress meta keys.
	 *
	 * @param string $strike Strike price.
	 * @return string|false Formatted strike or false on failure.
	 */
	public function format_strike_for_meta_key( string $strike ): string|false {
		// Validate strike is numeric
		if ( ! is_numeric( $strike ) ) {
			return false;
		}

		// Convert string to float and divide by 1000 to handle decimals
		$strike_float = (float) $strike / 1000;
		return (string) $strike_float;
		// Format as 8-digit number (e.g., 310.00 -> 00310000)
	}

	/**
	 * Get valid option fields
	 *
	 * Returns an array of all valid option field names that can be requested via the API.
	 *
	 * @return array Array of valid field names.
	 */
	public function get_valid_option_fields(): array {
		return [
			'last_update',
			'cboe_timestamp',
			'date',
			'option',
			'bid',
			'bid_size',
			'ask',
			'ask_size',
			'iv',
			'open_interest',
			'volume',
			'delta',
			'gamma',
			'vega',
			'theta',
			'rho',
			'theo',
			'change',
			'open',
			'high',
			'low',
			'tick',
			'last_trade_price',
			'last_trade_time',
			'percent_change',
			'prev_day_close',
		];
	}

	/**
	 * Check if an option field is valid
	 *
	 * @param string $field Field name to check.
	 * @return bool True if field is valid, false otherwise.
	 */
	public function is_valid_option_field( string $field ): bool {
		return in_array( $field, $this->get_valid_option_fields(), true );
	}
}
