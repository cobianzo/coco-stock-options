<?php
/**
 * Stock UI class
 *
 * @package CocoStockOptions
 * @since 1.0.0
 */

namespace CocoStockOptions\Models;

/**
 * Stock UI class
 */
class Stock_UI {

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
		add_action( 'add_meta_boxes', [ $this, 'add_stock_meta_boxes' ] );
		add_action( 'add_meta_boxes', [ $this, 'remove_slug_meta_box' ] );
		add_filter( 'manage_stock_posts_columns', [ $this, 'add_stock_columns' ] );
		add_action( 'manage_stock_posts_custom_column', [ $this, 'render_stock_columns' ], 10, 2 );
	}

	/**
	 * Add meta boxes for stock post type
	 */
	public function add_stock_meta_boxes(): void {
		add_meta_box(
			'stock_options_info',
			__( 'Stock Options Information', 'coco-stock-options' ),
			[ $this, 'render_stock_options_meta_box' ],
			'stock',
			'side',
			'high'
		);

		add_meta_box(
			'stock_options_json',
			__( 'Stock Options JSON', 'coco-stock-options' ),
			[ $this, 'render_stock_options_json_meta_box' ],
			'stock',
			'normal',
			'low'
		);
	}

	/**
	 * Remove slug meta box for stock post type
	 */
	public function remove_slug_meta_box(): void {
		remove_meta_box( 'slugdiv', 'stock', 'normal' );
	}

	/**
	 * Render stock options meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_stock_options_meta_box( \WP_Post $post ): void {
		$stock_meta    = new \CocoStockOptions\Models\Stock_Meta();
		$options_keys  = $stock_meta->get_stock_options_keys( $post->ID );
		$options_count = count( $options_keys );

		echo '<div class="stock-options-info">';
		echo '<p><strong>' . __( 'Total Options:', 'coco-stock-options' ) . '</strong> ' . $options_count . '</p>';

		if ( $options_count > 0 ) {
			$latest_option = $stock_meta->get_latest_option_data( $post->ID,  $post->post_title );
			if ( $latest_option ) {
				echo '<p><strong>' . __( 'Last Updated:', 'coco-stock-options' ) . '</strong> ' . esc_html( $latest_option['last_update'] ) . '</p>';
			}
		}

		echo '<p><em>' . __( 'Options data is automatically synced from CBOE API.', 'coco-stock-options' ) . '</em></p>';
		echo '</div>';
	}

	/**
	 * Render stock options json meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_stock_options_json_meta_box( \WP_Post $post ): void {
		$stock_meta   = new \CocoStockOptions\Models\Stock_Meta();
		$options_keys = $stock_meta->get_stock_options_keys( $post->ID );
		$options_data = [];

		foreach ( $options_keys as $meta_key ) {
			$fields = $stock_meta->parse_meta_key( $meta_key );
			$option_type = match ( $fields['option_type'] ) {
				'C' => 'CALL',
				'P' => 'PUT',
				default => 'Unknown',
			};
			$date_parts = str_split($fields['date'], 2);
			$date = sprintf(
				'%dth %s %d',
				intval($date_parts[2]),
				date('M', mktime(0, 0, 0, intval($date_parts[1]), 1)),
				2000 + intval($date_parts[0])
			);
			printf('<h3>%s - %s</h3>', $option_type, $date );
			echo "";
			echo '<pre>' . json_encode( $stock_meta->get_stock_options( $post->ID, $meta_key ), JSON_PRETTY_PRINT ) . '</pre>';
		}

	}

	/**
	 * Add custom columns to stock post type list
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_stock_columns( array $columns ): array {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['options_count'] = __( 'Options Count', 'coco-stock-options' );
				$new_columns['last_sync']     = __( 'Last Sync', 'coco-stock-options' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom columns for stock post type
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_stock_columns( string $column, int $post_id ): void {
		$stock_meta = new \CocoStockOptions\Models\Stock_Meta();
		switch ( $column ) {
			case 'options_count':
				$options_keys = $stock_meta->get_stock_options_keys( $post_id );
				$count        = count( $options_keys );
				echo '<span class="options-count">' . $count . '</span>';
				break;

			case 'last_sync':
				$latest_data = $stock_meta->get_latest_option_data( $post_id );
				if ( $latest_data && isset( $latest_data['last_update'] ) ) {
					echo '<span class="last-sync">' . esc_html( $latest_data['last_update'] ) . '</span>';
				} else {
					echo '<span class="no-sync">' . __( 'Never', 'coco-stock-options' ) . '</span>';
				}
				break;
		}
	}
}
