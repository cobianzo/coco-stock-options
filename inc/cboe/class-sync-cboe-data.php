<?php
/**
 * Sync CBOE Data class for parsing and saving options data
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Cboe;

use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;

/**
 * Sync CBOE Data class for processing and storing options data
 */
class SyncCboeData {

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
	 * CBOE Connection instance
	 *
	 * @var CboeConnection
	 */
	private CboeConnection $cboe_connection;

	/**
	 * Initialize the sync class
	 *
	 * @param Stock_CPT      $stock_cpt       Stock CPT instance.
	 * @param Stock_Meta     $stock_meta      Stock Meta instance.
	 * @param CboeConnection $cboe_connection CBOE Connection instance.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta, CboeConnection $cboe_connection ) {
		$this->stock_cpt       = $stock_cpt;
		$this->stock_meta      = $stock_meta;
		$this->cboe_connection = $cboe_connection;
	}

	/**
	 * Synchronize and update options data for a given stock symbol.
	 *
	 * This function retrieves the latest options data for the specified stock symbol
	 * from the CBOE API, validates the response, and parses the options chain. It then
	 * saves each relevant option's data as post meta associated with the stock's custom post type
	 * in the WordPress database. The function returns a summary of the sync operation,
	 * including the number of options processed, any errors encountered, and a status message.
	 *
	 * @param string $symbol Stock symbol (e.g., 'LMT').
	 * @return array {
	 *     @type string   $symbol    The stock symbol processed.
	 *     @type bool     $success   Whether the sync was successful.
	 *     @type string   $message   Human-readable status message.
	 *     @type int      $processed Number of options processed and saved.
	 *     @type array    $errors    List of errors encountered during sync.
	 *     @type string   $timestamp Timestamp of the sync operation.
	 * }
	 */
	public function sync_stock_options( string $symbol ): array {
		$results = [
			'symbol'    => $symbol,
			'success'   => false,
			'message'   => '',
			'processed' => 0,
			'errors'    => [],
			'timestamp' => current_time( 'mysql' ),
		];

		// Get stock post
		$stock_post = $this->stock_cpt->get_stock_by_symbol( $symbol );
		if ( ! $stock_post ) {
			$results['message'] = sprintf( 'Stock %s not found in database', $symbol );
			return $results;
		}

		// Get CBOE data
		$cboe_data = $this->cboe_connection->get_stock_options( $symbol );
		if ( is_wp_error( $cboe_data ) ) {
			$results['message'] = $cboe_data->get_error_message();
			return $results;
		}

		// Validate CBOE response
		if ( ! $this->cboe_connection->validate_cboe_response( $cboe_data ) ) {
			$results['message'] = 'Invalid CBOE API response structure';
			return $results;
		}

		// Parse and save options data
		$parse_results = $this->parse_and_save_options( $stock_post->ID, $cboe_data );

		$results['success']   = $parse_results['success'];
		$results['processed'] = $parse_results['processed'];
		$results['errors']    = $parse_results['errors'];

		if ( $parse_results['success'] ) {
			$results['message'] = sprintf( 'Successfully processed %d options for %s', $parse_results['processed'], $symbol );
		} else {
			$results['message'] = 'Failed to process options data';
		}

		return $results;
	}

	/**
	 * Parse CBOE data and save to database
	 *
	 * @param int   $post_id Stock post ID.
	 * @param array $cboe_data CBOE API response data. See assets/pretty-cboe-response-lmt.json
	 * @return array Parse results.
	 */
	private function parse_and_save_options( int $post_id, array $cboe_data ): array {

		$results = [
			'success'   => false,
			'processed' => 0,
			'errors'    => [],
		];

		$cboe_timestamp = $this->cboe_connection->get_cboe_timestamp( $cboe_data );
		$current_time   = current_time( 'mysql' );

		// Parse options from CBOE data
		// This will need to be adjusted based on actual CBOE API structure
		if ( ! isset( $cboe_data['data']['options'] ) ) {
			$results['errors'][] = 'No options data found in CBOE response';
			return $results;
		}

		$processed = 0;
		$errors    = [];

		foreach ( $cboe_data['data']['options'] as $option ) {
			try {

				$parsed_option = $this->parse_single_option_data( $option, $cboe_timestamp );

				if ( $parsed_option ) {
					$meta_key = $this->generate_meta_key_from_parsed_data( $parsed_option ); // see assets/pretty-cboe-response-lmt.json for the option structure.

					if ( $meta_key ) {
						$existing_options = $this->stock_meta->add_or_update_strike(
							$post_id,
							$parsed_option['stock_symbol'],
							$parsed_option['date'],
							$parsed_option['option_type'],
							$parsed_option['strike_price'],
							$parsed_option['value']
						);


						$processed++;

					} else {
						$errors[] = sprintf( 'Failed to generate meta key for option', $option );
					}
				} else {
					$errors[] = sprintf( 'Failed to parse option data', $option );
				}
			} catch ( \Exception $e ) {
				$errors[] = sprintf( 'Exception processing option: %s', $e->getMessage() );
			}
		}

		$results['processed'] = $processed;
		$results['errors']    = $errors;
		$results['success']   = $processed > 0;

		return $results;
	}

