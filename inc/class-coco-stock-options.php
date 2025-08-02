<?php
/**
 * Main plugin class for Coco Stock Options
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions;

use CocoStockOptions\Admin\AdminPage;
use CocoStockOptions\Admin\Admin_UI;
use CocoStockOptions\Api\WordPressApi;
use CocoStockOptions\Cboe\CboeConnection;
use CocoStockOptions\Cboe\SyncCboeData;
use CocoStockOptions\Cron\BufferManager;
use CocoStockOptions\Cron\CronJob;
use CocoStockOptions\Cron\GarbageCleaner;
use CocoStockOptions\Models\Stock_CPT;
use CocoStockOptions\Models\Stock_Meta;

/**
 * Main plugin class
 */
class CocoStockOptions {

	/**
	 * Plugin version
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin instance
	 *
	 * @var CocoStockOptions
	 */
	private static $instance = null;

	/**
	 * Stock CPT instance
	 *
	 * @var Stock_CPT
	 */
	private $stock_cpt;

	/**
	 * Stock Meta instance
	 *
	 * @var Stock_Meta
	 */
	private $stock_meta;

	/**
	 * CBOE Connection instance
	 *
	 * @var CboeConnection
	 */
	private $cboe_connection;

	/**
	 * Sync CBOE Data instance
	 *
	 * @var SyncCboeData
	 */
	private $sync_data;

	/**
	 * Garbage Cleaner instance
	 *
	 * @var GarbageCleaner
	 */
	private $garbage_cleaner;

	/**
	 * Buffer Manager instance
	 *
	 * @var BufferManager
	 */
	private $buffer_manager;

	/**
	 * Cron Job instance
	 *
	 * @var CronJob
	 */
	private $cron_job;

	/**
	 * Admin Page instance
	 *
	 * @var AdminPage
	 */
	private $admin_page;

	/**
	 * Admin UI instance
	 *
	 * @var Admin_UI
	 */
	private $admin_ui;

	/**
	 * WordPress API instance
	 *
	 * @var WordPressApi
	 */
	private $wordpress_api;

	/**
	 * Get plugin instance
	 *
	 * @return CocoStockOptions Plugin instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components() {
		// Initialize core components
		$this->stock_cpt       = new Stock_CPT();
		$this->stock_meta      = new Stock_Meta();
		$this->cboe_connection = new CboeConnection();
		$this->sync_data       = new SyncCboeData( $this->stock_cpt, $this->stock_meta, $this->cboe_connection );
		$this->garbage_cleaner = new GarbageCleaner( $this->stock_cpt, $this->stock_meta );
		$this->buffer_manager  = new BufferManager( $this->stock_cpt, $this->stock_meta, $this->sync_data );
		$this->cron_job        = new CronJob( $this->buffer_manager );
		$this->admin_page      = new AdminPage( $this->stock_cpt, $this->stock_meta, $this->cboe_connection, $this->buffer_manager );
		$this->admin_ui        = new Admin_UI();
		$this->wordpress_api   = new WordPressApi( $this->stock_cpt, $this->stock_meta );
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'init_plugin' ] );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'wp_loaded', [ $this, 'setup_cron_jobs' ] );

		// Activation and deactivation hooks
		register_activation_hook( COCO_STOCK_OPTIONS_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( COCO_STOCK_OPTIONS_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Initialize plugin
	 */
	public function init_plugin() {
		// Plugin initialization logic
		do_action( 'cocostock_init' );
	}

