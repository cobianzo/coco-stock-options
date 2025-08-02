<?php
/**
 * Stock CPT class
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Models;

/**
 * Stock CPT class
 */
class Stock_CPT {

	/**
	 * Custom post type name for stocks
	 */
	const POST_TYPE = 'stock';

	/**
	 * Initialize the models
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'register_stock_post_type' ] );
	}

	/**
	 * Register the stock custom post type
	 */
	public function register_stock_post_type(): void {
		$labels = [
			'name'               => __( 'Stocks', 'coco-stock-options' ),
			'singular_name'      => __( 'Stock', 'coco-stock-options' ),
			'menu_name'          => __( 'Stocks', 'coco-stock-options' ),
			'add_new'            => __( 'Add New', 'coco-stock-options' ),
			'add_new_item'       => __( 'Add New Stock', 'coco-stock-options' ),
			'edit_item'          => __( 'Edit Stock', 'coco-stock-options' ),
			'new_item'           => __( 'New Stock', 'coco-stock-options' ),
			'view_item'          => __( 'View Stock', 'coco-stock-options' ),
			'search_items'       => __( 'Search Stocks', 'coco-stock-options' ),
			'not_found'          => __( 'No stocks found', 'coco-stock-options' ),
			'not_found_in_trash' => __( 'No stocks found in trash', 'coco-stock-options' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true, // Show in admin menu
			'supports'           => [ 'title' ],
			'hierarchical'       => false,
			'show_in_rest'       => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'rewrite'            => false,
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Create a new stock post
	 *
	 * @param string $symbol Stock symbol (e.g., 'LMT').
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	public function create_stock( string $symbol ): int|\WP_Error {
		$post_data = [
			'post_title'  => $symbol,
			'post_name'   => strtolower( $symbol ),
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
		];

		return wp_insert_post( $post_data );
	}

	/**
	 * Get stock by symbol
	 *
	 * @param string $symbol Stock symbol.
	 * @return WP_Post|null Post object or null if not found.
	 */
	public function get_stock_by_symbol( string $symbol ): ?\WP_Post {
		$posts = get_posts( [
			'post_type'      => self::POST_TYPE,
			'name'           => strtolower( $symbol ),
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		] );
		error_log( '✅ checkpoint TODELETE ' . print_r( $posts, 1 ) );

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Get all stocks
	 *
	 * @return WP_Post[] Array of stock posts.
	 */
	public function get_all_stocks(): array {
		return get_posts( [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
	}

	/**
	 * Check if stock exists by symbol
	 *
	 * @param string $symbol Stock symbol.
	 * @return bool True if exists, false otherwise.
	 */
	public function stock_exists( string $symbol ): bool {
		return null !== $this->get_stock_by_symbol( $symbol );
	}
}
