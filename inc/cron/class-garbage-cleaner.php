<?php
/**
 * Garbage Cleaner class for cleaning old options data
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Cron;

use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;

/**
 * Garbage Cleaner class for removing outdated options data
 */
class GarbageCleaner {

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
	 * Maximum age for CBOE timestamp in hours
	 */
	const MAX_CBOE_AGE_HOURS = 24;

	/**
	 * Initialize the garbage cleaner
	 *
	 * @param Stock_CPT  $stock_cpt  Stock CPT instance.
	 * @param Stock_Meta $stock_meta Stock Meta instance.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta ) {
		$this->stock_cpt  = $stock_cpt;
		$this->stock_meta = $stock_meta;
	}

	/**
	 * Clean old options data for all stocks
	 *
	 * @return array Cleanup results.
	 */
	public function clean_all_stocks(): array {
		$results = [
			'success'   => false,
			'processed' => 0,
			'deleted'   => 0,
			'errors'    => [],
			'timestamp' => current_time( 'mysql' ),
		];

		$stocks    = $this->stock_cpt->get_all_stocks();
		$processed = 0;
		$deleted   = 0;
		$errors    = [];

		foreach ( $stocks as $stock ) {
			try {
				$stock_results = $this->clean_stock_options( $stock->ID );
				$processed++;
				$deleted += $stock_results['deleted'];

				if ( ! empty( $stock_results['errors'] ) ) {
					$errors = array_merge( $errors, $stock_results['errors'] );
				}
			} catch ( \Exception $e ) {
				$errors[] = sprintf( 'Error cleaning stock %s: %s', $stock->post_title, $e->getMessage() );
			}
		}

		$results['processed'] = $processed;
		$results['deleted']   = $deleted;
		$results['errors']    = $errors;
		$results['success']   = $processed > 0;

		return $results;
	}

	/**
	 * Clean old options data for a specific stock
	 *
	 * @param int $post_id Stock post ID.
	 * @return array Cleanup results.
	 */
	public function clean_stock_options( int $post_id ): array {
		$results = [
			'deleted' => 0,
			'errors'  => [],
		];

		$options_keys = $this->stock_meta->get_stock_options_keys( $post_id );
		$current_time = current_time( 'mysql' );
		$max_age      = self::MAX_CBOE_AGE_HOURS * HOUR_IN_SECONDS;

		foreach ( $options_keys as $meta_key ) {
			$options_data = $this->stock_meta->get_stock_options( $post_id, $meta_key );

			if ( ! $options_data ) {
				continue;
			}

			$should_delete = false;

			// Check if option date is in the past
			if ( isset( $options_data['date'] ) ) {
				$option_date = strtotime( $options_data['date'] );
				if ( $option_date && $option_date < time() ) {
					$should_delete = true;
				}
			}

			// Check if CBOE timestamp is too old
			if ( ! $should_delete && isset( $options_data['cboe_timestamp'] ) ) {
				$cboe_time = strtotime( $options_data['cboe_timestamp'] );
				if ( $cboe_time && ( time() - $cboe_time ) > $max_age ) {
					$should_delete = true;
				}
			}

			if ( $should_delete ) {
				$deleted = $this->stock_meta->delete_stock_options( $post_id, $meta_key );
				if ( $deleted ) {
					$results['deleted']++;
				} else {
					$results['errors'][] = sprintf( 'Failed to delete option %s', $meta_key );
				}
			}
		}

		return $results;
	}

	/**
	 * Clean options for a specific date range
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date End date (Y-m-d).
	 * @return array Cleanup results.
	 */
	public function clean_options_by_date_range( string $start_date, string $end_date ): array {
		$results = [
			'deleted' => 0,
			'errors'  => [],
		];

		$stocks          = $this->stock_cpt->get_all_stocks();
		$start_timestamp = strtotime( $start_date );
		$end_timestamp   = strtotime( $end_date );

		if ( ! $start_timestamp || ! $end_timestamp ) {
			$results['errors'][] = 'Invalid date format provided';
			return $results;
		}

		foreach ( $stocks as $stock ) {
			$options_keys = $this->stock_meta->get_stock_options_keys( $stock->ID );

			foreach ( $options_keys as $meta_key ) {
				$options_data = $this->stock_meta->get_stock_options( $stock->ID, $meta_key );

				if ( ! $options_data || ! isset( $options_data['date'] ) ) {
					continue;
				}

				$option_timestamp = strtotime( $options_data['date'] );

				if ( $option_timestamp && $option_timestamp >= $start_timestamp && $option_timestamp <= $end_timestamp ) {
					$deleted = $this->stock_meta->delete_stock_options( $stock->ID, $meta_key );
					if ( $deleted ) {
						$results['deleted']++;
					} else {
						$results['errors'][] = sprintf( 'Failed to delete option %s for stock %s', $meta_key, $stock->post_title );
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Get statistics about old options data
	 *
	 * @return array Statistics.
	 */
	public function get_cleanup_statistics(): array {
		$stats = [
			'total_stocks'         => 0,
			'total_options'        => 0,
			'expired_options'      => 0,
			'old_options'          => 0,
			'stocks_with_old_data' => 0,
		];

		$stocks                = $this->stock_cpt->get_all_stocks();
		$stats['total_stocks'] = count( $stocks );
		$current_time          = time();
		$max_age               = self::MAX_CBOE_AGE_HOURS * HOUR_IN_SECONDS;

		foreach ( $stocks as $stock ) {
			$options_keys            = $this->stock_meta->get_stock_options_keys( $stock->ID );
			$stats['total_options'] += count( $options_keys );

			$stock_has_old_data = false;

			foreach ( $options_keys as $meta_key ) {
				$options_data = $this->stock_meta->get_stock_options( $stock->ID, $meta_key );

				if ( ! $options_data ) {
					continue;
				}

				// Check expired options
				if ( isset( $options_data['date'] ) ) {
					$option_date = strtotime( $options_data['date'] );
					if ( $option_date && $option_date < $current_time ) {
						$stats['expired_options']++;
						$stock_has_old_data = true;
					}
				}

				// Check old CBOE timestamp
				if ( isset( $options_data['cboe_timestamp'] ) ) {
					$cboe_time = strtotime( $options_data['cboe_timestamp'] );
					if ( $cboe_time && ( $current_time - $cboe_time ) > $max_age ) {
						$stats['old_options']++;
						$stock_has_old_data = true;
					}
				}
			}

			if ( $stock_has_old_data ) {
				$stats['stocks_with_old_data']++;
			}
		}

		return $stats;
	}

	/**
	 * Schedule automatic cleanup
	 */
	public function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( 'cocostock_cleanup_old_options' ) ) {
			wp_schedule_event( time(), 'daily', 'cocostock_cleanup_old_options' );
		}
	}

	/**
	 * Unschedule automatic cleanup
	 */
	public function unschedule_cleanup(): void {
		wp_clear_scheduled_hook( 'cocostock_cleanup_old_options' );
	}
}
