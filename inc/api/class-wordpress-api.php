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
use WP_REST_Response;

/**
 * WordPress API class for handling REST API endpoints
 */
class WordPressApi {

	/**
	 * Validator for API parameters
	 *
	 * @var ApiParameterValidator
	 */
	private ApiParameterValidator $validator;

	/**
	 * Helper for options data manipulation
	 *
	 * @var OptionsDataHelper
	 */
	private OptionsDataHelper $data_helper;

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
	 * @param Stock_CPT             $stock_cpt   Stock CPT instance.
	 * @param Stock_Meta            $stock_meta  Stock Meta instance.
	 * @param ApiParameterValidator $validator  API parameter validator.
	 * @param OptionsDataHelper     $data_helper Options data helper.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta, ApiParameterValidator $validator, OptionsDataHelper $data_helper ) {
		$this->stock_cpt   = $stock_cpt;
		$this->stock_meta  = $stock_meta;
		$this->validator   = $validator;
		$this->data_helper = $data_helper;
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
	 *
	 * Example endpoint: /wp-json/coco/v1/puts/BXMT?date=250815&strike=00011000&field=bid
	 */
	public function register_rest_routes(): void {
		$args = [
			'symbol' => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'validate_symbol' ],
			],
			'date'   => [
				'required'          => false,
				'validate_callback' => [ $this->validator, 'validate_date' ],
			],
			'strike' => [
				'required'          => false,
				'validate_callback' => [ $this->validator, 'validate_strike' ],
			],
			'field'  => [
				'required'          => false,
				'validate_callback' => [ $this->validator, 'validate_field' ],
			],
		];

		register_rest_route( 'coco/v1', '/puts/(?P<symbol>[a-zA-Z]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'callback_get_puts_options' ],
			'permission_callback' => '__return_true',
			'args'                => $args,
		] );

		register_rest_route( 'coco/v1', '/calls/(?P<symbol>[a-zA-Z]+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'callback_get_calls_options' ],
			'permission_callback' => '__return_true',
			'args'                => $args,
		] );

		// Route to sync stock data from CBOE: /wp-json/coco/v1/sync-stock/<symbol>
		register_rest_route( 'coco/v1', '/sync-stock/(?P<symbol>[a-zA-Z]+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'callback_sync_stock_from_cboe' ],
			'permission_callback' => [ $this, 'check_sync_permissions' ],
			'args'                => [
				'symbol' => [
					'required'          => true,
					'validate_callback' => [ $this->validator, 'validate_symbol' ],
				],
			],
		] );

		// Route to get all options data for a stock by ID: /wp-json/coco/v1/stock-options-by-id/<id>
		register_rest_route(
			'coco/v1',
			'/stock-options-by-id/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'callback_get_stock_options_by_id' ],
				'permission_callback' => '__return_true', // Adjust permissions as needed
				'args'                => [
					'type'          => [
						'validate_callback' => [ $this->validator, 'validate_option_type' ],
						'required'          => false,
					],
					'exclude_bid_0' => [
						'validate_callback' => [ $this->validator, 'validate_exclude_bid_0' ],
						'required'          => false,
						'default'           => false,
					],
				],
			]
		);
	}

	/**
	 * Get all options data for a stock by ID
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function callback_get_stock_options_by_id( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id            = (int) $request['id'];
		$type          = strtolower( $request->get_param( 'type' )?? 'all' );
		$exclude_bid_0 = $request->get_param( 'exclude_bid_0' );

		// Convert exclude_bid_0 to boolean
		$exclude_bid_0 = $this->validator->sanitize_boolean_param( $exclude_bid_0 );

		$options_data = [];

		if ( 'put' === $type ) {
			$options_data = $this->stock_meta->get_all_puts_meta( $id );
		} elseif ( 'call' === $type ) {
			$options_data = $this->stock_meta->get_all_calls_meta( $id );
		} else {
			// If no type specified, return all post meta
			$options_data = get_post_meta( $id, '', false );
		}

		if ( empty( $options_data ) ) {
			return new \WP_REST_Response( [ 'message' => 'No options data found for this stock ID' ], 404 );
		}

		// Apply bid filtering if requested
		if ( $exclude_bid_0 ) {
			$options_data = $this->data_helper->filter_options_by_bid( $options_data );
		}

		return new \WP_REST_Response( $options_data, 200 );
	}

	/**
	 * Get puts options endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function callback_get_puts_options( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return $this->get_api_stockoptions( $request, 'P' );
	}

	/**
	 * Get calls options endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function callback_get_calls_options( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		return $this->get_api_stockoptions( $request, 'C' );
	}

	/**
	 * Get options endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $type    Option type (P or C).
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	private function get_api_stockoptions( \WP_REST_Request $request, string $type ): \WP_REST_Response|\WP_Error {
		$symbol = strtoupper( $request->get_param( 'symbol' ) );
		$date   = $request->get_param( 'date' );
		$strike = $request->get_param( 'strike' );
		$field  = $request->get_param( 'field' );

		// return new WP_REST_Response( [ 'a' => $strike?? 's' ] );
		// Get stock post
		$stock_post = $this->stock_cpt->get_stock_by_symbol( $symbol );
		if ( ! $stock_post ) {
			return new \WP_Error(
				'stock_not_found',
				sprintf( 'Stock %s not found', $symbol ),
				[ 'status' => 404 ]
			);
		}

		// If strike specified, look for specific option
		if ( !empty( $date) && ! empty( $strike ) ) {
			return $this->get_specific_stockoption( $stock_post->ID, $date, $strike, $field, $type );
		}

		// If no date specified, return all options for the stock
		if ( empty( $date ) ) {
			return $this->get_all_options_for_stock( $stock_post->ID, $type );
		}

		// Return all options for specific date
		return $this->get_stockoptions_for_date( $stock_post->ID, $date, $type );
	}

	/**
	 * Get all options for a stock
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $type    Option type (P or C).
	 * @return \WP_REST_Response Response object.
	 */
	private function get_all_options_for_stock( int $post_id, string $type ): \WP_REST_Response {
		$options_keys = $this->stock_meta->get_stock_options_keys( $post_id );
		$options_data = [];

		foreach ( $options_keys as $meta_key ) {
			if ( str_contains( $meta_key, $type ) ) {
				$options_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
			}
		}

		return new \WP_REST_Response( $options_data, 200 );
	}

	/**
	 * Get options for a specific date
	 *
	 * @param int    $post_id Stock post ID.
	 * @param string $date    Date in format YYMMDD.
	 * @param string $type    Option type (P or C).
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	private function get_stockoptions_for_date( int $post_id, string $date, string $type ): \WP_REST_Response|\WP_Error {

		$ticker        = get_post_field( 'post_title', $post_id );
		$options_data  = $this->stock_meta->get_stock_options_by_date( $post_id, $ticker, $date, $type );
		if ( $options_data ) {
			return new \WP_REST_Response( $options_data, 200 );
		}

		// TODO: check if this code is needed.
		// TODO: accept parameter to filter by field

		$options_keys = $this->stock_meta->get_stock_options_keys( $post_id );
		$options_data = [];

		foreach ( $options_keys as $meta_key ) {
			// Check if meta key contains the date and type
			if ( str_contains( $meta_key, $date ) && str_contains( $meta_key, $type ) ) {
				$options_data[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
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
	 * @param int     $post_id Stock post ID.
	 * @param string  $date    Date in format YYMMDD.
	 * @param string  $strike  Strike price.
	 * @param ?string $field   Specific field to return.
	 * @param string  $type    Option type (P or C).
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	private function get_specific_stockoption( int $post_id, string $date, string $strike, ?string $field, string $type ): \WP_REST_Response|\WP_Error {
		// Convert strike to the format used in meta key
		$strike_formatted = $this->data_helper->format_strike_for_meta_key( $strike );
		if ( ! $strike_formatted ) {
			return new \WP_Error(
				'invalid_strike',
				sprintf( 'Invalid strike price: %s', $strike ),
				[ 'status' => 400 ]
			);
		}

		$meta_key    = $date . $type . $strike_formatted;
		$option_data = get_post_meta( $post_id, $meta_key, true );

		if ( ! $option_data ) {
			return new \WP_Error(
				'option_not_found',
				sprintf( 'Option not found for date %s and strike %s', $date, $strike ),
				[ 'status' => 404 ]
			);
		}

		// If specific field requested, return only that field
		if ( ! empty( $field ) ) {
			if ( ! isset( $option_data[ $field ] ) ) {
				return new \WP_Error(
					'field_not_found',
					sprintf( 'Field %s not found in option data', $field ),
					[ 'status' => 400 ]
				);
			}
			return new \WP_REST_Response( $option_data[ $field ], 200 );
		}

		return new \WP_REST_Response( $option_data, 200 );
	}

	/**
	 * Sync stock data from CBOE endpoint callback
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function callback_sync_stock_from_cboe( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$symbol = strtoupper( $request->get_param( 'symbol' ) );

		// Verify that the stock exists in our database
		$stock_post = $this->stock_cpt->get_stock_by_symbol( $symbol );
		if ( ! $stock_post ) {
			return new \WP_Error(
				'stock_not_found',
				sprintf( 'Stock %s not found in database. Please add it first.', $symbol ),
				[ 'status' => 404 ]
			);
		}

		try {
			// Get the SyncCboeData instance from the main plugin
			$plugin    = \CocoStockOptions\CocoStockOptions::get_instance();
			$sync_data = $plugin->get_sync_data();

			// Perform the sync
			$result = $sync_data->sync_stock_options( $symbol );

			// Prepare response data
			$response_data = [
				'success'           => $result['success'],
				'symbol'            => $result['symbol'],
				'message'           => $result['message'],
				'options_processed' => $result['processed'],
				'timestamp'         => $result['timestamp'],
			];

			// Add errors if any
			if ( ! empty( $result['errors'] ) ) {
				$response_data['errors'] = $result['errors'];
			}

			// Add stock information
			if ( $result['success'] ) {
				$options_keys                    = $this->stock_meta->get_stock_options_keys( $stock_post->ID );
				$response_data['total_options']  = count( $options_keys );
				$response_data['stock_post_id']  = $stock_post->ID;

				// Get latest option data for additional info
				$latest_data = $this->stock_meta->get_latest_option_data( $stock_post->ID, $symbol );
				if ( $latest_data ) {
					$response_data['latest_update'] = $latest_data['last_update'] ?? null;
				}
			}

			$status_code = $result['success'] ? 200 : 422; // 422 Unprocessable Entity for sync failures
			return new \WP_REST_Response( $response_data, $status_code );

		} catch ( \Exception $e ) {
			return new \WP_Error(
				'sync_exception',
				sprintf( 'An error occurred while syncing %s: %s', $symbol, $e->getMessage() ),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Check permissions for sync operations
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if user has permissions, false or WP_Error otherwise.
	 */
	public function check_sync_permissions( \WP_REST_Request $request ): bool|\WP_Error {
		// Allow only authenticated users with capability to manage options
		// You can adjust this based on your security requirements
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'auth_required',
				'Authentication required for sync operations',
				[ 'status' => 401 ]
			);
		}

		// Check if user can manage options (admin capability)
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'insufficient_permissions',
				'Insufficient permissions for sync operations',
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get API documentation
	 *
	 * @return array API documentation.
	 */
	public function get_api_documentation(): array {
		return [
			'endpoints'  => [
				'get_all_puts'            => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}',
					'method'      => 'GET',
					'description' => 'Get all put options for a stock symbol',
					'example'     => '/wp-json/coco/v1/puts/LMT',
				],
				'get_puts_by_date'        => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}',
					'method'      => 'GET',
					'description' => 'Get all put options for a stock on a specific date',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801',
				],
				'get_specific_put'        => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}&strike={strike}',
					'method'      => 'GET',
					'description' => 'Get a specific put option by date and strike',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801&strike=310',
				],
				'get_specific_put_field'  => [
					'url'         => '/wp-json/coco/v1/puts/{symbol}?date={date}&strike={strike}&field={field}',
					'method'      => 'GET',
					'description' => 'Get a specific field from a put option',
					'example'     => '/wp-json/coco/v1/puts/LMT?date=250801&strike=310&field=bid',
				],
				'get_all_calls'           => [
					'url'         => '/wp-json/coco/v1/calls/{symbol}',
					'method'      => 'GET',
					'description' => 'Get all call options for a stock symbol',
					'example'     => '/wp-json/coco/v1/calls/LMT',
				],
				'get_calls_by_date'       => [
					'url'         => '/wp-json/coco/v1/calls/{symbol}?date={date}',
					'method'      => 'GET',
					'description' => 'Get all call options for a stock on a specific date',
					'example'     => '/wp-json/coco/v1/calls/LMT?date=250801',
				],
				'get_specific_call'       => [
					'url'         => '/wp-json/coco/v1/calls/{symbol}?date={date}&strike={strike}',
					'method'      => 'GET',
					'description' => 'Get a specific call option by date and strike',
					'example'     => '/wp-json/coco/v1/calls/LMT?date=250801&strike=310',
				],
				'get_specific_call_field' => [
					'url'         => '/wp-json/coco/v1/calls/{symbol}?date={date}&strike={strike}&field={field}',
					'method'      => 'GET',
					'description' => 'Get a specific field from a call option',
					'example'     => '/wp-json/coco/v1/calls/LMT?date=250801&strike=310&field=bid',
				],
				'sync_stock_from_cboe'    => [
					'url'         => '/wp-json/coco/v1/sync-stock/{symbol}',
					'method'      => 'POST',
					'description' => 'Sync a stock\'s options data from CBOE API (requires authentication)',
					'example'     => '/wp-json/coco/v1/sync-stock/LMT',
					'auth'        => 'Required (manage_options capability)',
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
