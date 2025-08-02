<?php
/**
 * Buffer Manager class for handling processing queue
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Cron;

use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;
use CocoStockOptions\Cboe\SyncCboeData;

/**
 * Buffer Manager class for managing stock processing queue
 */
class BufferManager {

	/**
	 * Buffer option name
	 */
	const BUFFER_OPTION = 'cocostock_processing_buffer';

	/**
	 * Processing status option name
	 */
	const PROCESSING_STATUS_OPTION = 'cocostock_processing_status';

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
	 * Sync CBOE Data instance
	 *
	 * @var SyncCboeData
	 */
	private SyncCboeData $sync_data;

	/**
	 * Initialize the buffer manager
	 *
	 * @param Stock_CPT    $stock_cpt  Stock CPT instance.
	 * @param Stock_Meta   $stock_meta Stock Meta instance.
	 * @param SyncCboeData $sync_data  Sync CBOE Data instance.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta, SyncCboeData $sync_data ) {
		$this->stock_cpt  = $stock_cpt;
		$this->stock_meta = $stock_meta;
		$this->sync_data  = $sync_data;
	}

	/**
	 * Add a stock to the buffer
	 *
	 * @param string $symbol Stock symbol.
	 * @return bool True on success, false on failure.
	 */
	public function add_to_buffer( string $symbol ): bool {
		$buffer = $this->get_buffer();

		// Check if already in buffer
		if ( in_array( $symbol, $buffer, true ) ) {
			return true;
		}

		$buffer[] = $symbol;
		return $this->save_buffer( $buffer );
	}

	/**
	 * Add multiple stocks to the buffer
	 *
	 * @param array $symbols Array of stock symbols.
	 * @return bool True on success, false on failure.
	 */
	public function add_multiple_to_buffer( array $symbols ): bool {
		$buffer = $this->get_buffer();

		foreach ( $symbols as $symbol ) {
			if ( ! in_array( $symbol, $buffer, true ) ) {
				$buffer[] = $symbol;
			}
		}

		return $this->save_buffer( $buffer );
	}

	/**
	 * Remove a stock from the buffer
	 *
	 * @param string $symbol Stock symbol.
	 * @return bool True on success, false on failure.
	 */
	public function remove_from_buffer( string $symbol ): bool {
		$buffer = $this->get_buffer();
		$key    = array_search( $symbol, $buffer, true );

		if ( false !== $key ) {
			unset( $buffer[ $key ] );
			$buffer = array_values( $buffer ); // Re-index array
			return $this->save_buffer( $buffer );
		}

		return true;
	}

	/**
	 * Process a batch of stocks from the buffer
	 *
	 * @param int $batch_size Number of stocks to process.
	 * @return array Processing results.
	 */
	public function process_batch( int $batch_size ): array {
		$results = [
			'processed'    => 0,
			'successful'   => 0,
			'errors'       => [],
			'buffer_empty' => false,
		];

		$buffer = $this->get_buffer();

		if ( empty( $buffer ) ) {
			$results['buffer_empty'] = true;
			return $results;
		}

		// Set processing status
		$this->set_processing_status( true );

		// Process up to batch_size stocks
		$to_process = array_slice( $buffer, 0, $batch_size );
		$remaining  = array_slice( $buffer, $batch_size );

		foreach ( $to_process as $symbol ) {
			try {
				$sync_result = $this->sync_data->sync_stock_options( $symbol );

				if ( $sync_result['success'] ) {
					$results['successful']++;
				} else {
					$results['errors'][] = sprintf( 'Failed to sync %s: %s', $symbol, $sync_result['message'] );
				}

				$results['processed']++;

				// Remove from buffer regardless of success/failure
				$this->remove_from_buffer( $symbol );

			} catch ( \Exception $e ) {
				$results['errors'][] = sprintf( 'Exception processing %s: %s', $symbol, $e->getMessage() );
				$results['processed']++;
				$this->remove_from_buffer( $symbol );
			}
		}

		// Update processing status
		$this->set_processing_status( false );

		// Check if buffer is now empty
		$remaining_buffer        = $this->get_buffer();
		$results['buffer_empty'] = empty( $remaining_buffer );

		// Update last processed time
		update_option( 'cocostock_last_buffer_processed', current_time( 'mysql' ) );

		return $results;
	}