	/**
	 * Parse the ticker+date+option type+strike from a single option from CBOE data.
	 * Refer to assets/pretty-cboe-response-lmt.json to know how an option looks like,
	 * (see the first item in the data.options array).
	 *
	 * @param array  $option CBOE option data.
	 * @param string $cboe_timestamp the time "2025-07-01 20:16:40"
	 * @return array|false Parsed option data or false on failure.
	 */
	private function parse_single_option_data( array $option, string $cboe_timestamp ): array|false {

		// Validate required fields
		if ( ! isset( $option['option'] ) ) {
			return false;
		}

		$option_cboe_code = $option['option']; // ie: BXMT250815C00011000

		// Extract components from CBOE code using regex
		if ( ! preg_match( '/^([A-Z]+)(\d{6})([CP])(\d+)$/', $option_cboe_code, $matches ) ) {
				return false;
		}


		$option = array_merge( [
			'cboe_timestamp' => $cboe_timestamp,
			'last_update'    => current_time( 'mysql' ),
		], $option );

		$parsed = [
			'stock_symbol' => $matches[1], // BXMT
			'date'         => $matches[2], // 250815
			'option_type'  => $matches[3], // C
			'strike_price' => $matches[4], // 00011000
			'value'        => $option,
		];
		return $parsed;
	}

	/**
	 * Generate meta key for option data in the format YYMMDDX00000000
	 * where:
	 * - YYMMDD is the expiration date (e.g. 240315 for March 15, 2024)
	 * - X is C for call options or P for put options
	 * - 00000000 is the strike price multiplied by 100 with leading zeros (e.g. 00310000 for $310.00)
	 *
	 * @param array $parsed_option associative array
	 * @return string|false Meta key or false on failure.
	 */
	private function generate_meta_key_from_parsed_data( array $parsed_option ): string|false {

		if ( ! $parsed_option ) {
			return false;
		}
		// [
		// 'stock_symbol' => 'BXMT'
		// 'date'         => '250815'
		// 'option_type'  => 'C'
		// 'strike_price' => '00011000'
		// ];
		$meta_key = sprintf(
			'%s%s%s',
			$parsed_option['stock_symbol'],
			$parsed_option['date'],
			$parsed_option['option_type']
		);

		return $meta_key;
	}

	/**
	 * Get sync status for a stock
	 *
	 * @param string $symbol Stock symbol.
	 * @return array Status information.
	 */
	public function get_sync_status( string $symbol ): array {
		$stock_post = $this->stock_cpt->get_stock_by_symbol( $symbol );

		if ( ! $stock_post ) {
			return [
				'exists'        => false,
				'last_sync'     => null,
				'options_count' => 0,
			];
		}

		$options_keys = $this->stock_meta->get_stock_options_keys( $stock_post->ID );
		$last_sync    = null;

		// Find the most recent sync timestamp
		foreach ( $options_keys as $meta_key ) {
			$options_data = $this->stock_meta->get_stock_options( $stock_post->ID, $meta_key );
			if ( $options_data && isset( $options_data['last_update'] ) ) {
				if ( null === $last_sync || $options_data['last_update'] > $last_sync ) {
					$last_sync = $options_data['last_update'];
				}
			}
		}

		return [
			'exists'        => true,
			'last_sync'     => $last_sync,
			'options_count' => count( $options_keys ),
		];
	}
}
