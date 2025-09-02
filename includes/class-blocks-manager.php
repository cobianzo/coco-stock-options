<?php
/**
 * Blocks Manager Class
 *
 * Handles registration and management of dynamic blocks
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Blocks Manager Class
 */
class BlocksManager {

    /**
     * Instance of the class
     *
     * @var BlocksManager|null
     */
    private static $instance = null;

    /**
     * Get instance of the class
     *
     * @return BlocksManager
     */
    public static function get_instance(): BlocksManager {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    /**
     * Register all blocks
     *
     * @return void
     */
    public function register_blocks(): void {
        // Get all block directories
        $blocks_dir = COCO_STOCK_OPTIONS_DIR . 'src/blocks/';
        
        if ( ! is_dir( $blocks_dir ) ) {
            return;
        }

        // Scan for block directories
        $block_folders = array_filter( 
            scandir( $blocks_dir ), 
            function( $item ) use ( $blocks_dir ) {
                return is_dir( $blocks_dir . $item ) && ! in_array( $item, array( '.', '..' ), true );
            }
        );

        // Register each block
        foreach ( $block_folders as $block_name ) {
            $this->register_single_block( $block_name );
        }
    }

    /**
     * Register a single block
     *
     * @param string $block_name The block directory name.
     * @return void
     */
    private function register_single_block( string $block_name ): void {
        $block_json_path = COCO_STOCK_OPTIONS_DIR . "src/blocks/{$block_name}/block.json";
        
        if ( ! file_exists( $block_json_path ) ) {
            return;
        }

        // Include block's PHP file if exists
        $block_php_path = COCO_STOCK_OPTIONS_DIR . "src/blocks/{$block_name}/index.php";
        if ( file_exists( $block_php_path ) ) {
            include_once $block_php_path;
        }

        // Register block from metadata
        register_block_type( $block_json_path );
    }

    /**
     * Enqueue block editor assets
     *
     * @return void
     */
    public function enqueue_block_editor_assets(): void {
        $asset_file = COCO_STOCK_OPTIONS_BUILD_DIR . 'index.asset.php';
        
        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_enqueue_script(
            'coco-stock-options-blocks',
            COCO_STOCK_OPTIONS_BUILD_URL . 'index.js',
            $asset['dependencies'],
            $asset['version'],
            array( 'in_footer' => true )
        );

        wp_enqueue_style(
            'coco-stock-options-blocks-editor',
            COCO_STOCK_OPTIONS_BUILD_URL . 'index.css',
            array( 'wp-edit-blocks' ),
            $asset['version']
        );

        // Localize script for AJAX and other data
        wp_localize_script(
            'coco-stock-options-blocks',
            'cocoStockOptionsData',
            array(
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'coco_stock_options_nonce' ),
                'pluginUrl'   => COCO_STOCK_OPTIONS_URL,
                'apiEndpoint' => home_url( '/wp-json/coco/v1/' ),
                'isEditor'    => true,
            )
        );
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Only enqueue if blocks are present on the page
        if ( ! $this->has_blocks_on_page() ) {
            return;
        }

        $asset_file = COCO_STOCK_OPTIONS_BUILD_DIR . 'index.asset.php';
        
        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_enqueue_style(
            'coco-stock-options-blocks-frontend',
            COCO_STOCK_OPTIONS_BUILD_URL . 'style-index.css',
            array(),
            $asset['version']
        );

        // Enqueue frontend JS if needed for interactivity
        wp_enqueue_script(
            'coco-stock-options-frontend',
            COCO_STOCK_OPTIONS_BUILD_URL . 'frontend.js',
            array(),
            $asset['version'],
            array( 'in_footer' => true )
        );

        // Localize frontend script
        wp_localize_script(
            'coco-stock-options-frontend',
            'cocoStockOptionsData',
            array(
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'coco_stock_options_nonce' ),
                'apiEndpoint' => home_url( '/wp-json/coco/v1/' ),
                'isEditor'    => false,
            )
        );
    }

    /**
     * Check if any of our blocks are present on the current page
     *
     * @return bool
     */
    private function has_blocks_on_page(): bool {
        global $post;
        
        if ( ! $post || ! has_blocks( $post->post_content ) ) {
            return false;
        }

        // Get all our registered blocks
        $our_blocks = $this->get_registered_blocks();
        
        // Check if any of our blocks are present
        foreach ( $our_blocks as $block_name ) {
            if ( has_block( $block_name, $post->post_content ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of registered blocks from our plugin
     *
     * @return array
     */
    private function get_registered_blocks(): array {
        $blocks = array();
        $blocks_dir = COCO_STOCK_OPTIONS_DIR . 'src/blocks/';
        
        if ( ! is_dir( $blocks_dir ) ) {
            return $blocks;
        }

        $block_folders = array_filter( 
            scandir( $blocks_dir ), 
            function( $item ) use ( $blocks_dir ) {
                return is_dir( $blocks_dir . $item ) && ! in_array( $item, array( '.', '..' ), true );
            }
        );

        foreach ( $block_folders as $block_name ) {
            $blocks[] = "coco-stock-options/{$block_name}";
        }

        return $blocks;
    }
}