	/**
	 * Force process the buffer
	 *
	 * @return array Processing results.
	 */
	public function force_process_buffer(): array {
		$batch_size = get_option( 'cocostock_batch_size', 5 );
		return $this->process_batch( $batch_size );
	}

	/**
	 * Get buffer information
	 *
	 * @return array Buffer information.
	 */
	public function get_buffer_info(): array {
		$buffer         = $this->get_buffer();
		$is_processing  = $this->is_processing();
		$last_processed = get_option( 'cocostock_last_buffer_processed', 'Never' );

		// Calculate next processing time
		$next_processing = 'Not scheduled';
		$next_scheduled  = wp_next_scheduled( 'cocostock_process_buffer_batch' );
		if ( $next_scheduled ) {
			$next_processing = date( 'Y-m-d H:i:s', $next_scheduled );
		}

		return [
			'count'           => count( $buffer ),
			'is_processing'   => $is_processing,
			'last_processed'  => $last_processed,
			'next_processing' => $next_processing,
		];
	}

	/**
	 * Get all stocks for buffer processing
	 *
	 * @return array Array of stock symbols.
	 */
	public function get_all_stocks_for_buffer(): array {
		$stocks  = $this->stock_cpt->get_all_stocks();
		$symbols = [];

		foreach ( $stocks as $stock ) {
			$symbols[] = $stock->post_title;
		}

		return $symbols;
	}

	/**
	 * Clear the buffer
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_buffer(): bool {
		return delete_option( self::BUFFER_OPTION );
	}

	/**
	 * Get the current buffer
	 *
	 * @return array Array of stock symbols in buffer.
	 */
	public function get_buffer(): array {
		$buffer = get_option( self::BUFFER_OPTION, '' );

		if ( empty( $buffer ) ) {
			return [];
		}

		return explode( ',', $buffer );
	}

	/**
	 * Save the buffer
	 *
	 * @param array $buffer Array of stock symbols.
	 * @return bool True on success, false on failure.
	 */
	private function save_buffer( array $buffer ): bool {
		$buffer_string = implode( ',', $buffer );
		return update_option( self::BUFFER_OPTION, $buffer_string );
	}

	/**
	 * Set processing status
	 *
	 * @param bool $is_processing Whether buffer is being processed.
	 */
	private function set_processing_status( bool $is_processing ): void {
		update_option( self::PROCESSING_STATUS_OPTION, $is_processing );
	}

	/**
	 * Check if buffer is being processed
	 *
	 * @return bool True if processing, false otherwise.
	 */
	private function is_processing(): bool {
		return (bool) get_option( self::PROCESSING_STATUS_OPTION, false );
	}

	/**
	 * Get buffer statistics
	 *
	 * @return array Buffer statistics.
	 */
	public function get_buffer_statistics(): array {
		$buffer     = $this->get_buffer();
		$all_stocks = $this->get_all_stocks_for_buffer();

		return [
			'stocks_in_buffer'     => count( $buffer ),
			'total_stocks'         => count( $all_stocks ),
			'stocks_not_in_buffer' => count( array_diff( $all_stocks, $buffer ) ),
			'is_processing'        => $this->is_processing(),
			'last_processed'       => get_option( 'cocostock_last_buffer_processed', 'Never' ),
		];
	}

	/**
	 * Get buffer contents for debugging
	 *
	 * @return array Buffer contents with additional info.
	 */
	public function get_buffer_contents(): array {
		$buffer   = $this->get_buffer();
		$contents = [];

		foreach ( $buffer as $symbol ) {
			$stock_post    = $this->stock_cpt->get_stock_by_symbol( $symbol );
			$options_count = 0;

			if ( $stock_post ) {
				$options_keys  = $this->stock_meta->get_stock_options_keys( $stock_post->ID );
				$options_count = count( $options_keys );
			}

			$contents[] = [
				'symbol'        => $symbol,
				'exists_in_db'  => null !== $stock_post,
				'options_count' => $options_count,
			];
		}

		return $contents;
	}
}
