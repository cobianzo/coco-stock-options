<?php
/**
 * WordPress API class for REST endpoints
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Api;

use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;

/**
 * WordPress API class for handling REST API endpoints
 */
class WordPressApi {

	/**
	 * Stock CPT instance
	 *
	 * @var Stock_CPT
	 */
	private Stock_CPT $stock_cpt;

	/**
	 * Stock Meta instance
	 *
	 * @var Stock_Meta
	 */
	private Stock_Meta $stock_meta;

	/**
	 * Initialize the WordPress API
	 *
	 * @param Stock_CPT  $stock_cpt  Stock CPT instance.
	 * @param Stock_Meta $stock_meta Stock Meta instance.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta ) {
		$this->stock_cpt  = $stock_cpt;
		$this->stock_meta = $stock_meta;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route( 'coco/v1', '/puts/(?P<symbol>[a-zA-Z]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_puts_options' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'symbol' => [
					'required'          => true,
					'validate_callback' => [ $this, 'validate_symbol' ],
				],
				'date'   => [
					'required'          => false,
					'validate_callback' => [ $this, 'validate_date' ],
				],
				'strike' => [
					'required'          => false,
					'validate_callback' => [ $this, 'validate_strike' ],
				],
				'field'  => [
					'required'          => false,
					'validate_callback' => [ $this, 'validate_field' ],
				],
			],
		] );
	}

	/**
	 * Get puts options endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_puts_options( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$symbol = strtoupper( $request->get_param( 'symbol' ) );
		$date   = $request->get_param( 'date' );
		$strike = $request->get_param( 'strike' );
		$field  = $request->get_param( 'field' );

		// Get stock post
		$stock_post = $this->stock_cpt->get_stock_by_symbol( $symbol );
		if ( ! $stock_post ) {
			return new \WP_Error(
				'stock_not_found',
				sprintf( 'Stock %s not found', $symbol ),
				[ 'status' => 404 ]
			);
		}

		// If no date specified, return all options for the stock
		if ( empty( $date ) ) {
			return $this->get_all_options_for_stock( $stock_post->ID );
		}

		// If strike specified, look for specific option
		if ( ! empty( $strike ) ) {
			return $this->get_specific_option( $stock_post->ID, $date, $strike, $field );
		}

		// Return all options for specific date
		return $this->get_options_for_date( $stock_post->ID, $date );
	}

	/**
	 * Get all options for a stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return \WP_REST_Response Response object.
	 */
	private function get_all_options_for_stock( int $post_id ): \WP_REST_Response {
		$options_keys = $this->stock_meta->get_stock_options_keys( $post_id );
		$options_data = [];

		foreach ( $options_keys as $meta_key ) {
			$options_data[ $meta_key ] = $this->stock_meta->get_stock_options( $post_id, $meta_key );
		}

		return new \WP_REST_Response( $options_data, 200 );
	}

	/**
	 * Get options for a specific date
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $date Date in format YYMMDD.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	private function get_options_for_date( int $post_id, string $date ): \WP_REST_Response|\WP_Error {
		$options_keys = $this->stock_meta->get_stock_options_keys( $post_id );
		$options_data = [];

		foreach ( $options_keys as $meta_key ) {
			// Check if meta key starts with the date
			if ( strpos( $meta_key, $date ) === 0 ) {
				$options_data[ $meta_key ] = $this->stock_meta->get_stock_options( $post_id, $meta_key );
			}
		}

		if ( empty( $options_data ) ) {
			return new \WP_Error(
				'no_options_found',
				sprintf( 'No options found for date %s', $date ),
				[ 'status' => 404 ]
			);
		}

		return new \WP_REST_Response( $options_data, 200 );
	}

	/**
	 * Get specific option by date and strike
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $date Date in format YYMMDD.
	 * @param string $strike Strike price.
	 * @param string $field Specific field to return.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	private function get_specific_option( int $post_id, string $date, string $strike, ?string $field ): \WP_REST_Response|\WP_Error {
		// Convert strike to the format used in meta key
		$strike_formatted = $this->format_strike_for_meta_key( $strike );
		if ( ! $strike_formatted ) {
			return new \WP_Error(
				'invalid_strike',
				sprintf( 'Invalid strike price: %s', $strike ),
				[ 'status' => 400 ]
			);
		}

		// Try both call and put options
		$option_types = [ 'C', 'P' ];
		$found_option = null;

		foreach ( $option_types as $type ) {
			$meta_key    = $date . $type . $strike_formatted;
			$option_data = $this->stock_meta->get_stock_options( $post_id, $meta_key );

			if ( $option_data ) {
				$found_option = $option_data;
				break;
			}
		}

		if ( ! $found_option ) {
			return new \WP_Error(
				'option_not_found',
				sprintf( 'Option not found for date %s and strike %s', $date, $strike ),
				[ 'status' => 404 ]
			);
		}

		// If specific field requested, return only that field
		if ( ! empty( $field ) ) {
			if ( ! isset( $found_option[ $field ] ) ) {
				return new \WP_Error(
					'field_not_found',
					sprintf( 'Field %s not found in option data', $field ),
					[ 'status' => 400 ]
				);
			}
			return new \WP_REST_Response( $found_option[ $field ], 200 );
		}

		return new \WP_REST_Response( $found_option, 200 );
	}

	/**
	 * Format strike price for meta key
	 *
	 * @param string $strike Strike price.
	 * @return string|false Formatted strike or false on failure.
	 */
	private function format_strike_for_meta_key( string $strike ): string|false {
		// Validate strike is numeric
		if ( ! is_numeric( $strike ) ) {
			return false;
		}

		$strike_float = (float) $strike;

		// Format as 8-digit number (e.g., 310.00 -> 00310000)
		return sprintf( '%08d', (int) ( $strike_float * 100 ) );
	}

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
	 * Get API documentation
	 *
	 * @return array API documentation.
	 */
	public function get_api_documentation(): array {
		return [
			'endpoints'  => [
				'get_all_options'     => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}',
					'method'      => 'GET',
					'description' => 'Get all options for a stock symbol',
					'example'     => '/wp-json/coco/v1/puts/LMT',
				],
				'get_options_by_date' => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}',
					'method'      => 'GET',
					'description' => 'Get all options for a stock on a specific date',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801',
				],
				'get_specific_option' => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}&strike={strike}',
					'method'      => 'GET',
					'description' => 'Get a specific option by date and strike',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801&strike=310',
				],
				'get_specific_field'  => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}&strike={strike}&field={field}',
					'method'      => 'GET',
					'description' => 'Get a specific field from an option',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801&strike=310&field=bid',
				],
			],
			'parameters' => [
				'symbol' => 'Stock symbol (e.g., LMT)',
				'date'   => 'Date in format YYMMDD (e.g., 250801 for August 1, 2025)',
				'strike' => 'Strike price (e.g., 310)',
				'field'  => 'Specific field to return (e.g., bid, ask, delta, etc.)',
			],
		];
	}
}
