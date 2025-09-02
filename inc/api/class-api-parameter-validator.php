<?php
/**
 * API Parameter Validator class
 *
 * Handles validation of REST API parameters following single responsibility principle.
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Api;

/**
 * API Parameter Validator class
 *
 * Responsible for validating REST API request parameters
 */
class ApiParameterValidator {

	/**
	 * Validate symbol parameter
	 *
	 * @param string $symbol Stock symbol.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_symbol( string $symbol ): bool {
		return ! empty( $symbol ) && preg_match( '/^[A-Za-z]+$/', $symbol );
	}

	/**
	 * Validate date parameter
	 *
	 * @param string $date Date in format YYMMDD.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_date( string $date ): bool {
		if ( empty( $date ) ) {
			return true; // Optional parameter
		}

		return preg_match( '/^\d{6}$/', $date );
	}

	/**
	 * Validate strike parameter
	 *
	 * @param string $strike Strike price.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_strike( string $strike ): bool {
		if ( empty( $strike ) ) {
			return true; // Optional parameter
		}

		return is_numeric( $strike ) && (float) $strike > 0;
	}

	/**
	 * Validate field parameter
	 *
	 * @param string $field Field name.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_field( string $field ): bool {
		if ( empty( $field ) ) {
			return true; // Optional parameter
		}

		$valid_fields = [
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

		return in_array( $field, $valid_fields, true );
	}

	/**
	 * Validate exclude_bid_0 parameter
	 *
	 * @param mixed $param Parameter value.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_exclude_bid_0( $param ): bool {
		if ( is_null( $param ) || '' === $param ) {
			return true; // Optional parameter
		}

		// Accept boolean, "true", "false", "1", "0"
		return is_bool( $param ) || in_array( strtolower( (string) $param ), [ 'true', 'false', '1', '0' ], true );
	}

	/**
	 * Validate option type parameter for stock-options-by-id endpoint
	 *
	 * @param mixed $param Parameter value.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_option_type( $param ): bool {
		if ( is_null( $param ) || '' === $param ) {
			return true; // Optional parameter
		}

		return in_array( strtolower( $param ), [ 'put', 'call' ], true );
	}

	/**
	 * Sanitize boolean parameter from request
	 *
	 * @param mixed $param Parameter value.
	 * @return bool Sanitized boolean value.
	 */
	public function sanitize_boolean_param( $param ): bool {
		if ( is_bool( $param ) ) {
			return $param;
		}

		// Convert string values to boolean
		$param_lower = strtolower( (string) $param );
		return in_array( $param_lower, [ 'true', '1' ], true );
	}
}
