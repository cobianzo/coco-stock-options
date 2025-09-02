<?php
/**
 * Stock CPT class
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Models;

use CocoStockOptions\Models\Stock_Meta;

/**
 * Stock CPT class
 */
class Stock_CPT {

	/**
	 * Custom post type name for stocks
	 */
	const POST_TYPE = 'stock';

	/**
	 * Stock Meta instance
	 *
	 * @var Stock_Meta
	 */
	private Stock_Meta $stock_meta;

	/**
	 * Initialize the models
	 *
	 * @param Stock_Meta $stock_meta Stock Meta instance.
	 */
	public function __construct( Stock_Meta $stock_meta ) {
		$this->stock_meta = $stock_meta;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'register_stock_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_stock_meta_boxes' ] );
	}

	/**
	 * Add meta boxes for the stock CPT
	 */
	public function add_stock_meta_boxes(): void {
		add_meta_box(
			'coco_stock_meta_box',
			__( 'Stock Options Data', 'coco-stock-options' ),
			[ $this, 'display_stock_meta_box_content' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Display the content of the stock meta box
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function display_stock_meta_box_content( \WP_Post $post ): void {
		$all_meta = get_post_meta( $post->ID );

		// Filter out internal WordPress meta keys
		$filtered_meta = array_filter( $all_meta, function( $key ) {
			return ! str_starts_with( $key, '_' );
		}, ARRAY_FILTER_USE_KEY );

		if ( empty( $filtered_meta ) ) {
			echo '<p>' . esc_html__( 'No custom data found for this stock.', 'coco-stock-options' ) . '</p>';
			return;
		}

		echo '<table class="form-table">';
		foreach ( $filtered_meta as $key => $value ) {
			echo '<tr>';
			echo '<th scope="row">' . esc_html( $key ) . '</th>';
			echo '<td>';
			// Try to decode JSON if it looks like JSON
			$decoded_value = json_decode( $value[0], true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_value ) ) {
				echo '<pre>' . esc_html( wp_json_encode( $decoded_value, JSON_PRETTY_PRINT ) ) . '</pre>';
			} else {
				echo '<pre>' . esc_html( $value[0] ) . '</pre>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
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
			'show_in_rest'       => true,
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
