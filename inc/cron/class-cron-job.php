<?php
/**
 * Cron Job class for handling scheduled tasks
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Cron;

/**
 * Cron Job class for managing scheduled operations
 */
class CronJob {

	/**
	 * Buffer Manager instance
	 *
	 * @var BufferManager
	 */
	private $buffer_manager;

	/**
	 * Initialize the cron job
	 *
	 * @param BufferManager $buffer_manager Buffer Manager instance.
	 */
	public function __construct( $buffer_manager ) {
		$this->buffer_manager = $buffer_manager;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'cocostock_update_buffer', [ $this, 'update_buffer' ] );
		add_action( 'cocostock_process_buffer_batch', [ $this, 'process_buffer_batch' ] );
		add_action( 'cocostock_cleanup_old_options', [ $this, 'cleanup_old_options' ] );

		// Add custom cron schedules
		add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedules( $schedules ) {
		$schedules['every_15_minutes'] = [
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes', 'coco-stock-options' ),
		];

		$schedules['every_30_minutes'] = [
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 30 minutes', 'coco-stock-options' ),
		];

		return $schedules;
	}

	/**
	 * Update buffer with all stocks
	 */
	public function update_buffer() {
		// Update last cron run time
		update_option( 'cocostock_last_cron_run', current_time( 'mysql' ) );

		// Get all stocks and add them to buffer
		$stocks = $this->buffer_manager->get_all_stocks_for_buffer();

		if ( ! empty( $stocks ) ) {
			$this->buffer_manager->add_multiple_to_buffer( $stocks );

			// Trigger buffer processing
			$this->trigger_buffer_processing();
		}

		// Log the cron execution
		$this->log_cron_execution( 'Buffer updated with ' . count( $stocks ) . ' stocks' );
	}

	/**
	 * Process a batch of stocks from the buffer
	 */
	public function process_buffer_batch() {
		$batch_size = get_option( 'cocostock_batch_size', 5 );
		$results    = $this->buffer_manager->process_batch( $batch_size );

		// Log the batch processing
		$this->log_cron_execution( sprintf(
			'Processed batch: %d stocks, %d successful, %d errors',
			$results['processed'],
			$results['successful'],
			count( $results['errors'] )
		) );

		// Trigger WordPress actions for logging/notifications
		do_action( 'cocostock_batch_processed', $results );

		// Check if buffer is empty
		if ( $results['buffer_empty'] ) {
			do_action( 'cocostock_buffer_empty' );
		} else {
			// Schedule next batch processing
			wp_schedule_single_event( time() + 60, 'cocostock_process_buffer_batch' );
		}
	}

	/**
	 * Cleanup old options data
	 */
	public function cleanup_old_options() {
		// This will be handled by the GarbageCleaner class
		// We'll trigger it here for the cron job
		do_action( 'cocostock_cleanup_old_options' );

		$this->log_cron_execution( 'Old options cleanup triggered' );
	}

	/**
	 * Trigger buffer processing
	 */
	private function trigger_buffer_processing() {
		// Check if buffer processing is already scheduled
		$next_scheduled = wp_next_scheduled( 'cocostock_process_buffer_batch' );

		if ( ! $next_scheduled ) {
			wp_schedule_single_event( time() + 30, 'cocostock_process_buffer_batch' );
		}
	}

	/**
	 * Schedule the main update buffer cron
	 *
	 * @param string $schedule Cron schedule.
	 */
	public function schedule_update_buffer( $schedule ) {
		// Clear existing schedule
		wp_clear_scheduled_hook( 'cocostock_update_buffer' );

		// Schedule new cron if not 'never'
		if ( 'never' !== $schedule ) {
			wp_schedule_event( time(), $schedule, 'cocostock_update_buffer' );
		}
	}

	/**
	 * Unschedule all cron jobs
	 */
	public function unschedule_all() {
		wp_clear_scheduled_hook( 'cocostock_update_buffer' );
		wp_clear_scheduled_hook( 'cocostock_process_buffer_batch' );
		wp_clear_scheduled_hook( 'cocostock_cleanup_old_options' );
	}

	/**
	 * Get cron job status
	 *
	 * @return array Cron status information.
	 */
	public function get_cron_status() {
		$status = [
			'update_buffer_scheduled' => wp_next_scheduled( 'cocostock_update_buffer' ),
			'process_batch_scheduled' => wp_next_scheduled( 'cocostock_process_buffer_batch' ),
			'cleanup_scheduled'       => wp_next_scheduled( 'cocostock_cleanup_old_options' ),
			'last_run'                => get_option( 'cocostock_last_cron_run', 'Never' ),
			'current_schedule'        => get_option( 'cocostock_cron_schedule', 'never' ),
		];

		return $status;
	}

	/**
	 * Log cron execution
	 *
	 * @param string $message Log message.
	 */
	private function log_cron_execution( $message ) {
		$log_entry = [
			'timestamp' => current_time( 'mysql' ),
			'message'   => $message,
		];

		$logs   = get_option( 'cocostock_cron_logs', [] );
		$logs[] = $log_entry;

		// Keep only last 100 log entries
		if ( count( $logs ) > 100 ) {
			$logs = array_slice( $logs, -100 );
		}

		update_option( 'cocostock_cron_logs', $logs );
	}

	/**
	 * Get recent cron logs
	 *
	 * @param int $limit Number of log entries to return.
	 * @return array Log entries.
	 */
	public function get_recent_logs( $limit = 10 ) {
		$logs = get_option( 'cocostock_cron_logs', [] );
		return array_slice( $logs, -$limit );
	}

	/**
	 * Clear cron logs
	 */
	public function clear_logs() {
		delete_option( 'cocostock_cron_logs' );
	}

	/**
	 * Test cron functionality
	 *
	 * @return array Test results.
	 */
	public function test_cron_functionality() {
		$results = [
			'success' => false,
			'message' => '',
			'tests'   => [],
		];

		// Test 1: Check if cron is enabled
		$cron_disabled                    = defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON;
		$results['tests']['cron_enabled'] = ! $cron_disabled;

		// Test 2: Check if we can schedule events
		$test_event = 'cocostock_test_event';
		wp_schedule_single_event( time() + 60, $test_event );
		$scheduled                        = wp_next_scheduled( $test_event );
		$results['tests']['can_schedule'] = ! empty( $scheduled );

		// Clean up test event
		wp_clear_scheduled_hook( $test_event );

		// Test 3: Check current schedule
		$current_schedule                 = get_option( 'cocostock_cron_schedule', 'never' );
		$results['tests']['has_schedule'] = 'never' !== $current_schedule;

		// Overall success
		$results['success'] = $results['tests']['cron_enabled'] && $results['tests']['can_schedule'];

		if ( $results['success'] ) {
			$results['message'] = 'Cron functionality is working properly';
		} else {
			$results['message'] = 'Cron functionality has issues';
		}

		return $results;
	}
}