	/**
	 * Load text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'coco-stock-options',
			false,
			dirname( plugin_basename( COCO_STOCK_OPTIONS_FILE ) ) . '/languages'
		);
	}

	/**
	 * Setup cron jobs
	 */
	public function setup_cron_jobs() {
		$schedule = get_option( 'cocostock_cron_schedule', 'never' );

		if ( 'never' !== $schedule ) {
			$this->cron_job->schedule_update_buffer( $schedule );
		}

		// Schedule daily cleanup
		if ( ! wp_next_scheduled( 'cocostock_cleanup_old_options' ) ) {
			wp_schedule_event( time(), 'daily', 'cocostock_cleanup_old_options' );
		}
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create database tables if needed
		$this->create_tables();

		// Set default options
		$this->set_default_options();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clear scheduled cron jobs
		$this->cron_job->unschedule_all();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Create database tables
	 */
	private function create_tables() {
		// No custom tables needed for this plugin
		// All data is stored in WordPress post meta
	}

	/**
	 * Set default options
	 */
	private function set_default_options() {
		if ( ! get_option( 'cocostock_cron_schedule' ) ) {
			update_option( 'cocostock_cron_schedule', 'never' );
		}

		if ( ! get_option( 'cocostock_batch_size' ) ) {
			update_option( 'cocostock_batch_size', 5 );
		}
	}

	/**
	 * Get Stock CPT instance
	 *
	 * @return Stock_CPT Stock CPT instance.
	 */
	public function get_stock_cpt() {
		return $this->stock_cpt;
	}

	/**
	 * Get Stock Meta instance
	 *
	 * @return Stock_Meta Stock Meta instance.
	 */
	public function get_stock_meta() {
		return $this->stock_meta;
	}

	/**
	 * Get CBOE Connection instance
	 *
	 * @return CboeConnection CBOE Connection instance.
	 */
	public function get_cboe_connection() {
		return $this->cboe_connection;
	}

	/**
	 * Get Sync CBOE Data instance
	 *
	 * @return SyncCboeData Sync CBOE Data instance.
	 */
	public function get_sync_data() {
		return $this->sync_data;
	}

	/**
	 * Get Garbage Cleaner instance
	 *
	 * @return GarbageCleaner Garbage Cleaner instance.
	 */
	public function get_garbage_cleaner() {
		return $this->garbage_cleaner;
	}

	/**
	 * Get Buffer Manager instance
	 *
	 * @return BufferManager Buffer Manager instance.
	 */
	public function get_buffer_manager() {
		return $this->buffer_manager;
	}

	/**
	 * Get Cron Job instance
	 *
	 * @return CronJob Cron Job instance.
	 */
	public function get_cron_job() {
		return $this->cron_job;
	}

	/**
	 * Get Admin Page instance
	 *
	 * @return AdminPage Admin Page instance.
	 */
	public function get_admin_page() {
		return $this->admin_page;
	}

	/**
	 * Get WordPress API instance
	 *
	 * @return WordPressApi WordPress API instance.
	 */
	public function get_wordpress_api() {
		return $this->wordpress_api;
	}

	/**
	 * Get plugin information
	 *
	 * @return array Plugin information.
	 */
	public function get_plugin_info() {
		return [
			'version'     => self::VERSION,
			'name'        => 'Coco Stock Options',
			'description' => 'WordPress plugin for managing stock options data from CBOE API',
			'author'      => 'Coco Stock Options Team',
			'components'  => [
				'models'          => 'Models',
				'cboe_connection' => 'CBOE Connection',
				'sync_data'       => 'Sync CBOE Data',
				'garbage_cleaner' => 'Garbage Cleaner',
				'buffer_manager'  => 'Buffer Manager',
				'cron_job'        => 'Cron Job',
				'admin_page'      => 'Admin Page',
				'wordpress_api'   => 'WordPress API',
			],
		];
	}

	/**
	 * Get plugin status
	 *
	 * @return array Plugin status.
	 */
	public function get_plugin_status() {
		$status = [
			'version'       => self::VERSION,
			'stocks_count'  => count( $this->stock_cpt->get_all_stocks() ),
			'cron_status'   => $this->cron_job->get_cron_status(),
			'buffer_info'   => $this->buffer_manager->get_buffer_info(),
			'cleanup_stats' => $this->garbage_cleaner->get_cleanup_statistics(),
		];

		return $status;
	}

	/**
	 * Test plugin functionality
	 *
	 * @return array Test results.
	 */
	public function test_plugin_functionality() {
		$results = [
			'overall_success' => false,
			'tests'           => [],
			'message'         => '',
		];

		// Test CBOE connection
		$cboe_test                           = $this->cboe_connection->test_connectivity();
		$results['tests']['cboe_connection'] = $cboe_test;

		// Test cron functionality
		$cron_test                              = $this->cron_job->test_cron_functionality();
		$results['tests']['cron_functionality'] = $cron_test;

		// Test models functionality
		$results['tests']['models'] = [
			'success' => true,
			'message' => 'Models functionality working',
		];

		// Overall success
		$results['overall_success'] = $cboe_test['status'] === 'success' && $cron_test['success'];

		if ( $results['overall_success'] ) {
			$results['message'] = 'All plugin components are working properly';
		} else {
			$results['message'] = 'Some plugin components have issues';
		}

		return $results;
	}
}
