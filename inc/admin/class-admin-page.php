<?php
/**
 * Admin Page class for WordPress admin interface
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Admin;

use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;
use CocoStockOptions\Cboe\CboeConnection;
use CocoStockOptions\Cron\BufferManager;
use CocoStockOptions\Cron\CronJob;

/**
 * Admin Page class for managing plugin settings
 */
class AdminPage {

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
	 * Buffer Manager instance
	 *
	 * @var BufferManager
	 */
	private BufferManager $buffer_manager;

	/**
	 * Cron Job instance
	 *
	 * @var CronJob
	 */
	private CronJob $cron_job;

	/**
	 * Initialize the admin page
	 *
	 * @param Stock_CPT      $stock_cpt       Stock CPT instance.
	 * @param Stock_Meta     $stock_meta      Stock Meta instance.
	 * @param CboeConnection $cboe_connection CBOE Connection instance.
	 * @param BufferManager  $buffer_manager  Buffer Manager instance.
	 * @param CronJob        $cron_job        Cron Job instance.
	 */
	public function __construct( Stock_CPT $stock_cpt, Stock_Meta $stock_meta, CboeConnection $cboe_connection, BufferManager $buffer_manager, CronJob $cron_job ) {
		$this->stock_cpt       = $stock_cpt;
		$this->stock_meta      = $stock_meta;
		$this->cboe_connection = $cboe_connection;
		$this->buffer_manager  = $buffer_manager;
		$this->cron_job        = $cron_job;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_form_submissions' ] );
		add_action( 'wp_ajax_cocostock_add_stock', [ $this, 'ajax_add_stock' ] );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'edit.php?post_type=stock',
			__( 'Coco Stock Options', 'coco-stock-options' ),
			__( 'Options Settings', 'coco-stock-options' ),
			'manage_options',
			'coco-stock-options',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Handle form submissions
	 */
	public function handle_form_submissions(): void {
		// Handle force buffer action
		if ( isset( $_POST['cocostock_action'] ) && 'force_buffer' === $_POST['cocostock_action'] ) {
			$this->handle_force_buffer();
			return;
		}

		// Handle update buffer action
		if ( isset( $_POST['cocostock_action'] ) && 'update_buffer' === $_POST['cocostock_action'] ) {
			$this->handle_update_buffer();
			return;
		}

		// Handle trigger cron action
		if ( isset( $_POST['cocostock_action'] ) && 'trigger_cron' === $_POST['cocostock_action'] ) {
			$this->handle_trigger_cron();
			return;
		}

		// Handle cancel next cron action
		if ( isset( $_POST['cocostock_action'] ) && 'cancel_next_cron' === $_POST['cocostock_action'] ) {
			$this->handle_cancel_next_cron();
			return;
		}

		// Handle other form submissions
		if ( ! isset( $_POST['cocostock_nonce'] ) || ! wp_verify_nonce( $_POST['cocostock_nonce'], 'cocostock_admin_action' ) ) {
			return;
		}

		if ( isset( $_POST['cocostock_cron_schedule'] ) ) {
			$this->handle_cron_schedule_update();
		}

		if ( isset( $_POST['cocostock_batch_size'] ) ) {
			$this->handle_batch_size_update();
		}
	}

	/**
	 * Handle trigger cron manually
	 */
	private function handle_trigger_cron(): void {
		// Trigger the cron job directly
		$this->cron_job->update_buffer();

		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Cron job triggered manually.', 'coco-stock-options' ) . '</p></div>';
		} );
	}

	/**
	 * Handle canceling the next scheduled cron job
	 */
	private function handle_cancel_next_cron(): void {
		$next_scheduled = wp_next_scheduled( 'cocostock_update_buffer' );

		if ( $next_scheduled ) {
			wp_clear_scheduled_hook( 'cocostock_update_buffer' );
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Next cron job cancelled successfully.', 'coco-stock-options' ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-info"><p>' . esc_html__( 'No cron job scheduled to cancel.', 'coco-stock-options' ) . '</p></div>';
			} );
		}
	}

	/**
	 * Handle cron schedule update
	 */

	/**
	 * Handle cron schedule update
	 */

	/**
	 * Handle cron schedule update
	 */
	private function handle_cron_schedule_update(): void {
		$schedule = sanitize_text_field( $_POST['cocostock_cron_schedule'] );
		update_option( 'cocostock_cron_schedule', $schedule );

		// Reschedule cron job
		wp_clear_scheduled_hook( 'cocostock_update_buffer' );
		if ( 'never' !== $schedule ) {
			wp_schedule_event( time(), $schedule, 'cocostock_update_buffer' );
		}

		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Cron schedule updated successfully.', 'coco-stock-options' ) . '</p></div>';
		} );
	}

	/**
	 * Handle force buffer processing.
	 * Wrapper for force _ process _ buffer, when clicked in the button
	 */
	private function handle_force_buffer(): void {
		if ( ! isset( $_POST['cocostock_force_buffer_nonce'] ) || ! wp_verify_nonce( $_POST['cocostock_force_buffer_nonce'], 'cocostock_force_buffer' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'coco-stock-options' ) . '</p></div>';
			} );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Unauthorized access.', 'coco-stock-options' ) . '</p></div>';
			} );
			return;
		}

		$result = $this->buffer_manager->force_process_buffer();

		if ( $result ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Buffer processing completed successfully.', 'coco-stock-options' ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to process buffer.', 'coco-stock-options' ) . '</p></div>';
			} );
		}
	}

	/**
	 * Handle update buffer action
	 */
	private function handle_update_buffer(): void {
		if ( ! isset( $_POST['cocostock_update_buffer_nonce'] ) || ! wp_verify_nonce( $_POST['cocostock_update_buffer_nonce'], 'cocostock_update_buffer' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed.', 'coco-stock-options' ) . '</p></div>';
			} );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Unauthorized access.', 'coco-stock-options' ) . '</p></div>';
			} );
			return;
		}

		// Get all stocks that are not in the buffer
		$all_stocks              = $this->buffer_manager->get_all_stocks_for_buffer();
		$current_buffer_contents = $this->buffer_manager->get_buffer_contents();
		$current_buffer_symbols  = array_column( $current_buffer_contents, 'symbol' );
		$stocks_to_add           = array_diff( $all_stocks, $current_buffer_symbols );

		if ( empty( $stocks_to_add ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-info"><p>' . esc_html__( 'All stocks are already in the buffer.', 'coco-stock-options' ) . '</p></div>';
			} );
			return;
		}

		// Add stocks to buffer
		$result = $this->buffer_manager->add_multiple_to_buffer( $stocks_to_add );

		if ( $result ) {
			$count = count( $stocks_to_add );
			add_action( 'admin_notices', function () use ( $count ) {
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Buffer updated successfully. Added %d stocks to buffer.', 'coco-stock-options' ), $count ) ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to update buffer.', 'coco-stock-options' ) . '</p></div>';
			} );
		}
	}

	/**
	 * Handle batch size update
	 */
	private function handle_batch_size_update(): void {
		$batch_size = (int) $_POST['cocostock_batch_size'];
		if ( $batch_size > 0 ) {
			update_option( 'cocostock_batch_size', $batch_size );
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Batch size updated successfully.', 'coco-stock-options' ) . '</p></div>';
			} );
		}
	}

	/**
	 * AJAX handler for adding stock
	 */
	public function ajax_add_stock(): void {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cocostock_ajax_nonce' ) ) {
			wp_send_json_error( 'Invalid or missing nonce: ' . $_POST['nonce'] );
		}


		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}


		$symbol = sanitize_text_field( $_POST['symbol'] );

		if ( empty( $symbol ) ) {
			wp_send_json_error( 'Symbol is required' );
		}

		// Check if stock exists in CBOE
		if ( ! $this->cboe_connection->stock_exists_in_cboe( $symbol ) ) {
			wp_send_json_error( sprintf( 'Stock %s not found in CBOE API', $symbol ) );
		}

		// Check if stock already exists in database
		if ( $this->stock_cpt->stock_exists( $symbol ) ) {
			wp_send_json_error( sprintf( 'Stock %s already exists', $symbol ) );
		}

		// Create stock
		$post_id = $this->stock_cpt->create_stock( $symbol );
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( 'Failed to create stock' );
		}

		// Add to buffer for processing
		$this->buffer_manager->add_to_buffer( $symbol );

		wp_send_json_success( sprintf( 'Stock %s added successfully', $symbol ) );
	}



	/**
	 * Render buffer info HTML
	 *
	 * @return string HTML content for buffer info section.
	 */
	private function render_buffer_info_html(): string {
		$buffer_info = $this->buffer_manager->get_buffer_info();

		ob_start();
		?>
		<p>
			<strong><?php esc_html_e( 'Stocks in Buffer:', 'coco-stock-options' ); ?></strong>
			<?php echo esc_html( $buffer_info['count'] ); ?>
				<br>
				<span>
					<?php
					echo esc_html( implode( ', ', $this->buffer_manager->get_buffer() ) );
					?>
				</span>
		</p>
		<p><strong><?php esc_html_e( 'Last Processing:', 'coco-stock-options' ); ?></strong> <?php echo esc_html( $buffer_info['last_processed'] ); ?></p>
		<p><strong><?php esc_html_e( 'Next Processing:', 'coco-stock-options' ); ?></strong> <?php echo esc_html( $buffer_info['next_processing'] ); ?></p>

			<?php if ( $buffer_info['count'] > 0 && ! $buffer_info['is_processing'] ) : ?>
			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'cocostock_force_buffer', 'cocostock_force_buffer_nonce' ); ?>
				<input type="hidden" name="cocostock_action" value="force_buffer" />
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Force Process Buffer', 'coco-stock-options' ); ?>
				</button>
			</form>
		<?php endif; ?>

		<form method="post" style="display: inline; margin-left: 10px;">
				<?php wp_nonce_field( 'cocostock_update_buffer', 'cocostock_update_buffer_nonce' ); ?>
			<input type="hidden" name="cocostock_action" value="update_buffer" />
			<button type="submit" class="button button-secondary">
					<?php esc_html_e( 'Update Buffer', 'coco-stock-options' ); ?>
			</button>
		</form>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page(): void {
		$current_schedule = get_option( 'cocostock_cron_schedule', 'never' );
		$batch_size       = get_option( 'cocostock_batch_size', 5 );
		$stocks           = $this->stock_cpt->get_all_stocks();
		$buffer_info      = $this->buffer_manager->get_buffer_info();
		$cron_info        = $this->get_cron_info();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Coco Stock Options', 'coco-stock-options' ); ?></h1>

			<!-- API Information Section -->
			<div class="cocostock-section">
				<h2><?php esc_html_e( 'API Information', 'coco-stock-options' ); ?></h2>
				<div class="cocostock-api-info">
					<p><strong><?php esc_html_e( 'CBOE API Endpoint:', 'coco-stock-options' ); ?></strong>
						<a href="https://cdn.cboe.com/api/global/delayed_quotes/options/LMT.json" target="_blank" rel="noopener noreferrer">
							https://cdn.cboe.com/api/global/delayed_quotes/options/LMT.json
						</a>
					</p>
					<p class="description">
						<?php esc_html_e( 'This plugin fetches options data from the CBOE API. Replace "LMT" with any stock symbol to access data for that specific stock.', 'coco-stock-options' ); ?>
					</p>
				</div>
			</div>

			<div class="cocostock-admin-container">
				<!-- Cron Job Section -->
				<div class="cocostock-section">
					<h2><?php esc_html_e( 'Cron Job Settings', 'coco-stock-options' ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'cocostock_admin_action', 'cocostock_nonce' ); ?>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Update Frequency', 'coco-stock-options' ); ?></th>
								<td>
									<select name="cocostock_cron_schedule">
										<option value="never" <?php selected( $current_schedule, 'never' ); ?>><?php esc_html_e( 'Never', 'coco-stock-options' ); ?></option>
										<option value="every_15_minutes" <?php selected( $current_schedule, 'every_15_minutes' ); ?>><?php esc_html_e( 'Every 15 minutes', 'coco-stock-options' ); ?></option>
										<option value="every_30_minutes" <?php selected( $current_schedule, 'every_30_minutes' ); ?>><?php esc_html_e( 'Every 30 minutes', 'coco-stock-options' ); ?></option>
										<option value="hourly" <?php selected( $current_schedule, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'coco-stock-options' ); ?></option>
										<option value="twicedaily" <?php selected( $current_schedule, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'coco-stock-options' ); ?></option>
										<option value="daily" <?php selected( $current_schedule, 'daily' ); ?>><?php esc_html_e( 'Daily', 'coco-stock-options' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
						<?php submit_button( __( 'Update Schedule', 'coco-stock-options' ) ); ?>
						<button type="submit" name="cocostock_action" value="trigger_cron" class="button button-secondary">
							<?php esc_html_e( 'Trigger Cron Manually', 'coco-stock-options' ); ?>
						</button>
						<button type="submit" name="cocostock_action" value="cancel_next_cron" class="button button-secondary">
							<?php esc_html_e( 'Cancel Next Cron', 'coco-stock-options' ); ?>
						</button>
					</form>

					<div class="cocostock-cron-info">
						<h3><?php esc_html_e( 'Current Status', 'coco-stock-options' ); ?></h3>
						<p><strong><?php esc_html_e( 'Last Run:', 'coco-stock-options' ); ?></strong> <?php echo esc_html( $cron_info['last_run'] ); ?></p>
						<p><strong><?php esc_html_e( 'Next Run:', 'coco-stock-options' ); ?></strong> <?php echo esc_html( $cron_info['next_run'] ); ?></p>
					</div>
				</div>

				<!-- Buffer Information Section -->
				<div class="cocostock-section">
					<h2><?php esc_html_e( 'Buffer Information', 'coco-stock-options' ); ?></h2>
					<div class="cocostock-buffer-info">
						<?php echo $this->render_buffer_info_html(); ?>
					</div>

				</div>

				<!-- Stocks List Section -->
				<div class="cocostock-section">
					<h2><?php esc_html_e( 'Stocks Management', 'coco-stock-options' ); ?></h2>

					<div class="cocostock-add-stock">
						<h3><?php esc_html_e( 'Add New Stock', 'coco-stock-options' ); ?></h3>
						<input type="text" id="new-stock-symbol" placeholder="<?php esc_attr_e( 'Enter stock symbol (e.g., LMT)', 'coco-stock-options' ); ?>" />
						<button type="button" id="add-stock" class="button button-primary">
							<?php esc_html_e( 'Add Stock', 'coco-stock-options' ); ?>
						</button>
						<div id="add-stock-result"></div>
					</div>

					<div class="cocostock-stocks-list">
						<h3><?php esc_html_e( 'Current Stocks', 'coco-stock-options' ); ?></h3>
						<?php if ( empty( $stocks ) ) : ?>
							<p><?php esc_html_e( 'No stocks found.', 'coco-stock-options' ); ?></p>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Symbol', 'coco-stock-options' ); ?></th>
										<th><?php esc_html_e( 'Options Count', 'coco-stock-options' ); ?></th>
										<th><?php esc_html_e( 'Last Sync', 'coco-stock-options' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'coco-stock-options' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $stocks as $stock ) : ?>
										<tr>
											<td><?php echo esc_html( $stock->post_title ); ?></td>
											<td><?php echo esc_html( $this->get_stock_options_count( $stock->ID ) ); ?></td>
											<td><?php echo esc_html( $this->get_stock_last_sync( $stock->ID ) ); ?></td>
											<td>
												<a href="<?php echo esc_url( get_edit_post_link( $stock->ID ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'coco-stock-options' ); ?>
												</a>
												<?php
												// Add CBOE endpoint link for this stock
												$symbol   = $stock->post_title;
												$cboe_url = 'https://cdn.cboe.com/api/global/delayed_quotes/options/' . urlencode( $symbol ) . '.json';
												?>
												<a href="<?php echo esc_url( $cboe_url ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">
													<?php esc_html_e( 'CBOE API', 'coco-stock-options' ); ?>
												</a>
												<?php
												// Add REST API endpoint links for puts and calls
												$site_url       = get_site_url();
												$puts_endpoint  = $site_url . '/wp-json/coco/v1/puts/' . urlencode( $symbol );
												$calls_endpoint = $site_url . '/wp-json/coco/v1/calls/' . urlencode( $symbol );
												?>
												<a href="<?php echo esc_url( $puts_endpoint ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">
													<?php esc_html_e( 'Puts API', 'coco-stock-options' ); ?>
												</a>
												<a href="<?php echo esc_url( $calls_endpoint ); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">
													<?php esc_html_e( 'Calls API', 'coco-stock-options' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- Task Buffer Manager Section -->
				<div class="cocostock-section">
					<h2><?php esc_html_e( 'Task Buffer Manager', 'coco-stock-options' ); ?></h2>
					<form method="post">
							<?php wp_nonce_field( 'cocostock_admin_action', 'cocostock_nonce' ); ?>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Processing Batch Size', 'coco-stock-options' ); ?></th>
								<td>
									<input type="number" name="cocostock_batch_size" value="<?php echo esc_attr( $batch_size ); ?>" min="1" max="50" />
									<p class="description"><?php esc_html_e( 'Number of stocks to process in each batch (1-50)', 'coco-stock-options' ); ?></p>
								</td>
							</tr>
						</table>
							<?php submit_button( __( 'Update Batch Size', 'coco-stock-options' ) ); ?>
					</form>
				</div>
			</div>
		</div>
			<?php
	}

	/**
	 * Get cron job information
	 *
	 * @return array Cron information.
	 */
	private function get_cron_info(): array {
		$last_run = get_option( 'cocostock_last_cron_run', 'Never' );
		$next_run = wp_next_scheduled( 'cocostock_update_buffer' );

		return [
			'last_run' => $last_run,
			'next_run' => $next_run ? date( 'Y-m-d H:i:s', $next_run ) : 'Not scheduled',
		];
	}

	/**
	 * Get stock options count
	 *
	 * @param int $post_id Stock post ID.
	 * @return int Options count.
	 */
	private function get_stock_options_count( int $post_id ): int {
		$keys = $this->stock_meta->get_stock_options_keys( $post_id );
		return count( $keys );
	}

	/**
	 * Get stock last sync time
	 *
	 * @param int $post_id Stock post ID.
	 * @return string Last sync time.
	 */
	private function get_stock_last_sync( int $post_id ): string {
		$keys      = $this->stock_meta->get_stock_options_keys( $post_id );
		$last_sync = null;

		foreach ( $keys as $meta_key ) {
			$options_data = $this->stock_meta->get_stock_options( $post_id, $meta_key );
			$first_strike_option = empty( $options_data ) ? null :  array_pop( $options_data );
			if ( $first_strike_option && isset( $first_strike_option['last_update'] ) ) {
				if ( null === $last_sync || $first_strike_option['last_update'] > $last_sync ) {
					$last_sync = $first_strike_option['last_update'];
				}
			}
		}

		return $last_sync ?: 'Never';
	}
}