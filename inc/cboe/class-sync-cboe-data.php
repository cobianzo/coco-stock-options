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
	 * Sync options data for a specific stock symbol
	 *
	 * @param string $symbol Stock symbol.
	 * @return array Sync results.
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
	 * @param array $cboe_data CBOE API response data.
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
				$parsed_option = $this->parse_single_option( $option, $cboe_timestamp, $current_time );

				if ( $parsed_option ) {
					$meta_key = $this->generate_meta_key( $option );

					if ( $meta_key ) {
						$saved = $this->stock_meta->save_stock_options( $post_id, $meta_key, $parsed_option );

						if ( $saved ) {
							$processed++;
						} else {
							$errors[] = sprintf( 'Failed to save option %s', $meta_key );
						}
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
	 * Parse a single option from CBOE data
	 *
	 * @param array  $option CBOE option data.
	 * @param string $cboe_timestamp CBOE timestamp.
	 * @param string $current_time Current timestamp.
	 * @return array|false Parsed option data or false on failure.
	 */
	private function parse_single_option( array $option, ?string $cboe_timestamp, string $current_time ): array|false {
		// This parsing logic will need to be adjusted based on actual CBOE API structure
		$parsed = [
			'last_update'      => $current_time,
			'cboe_timestamp'   => $cboe_timestamp,
			'date'             => isset( $option['expiration'] ) ? $option['expiration'] : '',
			'option'           => isset( $option['symbol'] ) ? $option['symbol'] : '',
			'bid'              => isset( $option['bid'] ) ? (float) $option['bid'] : 0,
			'bid_size'         => isset( $option['bidSize'] ) ? (int) $option['bidSize'] : 0,
			'ask'              => isset( $option['ask'] ) ? (float) $option['ask'] : 0,
			'ask_size'         => isset( $option['askSize'] ) ? (int) $option['askSize'] : 0,
			'iv'               => isset( $option['iv'] ) ? (float) $option['iv'] : 0,
			'open_interest'    => isset( $option['openInterest'] ) ? (int) $option['openInterest'] : 0,
			'volume'           => isset( $option['volume'] ) ? (int) $option['volume'] : 0,
			'delta'            => isset( $option['delta'] ) ? (float) $option['delta'] : 0,
			'gamma'            => isset( $option['gamma'] ) ? (float) $option['gamma'] : 0,
			'vega'             => isset( $option['vega'] ) ? (float) $option['vega'] : 0,
			'theta'            => isset( $option['theta'] ) ? (float) $option['theta'] : 0,
			'rho'              => isset( $option['rho'] ) ? (float) $option['rho'] : 0,
			'theo'             => isset( $option['theo'] ) ? (float) $option['theo'] : 0,
			'change'           => isset( $option['change'] ) ? (float) $option['change'] : 0,
			'open'             => isset( $option['open'] ) ? (float) $option['open'] : 0,
			'high'             => isset( $option['high'] ) ? (float) $option['high'] : 0,
			'low'              => isset( $option['low'] ) ? (float) $option['low'] : 0,
			'tick'             => isset( $option['tick'] ) ? $option['tick'] : 'no_change',
			'last_trade_price' => isset( $option['lastTradePrice'] ) ? (float) $option['lastTradePrice'] : 0,
			'last_trade_time'  => isset( $option['lastTradeTime'] ) ? $option['lastTradeTime'] : null,
			'percent_change'   => isset( $option['percentChange'] ) ? (float) $option['percentChange'] : 0,
			'prev_day_close'   => isset( $option['prevDayClose'] ) ? (float) $option['prevDayClose'] : 0,
		];

		// Validate required fields
		if ( empty( $parsed['date'] ) || empty( $parsed['option'] ) ) {
			return false;
		}

		return $parsed;
	}

	/**
	 * Generate meta key for option data
	 *
	 * @param array $option CBOE option data.
	 * @return string|false Meta key or false on failure.
	 */
	private function generate_meta_key( array $option ): string|false {
		if ( ! isset( $option['expiration'] ) || ! isset( $option['strike'] ) || ! isset( $option['type'] ) ) {
			return false;
		}

		// Convert date format (assuming YYYY-MM-DD to YYMMDD)
		$date = $option['expiration'];
		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) ) {
			$date = $matches[1][2] . $matches[1][3] . $matches[2] . $matches[3];
		}

		// Convert strike price to format (e.g., 310.00 -> 00310000)
		$strike           = (float) $option['strike'];
		$strike_formatted = sprintf( '%08d', (int) ( $strike * 100 ) );

		// Option type (C for call, P for put)
		$type = strtoupper( $option['type'] ) === 'CALL' ? 'C' : 'P';

		return $date . $type . $strike_formatted;
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
