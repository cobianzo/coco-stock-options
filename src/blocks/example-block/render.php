<?php
/**
 * Dynamic render template for Stock Options Display block
 *
 * @package CocoStockOptions
 * @since 1.0.0
 *
 * @var array    $attributes Block attributes
 * @var string   $content    Block default content
 * @var WP_Block $block      Block instance
 */

use CocoStockOptions\Blocks\Stock_Block_UI as Stock_UI;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get block attributes with defaults
$stock_symbol     = $attributes['stockSymbol'] ?? 'AAPL';
$display_type     = $attributes['displayType'] ?? 'table';
$max_results      = $attributes['maxResults'] ?? 10;
$show_header      = $attributes['showHeader'] ?? true;
$background_color = $attributes['backgroundColor'] ?? '#ffffff';
$text_color       = $attributes['textColor'] ?? '#333333';

// Get unique ID for this block instance
$unique_id = 'stock-options-block-' . uniqid();

// Build CSS classes
$wrapper_classes = [
	'wp-block-coco-stock-options-example-block',
	'stock-options-display',
	"display-type-{$display_type}",
];

if ( ! empty( $attributes['className'] ) ) {
	$wrapper_classes[] = $attributes['className'];
}

// Inline styles
$wrapper_styles = [
	"background-color: {$background_color}",
	"color: {$text_color}",
];

// Try to get stock data (this would connect to your existing API)
$stock_data = Stock_UI::get_stock_options_data( $stock_symbol, $max_results );

?>
<div
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
	style="<?php echo esc_attr( implode( '; ', $wrapper_styles ) ); ?>"
	data-stock-symbol="<?php echo esc_attr( $stock_symbol ); ?>"
	data-display-type="<?php echo esc_attr( $display_type ); ?>"
>
	<?php if ( $show_header ) : ?>
		<header class="stock-options-header">
			<h3 class="stock-symbol-title">
				<?php
				printf(
					/* translators: %s: Stock symbol */
					esc_html__( 'Options for %s', 'coco-stock-options' ),
					esc_html( strtoupper( $stock_symbol ) )
				);
				?>
			</h3>
			<div class="last-updated">
				<?php
				printf(
					/* translators: %s: Date and time */
					esc_html__( 'Last updated: %s', 'coco-stock-options' ),
					esc_html( current_time( 'F j, Y g:i a' ) )
				);
				?>
			</div>
		</header>
	<?php endif; ?>

	<div class="stock-options-content">
		<?php if ( $stock_data && ! is_wp_error( $stock_data ) ) : ?>

			<?php if ( 'table' === $display_type ) : ?>
				<?php Stock_UI::render_stock_table( $stock_data, $max_results ); ?>

			<?php elseif ( 'chart' === $display_type ) : ?>
				<?php Stock_UI::render_stock_chart( $stock_data, $unique_id ); ?>

			<?php elseif ( 'summary' === $display_type ) : ?>
				<?php Stock_UI::render_stock_summary( $stock_data ); ?>

			<?php endif; ?>

		<?php else : ?>
			<div class="stock-options-error">
				<p><?php esc_html_e( 'Unable to load stock options data at this time.', 'coco-stock-options' ); ?></p>
				<?php if ( current_user_can( 'edit_posts' ) && is_wp_error( $stock_data ) ) : ?>
					<p class="error-details">
						<strong><?php esc_html_e( 'Error details (visible to editors):', 'coco-stock-options' ); ?></strong>
						<?php echo esc_html( $stock_data->get_error_message() ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="stock-options-loading" style="display: none;">
		<p><?php esc_html_e( 'Loading stock data...', 'coco-stock-options' ); ?></p>
	</div>
</div>
