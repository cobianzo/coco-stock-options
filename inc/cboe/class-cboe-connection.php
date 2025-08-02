<?php
/**
 * CBOE Connection class for handling API requests
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Cboe;

/**
 * CBOE Connection class for external API communication
 */
class CboeConnection {

	/**
	 * CBOE API base URL
	 */
	const CBOE_API_BASE = 'https://cdn.cboe.com/api/global/delayed_quotes/options/';

	/**
	 * Request timeout in seconds
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Initialize the CBOE connection
	 */
	public function __construct() {
		// No hooks needed for this class
	}

	/**
	 * Get options data for a specific stock symbol
	 *
	 * @param string $symbol Stock symbol (e.g., 'LMT').
	 * @return array|WP_Error Options data array or WP_Error on failure.
	 */
	public function get_stock_options( string $symbol ): array|\WP_Error {
		$url = self::CBOE_API_BASE . strtoupper( $symbol ) . '.json';

		$response = wp_remote_get( $url, [
			'timeout' => self::REQUEST_TIMEOUT,
			'headers' => [
				'User-Agent' => 'CocoStockOptions/1.0.0',
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new \WP_Error(
				'cboe_api_error',
				sprintf( 'CBOE API returned status code %d', $status_code ),
				[ 'status' => $status_code ]
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( null === $data ) {
			return new \WP_Error(
				'cboe_json_error',
				'Failed to parse JSON response from CBOE API'
			);
		}

		return $data;
	}

	/**
	 * Check if a stock symbol exists in CBOE API
	 *
	 * @param string $symbol Stock symbol.
	 * @return bool True if exists, false otherwise.
	 */
	public function stock_exists_in_cboe( string $symbol ): bool {
		$data = $this->get_stock_options( $symbol );

		if ( is_wp_error( $data ) ) {
			return false;
		}

		// Check if the response contains valid data structure
		return isset( $data['data'] ) && is_array( $data['data'] );
	}

	/**
	 * Get the last update timestamp from CBOE response
	 *
	 * @param array $cboe_data CBOE API response data.
	 * @return string|null Timestamp string or null if not found.
	 */
	public function get_cboe_timestamp( array $cboe_data ): ?string {
		return isset( $cboe_data['timestamp'] ) ? $cboe_data['timestamp'] : null;
	}

	/**
	 * Validate CBOE API response structure
	 *
	 * @param array $cboe_data CBOE API response data.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_cboe_response( array $cboe_data ): bool {
		return is_array( $cboe_data ) && isset( $cboe_data['data'] ) && is_array( $cboe_data['data'] );
	}

	/**
	 * Get available expiration dates from CBOE response
	 *
	 * @param array $cboe_data CBOE API response data.
	 * @return array Array of expiration dates.
	 */
	public function get_expiration_dates( array $cboe_data ): array {
		if ( ! $this->validate_cboe_response( $cboe_data ) ) {
			return [];
		}

		$dates = [];

		// Extract dates from the CBOE response structure
		// This will need to be adjusted based on actual CBOE API format
		if ( isset( $cboe_data['data']['options'] ) ) {
			foreach ( $cboe_data['data']['options'] as $option ) {
				if ( isset( $option['expiration'] ) ) {
					$dates[] = $option['expiration'];
				}
			}
		}

		return array_unique( $dates );
	}

	/**
	 * Test CBOE API connectivity
	 *
	 * @return array Test results.
	 */
	public function test_connectivity(): array {
		$results = [
			'status'    => 'unknown',
			'message'   => '',
			'timestamp' => current_time( 'mysql' ),
		];

		// Test with a known stock symbol (LMT)
		$test_data = $this->get_stock_options( 'LMT' );

		if ( is_wp_error( $test_data ) ) {
			$results['status']  = 'error';
			$results['message'] = $test_data->get_error_message();
		} else {
			$results['status']  = 'success';
			$results['message'] = 'CBOE API connection successful';
		}

		return $results;
	}
}
